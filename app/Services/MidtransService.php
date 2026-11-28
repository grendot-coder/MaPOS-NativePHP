<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MidtransService
{
    public function isConfigured(): bool
    {
        return filled(config('midtrans.server_key'));
    }

    /**
     * Buat transaksi QRIS melalui Midtrans Core API.
     *
     * @return array{
     *     success: bool,
     *     order_id: string,
     *     qr_url: ?string,
     *     qr_string: ?string,
     *     status: string,
     *     message: string
     * }
     */
    public function createQrisCharge(string $orderId, int $grossAmount): array
    {
        $serverKey = (string) config('midtrans.server_key');
        if (empty($serverKey)) {
            return [
                'success' => false,
                'order_id' => $orderId,
                'qr_url' => null,
                'qr_string' => null,
                'status' => 'unconfigured',
                'message' => 'MIDTRANS_SERVER_KEY belum diisi pada file .env.',
            ];
        }

        $baseUrl = rtrim((string) config('midtrans.base_url'), '/');

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($serverKey, '')
                ->acceptJson()
                ->post("{$baseUrl}/charge", [
                    'payment_type' => 'qris',
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => max(1, $grossAmount),
                    ],
                    'qris' => [
                        'acquirer' => 'gopay',
                    ],
                ]);

            $json = $response->json() ?? [];
            $statusCode = (string) ($json['status_code'] ?? $response->status());

            if (! $response->successful() || ! in_array($statusCode, ['200', '201'], true)) {
                $errorMsg = $json['status_message']
                    ?? $json['error']
                    ?? ($json['error_messages'][0] ?? null)
                    ?? "Gagal membuat QRIS Midtrans (HTTP {$response->status()}).";

                return [
                    'success' => false,
                    'order_id' => $orderId,
                    'qr_url' => null,
                    'qr_string' => null,
                    'status' => 'error',
                    'message' => (string) $errorMsg,
                ];
            }

            $qrUrl = null;
            foreach ($json['actions'] ?? [] as $action) {
                if (($action['name'] ?? '') === 'generate-qr-code') {
                    $qrUrl = $action['url'] ?? null;
                    break;
                }
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'qr_url' => $qrUrl,
                'qr_string' => $json['qr_string'] ?? null,
                'status' => (string) ($json['transaction_status'] ?? 'pending'),
                'message' => 'QRIS Midtrans siap dipindai.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'order_id' => $orderId,
                'qr_url' => null,
                'qr_string' => null,
                'status' => 'error',
                'message' => 'Koneksi Midtrans gagal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Cek status transaksi Midtrans berdasarkan Order ID.
     *
     * @return array{success: bool, status: string, is_paid: bool, is_failed: bool, message: string}
     */
    public function checkStatus(string $orderId): array
    {
        $serverKey = (string) config('midtrans.server_key');
        if (empty($serverKey)) {
            return [
                'success' => false,
                'status' => 'unconfigured',
                'is_paid' => false,
                'is_failed' => false,
                'message' => 'MIDTRANS_SERVER_KEY belum diisi.',
            ];
        }

        $baseUrl = rtrim((string) config('midtrans.base_url'), '/');

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($serverKey, '')
                ->acceptJson()
                ->get("{$baseUrl}/{$orderId}/status");

            $json = $response->json() ?? [];
            $status = (string) ($json['transaction_status'] ?? 'pending');

            return [
                'success' => $response->successful(),
                'status' => $status,
                'is_paid' => in_array($status, ['settlement', 'capture'], true),
                'is_failed' => in_array($status, ['expire', 'cancel', 'deny', 'failure'], true),
                'message' => (string) ($json['status_message'] ?? "Status: {$status}"),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'error',
                'is_paid' => false,
                'is_failed' => false,
                'message' => 'Gagal mengecek status: '.$e->getMessage(),
            ];
        }
    }

    public function cancelTransaction(string $orderId): void
    {
        $serverKey = (string) config('midtrans.server_key');
        if (empty($serverKey) || empty($orderId)) {
            return;
        }

        $baseUrl = rtrim((string) config('midtrans.base_url'), '/');

        try {
            Http::timeout(10)
                ->withBasicAuth($serverKey, '')
                ->acceptJson()
                ->post("{$baseUrl}/{$orderId}/cancel");
        } catch (\Throwable) {
        }
    }
}
