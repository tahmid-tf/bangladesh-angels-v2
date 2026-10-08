<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DgepayGateway
{
    public const STATUS_SUCCESS = '3';
    public const STATUS_CANCELLED = '8';

    /**
     * Get the active gateway mode (sandbox or live).
     */
    public function mode(): string
    {
        $mode = (string) config('dgepay.mode', 'sandbox');

        return in_array($mode, ['sandbox', 'live'], true) ? $mode : 'sandbox';
    }

    /**
     * Get the configured currency.
     */
    public function currency(): string
    {
        return strtoupper((string) config('dgepay.currency', 'USD'));
    }

    /**
     * Get active configuration for the current mode.
     *
     * @return array{base_url: string, client_id: string, client_secret: string, client_api_key: string}
     */
    public function activeConfig(): array
    {
        $mode = $this->mode();
        $cfg = config("dgepay.{$mode}", []);

        if (empty($cfg['client_id']) || empty($cfg['client_secret']) || empty($cfg['client_api_key'])) {
            throw new \RuntimeException(
                "DGePay {$mode} credentials are not configured. Set DGEPAY_".strtoupper($mode).'_CLIENT_ID, DGEPAY_'.strtoupper($mode).'_CLIENT_SECRET, and DGEPAY_'.strtoupper($mode).'_CLIENT_API_KEY in .env.'
            );
        }

        $cfg['base_url'] = rtrim($cfg['base_url'] ?? 'https://apiv2.dgepay.net/dipon/v3', '/');

        return $cfg;
    }

    /**
     * Authenticate with DGePay and obtain a JWT access token.
     *
     * @return array{success: bool, access_token?: string, message?: string}
     */
    public function authenticate(): array
    {
        try {
            $cfg = $this->activeConfig();
            $basicAuth = base64_encode($cfg['client_id'].':'.$cfg['client_secret']);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Basic '.$basicAuth,
                    'Content-Type' => 'application/json',
                ])
                ->post($cfg['base_url'].'/payment_gateway/authenticate', [
                    'client_id' => $cfg['client_id'],
                    'client_secret' => $cfg['client_secret'],
                ]);

            $data = $response->json() ?? [];

            if (! $response->successful() || empty($data['data']['access_token'])) {
                $errorMsg = $data['error'][0] ?? $data['message'] ?? 'DGePay authentication failed (HTTP '.$response->status().').';
                Log::warning('DGePay authentication failed', [
                    'status' => $response->status(),
                    'response' => $this->redactForLog($response->body()),
                ]);

                return ['success' => false, 'message' => $errorMsg];
            }

            return [
                'success' => true,
                'access_token' => $data['data']['access_token'],
            ];
        } catch (\Throwable $e) {
            Log::error('DGePay authenticate exception: '.$e->getMessage());

            return ['success' => false, 'message' => 'Could not connect to DGePay authentication: '.$e->getMessage()];
        }
    }

    /**
     * Initiate a payment session with DGePay.
     *
     * @param array{
     *     amount: float|int|string,
     *     orderId: string,
     *     redirectUrl: string,
     *     description?: string,
     *     payment_method?: string|null,
     *     customer_token?: string|null,
     *     payee_information?: string|null,
     *     unique_user_reference?: string|null,
     *     meta_data?: array|null
     * } $paymentData
     * @return array{success: bool, payment_url?: string, transaction_id?: string, message?: string}
     */
    public function initiatePayment(array $paymentData): array
    {
        try {
            $cfg = $this->activeConfig();
            $auth = $this->authenticate();

            if (! $auth['success']) {
                return $auth;
            }

            $payload = [
                'amount' => (float) ($paymentData['amount'] ?? 0),
                'customer_token' => $paymentData['customer_token'] ?? null,
                'note' => $paymentData['description'] ?? 'Membership Payment',
                'payee_information' => $paymentData['payee_information'] ?? null,
                'payment_method' => $paymentData['payment_method'] ?? null,
                'redirect_url' => $paymentData['redirectUrl'],
                'unique_txn_id' => $paymentData['orderId'],
                'meta_data' => $paymentData['meta_data'] ?? null,
                'unique_user_reference' => $paymentData['unique_user_reference'] ?? null,
            ];

            $signature = $this->generateSignature($payload, $cfg['client_api_key']);
            $encryptedBody = $this->encryptPayload($payload, $cfg['client_secret']);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$auth['access_token'],
                    'Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($encryptedBody, 'application/json')
                ->post($cfg['base_url'].'/payment_gateway/initiate_payment');

            $data = $response->json() ?? [];

            if (! $response->successful() || empty($data['data']['webview_url'])) {
                $errorMsg = $data['error'][0] ?? $data['message'] ?? 'Failed to initiate DGePay payment (HTTP '.$response->status().').';
                Log::warning('DGePay initiate payment failed', [
                    'status' => $response->status(),
                    'response' => $this->redactForLog($response->body()),
                    'order_id' => $paymentData['orderId'],
                ]);

                return ['success' => false, 'message' => $errorMsg];
            }

            return [
                'success' => true,
                'payment_url' => $data['data']['webview_url'],
                'transaction_id' => $data['data']['unique_txn_id'] ?? $paymentData['orderId'],
            ];
        } catch (\Throwable $e) {
            Log::error('DGePay initiatePayment exception: '.$e->getMessage());

            return ['success' => false, 'message' => 'Could not connect to DGePay: '.$e->getMessage()];
        }
    }

    /**
     * Check transaction status with DGePay server-to-server.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function getTransactionStatus(string $uniqueTxnId): array
    {
        try {
            $cfg = $this->activeConfig();
            $auth = $this->authenticate();

            if (! $auth['success']) {
                return $auth;
            }

            $payload = ['unique_txn_id' => $uniqueTxnId];
            $signature = $this->generateSignature($payload, $cfg['client_api_key']);
            $encryptedBody = $this->encryptPayload($payload, $cfg['client_secret']);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$auth['access_token'],
                    'Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($encryptedBody, 'application/json')
                ->post($cfg['base_url'].'/payment_gateway/check_transaction_status');

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                Log::warning('DGePay status check failed', [
                    'status' => $response->status(),
                    'unique_txn_id' => $uniqueTxnId,
                    'response' => $this->redactForLog($response->body()),
                ]);

                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to check payment status (HTTP '.$response->status().').',
                ];
            }

            return [
                'success' => true,
                'data' => $data['data'] ?? $data,
            ];
        } catch (\Throwable $e) {
            Log::error('DGePay getTransactionStatus exception: '.$e->getMessage());

            return ['success' => false, 'message' => 'Could not verify transaction with DGePay: '.$e->getMessage()];
        }
    }

    /**
     * Generate HMAC-SHA256 signature according to DGePay v1.9 specification.
     */
    public function generateSignature(array $data, ?string $apiKey = null): string
    {
        $key = $apiKey ?? ($this->activeConfig()['client_api_key'] ?? '');
        $flatString = $this->flattenParams($data);
        $flatString = str_replace(['{', '}', '"', ':', ' ', ','], '', $flatString);

        $hmac = hash_hmac('sha256', $flatString, $key, true);

        return base64_encode($hmac);
    }

    /**
     * Recursively flatten parameters into a concatenated string.
     */
    protected function flattenParams(array $data): string
    {
        ksort($data);

        $result = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result .= $key.$this->flattenParams($value);
            } else {
                if (is_null($value)) {
                    $strValue = 'null';
                } elseif (is_bool($value)) {
                    $strValue = $value ? 'true' : 'false';
                } elseif (is_int($value) || is_float($value)) {
                    $strValue = number_format((float) $value, 1, '.', '');
                } else {
                    $strValue = (string) $value;
                }
                $result .= $key.$strValue;
            }
        }

        return $result;
    }

    /**
     * Encrypt payload using AES-128-ECB with client secret.
     */
    public function encryptPayload(array $data, ?string $clientSecret = null): string
    {
        $secret = $clientSecret ?? ($this->activeConfig()['client_secret'] ?? '');
        $json = json_encode($data);

        return (string) openssl_encrypt($json, 'AES-128-ECB', $secret, 0);
    }

    /**
     * Decrypt AES-128-ECB encrypted payload.
     */
    public function decryptPayload(string $encrypted, ?string $clientSecret = null): string|false
    {
        $secret = $clientSecret ?? ($this->activeConfig()['client_secret'] ?? '');

        // Try standard base64 decryption
        $decrypted = openssl_decrypt($encrypted, 'AES-128-ECB', $secret, 0);

        if ($decrypted === false) {
            $decoded = base64_decode($encrypted, true);
            if ($decoded !== false) {
                $decrypted = openssl_decrypt($decoded, 'AES-128-ECB', $secret, OPENSSL_RAW_DATA);
            }
        }

        return $decrypted;
    }

    /**
     * Decrypt callback data received in query params.
     * Automatically fixes PHP's query-string '+' to ' ' conversion.
     */
    public function decryptCallbackData(string $encryptedData, ?string $clientSecret = null): ?array
    {
        $cleanData = str_replace(' ', '+', trim($encryptedData));
        $decrypted = $this->decryptPayload($cleanData, $clientSecret);

        if ($decrypted === false || $decrypted === null || $decrypted === '') {
            return null;
        }

        $decoded = json_decode($decrypted, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Normalize callback parameters into a structured result.
     *
     * @param array $params
     * @return array{
     *     is_success: bool,
     *     is_cancelled: bool,
     *     unique_txn_id: string,
     *     txn_number: string,
     *     message: string,
     *     status_code: string,
     *     payment_method: string,
     *     amount: mixed,
     *     metadata: array
     * }
     */
    public function parseCallbackResult(array $params): array
    {
        $statusCode = (string) ($params['status_code'] ?? '');
        $status = strtolower((string) ($params['status'] ?? ''));
        $message = (string) ($params['message'] ?? '');

        $isSuccess = ($params['unique_txn_id'] ?? '') !== '' && (
            $statusCode === self::STATUS_SUCCESS
            || in_array($status, ['1', 'success', 'completed'], true)
        );

        $isCancelled = $statusCode === self::STATUS_CANCELLED
            || in_array($status, ['8', 'cancel', 'cancelled'], true)
            || stripos($message, 'cancel') !== false;

        return [
            'is_success' => $isSuccess,
            'is_cancelled' => $isCancelled,
            'unique_txn_id' => (string) ($params['unique_txn_id'] ?? ''),
            'txn_number' => (string) ($params['txn_number'] ?? $params['txn_id'] ?? ''),
            'message' => $message,
            'status_code' => $statusCode,
            'payment_method' => (string) ($params['payment_method'] ?? ''),
            'amount' => $params['amount'] ?? null,
            'metadata' => (array) ($params['metadata'] ?? $params['meta_data'] ?? []),
        ];
    }

    public static function isSuccessStatus(mixed $statusCode): bool
    {
        return (string) $statusCode === self::STATUS_SUCCESS;
    }

    public static function isCancelledStatus(mixed $statusCode): bool
    {
        return (string) $statusCode === self::STATUS_CANCELLED;
    }

    /**
     * Generate unique transaction ID for DGePay.
     */
    public static function generateTransactionId(string $prefix = 'DG'): string
    {
        return $prefix.date('YmdHis').random_int(100, 999);
    }

    /**
     * Redact secrets before writing gateway bodies to logs.
     */
    public function redactForLog(?string $body): string
    {
        if ($body === null || $body === '') {
            return '(empty)';
        }

        $out = preg_replace(
            '/"(client_secret|client_api_key|access_token|password)"\s*:\s*"[^"]*"/i',
            '"$1":"[redacted]"',
            $body
        );

        return is_string($out) ? $out : $body;
    }
}
