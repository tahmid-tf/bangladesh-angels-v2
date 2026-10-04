<?php

namespace App\Http\Controllers\Api;

use App\Models\MembershipOrder;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\AamarpayGateway;
use App\Services\MembershipEvidence;
use App\Support\MembershipPolicies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutApiController extends BaseApiController
{
    public function __construct(
        protected AamarpayGateway $aamarpay
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

        $tran_id = 'BAN'.time().rand(1000, 9999);
        $currency = strtoupper((string) config('aamarpay.currency', 'USD'));

        $order = app(MembershipEvidence::class)->begin(
            $request,
            $user,
            $subscription,
            $tier,
            $tran_id,
            $currency
        );

        $authToken = $user->createToken('mobile-app')->plainTextToken;

        // Local environment or test mode skip payment
        if (app()->environment('testing') || (app()->environment('local') && filter_var(env('SKIP_PAYMENT_GATEWAY', false), FILTER_VALIDATE_BOOLEAN))) {
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
                'order_reference' => $order->reference,
                'user' => $this->formatUser($user->fresh()),
                'token' => $authToken,
            ], 'Subscription activated in test mode.');
        }

        try {
            $cfg = $this->aamarpay->activeConfig();
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

            $response = $this->aamarpay->postJson($cfg['jsonpost_url'], $postData);
            $responseObj = is_string($response) ? json_decode($response) : $response;

            $paymentUrl = $this->aamarpay->extractPaymentUrl($responseObj);

            if ($paymentUrl) {
                return $this->success([
                    'status' => 'pending_payment',
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
        $order = MembershipOrder::with('payment')->where('reference', $reference)->first();

        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        return $this->success([
            'reference' => $order->reference,
            'amount' => (float) $order->amount,
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'delivery_confirmed_at' => $order->delivery_confirmed_at?->toIso8601String(),
            'payment_status' => $order->payment?->status ?? 'pending',
            'plan' => $order->plan_snapshot,
        ]);
    }
}
