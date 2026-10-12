<?php

namespace App\Http\Controllers;

use App\Mail\CheckoutConfirmation;
use App\Models\MembershipOrder;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\AamarpayGateway;
use App\Services\DgepayGateway;
use App\Services\MembershipEvidence;
use App\Support\MembershipPolicies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(
        protected AamarpayGateway $aamarpay,
        protected DgepayGateway $dgepay
    ) {}

    protected function activeGatewayName(): string
    {
        return strtolower((string) config('payment.default', env('PAYMENT_GATEWAY', 'dgepay')));
    }

    public function checkout(Request $request)
    {
        if ($request->user()?->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'plan' => ['required', 'string', 'max:64'],
                'plan_price' => ['nullable', 'numeric', 'min:0'],
            ]);

            $tier = SubscriptionTier::query()
                ->active()
                ->where('slug', $validated['plan'])
                ->first();

            if (! $tier) {
                return redirect()->route('plans')->with('error', 'That plan is no longer available.');
            }

            if (isset($validated['plan_price']) && abs((float) $tier->price_yearly - (float) $validated['plan_price']) > 0.009) {
                return redirect()->route('plans')->with('error', 'Plan pricing was updated. Please choose your plan again.');
            }

            $request->session()->put('checkout.plan', [
                'slug' => $tier->slug,
                'name' => $tier->name,
                'price' => (float) $tier->price_yearly,
            ]);
        } elseif ($request->filled('plan')) {
            $tier = SubscriptionTier::query()
                ->active()
                ->where('slug', (string) $request->input('plan'))
                ->first();

            if ($tier) {
                $request->session()->put('checkout.plan', [
                    'slug' => $tier->slug,
                    'name' => $tier->name,
                    'price' => (float) $tier->price_yearly,
                ]);
            }
        } elseif (! $request->session()->has('checkout.plan')) {
            $tier = SubscriptionTier::query()->active()->first();
            if ($tier) {
                $request->session()->put('checkout.plan', [
                    'slug' => $tier->slug,
                    'name' => $tier->name,
                    'price' => (float) $tier->price_yearly,
                ]);
            }
        }

        return view('payment.checkout');
    }

    public function processCheckout(Request $request)
    {
        if ($request->user()?->hasPaidMembership()) {
            return redirect()->route('dashboard')->with('info', 'Your membership plan is already active.');
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
            'profile_photo' => 'nullable',
            'plan' => 'required|string|max:64',
            'price' => 'required|numeric|min:0',
            'policy_consent' => ['required', 'accepted'],
            'policy_version' => ['required', Rule::in([MembershipPolicies::version()])],
            'policy_accepted_at' => ['nullable', 'date'],
        ]);

        abort_unless(Schema::hasTable('membership_orders'), 503, 'Membership checkout is being updated. Please try again shortly.');

        $tier = SubscriptionTier::query()
            ->active()
            ->where('slug', $validated['plan'])
            ->first();

        if (! $tier) {
            return redirect()->route('plans')->with('error', 'That plan is no longer available.');
        }

        if (abs((float) $tier->price_yearly - (float) $validated['price']) > 0.009) {
            return redirect()->route('plans')->with('error', 'Plan pricing was updated. Please choose your plan again.');
        }

        $activeGateway = $this->activeGatewayName();
        $configuredCurrency = $activeGateway === 'dgepay'
            ? $this->dgepay->currency()
            : strtoupper((string) config('aamarpay.currency', 'USD'));

        // The public catalogue quotes USD; never send the same numeric price as BDT without conversion.
        if ($activeGateway === 'aamarpay' && $configuredCurrency !== 'USD') {
            return redirect()->route('checkout')->with('error', 'USD membership checkout is unavailable. Please contact support.');
        }

        if ($activeGateway === 'dgepay' && ! $this->dgepay->shouldConvertUsdToBdt() && $configuredCurrency !== 'USD') {
            return redirect()->route('checkout')->with('error', 'USD membership checkout is unavailable. Please contact support.');
        }

        try {
            $user = User::firstOrCreate(
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
                    'password' => Auth::check() ? auth()->user()->password : Hash::make($request->password),
                    'is_approved' => false,
                ]
            );

            if ($user->wasRecentlyCreated === false) {
                $user->update([
                    'name' => $validated['name'],
                    'phone' => $validated['country_code'].$validated['phone'],
                    'address' => $validated['address'],
                    'primary_country' => $validated['primary_country'],
                    'company_name' => $validated['company_name'],
                    'designation' => $validated['designation'],
                    'gender' => $validated['gender'],
                    'investment_expertise' => $validated['investment_expertise'],
                    'linkedin' => $validated['linkedin'] ?? null,
                ]);
            }

            if ($request->hasFile('profile_photo')) {
                $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
            }
            if ($request->hasFile('media.profile_photo')) {
                $user->addMediaFromRequest('media.profile_photo')->toMediaCollection('profile_photos');
            }

            if (! Auth::check()) {
                Auth::login($user);
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan' => $validated['plan'],
                'price' => $validated['price'],
                'status' => 'pending',
            ]);

            session([
                'checkout.user_id' => $user->id,
                'checkout.subscription_id' => $subscription->id,
                'checkout.plan' => [
                    'slug' => $tier->slug,
                    'name' => $tier->name,
                    'price' => (float) $validated['price'],
                ],
            ]);

            if (app()->environment('local') && filter_var(env('SKIP_PAYMENT_GATEWAY', false), FILTER_VALIDATE_BOOLEAN)) {
                $user->update([
                    'account_status' => $validated['plan'],
                    'payment_status' => 'paid',
                ]);

                $subscription->update([
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                ]);

                return redirect()->route('dashboard')->with('success', 'Your subscription has been activated in test mode.');
            }

            if ($activeGateway === 'dgepay') {
                $gatewayMode = $this->dgepay->mode();

                try {
                    $this->dgepay->activeConfig();
                } catch (\RuntimeException $e) {
                    Log::error($e->getMessage());

                    return redirect()->route('checkout')->with('error', 'Payment gateway is not configured. Please contact support.');
                }

                $tranPrefix = $gatewayMode === 'live' ? 'DG' : 'DGTEST';
                $tran_id = DgepayGateway::generateTransactionId($tranPrefix);
                $usdAmount = (float) $validated['price'];
                $chargeAmount = $this->dgepay->resolveChargeAmount($usdAmount);
                $currency = 'USD';

                app(MembershipEvidence::class)->begin($request, $user, $subscription, $tier, $tran_id, $currency);

                $callbackUrl = route('payment.dgepay.callback', [], true);

                $desc = $this->dgepay->shouldConvertUsdToBdt()
                    ? "BAN Membership - {$tier->name} ($ {$usdAmount} USD / ৳ {$chargeAmount} BDT)"
                    : 'Membership - '.$tier->name;

                $gatewayResponse = $this->dgepay->initiatePayment([
                    'amount' => $chargeAmount,
                    'orderId' => $tran_id,
                    'redirectUrl' => $callbackUrl,
                    'description' => $desc,
                    'unique_user_reference' => (string) $user->id,
                    'meta_data' => [
                        'custom_field_1' => (string) $validated['plan'],
                        'custom_field_2' => (string) $user->id,
                        'custom_field_3' => (string) $gatewayMode,
                    ],
                ]);

                if (! empty($gatewayResponse['success']) && ! empty($gatewayResponse['payment_url'])) {
                    session(['checkout.tran_id' => $tran_id]);

                    return redirect()->away($gatewayResponse['payment_url']);
                }

                Log::error('DGePay initiate payment failed', [
                    'mode' => $gatewayMode,
                    'tran_id' => $tran_id,
                    'response' => $gatewayResponse,
                ]);

                if ($gatewayMode === 'sandbox' && filter_var(env('DGEPAY_SANDBOX_FALLBACK', false), FILTER_VALIDATE_BOOLEAN)) {
                    return $this->activateSandboxWithoutGatewayFallback($user, $subscription, $validated);
                }

                $userMessage = $gatewayResponse['message'] ?? 'Payment gateway error. Please try again or contact support.';

                return redirect()->route('checkout')->with('error', $userMessage);
            }

            $gatewayMode = $this->aamarpay->mode();

            try {
                $cfg = $this->aamarpay->activeConfig();
            } catch (\RuntimeException $e) {
                Log::error($e->getMessage());

                return redirect()->route('checkout')->with('error', 'Payment gateway is not configured. Please contact support.');
            }

            if (str_contains(strtolower($cfg['jsonpost_url']), 'trxcheck')) {
                Log::error('AamarPay misconfiguration: JSONPOST URL must be jsonpost.php, not trxcheck', [
                    'jsonpost_url' => $cfg['jsonpost_url'],
                ]);

                return redirect()->route('checkout')->with(
                    'error',
                    'Payment gateway configuration error. The init URL must be jsonpost.php (see AAMARPAY_*_JSONPOST_URL in server env).'
                );
            }

            $tranPrefix = $gatewayMode === 'live' ? 'bdangels' : 'test';
            $tran_id = $tranPrefix.Str::ulid();

            $currency = strtoupper((string) config('aamarpay.currency', 'USD'));
            $amount = $validated['price'];

            app(MembershipEvidence::class)->begin($request, $user, $subscription, $tier, $tran_id, $currency);

            $callbackUrl = route('payment.aamarpay.callback', [], true);

            $payload = [
                'store_id' => $cfg['store_id'],
                'tran_id' => $tran_id,
                'success_url' => $callbackUrl,
                'fail_url' => $callbackUrl,
                'cancel_url' => $callbackUrl,
                'amount' => (string) $amount,
                'currency' => $currency,
                'signature_key' => $cfg['signature_key'],
                'desc' => 'Membership Payment',
                'cus_name' => $validated['name'],
                'cus_email' => $validated['email'],
                'cus_add1' => $validated['address'],
                'cus_country' => $validated['primary_country'],
                'cus_phone' => $validated['country_code'].$validated['phone'],
                'opt_a' => $validated['plan'],
                'opt_b' => (string) $user->id,
                'opt_c' => $gatewayMode,
                'type' => 'json',
            ];

            $gatewayResponse = $this->aamarpay->initiateTransaction($cfg['jsonpost_url'], $payload);
            $response = $gatewayResponse['body'];
            $err = $gatewayResponse['error'];
            $httpCode = $gatewayResponse['status'];

            Log::info("AamarPay init response ({$gatewayMode}, HTTP {$httpCode}): ".($response ?: '(empty)'));

            if ($err) {
                Log::error('AamarPay cURL error: '.$err);

                if ($gatewayMode === 'sandbox') {
                    return $this->activateSandboxWithoutGatewayFallback($user, $subscription, $validated);
                }

                return redirect()->route('checkout')->with('error', 'Could not connect to payment gateway. Please try again.');
            }

            $responseObj = json_decode($response ?: '');

            $paymentUrl = null;
            if (is_object($responseObj) && isset($responseObj->payment_url)) {
                $paymentUrl = $responseObj->payment_url;
            }
            $resultOk = is_object($responseObj)
                && isset($responseObj->result)
                && (string) $responseObj->result === 'true';

            $hasPaymentUrl = $paymentUrl !== null && $paymentUrl !== '';

            if ($hasPaymentUrl && ($resultOk || ! isset($responseObj->result))) {
                session(['checkout.tran_id' => $tran_id]);

                return redirect()->away($paymentUrl);
            }

            if ($hasPaymentUrl && isset($responseObj->result) && ! $resultOk) {
                Log::warning('AamarPay returned payment_url but result is not true', [
                    'mode' => $gatewayMode,
                    'result' => $responseObj->result ?? null,
                ]);
            }

            $gatewayHint = $this->aamarpay->parseJsonpostErrorHint($responseObj);
            $bodyPreview = $this->aamarpay->logJsonpostBodyPreview($response);

            Log::error('AamarPay jsonpost did not return payment_url', [
                'mode' => $gatewayMode,
                'http_code' => $httpCode,
                'jsonpost_url' => $cfg['jsonpost_url'],
                'trxcheck_base' => $cfg['trxcheck_base'],
                'gateway_hint' => $gatewayHint,
                'body_preview' => $bodyPreview,
                'tran_id' => $tran_id,
                'currency' => $currency,
                'callback_host' => parse_url($callbackUrl, PHP_URL_HOST) ?: null,
            ]);

            if ($httpCode < 200 || $httpCode >= 300) {
                Log::warning('AamarPay jsonpost returned non-2xx HTTP status', [
                    'mode' => $gatewayMode,
                    'http_code' => $httpCode,
                ]);
            }

            if ($gatewayMode === 'sandbox') {
                return $this->activateSandboxWithoutGatewayFallback($user, $subscription, $validated);
            }

            $userMessage = 'Payment gateway error. Please try again or contact support.';
            if (config('app.debug')) {
                $userMessage .= ' Technical: '.$gatewayHint.' (HTTP '.$httpCode.').';
                $userMessage .= ' '.$this->aamarpay->liveJsonpostTroubleshootingFootnote($gatewayHint);
            } elseif (str_contains(strtolower($gatewayHint), 'store id blocked')) {
                $userMessage = 'Online payment is unavailable: the payment provider has blocked this merchant account. Please contact support.';
            }

            return redirect()->route('checkout')->with('error', $userMessage);
        } catch (\Exception $e) {
            Log::error('Exception in checkout process: '.$e->getMessage()."\n".$e->getTraceAsString());

            return redirect()->route('checkout')->with('error', 'An error occurred: '.$e->getMessage());
        }
    }

    /**
     * DGePay redirects or returns here after payment.
     */
    public function dgepayCallback(Request $request)
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
        $uniqueTxnId = $parsed['unique_txn_id'] ?: ($request->input('unique_txn_id') ?: (string) session('checkout.tran_id'));

        Log::info('DGePay callback received', [
            'unique_txn_id' => $uniqueTxnId,
            'parsed' => $parsed,
        ]);

        if (empty($uniqueTxnId)) {
            Log::warning('DGePay callback missing transaction ID', ['input' => $request->all()]);

            return redirect()->route('upgrade.page')->with('error', 'Payment session invalid. Please try again.');
        }

        if ($parsed['is_cancelled']) {
            Log::info('DGePay payment cancelled by customer', ['unique_txn_id' => $uniqueTxnId]);

            return redirect()->route('upgrade.page')->with('info', 'Checkout was cancelled.');
        }

        // Server-to-server transaction status verification
        $statusCheck = $this->dgepay->getTransactionStatus($uniqueTxnId);

        if (! ($statusCheck['success'] ?? false) || ! is_array($statusCheck['data'] ?? null)) {
            Log::error('DGePay status verification failed', [
                'unique_txn_id' => $uniqueTxnId,
                'status_check' => $statusCheck,
            ]);

            return redirect()->route('upgrade.page')->with('error', 'Could not verify payment with gateway. Please contact support.');
        }

        $verified = $statusCheck['data'];
        $statusCode = (string) ($verified['status_code'] ?? '');
        $pgTxnId = $verified['txn_number'] ?? $verified['txn_id'] ?? null;
        $amount = $verified['amount'] ?? null;
        $order = MembershipOrder::where('merchant_txnid', $uniqueTxnId)->first();

        $subscriptionPlan = $order?->plan_slug ?? ($verified['metadata']['custom_field_1'] ?? ($verified['metadata']['plan'] ?? null));
        $userId = $order?->user_id ?? ($verified['metadata']['custom_field_2'] ?? ($verified['metadata']['user_id'] ?? null));
        $currency = $order?->currency ?? $this->dgepay->currency();

        $expectedChargeAmount = $this->dgepay->resolveChargeAmount((float) ($order?->amount ?? 0));

        if (($verified['unique_txn_id'] ?? '') !== $uniqueTxnId
            || ! is_numeric($amount)
            || ! $subscriptionPlan
            || ($order && abs((float) $amount - (float) $expectedChargeAmount) > 0.009 && abs((float) $amount - (float) $order->amount) > 0.009)) {
            Log::error('DGePay payment verification mismatch', [
                'unique_txn_id' => $uniqueTxnId,
                'order' => $order?->toArray(),
                'verified' => $verified,
                'expected_charge_amount' => $expectedChargeAmount,
            ]);

            return redirect()->route('upgrade.page')->with('error', 'Payment details could not be matched to your order. Please contact support.');
        }

        if (empty($userId) || ! User::query()->whereKey($userId)->exists()) {
            Log::error('DGePay callback invalid user', ['user_id' => $userId, 'unique_txn_id' => $uniqueTxnId]);

            return redirect()->route('payment.error', ['error' => 'user_not_found', 'user_id' => $userId]);
        }

        if (DgepayGateway::isSuccessStatus($statusCode)) {
            try {
                $payment = $this->fulfillSuccessfulPayment(
                    (int) $userId,
                    (string) $subscriptionPlan,
                    (string) $uniqueTxnId,
                    $pgTxnId ? (string) $pgTxnId : null,
                    $order ? $order->amount : $amount,
                    (string) $currency,
                    (int) $statusCode,
                    $request->ip(),
                    'dgepay'
                );

                app(MembershipEvidence::class)->sendReceipt($payment);

                return redirect()->temporarySignedRoute(
                    'payment.complete',
                    now()->addHours(48),
                    ['payment' => $payment->id]
                );
            } catch (\Throwable $e) {
                Log::error('Fulfill DGePay payment failed: '.$e->getMessage());

                return redirect()->route('upgrade.page')->with('error', 'An error occurred while completing your payment. Please contact support.');
            }
        }

        if (DgepayGateway::isCancelledStatus($statusCode)) {
            Log::warning('DGePay payment cancelled', ['unique_txn_id' => $uniqueTxnId]);

            return redirect()->route('payment.failed', [
                'mer_txnid' => $uniqueTxnId,
                'error_message' => $verified['message'] ?? 'Payment was cancelled',
                'user_id' => $userId,
            ]);
        }

        Log::error('DGePay unknown or failed status', ['status_code' => $statusCode, 'unique_txn_id' => $uniqueTxnId]);

        return redirect()->route('payment.failed', [
            'mer_txnid' => $uniqueTxnId,
            'error_message' => $verified['message'] ?? 'Payment could not be confirmed (status: '.$statusCode.').',
            'user_id' => $userId,
            'status_code' => $statusCode,
        ]);
    }

    /**
     * AamarPay redirects or POSTs here after payment (success, fail, or cancel — verify server-side).
     */
    public function aamarpayCallback(Request $request)
    {
        return $this->handleGatewayReturn($request);
    }

    /**
     * Legacy route: same body as AamarPay return.
     */
    public function success(Request $request)
    {
        return $this->handleGatewayReturn($request);
    }

    public function cancel(Request $request)
    {
        return redirect()->route('upgrade.page')->with('info', 'Checkout was cancelled.');
    }

    protected function handleGatewayReturn(Request $request)
    {
        $mer_txnid = $request->input('mer_txnid') ?? $request->input('opt_d');

        if (empty($mer_txnid)) {
            Log::warning('AamarPay callback missing mer_txnid', ['input' => $request->except(['signature_key', 'password'])]);

            return redirect()->route('upgrade.page')->with('error', 'Payment session invalid. Please try again.');
        }

        $data = $this->aamarpay->verifyTransaction($mer_txnid);

        if (! $data) {
            return redirect()->route('upgrade.page')->with('error', 'Could not verify payment with gateway. Please contact support.');
        }

        $status_code = isset($data->status_code) ? (int) $data->status_code : null;
        $pg_txnid = $data->pg_txnid ?? null;
        $currency = strtoupper((string) ($data->currency_merchant ?? $data->currency ?? ''));
        $amount = $data->amount_currency ?? $data->amount ?? null;
        $order = MembershipOrder::where('merchant_txnid', $mer_txnid)->first();
        // Old in-flight transactions can use server-verified gateway metadata, never callback form fields.
        $subscription_plan = $order?->plan_slug ?? ($data->opt_a ?? null);
        $user_id = $order?->user_id ?? ($data->opt_b ?? null);

        if (($data->mer_txnid ?? '') !== $mer_txnid || ! is_numeric($amount) || ! $subscription_plan
            || ($order && ($currency !== $order->currency || abs((float) $amount - (float) $order->amount) > 0.009))) {
            return redirect()->route('upgrade.page')->with('error', 'Payment details could not be matched to your order. Please contact support.');
        }

        if (empty($user_id) || ! User::query()->whereKey($user_id)->exists()) {
            Log::error('AamarPay callback invalid user', ['user_id' => $user_id, 'mer_txnid' => $mer_txnid]);

            return redirect()->route('payment.error', ['error' => 'user_not_found', 'user_id' => $user_id]);
        }

        if ($status_code === 2) {
            try {
                $payment = $this->fulfillSuccessfulPayment(
                    (int) $user_id,
                    (string) $subscription_plan,
                    (string) $mer_txnid,
                    $pg_txnid ? (string) $pg_txnid : null,
                    $amount,
                    (string) $currency,
                    $status_code,
                    $request->ip(),
                    'aamarpay'
                );

                app(MembershipEvidence::class)->sendReceipt($payment);

                return redirect()->temporarySignedRoute(
                    'payment.complete',
                    now()->addHours(48),
                    ['payment' => $payment->id]
                );
            } catch (\Throwable $e) {
                Log::error('Fulfill payment failed: '.$e->getMessage());

                return redirect()->route('upgrade.page')->with('error', 'An error occurred while completing your payment. Please contact support.');
            }
        }

        if ($status_code === 7) {
            Log::warning('AamarPay payment failed', ['mer_txnid' => $mer_txnid]);

            return redirect()->route('payment.failed', [
                'mer_txnid' => $mer_txnid,
                'error_message' => $data->error_message ?? ($data->message ?? 'Payment declined'),
                'user_id' => $user_id,
            ]);
        }

        Log::error('AamarPay unknown status', ['status_code' => $status_code, 'mer_txnid' => $mer_txnid]);

        return redirect()->route('payment.failed', [
            'mer_txnid' => $mer_txnid,
            'error_message' => 'Payment could not be confirmed (code: '.($status_code ?? 'unknown').').',
            'user_id' => $user_id,
            'status_code' => $status_code,
        ]);
    }

    /**
     * Idempotent: same mer_txnid only creates one completed payment.
     */
    protected function fulfillSuccessfulPayment(
        int $user_id,
        string $subscription_plan,
        string $mer_txnid,
        ?string $pg_txnid,
        $amount,
        string $currency,
        int $status_code,
        ?string $customerIp,
        string $paymentMethod = 'dgepay'
    ): Payment {
        return DB::transaction(function () use ($user_id, $subscription_plan, $mer_txnid, $pg_txnid, $amount, $currency, $status_code, $customerIp, $paymentMethod) {
            $order = MembershipOrder::where('merchant_txnid', $mer_txnid)->lockForUpdate()->first();
            $user = User::query()->whereKey($user_id)->lockForUpdate()->firstOrFail();
            $existing = Payment::query()
                ->where('merchant_txnid', $mer_txnid)
                ->where('status', 'completed')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $user->update([
                'account_status' => $subscription_plan,
                'payment_status' => 'paid',
            ]);

            $payment = Payment::create([
                'user_id' => $user_id,
                'payment_method' => $paymentMethod,
                'transaction_id' => $pg_txnid,
                'merchant_txnid' => $mer_txnid,
                'pg_txnid' => $pg_txnid,
                'subscription_plan' => $subscription_plan,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'completed',
                'status_code' => $status_code,
                'payment_date' => now(),
                'expiry_date' => now()->addYear(),
                'customer_ip' => $customerIp,
            ]);

            Subscription::query()->updateOrCreate(
                ['user_id' => $user_id, 'plan' => $subscription_plan],
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

            Log::info("Payment fulfilled for user #{$user_id}: {$amount} {$currency} ({$subscription_plan})");

            return $payment;
        });
    }

    protected function activateSandboxWithoutGatewayFallback(User $user, Subscription $subscription, array $validated)
    {
        $user->update([
            'account_status' => $validated['plan'],
            'payment_status' => 'paid',
        ]);

        $subscription->update([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $tier = SubscriptionTier::query()->where('slug', $validated['plan'])->first();
        $planLabel = $tier?->name ?? $validated['plan'];

        try {
            Mail::to($user->email)->send(new CheckoutConfirmation($user, $planLabel, $validated['price']));
        } catch (\Exception $e) {
            Log::error('Error sending checkout email: '.$e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Your subscription has been activated in sandbox fallback mode.');
    }

    public function paymentComplete(Request $request)
    {
        $paymentId = $request->query('payment') ?? $request->query('payment_id');

        if (! $paymentId) {
            abort(404);
        }

        $payment = Payment::find($paymentId);

        if (! $payment || $payment->status !== 'completed') {
            abort(404);
        }

        $authorized = false;

        if ($request->hasValidSignature()) {
            $authorized = true;
        } elseif (Auth::check() && (int) Auth::id() === (int) $payment->user_id) {
            $authorized = true;
        } elseif (config('aamarpay.allow_legacy_payment_complete', false)
            && $payment->created_at
            && $payment->created_at->isAfter(now()->subDay())) {
            Log::warning('payment.complete: legacy unsigned access', ['payment_id' => $payment->id]);
            $authorized = true;
        }

        if (! $authorized) {
            abort(403, 'Invalid or expired payment link.');
        }

        $plan = $payment->subscription_plan;
        $membershipOrder = MembershipOrder::where('payment_id', $payment->id)->first();

        return view('payment.success', compact('payment', 'plan', 'membershipOrder'));
    }

    public function fail(Request $request)
    {
        return $this->paymentFailed($request);
    }

    public function paymentFailed(Request $request)
    {
        $mer_txnid = $request->query('mer_txnid');
        $error_message = $request->query('error_message', 'Payment was not completed.');
        $user_id = $request->query('user_id');
        $status_code = $request->query('status_code');

        Log::warning('Payment failed page', compact('mer_txnid', 'user_id', 'error_message'));

        if ($user_id) {
            try {
                Payment::create([
                    'user_id' => $user_id,
                    'payment_method' => 'aamarpay',
                    'merchant_txnid' => $mer_txnid,
                    'status' => 'failed',
                    'status_code' => (int) ($status_code ?? 7),
                    'payment_date' => now(),
                    'customer_ip' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to record failed payment: '.$e->getMessage());
            }
        }

        return view('payment.failed', compact('mer_txnid', 'error_message', 'user_id'));
    }

    public function paymentError(Request $request)
    {
        $error = $request->query('error');

        $message = 'An error occurred with your payment.';

        if ($error === 'user_not_found') {
            $message = 'User account not found. Please contact support.';
        } elseif ($error === 'processing_error') {
            $message = 'There was an error processing your payment. Please contact support.';
        }

        return redirect()->route('upgrade.page')
            ->with('error', $message);
    }

    public function paymentStatus(Request $request)
    {
        $status_code = $request->query('status_code');

        if ((int) $status_code === 2) {
            return redirect()->route('upgrade.page')
                ->with('success', 'Your payment was successful!');
        }

        return redirect()->route('upgrade.page')
            ->with('error', 'Your payment status could not be determined. Please contact support.');
    }
}
