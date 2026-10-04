<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Get the active SMS Gate configuration.
     * Checks database SystemSetting first, then falls back to config/services.php / .env
     *
     * @return array
     */
    public static function getConfig()
    {
        $dbSettings = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $dbSettings = SystemSetting::whereIn('key', [
                    'sms_gate_server_url',
                    'sms_gate_username',
                    'sms_gate_password',
                    'sms_gate_device_id',
                    'sms_gate_sim_number',
                ])->pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            // In case DB is not yet migrated
        }

        $serverUrl = !empty($dbSettings['sms_gate_server_url']) 
            ? $dbSettings['sms_gate_server_url'] 
            : config('services.sms_gate.server_url', 'https://api.sms-gate.app');

        // Normalize URL (strip trailing slash)
        $serverUrl = rtrim($serverUrl, '/');
        if (!str_starts_with($serverUrl, 'http://') && !str_starts_with($serverUrl, 'https://')) {
            $serverUrl = 'https://' . $serverUrl;
        }

        $username = !empty($dbSettings['sms_gate_username']) 
            ? $dbSettings['sms_gate_username'] 
            : config('services.sms_gate.username', '');

        $password = !empty($dbSettings['sms_gate_password']) 
            ? $dbSettings['sms_gate_password'] 
            : config('services.sms_gate.password', '');

        $deviceId = !empty($dbSettings['sms_gate_device_id']) 
            ? $dbSettings['sms_gate_device_id'] 
            : config('services.sms_gate.device_id', '');

        $simNumber = !empty($dbSettings['sms_gate_sim_number']) 
            ? (int) $dbSettings['sms_gate_sim_number'] 
            : config('services.sms_gate.sim_number', 1);

        return [
            'server_url' => $serverUrl,
            'username'   => $username,
            'password'   => $password,
            'device_id'  => $deviceId,
            'sim_number' => $simNumber,
        ];
    }

    /**
     * Send an SMS using the SMS Gate Android Cloud API
     *
     * @param string $phone
     * @param string $message
     * @param int|null $simNumber
     * @return bool
     */
    public static function send($phone, $message, $simNumber = null)
    {
        if (empty($phone) || empty(trim($message))) {
            Log::warning('SmsService: Phone number or message is empty.');
            return false;
        }

        $config = self::getConfig();

        if (empty($config['username']) || empty($config['password'])) {
            Log::error('SMS Gate: Username or Password not configured.');
            return false;
        }

        // Format phone number to E.164 (+255...)
        $formattedPhone = self::formatPhoneNumber($phone);

        $payload = [
            'phoneNumbers' => [$formattedPhone],
            'textMessage'  => [
                'text' => $message,
            ],
        ];

        if (!empty($config['device_id'])) {
            $payload['deviceId'] = $config['device_id'];
        }

        $activeSim = $simNumber ?? $config['sim_number'];
        if (!empty($activeSim)) {
            $payload['simNumber'] = (int) $activeSim;
        }

        try {
            $endpoint = $config['server_url'] . '/3rdparty/v1/messages';

            $response = Http::withoutVerifying()
                ->withBasicAuth($config['username'], $config['password'])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->timeout(15)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("SMS sent successfully via SMS Gate to {$formattedPhone}: {$message}");
                return true;
            }

            Log::error("SMS Gate Failed sending to {$formattedPhone}. Status: " . $response->status() . " Response: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("SMS Gate Exception sending to {$formattedPhone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk SMS to multiple phone numbers at once.
     *
     * @param array $phones
     * @param string $message
     * @param int|null $simNumber
     * @return array ['success' => bool, 'sent_count' => int, 'total' => int]
     */
    public static function sendBulk(array $phones, $message, $simNumber = null)
    {
        if (empty($phones) || empty(trim($message))) {
            return ['success' => false, 'sent_count' => 0, 'total' => 0];
        }

        $config = self::getConfig();

        if (empty($config['username']) || empty($config['password'])) {
            Log::error('SMS Gate: Username or Password not configured for bulk send.');
            return ['success' => false, 'sent_count' => 0, 'total' => count($phones)];
        }

        // Clean & normalize phone numbers
        $formattedPhones = [];
        foreach ($phones as $p) {
            $formatted = self::formatPhoneNumber($p);
            if (!empty($formatted) && !in_array($formatted, $formattedPhones, true)) {
                $formattedPhones[] = $formatted;
            }
        }

        $totalRecipients = count($formattedPhones);
        if ($totalRecipients === 0) {
            return ['success' => false, 'sent_count' => 0, 'total' => 0];
        }

        $sentCount = 0;
        $endpoint = $config['server_url'] . '/3rdparty/v1/messages';

        // Chunk into groups of 50 to avoid oversized request bodies and keep delivery smooth
        $chunks = array_chunk($formattedPhones, 50);

        foreach ($chunks as $chunk) {
            $payload = [
                'phoneNumbers' => $chunk,
                'textMessage'  => [
                    'text' => $message,
                ],
            ];

            if (!empty($config['device_id'])) {
                $payload['deviceId'] = $config['device_id'];
            }

            $activeSim = $simNumber ?? $config['sim_number'];
            if (!empty($activeSim)) {
                $payload['simNumber'] = (int) $activeSim;
            }

            try {
                $response = Http::withoutVerifying()
                    ->withBasicAuth($config['username'], $config['password'])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ])
                    ->timeout(20)
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $sentCount += count($chunk);
                    Log::info("Bulk SMS chunk sent successfully to " . count($chunk) . " numbers.");
                } else {
                    Log::error("Bulk SMS chunk failed. Status: " . $response->status() . " Response: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Bulk SMS chunk exception: " . $e->getMessage());
            }
        }

        return [
            'success'    => $sentCount > 0,
            'sent_count' => $sentCount,
            'total'      => $totalRecipients,
        ];
    }

    /**
     * Check connection to SMS Gate server and fetch registered devices.
     *
     * @return array ['connected' => bool, 'devices' => array, 'message' => string]
     */
    public static function testConnection()
    {
        $config = self::getConfig();

        if (empty($config['username']) || empty($config['password'])) {
            return [
                'connected' => false,
                'devices'   => [],
                'message'   => 'Credentials (Username/Password) are not set.',
            ];
        }

        try {
            $endpoint = $config['server_url'] . '/3rdparty/v1/devices';

            $response = Http::withoutVerifying()
                ->withBasicAuth($config['username'], $config['password'])
                ->timeout(10)
                ->get($endpoint);

            if ($response->successful()) {
                $devices = $response->json();
                return [
                    'connected' => true,
                    'devices'   => is_array($devices) ? $devices : [],
                    'message'   => 'Connected successfully to SMS Gate! ' . count($devices) . ' device(s) found.',
                ];
            }

            return [
                'connected' => false,
                'devices'   => [],
                'message'   => 'Server returned HTTP ' . $response->status() . ': ' . $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'connected' => false,
                'devices'   => [],
                'message'   => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Standardize phone numbers to Tanzanian E.164 format (+255...)
     * Handles formats like: 0712345678, 255712345678, +255712345678, 712345678
     *
     * @param string|null $phone
     * @return string
     */
    public static function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Remove spaces, dashes, brackets, and any non-numeric/non-plus characters
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);

        if (empty($phone)) {
            return '';
        }

        // If it already starts with a plus, ensure it's valid
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // If it starts with 0, replace 0 with +255
        if (str_starts_with($phone, '0')) {
            return '+255' . substr($phone, 1);
        }

        // If it starts with 255, prepend +
        if (str_starts_with($phone, '255')) {
            return '+' . $phone;
        }

        // If it's a 9-digit Tanzanian number starting with 6 or 7, prepend +255
        if (strlen($phone) === 9 && in_array($phone[0], ['6', '7'])) {
            return '+255' . $phone;
        }

        // Default fallback: prepend +255
        return '+255' . $phone;
    }

    /**
     * Get the configured church name.
     *
     * @return string
     */
    public static function getChurchName()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $name = SystemSetting::where('key', 'church_name')->value('value');
                if (!empty(trim((string)$name))) {
                    return trim($name);
                }
            }
        } catch (\Throwable $e) {}

        return config('app.name', 'Hosanna International Church');
    }

    /**
     * Build standard Church Header for SMS.
     * Since SMS Gate sends from a SIM card number without an alphanumeric sender ID,
     * having the Church Name clearly at the top is crucial.
     * We use pure GSM-7 characters (*, -, etc.) to prevent Unicode overhead.
     *
     * @param string|null $title
     * @return string
     */
    public static function getHeader($title = null)
    {
        $church = strtoupper(self::getChurchName());
        if ($title) {
            return "*{$church}*\n" . strtoupper($title) . "\n------------------------------";
        }
        return "*{$church}*\n------------------------------";
    }

    /**
     * Format a rich, comprehensive formal receipt for Financial Income (Zaka, Sadaka, etc.)
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param array $items Array of strings (e.g. ["Zaka: TZS 10,000", "Sadaka: TZS 5,000"])
     * @param float|int $grandTotal
     * @param string $dateStr
     * @return string
     */
    public static function buildIncomeReceiptMessage($memberName, array $items, $grandTotal, $dateStr)
    {
        $header = self::getHeader('RISITI YA MCHANGO / ZAKA');
        $currency = 'TZS';

        $itemsList = "";
        foreach ($items as $item) {
            $itemsList .= "- " . $item . "\n";
        }
        $itemsList = trim($itemsList);

        $totalFormatted = number_format($grandTotal);

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Uongozi wa Kanisa unathibitisha kupokea mchango wako wa tarehe {$dateStr}:\n" .
               "{$itemsList}\n" .
               "Jumla Iliyopokelewa: {$currency} {$totalFormatted} (Imelipwa)\n\n" .
               "Tunakushukuru sana kwa uaminifu wako katika kutoa kwa ajili ya kazi ya Bwana.\n" .
               "\"Mungu humpenda yeye atoaye kwa moyo wa ukunjufu.\" - 2 Kor 9:7\n" .
               "Mungu akubariki na kukulinda!";
    }

    /**
     * Format a rich, comprehensive formal receipt for a Single Contribution
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $typeLabel
     * @param float|int $amount
     * @param string $dateStr
     * @return string
     */
    public static function buildContributionReceiptMessage($memberName, $typeLabel, $amount, $dateStr)
    {
        $header = self::getHeader('RISITI YA MCHANGO');
        $currency = 'TZS';
        $amountFormatted = number_format($amount);

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Uongozi wa Kanisa unathibitisha kupokea mchango wako wa tarehe {$dateStr}:\n" .
               "- Aina: {$typeLabel}\n" .
               "- Kiasi: {$currency} {$amountFormatted}\n" .
               "- Hali: Imethibitishwa Kikamilifu\n\n" .
               "Tunakushukuru sana kwa moyo wako wa utoaji kwa ajili ya kazi ya Bwana.\n" .
               "\"Mungu humpenda yeye atoaye kwa moyo wa ukunjufu.\" - 2 Kor 9:7\n" .
               "Mungu akubariki na kukuzidishia!";
    }

    /**
     * Format a rich formal receipt for a Pledge Payment
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $purpose
     * @param float|int $amountPaid
     * @param float|int $remainingBalance
     * @param string $dateStr
     * @return string
     */
    public static function buildPledgePaymentMessage($memberName, $purpose, $amountPaid, $remainingBalance, $dateStr)
    {
        $header = self::getHeader('RISITI YA MALIPO YA AHADI');
        $currency = 'TZS';
        $paidFormatted = number_format($amountPaid);
        $balanceFormatted = number_format($remainingBalance);

        $balanceLine = $remainingBalance <= 0 
            ? "- Salio Lililobaki: {$currency} 0 (IMEKAMILIKA!)" 
            : "- Salio Lililobaki: {$currency} {$balanceFormatted}";

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Uongozi wa Kanisa unathibitisha kupokea malipo yako ya tarehe {$dateStr}:\n" .
               "- Kusudi la Ahadi: {$purpose}\n" .
               "- Kiasi Kilicholipwa: {$currency} {$paidFormatted}\n" .
               "{$balanceLine}\n" .
               "- Hali: Imethibitishwa\n\n" .
               "Asante kwa uaminifu wako katika kutimiza nadhiri uliyoweka mbele za Bwana. Mungu akufanikishe na kuzidisha pale ulipotoa!";
    }

    /**
     * Format a rich confirmation message when a new Pledge is registered
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $purpose
     * @param float|int $pledgeAmount
     * @return string
     */
    public static function buildPledgeCreatedMessage($memberName, $purpose, $pledgeAmount)
    {
        $header = self::getHeader('USAJILI WA AHADI');
        $currency = 'TZS';
        $amountFormatted = number_format($pledgeAmount);

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Ahadi yako imesajiliwa kikamilifu katika kumbukumbu za kanisa:\n" .
               "- Kusudi: {$purpose}\n" .
               "- Kiasi Ulichoahidi: {$currency} {$amountFormatted}\n\n" .
               "Uongozi wa kanisa unakushukuru kwa moyo wako wa upendo na utayari wa kuijenga nyumba ya Mungu. Mungu akubariki na kukufanikisha katika kuitimiza!";
    }

    /**
     * Format a rich confirmation for Small Group / Kanda payment
     * Fits cleanly in 2-3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $groupName
     * @param float|int $amount
     * @param string $dateStr
     * @return string
     */
    public static function buildSmallGroupPaymentMessage($memberName, $groupName, $amount, $dateStr)
    {
        $header = self::getHeader('RISITI YA MCHANGO WA KANDA');
        $currency = 'TZS';
        $amountFormatted = number_format($amount);

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Tumepokea mchango wako wa tarehe {$dateStr}:\n" .
               "- Kanda/Kikundi: {$groupName}\n" .
               "- Kiasi: {$currency} {$amountFormatted}\n" .
               "- Hali: Imethibitishwa\n\n" .
               "Asante kwa ushirikiano wako na uaminifu katika ushirika wa nyumbani. Mungu akubariki sana!";
    }

    /**
     * Format a warm, formal welcome message for visitors
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $visitorFullName
     * @param string|null $visitDate
     * @return string
     */
    public static function buildVisitorWelcomeMessage($visitorFullName, $visitDate = null)
    {
        $churchName = self::getChurchName();
        $header = self::getHeader('KARIBU NYUMBANI MWA BWANA');
        $dateText = $visitDate ? " tarehe {$visitDate}" : " leo";

        return "{$header}\n" .
               "Mpendwa Ndugu {$visitorFullName},\n\n" .
               "Uongozi na washiriki wa {$churchName} tunakusalimu katika jina la Bwana Yesu Kristo.\n\n" .
               "Tunafurahi sana kuwa nawe{$dateText} katika ibada yetu. Uwepo wako umekuwa baraka kwetu. Milango yetu iko wazi kukukaribisha tena wakati wowote.\n\n" .
               "\"Bwana akubarikie, na kukulinda; Bwana akuangazie nuru za uso wake.\" - Hesabu 6:24-25\n" .
               "Uwe na juma lenye baraka na ushindi tele!";
    }

    /**
     * Wrap any custom broadcast SMS with the official Church Header
     *
     * @param string $message
     * @return string
     */
    public static function wrapBroadcastMessage($message)
    {
        $message = trim($message);
        if (str_starts_with($message, '*')) {
            return $message;
        }

        $header = self::getHeader();
        return "{$header}\n{$message}";
    }

    /**
     * Format an official Zone / Kanda Meeting Invitation / Reminder SMS
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $groupName
     * @param string $dateStr
     * @param string $timeStr
     * @param string $location
     * @param string|null $topic
     * @return string
     */
    public static function buildZoneMeetingInvitationMessage($memberName, $groupName, $dateStr, $timeStr, $location, $topic = null)
    {
        $header = self::getHeader('MWALIKO WA IBADA YA KANDA');
        $topicLine = $topic ? "- Mada: {$topic}\n" : "";

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Uongozi wa Kanda ya {$groupName} unakualika kwenye Ibada yetu ya ushirika itakayofanyika:\n" .
               "- Tarehe: {$dateStr}\n" .
               "- Muda: {$timeStr}\n" .
               "- Mahali: {$location}\n" .
               $topicLine . "\n" .
               "Uwepo wako ni baraka kubwa kwetu. Karibu tujumuike pamoja kumwabudu Bwana wetu!";
    }

    /**
     * Format a Thank You SMS for attendees of a Zone / Kanda meeting
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $groupName
     * @param string $dateStr
     * @return string
     */
    public static function buildZoneAttendanceThankYouMessage($memberName, $groupName, $dateStr)
    {
        $header = self::getHeader('SHUKRANI YA IBADA YA KANDA');

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Uongozi na wanakanda wa Kanda ya {$groupName} tunakushukuru sana kwa kujumuika nasi katika ibada yetu ya tarehe {$dateStr}. Uwepo wako umekuwa baraka kubwa kwa ushirika wetu.\n\n" .
               "\"Tazama, jinsi ilivyo vema ndugu wakae pamoja kwa umoja!\" - Zaburi 133:1\n" .
               "Mungu akubariki na kukulinda daima!";
    }

    /**
     * Format a warm Follow-up / Miss-You SMS for members who were absent from a Zone meeting
     * Fits cleanly in 3 SMS segments (GSM-7 standard).
     *
     * @param string $memberName
     * @param string $groupName
     * @return string
     */
    public static function buildZoneAbsenceMissYouMessage($memberName, $groupName)
    {
        $header = self::getHeader('SALAMU ZA USHIRIKA WA KANDA');

        return "{$header}\n" .
               "Bwana asifiwe Ndugu {$memberName},\n\n" .
               "Wanakanda wa Kanda ya {$groupName} tulikumiss sana katika ibada yetu ya ushirika. Tunakuombea amani na afya njema, na tunatamani sana kuwa nawe katika ibada yetu inayokuja.\n\n" .
               "Milango na mioyo yetu ipo wazi daima kukukaribisha tena. Mungu akubariki na kukufanikisha!";
    }
}


