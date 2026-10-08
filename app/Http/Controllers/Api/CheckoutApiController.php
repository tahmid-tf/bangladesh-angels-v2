<?php

namespace App\Http\Controllers\Api;

use App\Models\MembershipOrder;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\AamarpayGateway;
use App\Services\DgepayGateway;
use App\Services\MembershipEvidence;
use App\Support\MembershipPolicies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutApiController extends BaseApiController
{
    public function __construct(
        protected DgepayGateway $dgepay,
        protected ?AamarpayGateway $aamarpay = null
    ) {}

    /**
     * Process membership checkout & obtain payment gateway URL.
     */
    public function process(Request $request): JsonResponse
    {
        if ($request->user()?->hasPaidMembership()) {
            return $this->error('Your membership plan is already active.', 400);
        }

        if ($request->user()) {
            $request->merge(['email' => $request->user()->email]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => array_filter(['required', 'email', Auth::check() ? null : Rule::unique('users', 'email')]),
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string',
            'primary_country' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'investment_expertise' => 'required|in:beginner,intermediate,expert',
            'linkedin' => 'nullable|url|max:255',
            'password' => Auth::check() ? '' : 'required|confirmed|min:8',
            'plan' => 'required|string|max:64',
            'price' => 'required|numeric|min:0',
            'policy_consent' => ['required', 'accepted'],
            'policy_version' => ['required', Rule::in([MembershipPolicies::version()])],
        ]);

        $tier = SubscriptionTier::query()->active()->where('slug', $validated['plan'])->first();

        if (! $tier) {
            return $this->error('That plan is no longer available.', 422);
        }

        if (abs((float) $tier->price_yearly - (float) $validated['price']) > 0.009) {
            return $this->error('Plan pricing was updated. Please refresh plans.', 422);
        }

        $user = $request->user() ?: User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'phone' => $validated['country_code'].$validated['phone'],
                'address' => $validated['address'],
                'primary_country' => $validated['primary_country'],
                'company_name' => $validated['company_name'],
                'designation' => $validated['designation'],
                'gender' => $validated['gender'],
                'investment_expertise' => $validated['investment_expertise'],
                'linkedin' => $validated['linkedin'] ?? null,
                'password' => Hash::make($request->password),
                'role' => 'investor',
                'account_status' => 'free',
                'payment_status' => 'free',
                'is_approved' => false,
            ]
        );

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => $validated['plan'],
            'price' => $validated['price'],
            'status' => 'pending',
        ]);

        $activeGateway = config('payment.default', env('PAYMENT_GATEWAY', 'dgepay'));

        if ($activeGateway === 'dgepay') {
            $gatewayMode = $this->dgepay->mode();
            $currency = $this->dgepay->currency();
            $tranPrefix = $gatewayMode === 'live' ? 'DG' : 'DGTEST';
            $tran_id = DgepayGateway::generateTransactionId($tranPrefix);
        } else {
            $currency = strtoupper((string) config('aamarpay.currency', 'USD'));
            $tran_id = 'BAN'.time().rand(1000, 9999);
        }

        $order = app(MembershipEvidence::class)->begin(
            $request,
            $user,
            $subscription,
            $tier,
            $tran_id,
            $currency
        );

        $authToken = $user->createToken('mobile-app')->plainTextToken;

        // Skip payment only in local when explicitly enabled, or in generic test runs unless testing gateway
        $shouldSkipPayment = (app()->environment('local') && filter_var(env('SKIP_PAYMENT_GATEWAY', false), FILTER_VALIDATE_BOOLEAN))
            || (app()->environment('testing') && ! config('dgepay.test_real_initiation', false) && ! config('aamarpay.test_real_initiation', false));

        if ($shouldSkipPayment) {
            $user->update([
                'account_status' => $validated['plan'],
                'payment_status' => 'paid',
            ]);

            $subscription->update([
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ]);

            return $this->success([
                'status' => 'completed',
                'gateway' => $activeGateway,
                'order_reference' => $order->reference,
                'transaction_id' => $tran_id,
                'user' => $this->formatUser($user->fresh()),
                'token' => $authToken,
            ], 'Subscription activated in test mode.');
        }

        if ($activeGateway === 'dgepay') {
            try {
                $this->dgepay->activeConfig();
            } catch (\RuntimeException $e) {
                Log::error('DGePay API configuration error: '.$e->getMessage());

                return $this->error('Payment gateway is not configured. Please contact support.', 500);
            }

            $callbackUrl = route('payment.dgepay.callback', [], true);

            $gatewayResponse = $this->dgepay->initiatePayment([
                'amount' => $validated['price'],
                'orderId' => $tran_id,
                'redirectUrl' => $callbackUrl,
                'description' => 'BAN Membership - '.$tier->name,
                'unique_user_reference' => (string) $user->id,
                'meta_data' => [
                    'custom_field_1' => (string) $validated['plan'],
                    'custom_field_2' => (string) $user->id,
                    'custom_field_3' => (string) $gatewayMode,
                ],
            ]);

            if (! empty($gatewayResponse['success']) && ! empty($gatewayResponse['payment_url'])) {
                return $this->success([
                    'status' => 'pending_payment',
                    'gateway' => 'dgepay',
                    'payment_url' => $gatewayResponse['payment_url'],
                    'transaction_id' => $tran_id,
                    'order_reference' => $order->reference,
                    'token' => $authToken,
                    'user' => $this->formatUser($user),
                ], 'Payment initiated. Redirect to payment URL.');
            }

            Log::error('DGePay API initiate payment failed', [
                'mode' => $gatewayMode,
                'tran_id' => $tran_id,
                'response' => $gatewayResponse,
            ]);

            return $this->error($gatewayResponse['message'] ?? 'Failed to generate payment gateway link. Please contact support.', 502);
        }

        // AamarPay Fallback Branch
        try {
            $aamarpay = $this->aamarpay ?? app(AamarpayGateway::class);
            $cfg = $aamarpay->activeConfig();
            $callbackUrl = route('payment.aamarpay.callback');

            $postData = [
                'store_id' => $cfg['store_id'],
                'signature_key' => $cfg['signature_key'],
                'tran_id' => $tran_id,
                'amount' => (int) round((float) $validated['price']),
                'currency' => $currency,
                'desc' => "BAN Membership - {$tier->name}",
                'cus_name' => $validated['name'],
                'cus_email' => $validated['email'],
                'cus_add1' => $validated['address'],
                'cus_city' => 'Dhaka',
                'cus_country' => $validated['primary_country'],
                'cus_phone' => $validated['country_code'].$validated['phone'],
                'success_url' => $callbackUrl,
                'fail_url' => $callbackUrl,
                'cancel_url' => $callbackUrl,
                'type' => 'json',
                'opt_a' => $validated['plan'],
                'opt_b' => (string) $user->id,
                'opt_c' => (string) $subscription->id,
                'opt_d' => $tran_id,
            ];

            $response = $aamarpay->postJson($cfg['jsonpost_url'], $postData);
            $responseObj = is_string($response) ? json_decode($response) : $response;

            $paymentUrl = $aamarpay->extractPaymentUrl($responseObj);

            if ($paymentUrl) {
                return $this->success([
                    'status' => 'pending_payment',
                    'gateway' => 'aamarpay',
                    'payment_url' => $paymentUrl,
                    'transaction_id' => $tran_id,
                    'order_reference' => $order->reference,
                    'token' => $authToken,
                    'user' => $this->formatUser($user),
                ], 'Payment initiated. Redirect to payment URL.');
            }

            return $this->error('Failed to generate payment gateway link. Please contact support.', 502);
        } catch (\Throwable $e) {
            Log::error('API Checkout Error: '.$e->getMessage());

            return $this->error('Payment initialization failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Check status of membership order.
     */
    public function status(string $reference): JsonResponse
    {
        $order = MembershipOrder::with('payment')
            ->where('reference', $reference)
            ->orWhere('merchant_txnid', $reference)
            ->first();

        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        // If payment is still pending, verify with DGePay server-to-server
        if ((! $order->payment || $order->payment->status !== 'completed') && $order->merchant_txnid) {
            try {
                $statusCheck = $this->dgepay->getTransactionStatus($order->merchant_txnid);
                if (($statusCheck['success'] ?? false) && is_array($statusCheck['data'] ?? null)) {
                    $verified = $statusCheck['data'];
                    $statusCode = (string) ($verified['status_code'] ?? '');
                    if (DgepayGateway::isSuccessStatus($statusCode)) {
                        $this->fulfillSuccessfulPayment(
                            (int) $order->user_id,
                            (string) $order->plan_slug,
                            (string) $order->merchant_txnid,
                            $verified['txn_number'] ?? $verified['txn_id'] ?? null,
                            $verified['amount'] ?? $order->amount,
                            $order->currency ?? $this->dgepay->currency(),
                            (int) $statusCode,
                            request()->ip(),
                            'dgepay'
                        );
                        $order->refresh();
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error checking gateway status in API status query: '.$e->getMessage());
            }
        }

        return $this->success([
            'reference' => $order->reference,
            'transaction_id' => $order->merchant_txnid,
            'amount' => (float) $order->amount,
            'currency' => $order->currency,
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'delivery_confirmed_at' => $order->delivery_confirmed_at?->toIso8601String(),
            'payment_status' => $order->payment?->status ?? 'pending',
            'payment_method' => $order->payment?->payment_method ?? null,
            'plan' => $order->plan_snapshot,
        ]);
    }

    /**
     * DGePay API callback handler (returns JSON).
     */
    public function callback(Request $request): JsonResponse
    {
        $callbackData = [];
        if ($request->has('data')) {
            $decrypted = $this->dgepay->decryptCallbackData((string) $request->input('data'));
            if ($decrypted) {
                $callbackData = $decrypted;
            }
        }

        if (empty($callbackData)) {
            $callbackData = $request->all();
        }

        $parsed = $this->dgepay->parseCallbackResult($callbackData);
        $uniqueTxnId = $parsed['unique_txn_id'] ?: (string) $request->input('unique_txn_id');

        Log::info('DGePay API callback received', [
            'unique_txn_id' => $uniqueTxnId,
            'parsed' => $parsed,
        ]);

        if (empty($uniqueTxnId)) {
            return $this->error('Payment session invalid: missing transaction ID.', 400);
        }

        if ($parsed['is_cancelled']) {
            return $this->error('Checkout was cancelled.', 400, ['status' => 'cancelled', 'transaction_id' => $uniqueTxnId]);
        }

        $statusCheck = $this->dgepay->getTransactionStatus($uniqueTxnId);
        if (! ($statusCheck['success'] ?? false) || ! is_array($statusCheck['data'] ?? null)) {
            return $this->error('Could not verify payment with gateway.', 502, ['transaction_id' => $uniqueTxnId]);
        }

        $verified = $statusCheck['data'];
        $statusCode = (string) ($verified['status_code'] ?? '');
        $pgTxnId = $verified['txn_number'] ?? $verified['txn_id'] ?? null;
        $amount = $verified['amount'] ?? null;
        $order = MembershipOrder::where('merchant_txnid', $uniqueTxnId)->first();

        $subscriptionPlan = $order?->plan_slug ?? ($verified['metadata']['custom_field_1'] ?? null);
        $userId = $order?->user_id ?? ($verified['metadata']['custom_field_2'] ?? null);
        $currency = $order?->currency ?? $this->dgepay->currency();

        if (DgepayGateway::isSuccessStatus($statusCode)) {
            if ($userId && $subscriptionPlan) {
                $payment = $this->fulfillSuccessfulPayment(
                    (int) $userId,
                    (string) $subscriptionPlan,
                    (string) $uniqueTxnId,
                    $pgTxnId ? (string) $pgTxnId : null,
                    $amount,
                    (string) $currency,
                    (int) $statusCode,
                    $request->ip(),
                    'dgepay'
                );

                return $this->success([
                    'status' => 'completed',
                    'order_reference' => $order?->reference,
                    'transaction_id' => $uniqueTxnId,
                    'payment_id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                ], 'Payment successful and membership active.');
            }
        }

        return $this->error('Payment could not be confirmed (status: '.$statusCode.').', 422, [
            'status' => 'unverified',
            'status_code' => $statusCode,
            'transaction_id' => $uniqueTxnId,
        ]);
    }

    /**
     * Fulfill successfully verified payment and activate subscription.
     */
    protected function fulfillSuccessfulPayment(
        int $userId,
        string $planSlug,
        string $merTxnid,
        ?string $pgTxnid,
        $amount,
        string $currency,
        int $statusCode,
        ?string $customerIp,
        string $paymentMethod = 'dgepay'
    ): Payment {
        return DB::transaction(function () use ($userId, $planSlug, $merTxnid, $pgTxnid, $amount, $currency, $statusCode, $customerIp, $paymentMethod) {
            $order = MembershipOrder::where('merchant_txnid', $merTxnid)->lockForUpdate()->first();
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $existing = Payment::query()
                ->where('merchant_txnid', $merTxnid)
                ->where('status', 'completed')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $user->update([
                'account_status' => $planSlug,
                'payment_status' => 'paid',
            ]);

            $payment = Payment::create([
                'user_id' => $userId,
                'payment_method' => $paymentMethod,
                'transaction_id' => $pgTxnid,
                'merchant_txnid' => $merTxnid,
                'pg_txnid' => $pgTxnid,
                'subscription_plan' => $planSlug,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'completed',
                'status_code' => $statusCode,
                'payment_date' => now(),
                'expiry_date' => now()->addYear(),
                'customer_ip' => $customerIp,
            ]);

            Subscription::query()->updateOrCreate(
                ['user_id' => $userId, 'plan' => $planSlug],
                [
                    'price' => $amount,
                    'status' => 'active',
                    'payment_id' => $payment->id,
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                ]
            );

            if ($order) {
                $order->update(['payment_id' => $payment->id, 'delivered_at' => now()]);
            }

            Log::info("Payment fulfilled via API for user #{$userId}: {$amount} {$currency} ({$planSlug})");

            app(MembershipEvidence::class)->sendReceipt($payment);

            return $payment;
        });
    }
}
