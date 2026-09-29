<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AamarpayGateway
{
    public function initiateTransaction(string $url, array $payload): array
    {
        try {
            $response = Http::timeout(30)->acceptJson()->post($url, $payload);

            return ['body' => $response->body(), 'status' => $response->status(), 'error' => null];
        } catch (ConnectionException $exception) {
            return ['body' => '', 'status' => 0, 'error' => 'Could not connect to payment gateway.'];
        }
    }

    public function mode(): string
    {
        $mode = config('aamarpay.mode', 'sandbox');

        return in_array($mode, ['sandbox', 'live'], true) ? $mode : 'sandbox';
    }

    /**
     * Active store/signature URLs for the configured mode.
     *
     * @return array{jsonpost_url: string, trxcheck_base: string, store_id: string, signature_key: string}
     */
    public function activeConfig(): array
    {
        $mode = $this->mode();
        $cfg = config("aamarpay.{$mode}");

        if (empty($cfg['store_id']) || empty($cfg['signature_key'])) {
            throw new \RuntimeException(
                "AamarPay {$mode} credentials are not configured. Set AAMARPAY_".strtoupper($mode).'_STORE_ID and AAMARPAY_'.strtoupper($mode).'_SIGNATURE_KEY in .env.'
            );
        }

        return $cfg;
    }

    /**
     * Server-to-server verification (trxcheck). Returns decoded JSON object or null on failure.
     */
    public function verifyTransaction(string $merTxnid): ?object
    {
        if ($merTxnid === '') {
            return null;
        }

        try {
            $cfg = $this->activeConfig();
        } catch (\RuntimeException $e) {
            Log::error('AamarPay verifyTransaction: '.$e->getMessage());

            return null;
        }

        $query = http_build_query([
            'request_id' => $merTxnid,
            'store_id' => $cfg['store_id'],
            'signature_key' => $cfg['signature_key'],
            'type' => 'json',
        ], '', '&', PHP_QUERY_RFC3986);

        $url = $cfg['trxcheck_base'].'?'.$query;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            Log::error("AamarPay trxcheck cURL error: {$err}");

            return null;
        }

        Log::info('AamarPay trxcheck response (HTTP '.$httpCode.'): '.$response);

        $data = json_decode($response ?: '');

        return is_object($data) ? $data : null;
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
            '/"(signature_key|signature|store_id|opt_d)"\s*:\s*"[^"]*"/i',
            '"$1":"[redacted]"',
            $body
        );

        return is_string($out) ? $out : $body;
    }

    /**
     * Best-effort parse of jsonpost.php error fields (AamarPay response shapes vary).
     *
     * @param  mixed  $decoded  json_decode result (object or array)
     */
    public function parseJsonpostErrorHint(mixed $decoded): string
    {
        if ($decoded === null) {
            return 'invalid or empty JSON';
        }

        if (is_object($decoded)) {
            $decoded = json_decode(json_encode($decoded), true);
        }

        if (! is_array($decoded)) {
            return 'unexpected response type';
        }

        $priorityKeys = [
            'errors', 'detailedError', 'error', 'message', 'reason', 'msg',
            'failedreason', 'failed_reason', 'error_message',
            'description', 'details', 'pg_error_code_details',
            'track', 'track_id', 'result', 'status_code', 'status',
        ];

        $candidates = [];
        foreach ($priorityKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                continue;
            }
            $val = $decoded[$key];
            if ($val === null || $val === '') {
                continue;
            }
            if ($key === 'result' && (string) $val === 'false') {
                $candidates[] = 'result: false';

                continue;
            }
            if ($key === 'result' && (string) $val === 'true') {
                continue;
            }
            if ($key === 'errors') {
                if (is_array($val)) {
                    $parts = [];
                    foreach ($val as $item) {
                        $parts[] = is_scalar($item) ? (string) $item : json_encode($item);
                    }
                    $candidates[] = 'errors: '.implode('; ', $parts);
                } else {
                    $candidates[] = 'errors: '.(is_scalar($val) ? (string) $val : json_encode($val));
                }

                continue;
            }
            $candidates[] = $key.': '.(is_scalar($val) ? (string) $val : json_encode($val));
        }

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            foreach (['detailedError', 'error', 'message', 'reason', 'msg'] as $key) {
                if (! array_key_exists($key, $decoded['data'])) {
                    continue;
                }
                $val = $decoded['data'][$key];
                if ($val === null || $val === '') {
                    continue;
                }
                $candidates[] = 'data.'.$key.': '.(is_scalar($val) ? (string) $val : json_encode($val));
            }
        }

        // If AamarPay only returns generic keys, surface any other scalar fields (often holds the real reason).
        if (count($candidates) <= 1) {
            $skip = array_flip(array_merge($priorityKeys, ['payment_url']));
            foreach ($decoded as $key => $val) {
                if (isset($skip[$key])) {
                    continue;
                }
                if (! is_scalar($val) || $val === '') {
                    continue;
                }
                $s = (string) $val;
                if (strlen($s) > 160) {
                    $s = substr($s, 0, 157).'…';
                }
                $candidates[] = $key.': '.$s;
                if (count($candidates) >= 6) {
                    break;
                }
            }
        }

        return $candidates !== [] ? implode(' | ', array_unique($candidates)) : 'no known error fields in JSON';
    }

    /**
     * Hints for developers when jsonpost returns HTTP 200 but no payment_url (live mode).
     */
    public function liveJsonpostTroubleshootingFootnote(string $gatewayHint): string
    {
        $hint = strtolower($gatewayHint);

        if (str_contains($hint, 'store id blocked') || str_contains($hint, 'store blocked') || (str_contains($hint, 'blocked') && str_contains($hint, 'store'))) {
            return 'AamarPay has blocked this live store ID (merchant account restriction). This is not a Laravel bug—open a ticket with AamarPay merchant/integration support to unblock or issue new live credentials. Until then, payments cannot be initiated.';
        }

        if (str_contains($hint, 'signature') || str_contains($hint, 'invalid')) {
            return 'Confirm AAMARPAY_LIVE_SIGNATURE_KEY and AAMARPAY_LIVE_STORE_ID match the live AamarPay dashboard exactly (no extra spaces).';
        }

        if (str_contains($hint, 'currency')) {
            return 'Confirm the merchant account is enabled for the currency in AAMARPAY_CURRENCY (e.g. USD vs BDT).';
        }

        if (str_contains($hint, 'url') || str_contains($hint, 'callback')) {
            return 'Confirm success/fail/cancel callbacks use a public HTTPS URL that AamarPay can reach.';
        }

        if (preg_match('/^status:\s*error$/i', trim($gatewayHint)) || $gatewayHint === 'no known error fields in JSON') {
            return 'AamarPay returned a generic error. Check storage/logs for body_preview (full redacted JSON). Common live issues: store/signature not activated for production, AAMARPAY_CURRENCY not enabled on the merchant (try BDT if USD fails), or callback URL not public HTTPS.';
        }

        return 'See storage/logs (body_preview) for the full redacted response. Verify live credentials, jsonpost.php URL, APP_URL/HTTPS, and AAMARPAY_CURRENCY.';
    }

    /**
     * Log-friendly preview of jsonpost response (length-capped, redacted).
     */
    public function logJsonpostBodyPreview(?string $rawBody, int $maxChars = 4000): string
    {
        $redacted = $this->redactForLog($rawBody);

        return Str::limit($redacted, $maxChars, '…(truncated)');
    }
}
