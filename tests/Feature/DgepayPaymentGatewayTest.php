<?php

namespace Tests\Feature;

use App\Models\MembershipOrder;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\DgepayGateway;
use App\Services\MembershipEvidence;
use App\Support\MembershipPolicies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\IsolatedDatabaseTestCase;

class DgepayPaymentGatewayTest extends IsolatedDatabaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        config([
            'payment.default' => 'dgepay',
            'dgepay.mode' => 'sandbox',
            'dgepay.currency' => 'USD',
            'dgepay.sandbox' => [
                'base_url' => 'https://sandbox.dgepay.net/dipon/v3',
                'client_id' => 'test_client_id',
                'client_secret' => 'test_secret_16ch',
                'client_api_key' => 'b0a5941bfdf4f2fb724a6382de52f44',
            ],
        ]);
    }

    protected function payload(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'johndoe@example.test',
            'address' => 'Banani, Dhaka',
            'country_code' => '+880',
            'phone' => '1712345678',
            'primary_country' => 'Bangladesh',
            'company_name' => 'Acme Ventures',
            'designation' => 'Managing Partner',
            'gender' => 'male',
            'investment_expertise' => 'expert',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'plan' => 'core',
            'price' => '399.00',
            'policy_consent' => '1',
            'policy_version' => MembershipPolicies::version(),
            'policy_accepted_at' => now()->toIso8601String(),
        ];
    }

    public function test_signature_generation_is_deterministic_and_compliant(): void
    {
        $gateway = app(DgepayGateway::class);

        $data = [
            'amount' => 15,
            'customer_token' => null,
            'note' => 'Testing note of DGePay',
            'payee_information' => null,
            'payment_method' => null,
            'redirect_url' => 'https://example.com/callback',
            'unique_txn_id' => 'ORDER123456',
            'meta_data' => [
                'custom_field_1' => 'value1',
                'custom_field_2' => 'value2',
                'custom_field_3' => 'value3',
            ],
            'unique_user_reference' => null,
        ];

        $sig1 = $gateway->generateSignature($data, 'b0a5941bfdf4f2fb724a6382de52f44');
        $sig2 = $gateway->generateSignature($data, 'b0a5941bfdf4f2fb724a6382de52f44');

        $this->assertSame($sig1, $sig2);
        $this->assertNotEmpty($sig1);
        $this->assertNotFalse(base64_decode($sig1, true));
    }

    public function test_payload_encryption_and_decryption_roundtrip(): void
    {
        $gateway = app(DgepayGateway::class);
        $original = ['amount' => 399.0, 'note' => 'BAN Membership'];

        $encrypted = $gateway->encryptPayload($original, 'test_secret_16ch');
        $this->assertNotEmpty($encrypted);
        $this->assertNotSame(json_encode($original), $encrypted);

        $decrypted = $gateway->decryptPayload($encrypted, 'test_secret_16ch');
        $this->assertEquals($original, json_decode($decrypted, true));
    }

    public function test_callback_decrypt_restores_plus_from_spaces(): void
    {
        $gateway = app(DgepayGateway::class);
        $original = [
            'status_code' => 3,
            'unique_txn_id' => 'DGTEST12345',
            'message' => 'TRANSACTION SUCCESS',
            'txn_number' => '46629357',
        ];

        $encrypted = $gateway->encryptPayload($original, 'test_secret_16ch');
        // Simulate PHP converting '+' in query strings to ' '
        $withSpaces = str_replace('+', ' ', $encrypted);

        $decrypted = $gateway->decryptCallbackData($withSpaces, 'test_secret_16ch');
        $this->assertNotNull($decrypted);
        $this->assertSame('DGTEST12345', $decrypted['unique_txn_id']);
        $this->assertSame(3, $decrypted['status_code']);
    }

    public function test_checkout_initiates_dgepay_and_redirects_to_webview_url(): void
    {
        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'fake_jwt_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/initiate_payment' => Http::response([
                'data' => [
                    'webview_url' => 'https://sandbox.dgepay.net/checkout/session_abc123',
                    'unique_txn_id' => 'DGTEST_ORDER',
                ],
            ]),
        ]);

        $response = $this->post(route('checkout.process'), $this->payload());

        $response->assertRedirect('https://sandbox.dgepay.net/checkout/session_abc123');

        $order = MembershipOrder::firstOrFail();
        $this->assertSame('399.00', $order->amount);
        $this->assertSame('johndoe@example.test', $order->customer_snapshot['email']);
        $this->assertStringStartsWith('DG', $order->merchant_txnid);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/payment_gateway/authenticate');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/payment_gateway/initiate_payment')
                && $request->hasHeader('Authorization', 'Bearer fake_jwt_token_123')
                && $request->hasHeader('Signature');
        });
    }

    public function test_successful_dgepay_callback_verifies_server_side_and_fulfills_membership(): void
    {
        $tier = SubscriptionTier::where('slug', 'core')->firstOrFail();
        $user = User::factory()->create(['email' => 'buyer@example.test', 'gender' => 'other']);
        $sub = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399.00, 'status' => 'pending']);

        $tranId = 'DG20261008001';
        app(MembershipEvidence::class)->begin(
            new \Illuminate\Http\Request,
            $user,
            $sub,
            $tier,
            $tranId,
            'USD'
        );

        $gateway = app(DgepayGateway::class);
        $callbackData = [
            'status_code' => 3,
            'unique_txn_id' => $tranId,
            'message' => 'TRANSACTION SUCCESS',
            'txn_number' => 'DGE_PG_9999',
            'amount' => 399.00,
        ];
        $encryptedQuery = $gateway->encryptPayload($callbackData, 'test_secret_16ch');

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'fake_jwt_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/check_transaction_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'status_code' => 3,
                    'message' => 'TRANSACTION SUCCESS',
                    'txn_number' => 'DGE_PG_9999',
                    'payment_method' => 'bKash',
                    'amount' => 399.00,
                    'unique_txn_id' => $tranId,
                    'metadata' => [
                        'plan' => 'core',
                        'user_id' => (string) $user->id,
                    ],
                ],
            ]),
        ]);

        $response = $this->get(route('payment.dgepay.callback', ['data' => $encryptedQuery]));

        $payment = Payment::where('merchant_txnid', $tranId)->firstOrFail();
        $this->assertSame('completed', $payment->status);
        $this->assertSame('dgepay', $payment->payment_method);
        $this->assertSame('DGE_PG_9999', $payment->transaction_id);
        $this->assertSame(3, $payment->status_code);

        $user->refresh();
        $this->assertSame('paid', $user->payment_status);
        $this->assertSame('core', $user->account_status);

        $sub->refresh();
        $this->assertSame('active', $sub->status);

        $order = MembershipOrder::where('merchant_txnid', $tranId)->firstOrFail();
        $this->assertSame($payment->id, $order->payment_id);
        $this->assertNotNull($order->delivered_at);

        $response->assertRedirect();
        $this->assertStringContainsString('/payment/complete', $response->headers->get('Location'));
        $this->assertStringContainsString('payment='.$payment->id, $response->headers->get('Location'));
    }

    public function test_cancelled_dgepay_callback_redirects_gracefully(): void
    {
        $gateway = app(DgepayGateway::class);
        $callbackData = [
            'status_code' => 8,
            'unique_txn_id' => 'DG_CANCEL_001',
            'message' => 'TRANSACTION CANCELLED',
        ];
        $encryptedQuery = $gateway->encryptPayload($callbackData, 'test_secret_16ch');

        $response = $this->get(route('payment.dgepay.callback', ['data' => $encryptedQuery]));

        $response->assertRedirect(route('upgrade.page'));
        $response->assertSessionHas('info', 'Checkout was cancelled.');
        $this->assertSame(0, Payment::count());
    }

    public function test_mismatched_amount_in_verification_is_rejected(): void
    {
        $tier = SubscriptionTier::where('slug', 'core')->firstOrFail();
        $user = User::factory()->create(['email' => 'buyer2@example.test', 'gender' => 'other']);
        $sub = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399.00, 'status' => 'pending']);

        $tranId = 'DG20261008002';
        app(MembershipEvidence::class)->begin(
            new \Illuminate\Http\Request,
            $user,
            $sub,
            $tier,
            $tranId,
            'USD'
        );

        $gateway = app(DgepayGateway::class);
        $callbackData = [
            'status_code' => 3,
            'unique_txn_id' => $tranId,
            'message' => 'TRANSACTION SUCCESS',
            'txn_number' => 'DGE_PG_8888',
            'amount' => 10.00, // Tampered amount!
        ];
        $encryptedQuery = $gateway->encryptPayload($callbackData, 'test_secret_16ch');

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'fake_jwt_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/check_transaction_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'status_code' => 3,
                    'message' => 'TRANSACTION SUCCESS',
                    'txn_number' => 'DGE_PG_8888',
                    'amount' => 10.00, // Mismatched amount
                    'unique_txn_id' => $tranId,
                    'metadata' => ['plan' => 'core', 'user_id' => (string) $user->id],
                ],
            ]),
        ]);

        $response = $this->get(route('payment.dgepay.callback', ['data' => $encryptedQuery]));

        $response->assertRedirect(route('upgrade.page'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
    }

    public function test_api_checkout_initiates_dgepay_and_returns_payment_url(): void
    {
        config(['dgepay.test_real_initiation' => true]);

        SubscriptionTier::firstOrCreate(
            ['slug' => 'core'],
            ['name' => 'Core', 'price_yearly' => 399.00, 'is_active' => true]
        );

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'jwt_api_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/initiate_payment' => Http::response([
                'status' => 'success',
                'data' => [
                    'payment_url' => 'https://checkout-sandbox.dgepay.net/payment/pay?data=xyz',
                ],
            ]),
        ]);

        $payload = $this->payload();
        $response = $this->postJson(route('api.checkout.process'), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.gateway', 'dgepay')
            ->assertJsonPath('data.payment_url', 'https://checkout-sandbox.dgepay.net/payment/pay?data=xyz');

        $this->assertDatabaseHas('membership_orders', [
            'plan_slug' => 'core',
            'amount' => 399.00,
        ]);
    }

    public function test_api_status_check_verifies_with_dgepay_and_fulfills(): void
    {
        $tier = SubscriptionTier::firstOrCreate(
            ['slug' => 'core'],
            ['name' => 'Core', 'price_yearly' => 399.00, 'is_active' => true]
        );
        $user = User::factory()->create(['email' => 'api_buyer@example.test', 'gender' => 'other']);
        $sub = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399.00, 'status' => 'pending']);

        $tranId = 'DG20261008API1';
        $order = app(MembershipEvidence::class)->begin(
            new \Illuminate\Http\Request,
            $user,
            $sub,
            $tier,
            $tranId,
            'USD'
        );

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'jwt_api_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/check_transaction_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'status_code' => 3,
                    'message' => 'TRANSACTION SUCCESS',
                    'txn_number' => 'DGE_PG_API_111',
                    'amount' => 399.00,
                    'unique_txn_id' => $tranId,
                    'metadata' => [
                        'custom_field_1' => 'core',
                        'custom_field_2' => (string) $user->id,
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson(route('api.checkout.status', ['reference' => $order->reference]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', $order->reference)
            ->assertJsonPath('data.payment_status', 'completed')
            ->assertJsonPath('data.payment_method', 'dgepay');

        $this->assertDatabaseHas('payments', [
            'merchant_txnid' => $tranId,
            'payment_method' => 'dgepay',
            'status' => 'completed',
        ]);

        $user->refresh();
        $this->assertSame('paid', $user->payment_status);
        $this->assertSame('core', $user->account_status);
    }

    public function test_api_callback_endpoint_fulfills_and_returns_json(): void
    {
        $tier = SubscriptionTier::firstOrCreate(
            ['slug' => 'core'],
            ['name' => 'Core', 'price_yearly' => 399.00, 'is_active' => true]
        );
        $user = User::factory()->create(['email' => 'api_callback_user@example.test', 'gender' => 'male']);
        $sub = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399.00, 'status' => 'pending']);

        $tranId = 'DG20261008API2';
        $order = app(MembershipEvidence::class)->begin(
            new \Illuminate\Http\Request,
            $user,
            $sub,
            $tier,
            $tranId,
            'USD'
        );

        $gateway = app(DgepayGateway::class);
        $callbackData = [
            'status_code' => 3,
            'unique_txn_id' => $tranId,
            'message' => 'TRANSACTION SUCCESS',
            'txn_number' => 'DGE_PG_API_222',
            'amount' => 399.00,
        ];
        $encryptedQuery = $gateway->encryptPayload($callbackData, 'test_secret_16ch');

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'jwt_api_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/check_transaction_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'status_code' => 3,
                    'message' => 'TRANSACTION SUCCESS',
                    'txn_number' => 'DGE_PG_API_222',
                    'amount' => 399.00,
                    'unique_txn_id' => $tranId,
                    'metadata' => [
                        'custom_field_1' => 'core',
                        'custom_field_2' => (string) $user->id,
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson(route('api.checkout.callback'), ['data' => $encryptedQuery]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.transaction_id', $tranId);

        $this->assertDatabaseHas('payments', [
            'merchant_txnid' => $tranId,
            'payment_method' => 'dgepay',
            'status' => 'completed',
        ]);
    }

    public function test_checkout_converts_usd_to_bdt_when_configured(): void
    {
        config([
            'dgepay.convert_usd_to_bdt' => true,
            'dgepay.usd_exchange_rate' => 122.0,
        ]);

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'fake_jwt_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/initiate_payment' => Http::response([
                'data' => [
                    'webview_url' => 'https://sandbox.dgepay.net/checkout/session_converted_bdt',
                    'unique_txn_id' => 'DGTEST_ORDER',
                ],
            ]),
        ]);

        $response = $this->post(route('checkout.process'), $this->payload());

        $response->assertRedirect('https://sandbox.dgepay.net/checkout/session_converted_bdt');

        // Check that DGePay received 48678 BDT (399 * 122)
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/payment_gateway/initiate_payment')) {
                return false;
            }

            $gateway = app(DgepayGateway::class);
            $decrypted = $gateway->decryptPayload($request->body(), 'test_secret_16ch');
            $data = json_decode($decrypted, true);

            return isset($data['amount']) && abs((float) $data['amount'] - 48678.0) < 0.01;
        });

        // The order retains USD 399
        $order = MembershipOrder::firstOrFail();
        $this->assertSame('399.00', $order->amount);
        $this->assertSame('USD', $order->currency);
    }

    public function test_callback_accepts_and_fulfills_converted_bdt_amount(): void
    {
        config([
            'dgepay.convert_usd_to_bdt' => true,
            'dgepay.usd_exchange_rate' => 122.0,
        ]);

        $tier = SubscriptionTier::where('slug', 'core')->firstOrFail();
        $user = User::factory()->create(['email' => 'converted_buyer@example.test', 'gender' => 'other']);
        $sub = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399.00, 'status' => 'pending']);

        $tranId = 'DGTEST_CONV_999';
        $order = app(MembershipEvidence::class)->begin(
            new \Illuminate\Http\Request,
            $user,
            $sub,
            $tier,
            $tranId,
            'USD'
        );

        $gateway = app(DgepayGateway::class);
        $callbackData = [
            'status_code' => 3,
            'unique_txn_id' => $tranId,
            'message' => 'TRANSACTION SUCCESS',
            'txn_number' => 'DGE_PG_CONV_111',
            'amount' => 48678.00, // DGePay returns BDT amount
        ];
        $encryptedQuery = $gateway->encryptPayload($callbackData, 'test_secret_16ch');

        Http::fake([
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/authenticate' => Http::response([
                'data' => ['access_token' => 'fake_jwt_token_123'],
            ]),
            'https://sandbox.dgepay.net/dipon/v3/payment_gateway/check_transaction_status' => Http::response([
                'status' => 'success',
                'data' => [
                    'status_code' => 3,
                    'message' => 'TRANSACTION SUCCESS',
                    'txn_number' => 'DGE_PG_CONV_111',
                    'payment_method' => 'bKash',
                    'amount' => 48678.00, // Verified in BDT
                    'unique_txn_id' => $tranId,
                    'metadata' => [
                        'plan' => 'core',
                        'user_id' => (string) $user->id,
                    ],
                ],
            ]),
        ]);

        $response = $this->get(route('payment.dgepay.callback', ['data' => $encryptedQuery]));

        $payment = Payment::where('merchant_txnid', $tranId)->firstOrFail();
        $this->assertSame('completed', $payment->status);
        $this->assertSame('dgepay', $payment->payment_method);
        $this->assertEquals(399.00, (float) $payment->amount);
        $this->assertSame('USD', $payment->currency);

        $user->refresh();
        $this->assertSame('paid', $user->payment_status);
        $this->assertSame('core', $user->account_status);
    }

    public function test_footer_renders_dgepay_payment_compliance_banner(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('dgepay-banner.webp');
        $response->assertSee('Verified by DGePay');
    }
}


