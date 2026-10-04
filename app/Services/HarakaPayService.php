<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HarakaPayService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.harakapay.api_key') ?? env('HARAKAPAY_API_KEY', ''));
        $this->baseUrl = rtrim((string) (config('services.harakapay.base_url') ?? env('HARAKAPAY_BASE_URL', 'https://harakapay.net')), '/');
    }

    /**
     * Format phone number to standard Tanzania format (07XXXXXXXX or 06XXXXXXXX)
     */
    public function formatPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert +255XXXXXXXXX to 0XXXXXXXXX
        if (str_starts_with($cleaned, '255') && strlen($cleaned) === 12) {
            $cleaned = '0' . substr($cleaned, 3);
        }

        return $cleaned;
    }

    /**
     * Trigger USSD Push Collection via HarakaPay
     *
     * @param float|int $amount
     * @param string $phone
     * @param string $reference
     * @return array
     */
    public function collect($amount, string $phone, string $reference): array
    {
        $formattedPhone = $this->formatPhone($phone);

        $payload = [
            'amount' => (int) $amount,
            'phone' => $formattedPhone,
            'reference' => $reference,
        ];

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(25)->post($this->baseUrl . '/api/v1/collect', $payload);

            $data = $response->json();

            if ($response->successful() && isset($data['success']) && $data['success']) {
                Log::info('HarakaPay Collection initiated successfully', [
                    'order_id' => $data['order_id'] ?? null,
                    'reference' => $reference,
                    'amount' => $amount,
                    'phone' => $formattedPhone,
                ]);

                return [
                    'success' => true,
                    'order_id' => $data['order_id'] ?? null,
                    'amount' => $data['amount'] ?? $amount,
                    'fee' => $data['fee'] ?? 0,
                    'message' => $data['message'] ?? 'USSD Push sent to phone.',
                    'raw' => $data,
                ];
            }

            Log::error('HarakaPay Collection returned error', [
                'status' => $response->status(),
                'response' => $data,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Imeshindwa kutuma ombi la malipo kwenye simu. Tafadhali hakikisha namba yako ni sahihi.',
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('HarakaPay Collection Exception', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => 'Hitilafu ya mtandao wakati wa kuanzisha malipo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check transaction status by HarakaPay order_id
     *
     * @param string $orderId
     * @return array
     */
    public function checkStatus(string $orderId): array
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(15)->get($this->baseUrl . '/api/v1/status/' . urlencode($orderId));

            $data = $response->json();

            if ($response->successful() && isset($data['success']) && $data['success']) {
                $payment = $data['payment'] ?? [];
                $status = strtolower($payment['status'] ?? 'unknown');

                return [
                    'success' => true,
                    'status' => $status, // 'processing', 'completed', 'failed', 'pending'
                    'payment' => $payment,
                    'raw' => $data,
                ];
            }

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => $data['message'] ?? 'Status not found',
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('HarakaPay Status Check Exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get HarakaPay wallet balance
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get($this->baseUrl . '/api/v1/balance');

            return $response->json() ?? ['success' => false];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
