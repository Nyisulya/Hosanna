<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Pledge;
use App\Models\SmallGroupOffering;
use App\Models\SmallGroupPayment;
use App\Models\Contribution;
use App\Services\PesapalService;
use App\Services\HarakaPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $pesapal;
    protected $harakapay;

    public function __construct(PesapalService $pesapal, HarakaPayService $harakapay)
    {
        $this->pesapal = $pesapal;
        $this->harakapay = $harakapay;
    }

    /**
     * Show the online giving form.
     */
    public function showForm(Request $request)
    {
        $user = Auth::user();
        if (!$user->member) {
            return redirect()->route('profile.index')->with('warning', 'Tafadhali kamilisha wasifu wa muumini kwanza.');
        }

        $categories = \App\Models\GivingCategory::active()->get();
        
        // Load active pledges for this member
        $pledges = Pledge::active()
            ->where('member_id', $user->member->id)
            ->where('amount_paid', '<', DB::raw('amount'))
            ->get();

        // Load active small group offerings for this member's groups
        $smallGroups = $user->member->smallGroups;
        $smallGroupOfferings = collect([]);
        if ($smallGroups->isNotEmpty()) {
            $smallGroupOfferings = SmallGroupOffering::whereIn('small_group_id', $smallGroups->pluck('id'))
                ->where('is_active', true)
                ->get();
        }

        // Check if pre-filled pledge
        $preselectedPledgeId = $request->query('pledge_id');
        $preselectedPledge = null;
        if ($preselectedPledgeId) {
            $preselectedPledge = $pledges->firstWhere('id', $preselectedPledgeId);
        }

        $gateway = config('services.payment_gateway', env('PAYMENT_GATEWAY', 'harakapay'));

        return view('payments.form', compact('categories', 'pledges', 'smallGroupOfferings', 'preselectedPledge', 'gateway'));
    }

    /**
     * Process the payment via HarakaPay (default) or Pesapal.
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|in:general,pledge,small_group',
            'amount' => 'required|numeric|min:100', // Minimum 100 TZS
            'phone_number' => 'required|string',
            'description' => 'nullable|string|max:255',
            
            // Conditional validation
            'category' => 'required_if:payment_type,general|nullable|string',
            'pledge_id' => 'required_if:payment_type,pledge|nullable|exists:pledges,id',
            'small_group_offering_id' => 'required_if:payment_type,small_group|nullable|exists:small_group_offerings,id',
        ]);

        $user = Auth::user();
        $member = $user->member;
        if (!$member) {
            return redirect()->back()->with('error', 'Haujaunganishwa na wasifu wa muumini.');
        }

        $reference = 'TX-' . Str::upper(Str::random(12));
        $category = 'General Giving';
        $pledgeId = null;
        $smallGroupOfferingId = null;
        $appName = config('app.name', 'Kanisa');

        // Determine category and details based on payment type
        if ($request->payment_type === 'general') {
            $category = 'Giving: ' . $request->category;
            $payDescription = "{$appName} - " . $request->category;
        } elseif ($request->payment_type === 'pledge') {
            $pledge = Pledge::findOrFail($request->pledge_id);
            $pledgeId = $pledge->id;
            $category = 'Pledge Payment';
            $payDescription = "Pledge Payment: " . $pledge->purpose;
        } elseif ($request->payment_type === 'small_group') {
            $offering = SmallGroupOffering::findOrFail($request->small_group_offering_id);
            $smallGroupOfferingId = $offering->id;
            $category = 'Small Group: ' . $offering->name;
            $payDescription = "Kanda Offering: " . $offering->name;
        }

        if ($request->filled('description')) {
            $payDescription .= " (" . $request->description . ")";
        }

        $phone = $request->phone_number ?: ($member->phone ?? '0700000000');
        $gateway = config('services.payment_gateway', env('PAYMENT_GATEWAY', 'harakapay'));

        // ========================================================
        // 1. HARAKAPAY MOBILE MONEY (USSD PUSH) - DEFAULT
        // ========================================================
        if ($gateway === 'harakapay') {
            $collectResult = $this->harakapay->collect(
                $request->amount,
                $phone,
                $reference
            );

            if ($collectResult['success']) {
                $orderId = $collectResult['order_id'];

                $payment = Payment::create([
                    'member_id' => $member->id,
                    'pledge_id' => $pledgeId,
                    'small_group_offering_id' => $smallGroupOfferingId,
                    'transaction_id' => $orderId,
                    'reference' => $reference,
                    'status' => 'pending',
                    'amount' => $request->amount,
                    'currency' => 'TZS',
                    'category' => $category,
                    'description' => $payDescription,
                    'phone_number' => $phone,
                    'network' => 'harakapay',
                ]);

                return redirect()->route('give.waiting', $payment->id)
                    ->with('success', 'Ombi la malipo limetumwa kwenye simu yako! Tafadhali weka PIN kukamilisha.');
            } else {
                return redirect()->back()->with('error', $collectResult['message'] ?? 'Imeshindwa kuanzisha malipo ya HarakaPay. Tafadhali hakikisha namba yako ni sahihi.');
            }
        }

        // ========================================================
        // 2. PESAPAL GATEWAY FALLBACK
        // ========================================================
        $response = $this->pesapal->submitOrder(
            $request->amount,
            $reference,
            $payDescription,
            $user->email,
            $member->full_name,
            $phone
        );

        if ($response && isset($response['redirect_url'])) {
            // Create pending payment record
            Payment::create([
                'member_id' => $member->id,
                'pledge_id' => $pledgeId,
                'small_group_offering_id' => $smallGroupOfferingId,
                'reference' => $reference,
                'status' => 'pending',
                'amount' => $request->amount,
                'currency' => 'TZS',
                'category' => $category,
                'description' => $payDescription,
                'phone_number' => $phone,
                'network' => 'pesapal',
            ]);

            return redirect()->away($response['redirect_url']);
        } else {
            Log::error('Pesapal Order Initiation Failed', ['response' => $response]);
            return redirect()->back()->with('error', 'Imeshindwa kuanzisha malipo. Tafadhali jaribu tena baadae au wasiliana na utawala.');
        }
    }

    /**
     * Show waiting prompt for mobile money USSD PIN confirmation
     */
    public function waiting(Payment $payment)
    {
        $user = Auth::user();
        if ($payment->member_id !== ($user->member->id ?? null) && !$user->hasRole('super_admin')) {
            abort(403);
        }

        if ($payment->status === 'succeeded') {
            return redirect()->route('give.success', ['reference' => $payment->reference]);
        }

        return view('payments.waiting', compact('payment'));
    }

    /**
     * AJAX Endpoint to poll payment status in real-time
     */
    public function checkStatus(Payment $payment)
    {
        $user = Auth::user();
        if ($payment->member_id !== ($user->member->id ?? null) && !$user->hasRole('super_admin')) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        if ($payment->status === 'succeeded') {
            return response()->json([
                'status' => 'completed',
                'redirect' => route('give.success', ['reference' => $payment->reference]),
                'message' => 'Malipo yamekamilika kikamilifu!'
            ]);
        }

        // If HarakaPay, check with HarakaPay status API
        if ($payment->transaction_id && ($payment->network === 'harakapay' || config('services.payment_gateway') === 'harakapay')) {
            $check = $this->harakapay->checkStatus($payment->transaction_id);

            if ($check['success']) {
                $remoteStatus = $check['status'];

                if (in_array($remoteStatus, ['completed', 'successful'])) {
                    $this->completePaymentTransaction($payment, $payment->transaction_id, [
                        'confirmation_code' => $payment->transaction_id,
                        'payment_method' => 'harakapay',
                    ]);

                    return response()->json([
                        'status' => 'completed',
                        'redirect' => route('give.success', ['reference' => $payment->reference]),
                        'message' => 'Malipo yamekamilika kikamilifu! Asante sana.'
                    ]);
                } elseif (in_array($remoteStatus, ['failed', 'cancelled', 'rejected'])) {
                    $payment->update(['status' => 'failed']);
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Malipo yamekataliwa au yameshindikana kwenye simu yako.'
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'processing',
            'message' => 'Inasubiri kuweka PIN kwenye simu yako...'
        ]);
    }

    /**
     * Handle successful payment redirect callback from Pesapal / Direct reference
     */
    public function success(Request $request)
    {
        $trackingId = $request->query('OrderTrackingId');
        $reference = $request->query('OrderMerchantReference') ?? $request->query('reference');

        if ($reference) {
            $payment = Payment::where('reference', $reference)->first();

            if ($payment) {
                // If payment is pending and trackingId exists (Pesapal)
                if ($payment->status === 'pending' && $trackingId) {
                    $verifyResponse = $this->pesapal->getTransactionStatus($trackingId);
                    if ($verifyResponse && isset($verifyResponse['payment_status_description'])) {
                        $status = $verifyResponse['payment_status_description'];
                        if ($status === 'Completed' || $status === 'Success') {
                            $this->completePaymentTransaction($payment, $trackingId, $verifyResponse);
                        }
                    }
                }
                
                // If HarakaPay pending check
                if ($payment->status === 'pending' && $payment->transaction_id && $payment->network === 'harakapay') {
                    $check = $this->harakapay->checkStatus($payment->transaction_id);
                    if ($check['success'] && in_array($check['status'], ['completed', 'successful'])) {
                        $this->completePaymentTransaction($payment, $payment->transaction_id, [
                            'confirmation_code' => $payment->transaction_id,
                            'payment_method' => 'harakapay',
                        ]);
                    }
                }

                if ($payment->status === 'succeeded') {
                    return view('payments.success', compact('payment', 'reference'));
                }
            }
        }

        return redirect()->route('give.form')->with('error', 'Malipo hayakukamilika au yameshindikana.');
    }

    /**
     * HarakaPay Webhook callback (if configured in HarakaPay dashboard)
     */
    public function harakapayCallback(Request $request)
    {
        $orderId = $request->input('order_id') ?? $request->input('orderId');
        $status = strtolower($request->input('status', ''));
        $reference = $request->input('reference');

        Log::info('HarakaPay Webhook Received', ['payload' => $request->all()]);

        if ($orderId || $reference) {
            $payment = Payment::where('transaction_id', $orderId)
                ->orWhere('reference', $reference)
                ->first();

            if ($payment && $payment->status === 'pending') {
                if (in_array($status, ['completed', 'successful', 'success'])) {
                    $this->completePaymentTransaction($payment, $orderId ?? $payment->transaction_id, [
                        'confirmation_code' => $orderId ?? $payment->transaction_id,
                        'payment_method' => 'harakapay',
                    ]);
                } elseif (in_array($status, ['failed', 'cancelled', 'rejected'])) {
                    $payment->update(['status' => 'failed']);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Pesapal IPN webhook endpoint.
     */
    public function webhook(Request $request)
    {
        $trackingId = $request->query('OrderTrackingId');
        $reference = $request->query('OrderMerchantReference');
        $notificationType = $request->query('OrderNotificationType');

        if ($trackingId && $reference && $notificationType === 'IPNCHANGE') {
            $verifyResponse = $this->pesapal->getTransactionStatus($trackingId);

            if ($verifyResponse && isset($verifyResponse['payment_status_description'])) {
                $status = $verifyResponse['payment_status_description'];
                $payment = Payment::where('reference', $reference)->first();

                if ($payment && $payment->status === 'pending') {
                    if ($status === 'Completed' || $status === 'Success') {
                        $this->completePaymentTransaction($payment, $trackingId, $verifyResponse);
                    } elseif ($status === 'Failed') {
                        $payment->update(['status' => 'failed']);
                    }
                }
            }
        }

        return response()->json([
            'orderNotificationType' => $notificationType,
            'orderTrackingId' => $trackingId,
            'orderMerchantReference' => $reference,
            'status' => '200'
        ]);
    }

    /**
     * Complete the payment transaction and create appropriate logs/pledge records.
     */
    protected function completePaymentTransaction($payment, $trackingId, $paymentData)
    {
        // Avoid duplicate execution if already marked succeeded
        if ($payment->status === 'succeeded') {
            return;
        }

        DB::transaction(function () use ($payment, $trackingId, $paymentData) {
            $confirmationCode = $paymentData['confirmation_code'] ?? $trackingId;
            
            // 1. Update payment record
            $payment->update([
                'status' => 'succeeded',
                'transaction_id' => $confirmationCode, // Store actual reference
                'network' => $paymentData['payment_method'] ?? 'harakapay',
            ]);

            // 2. Create financial transaction
            $transaction = Transaction::create([
                'type' => 'income',
                'category' => $payment->category,
                'amount' => $payment->amount,
                'payment_method' => 'mobile_money', 
                'member_id' => $payment->member_id,
                'payment_id' => $payment->id,
                'reference_number' => $confirmationCode,
                'description' => $payment->description,
                'transaction_date' => now(),
                'recorded_by' => $payment->member->user_id ?? Auth::id() ?? 1,
            ]);

            // 2b. If it is General Giving, ensure the Contribution record exists and is mapped correctly
            if (!$payment->pledge_id && !$payment->small_group_offering_id) {
                $type = 'other';
                $lowerCategory = strtolower($payment->category);
                if (str_contains($lowerCategory, 'zaka') || str_contains($lowerCategory, 'tithe') || str_contains($lowerCategory, 'kumi')) {
                    $type = 'zaka';
                } elseif (str_contains($lowerCategory, 'sadaka') || str_contains($lowerCategory, 'offering')) {
                    $type = 'sadaka';
                } elseif (str_contains($lowerCategory, 'ujenzi') || str_contains($lowerCategory, 'building')) {
                    $type = 'building';
                } elseif (str_contains($lowerCategory, 'shukrani') || str_contains($lowerCategory, 'thanksgiving')) {
                    $type = 'thanksgiving';
                } elseif (str_contains($lowerCategory, 'project')) {
                    $type = 'project';
                }
                
                $contribution = Contribution::where('transaction_id', $transaction->id)->first();
                if (!$contribution) {
                    Contribution::create([
                        'member_id' => $payment->member_id,
                        'amount' => $payment->amount,
                        'type' => $type,
                        'payment_method' => 'mpesa',
                        'reference_number' => $confirmationCode,
                        'date' => now(),
                        'notes' => $payment->description,
                        'recorded_by' => $payment->member->user_id ?? Auth::id() ?? 1,
                        'transaction_id' => $transaction->id,
                    ]);
                } else {
                    $contribution->update([
                        'type' => $type,
                        'payment_method' => 'mpesa',
                        'reference_number' => $confirmationCode,
                    ]);
                }
            }

            // 3. If linked to a Pledge, record the pledge payment
            if ($payment->pledge_id) {
                $pledge = Pledge::findOrFail($payment->pledge_id);
                $pledge->recordPayment($transaction->id, $payment->amount, now());
            }

            // 4. If linked to a Kanda Contribution, record small group payment
            if ($payment->small_group_offering_id) {
                $offering = SmallGroupOffering::findOrFail($payment->small_group_offering_id);
                
                SmallGroupPayment::create([
                    'small_group_offering_id' => $offering->id,
                    'member_id' => $payment->member_id,
                    'amount' => $payment->amount,
                    'transaction_reference' => $confirmationCode,
                    'paid_at' => now(),
                    'payment_method' => 'mobile_money',
                    'recorded_by' => $payment->member->user_id ?? Auth::id() ?? 1,
                    'notes' => 'Online payment via ' . ($paymentData['payment_method'] ?? 'mobile money'),
                ]);
            }
        });

        // Send SMS confirmation after successful database transaction commit
        try {
            $payment->loadMissing('member');
            if ($payment->member && $payment->member->phone) {
                $member = $payment->member;
                $dateStr = now()->format('d/m/Y');
                $message = "";

                if ($payment->pledge_id) {
                    // Pledge Payment SMS
                    $pledge = Pledge::find($payment->pledge_id);
                    if ($pledge) {
                        $pledge->refresh();
                        $remainingBalance = max(0, $pledge->amount - $pledge->amount_paid);
                        $message = \App\Services\SmsService::buildPledgePaymentMessage(
                            $member->full_name,
                            $pledge->purpose,
                            $payment->amount,
                            $remainingBalance,
                            $dateStr
                        );
                    }
                } elseif ($payment->small_group_offering_id) {
                    // Small Group Payment SMS
                    $offering = SmallGroupOffering::find($payment->small_group_offering_id);
                    $offeringName = $offering ? $offering->name : 'Kanda / Jumuiya';
                    $message = \App\Services\SmsService::buildSmallGroupPaymentMessage(
                        $member->full_name,
                        $offeringName,
                        $payment->amount,
                        $dateStr
                    );
                } else {
                    // General Giving SMS (Zaka/Sadaka/etc.)
                    $typeLabel = 'Mchango';
                    $lowerCategory = strtolower($payment->category);
                    if (str_contains($lowerCategory, 'zaka') || str_contains($lowerCategory, 'tithe') || str_contains($lowerCategory, 'kumi')) {
                        $typeLabel = 'Zaka';
                    } elseif (str_contains($lowerCategory, 'sadaka') || str_contains($lowerCategory, 'offering')) {
                        $typeLabel = 'Sadaka';
                    } elseif (str_contains($lowerCategory, 'ujenzi') || str_contains($lowerCategory, 'building')) {
                        $typeLabel = 'Mchango wa Ujenzi';
                    } elseif (str_contains($lowerCategory, 'shukrani') || str_contains($lowerCategory, 'thanksgiving')) {
                        $typeLabel = 'Shukrani';
                    } elseif (str_contains($lowerCategory, 'project')) {
                        $typeLabel = 'Mchango wa Mradi';
                    }

                    $message = \App\Services\SmsService::buildContributionReceiptMessage(
                        $member->full_name,
                        $typeLabel,
                        $payment->amount,
                        $dateStr
                    );
                }

                if (!empty($message)) {
                    \App\Services\SmsService::send($member->phone, $message);
                }
            }
        } catch (\Exception $e) {
            Log::error("completePaymentTransaction SMS Error: " . $e->getMessage());
        }
    }
}
