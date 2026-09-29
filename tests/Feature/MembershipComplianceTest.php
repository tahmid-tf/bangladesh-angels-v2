<?php

namespace Tests\Feature;

use App\Models\MembershipOrder;
use App\Models\MembershipOrderRecord;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use App\Services\AamarpayGateway;
use App\Services\MembershipEvidence;
use App\Support\MembershipPolicies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedDatabaseTestCase;

class MembershipComplianceTest extends IsolatedDatabaseTestCase
{
    use RefreshDatabase;

    public function test_company_pages_share_navigation_and_about_has_section_links(): void
    {
        foreach (['about-us', 'services', 'contact'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('aria-label="Company information and policies"', false)
                ->assertSee('aria-current="page"', false)
                ->assertSee('Services &amp; membership', false)
                ->assertSee('>About</a>', false);
        }

        $this->get(route('about-us'))->assertOk()
            ->assertSee('aria-label="About page sections"', false)
            ->assertSee('id="our-story"', false)
            ->assertSee('id="business-details"', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_checkout_retains_acknowledgement_before_gateway_redirect(): void
    {
        config(['aamarpay.mode' => 'live', 'aamarpay.currency' => 'USD', 'aamarpay.live' => ['jsonpost_url' => 'https://gateway.example.test/jsonpost.php', 'store_id' => 'test', 'signature_key' => 'test', 'trxcheck_base' => 'https://gateway.example.test/verify']]);
        Http::fake(['gateway.example.test/*' => Http::response(['result' => 'true', 'payment_url' => 'https://gateway.example.test/pay'])]);
        $this->post(route('checkout.process'), $this->payload())->assertRedirect('https://gateway.example.test/pay');
        $order = MembershipOrder::firstOrFail();
        $this->assertSame('399.00', $order->amount);
        $this->assertSame('member@example.test', $order->customer_snapshot['email']);
        $this->assertSame(MembershipPolicies::ACKNOWLEDGEMENT, $order->acknowledgement);
        $this->assertSame((int) auth()->id(), (int) $order->user_id);
        $this->assertNull($order->delivered_at);
        Http::assertSent(fn ($request) => $request['tran_id'] === $order->merchant_txnid && $request['opt_b'] === (string) $order->user_id && $request['amount'] === '399' && $request['currency'] === 'USD');
    }

    public function test_receipt_delivery_failure_does_not_undo_payment_and_is_retained(): void
    {
        [$user, $order] = $this->order();
        $this->mockGateway($order);
        Mail::shouldReceive('html')->once()->andThrow(new \RuntimeException('Simulated mail outage'));
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid])->assertRedirect();
        $this->assertNotNull($order->fresh()->payment_id);
        $this->assertSame('failed', MembershipOrderRecord::firstOrFail()->status);
        $this->assertStringContainsString($order->reference, MembershipOrderRecord::first()->body);
    }

    public function test_gateway_currency_mismatch_is_rejected(): void
    {
        [, $order] = $this->order();
        $this->mockGateway($order, ['currency_merchant' => 'BDT']);
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid])->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
    }

    public function test_usd_plan_is_not_silently_charged_in_bdt(): void
    {
        config(['aamarpay.currency' => 'BDT']);
        $this->post(route('checkout.process'), $this->payload())->assertSessionHas('error');
        $this->assertSame(0, MembershipOrder::count());
        $this->assertSame(0, Subscription::count());
        Http::assertNothingSent();
    }

    public function test_public_pages_publish_supplied_business_details_and_policies(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['about-us', 'services', 'contact'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('217004960041')->assertSee('183134')->assertSee('+8801724937741')->assertDontSee('RJSC registration / incorporation number to be inserted');
        }
        foreach (MembershipPolicies::checkoutPages() as $slug => $page) {
            $this->get(route('policies.show', $slug))->assertOk()->assertSee($page['title']);
        }
        $this->get(route('policies.show', 'refund-return-policy'))->assertOk()->assertSee('7 calendar days')->assertSee('10 working days')->assertSee('5 to 10 working days');
        $this->get(route('policies.show', 'not-a-policy'))->assertNotFound();
    }

    public function test_checkout_requires_active_consent_and_the_current_policy_version_without_writes(): void
    {
        $this->get(route('checkout'))->assertOk()->assertSee('name="policy_consent" value="1" required', false)->assertSee('target="_blank"', false);
        $before = User::count();
        $payload = $this->payload();
        unset($payload['policy_consent']);
        $this->post(route('checkout.process'), $payload)->assertSessionHasErrors('policy_consent');
        $payload['policy_consent'] = '0';
        $this->post(route('checkout.process'), $payload)->assertSessionHasErrors('policy_consent');
        $payload['policy_consent'] = '1';
        $payload['policy_version'] = 'old-policy';
        $this->post(route('checkout.process'), $payload)->assertSessionHasErrors('policy_version');
        $this->assertSame($before, User::count());
        $this->assertSame(0, MembershipOrder::count());
        $this->assertSame(0, Subscription::count());
    }

    public function test_checkout_cannot_claim_an_existing_members_email(): void
    {
        $user = User::factory()->create(['gender' => 'other', 'email' => 'member@example.test']);
        $payload = $this->payload();
        $this->post(route('checkout.process'), $payload)->assertSessionHasErrors('email');
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_order_retains_plan_policy_and_customer_snapshots(): void
    {
        [$user, $order] = $this->order();
        SubscriptionTier::where('slug', 'core')->update(['price_yearly' => 1200, 'name' => 'Updated tier']);
        $user->update(['name' => 'Changed profile']);
        $this->assertSame('399.00', $order->fresh()->amount);
        $this->assertSame('Core', $order->plan_snapshot['name']);
        $this->assertNotSame($user->name, $order->customer_snapshot['name']);
        $this->assertSame(MembershipPolicies::version(), $order->policy_version);
        $this->assertNotNull($order->accepted_at);
        $this->actingAs($user)->get(route('membership-orders.show', $order))->assertOk()->assertSee('Membership order')->assertSee('not a payment receipt');
        $this->get(route('membership-orders.policies', $order))->assertOk()->assertSee('399.00')->assertDontSee('1,200.00');
    }

    public function test_verified_payment_is_bound_to_order_and_repeated_callbacks_do_not_duplicate_evidence(): void
    {
        Mail::fake();
        [$user, $order] = $this->order();
        $other = User::factory()->create(['gender' => 'other']);
        $this->mockGateway($order);
        $callback = ['mer_txnid' => $order->merchant_txnid, 'opt_b' => $other->id, 'opt_a' => 'institutional', 'amount' => '1'];
        $this->post(route('payment.aamarpay.callback'), $callback)->assertRedirect();
        $this->post(route('payment.aamarpay.callback'), $callback)->assertRedirect();
        $this->assertSame(1, Payment::count());
        $this->assertSame($user->id, Payment::first()->user_id);
        $this->assertSame('core', Payment::first()->subscription_plan);
        $this->assertSame('core', $user->fresh()->account_status);
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->assertNull($order->fresh()->delivery_confirmed_at);
        $this->assertSame(1, MembershipOrderRecord::where('kind', 'confirmation_email')->count());
        $this->assertSame('sent', MembershipOrderRecord::first()->status);
        $this->actingAs($user)->get(route('membership-orders.show', $order))->assertOk()->assertSee('Membership receipt')->assertSee('Confirm membership access');
    }

    public function test_mismatched_verified_payment_does_not_activate_membership(): void
    {
        [$user, $order] = $this->order();
        $this->mockGateway($order, ['amount_currency' => '1.00']);
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid])->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
        $this->assertNull($order->fresh()->delivered_at);
        $this->assertSame('free', $user->fresh()->account_status);
    }

    public function test_post_delivery_confirmation_requires_the_owner_and_is_not_overwritten(): void
    {
        Mail::fake();
        [$user, $order] = $this->order();
        $url = route('membership-orders.confirm-delivery', $order);
        $this->actingAs($user)->post($url, ['delivery_consent' => 1])->assertStatus(422);
        $this->mockGateway($order);
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid]);
        $admin = User::factory()->create(['gender' => 'other', 'role' => 'admin']);
        $this->actingAs($admin)->post($url, ['delivery_consent' => 1])->assertForbidden();
        $this->actingAs($user)->post($url, [])->assertSessionHasErrors('delivery_consent');
        $this->post($url, ['delivery_consent' => 1])->assertRedirect();
        $accepted = $order->fresh()->delivery_confirmed_at->toIso8601String();
        $this->travel(1)->hours();
        $this->post($url, ['delivery_consent' => 1])->assertRedirect();
        $this->assertSame($accepted, $order->fresh()->delivery_confirmed_at->toIso8601String());
    }

    public function test_records_and_private_attachments_are_admin_only(): void
    {
        Storage::fake('local');
        [$owner, $order] = $this->order();
        $other = User::factory()->create(['gender' => 'other']);
        $this->actingAs($other)->get(route('membership-orders.show', $order))->assertForbidden();
        $this->get(route('membership-orders.policies', $order))->assertForbidden();
        $this->get(route('admin.membership-orders.index'))->assertForbidden();
        $data = ['kind' => 'customer_communication', 'subject' => 'Access inquiry', 'body' => 'Customer emailed to ask about access.', 'attachment' => UploadedFile::fake()->create('correspondence.pdf', 20, 'application/pdf')];
        $this->actingAs($owner)->post(route('admin.membership-orders.records.store', $order), $data)->assertForbidden();
        $admin = User::factory()->create(['gender' => 'other', 'role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.membership-orders.records.store', $order), $data)->assertRedirect();
        $record = MembershipOrderRecord::firstOrFail();
        Storage::disk('local')->assertExists($record->attachment_path);
        $this->get(route('admin.membership-orders.records.download', $record))->assertOk();
        $this->get(route('membership-orders.show', $order))->assertOk()->assertSee('Access inquiry');
        $this->actingAs($owner)->get(route('admin.membership-orders.records.download', $record))->assertForbidden();
        $this->get(route('membership-orders.show', $order))->assertOk()->assertDontSee('Access inquiry');
    }

    public function test_missing_and_unverified_callbacks_cannot_activate_membership(): void
    {
        $this->mock(AamarpayGateway::class, function ($mock) {
            $mock->shouldReceive('verifyTransaction')->once()->with('unknown-reference')->andReturn(null);
        });
        $this->post(route('payment.aamarpay.callback'), [])->assertSessionHas('error');
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => 'unknown-reference'])->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, MembershipOrderRecord::count());
    }

    public function test_declined_payment_does_not_deliver_membership_or_send_receipt(): void
    {
        Mail::fake();
        [$user, $order] = $this->order();
        $this->mockGateway($order, ['status_code' => 7]);
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid])->assertRedirect();
        $this->assertSame('free', $user->fresh()->account_status);
        $this->assertNull($order->fresh()->payment_id);
        $this->assertNull($order->fresh()->delivered_at);
        $this->assertSame(0, MembershipOrderRecord::count());
        Mail::assertNothingSent();
    }

    public function test_wrong_transaction_reference_is_rejected(): void
    {
        [, $order] = $this->order();
        $this->mockGateway($order, ['mer_txnid' => 'different-order']);
        $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid])->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
    }

    public function test_invalid_prices_and_unavailable_plans_do_not_create_orders(): void
    {
        $payload = $this->payload();
        $payload['price'] = 1;
        $this->post(route('checkout.process'), $payload)->assertRedirect(route('plans'));
        $payload['price'] = 399;
        $payload['plan'] = 'not-an-active-plan';
        $this->post(route('checkout.process'), $payload)->assertRedirect(route('plans'));
        $this->assertSame(0, MembershipOrder::count());
        $this->assertSame(0, Subscription::count());
        Http::assertNothingSent();
    }

    public function test_guests_cannot_read_order_evidence(): void
    {
        [, $order] = $this->order();
        foreach (['membership-orders.index' => [], 'membership-orders.show' => [$order], 'membership-orders.policies' => [$order], 'admin.membership-orders.index' => []] as $route => $params) {
            $this->get(route($route, $params))->assertRedirect(route('login'));
        }
        $this->post(route('membership-orders.confirm-delivery', $order), ['delivery_consent' => 1])->assertRedirect(route('login'));
    }

    public function test_admin_upload_rejects_disallowed_files_and_oversized_files(): void
    {
        Storage::fake('local');
        [, $order] = $this->order();
        $admin = User::factory()->create(['gender' => 'other', 'role' => 'admin']);
        $this->actingAs($admin);
        foreach ([UploadedFile::fake()->create('script.php', 1, 'application/x-httpd-php'), UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            $this->post(route('admin.membership-orders.records.store', $order), ['kind' => 'delivery_document', 'subject' => 'Test', 'body' => 'Test record', 'attachment' => $file])->assertSessionHasErrors('attachment');
        }
        $this->assertSame(0, MembershipOrderRecord::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_payment_complete_requires_owner_or_signed_link_and_does_not_extend_membership(): void
    {
        Mail::fake();
        [$owner, $order] = $this->order();
        $this->mockGateway($order);
        $response = $this->post(route('payment.aamarpay.callback'), ['mer_txnid' => $order->merchant_txnid]);
        $signedUrl = $response->headers->get('Location');
        $payment = Payment::firstOrFail();
        $expiry = Subscription::firstOrFail()->end_date;
        $url = route('payment.complete', ['payment' => $payment->id]);
        config(['aamarpay.allow_legacy_payment_complete' => false]);
        $this->get($url)->assertForbidden();
        $other = User::factory()->create(['gender' => 'other']);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->travel(1)->hours();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->assertEquals($expiry, Subscription::firstOrFail()->end_date);
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, MembershipOrderRecord::count());
        auth()->logout();
        $this->get($signedUrl)->assertOk()->assertSee('Receipt Summary')->assertSee('SIGN IN');
        $this->get($signedUrl.'&tampered=1')->assertForbidden();
        $this->travel(49)->hours();
        $this->get($signedUrl)->assertForbidden();
    }

    private function payload(): array
    {
        return ['name' => 'Test Member', 'email' => 'member@example.test', 'address' => 'Test address', 'phone' => '1700000000', 'country_code' => '880', 'primary_country' => 'Bangladesh', 'company_name' => 'Test Company', 'designation' => 'Director', 'gender' => 'other', 'investment_expertise' => 'beginner', 'linkedin' => null, 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'plan' => 'core', 'price' => 399, 'policy_consent' => 1, 'policy_version' => MembershipPolicies::version()];
    }

    private function order(): array
    {
        $user = User::factory()->create(['gender' => 'other', 'role' => 'investor', 'account_status' => 'free', 'email' => 'owner@example.test', 'phone' => '+8801700000000', 'address' => 'Test address', 'primary_country' => 'Bangladesh']);
        $tier = SubscriptionTier::where('slug', 'core')->firstOrFail();
        $subscription = Subscription::create(['user_id' => $user->id, 'plan' => 'core', 'price' => 399, 'status' => 'pending']);
        $request = Request::create('/checkout', 'POST', ['policy_consent' => 1]);
        $order = app(MembershipEvidence::class)->begin($request, $user, $subscription, $tier, 'test-order-123', 'USD');

        return [$user, $order];
    }

    private function mockGateway(MembershipOrder $order, array $overrides = []): void
    {
        $this->mock(AamarpayGateway::class, function ($mock) use ($order, $overrides) {
            $mock->shouldReceive('verifyTransaction')->with($order->merchant_txnid)->andReturn((object) array_merge(['mer_txnid' => $order->merchant_txnid, 'status_code' => 2, 'pg_txnid' => 'gateway-reference', 'amount_currency' => $order->amount, 'currency_merchant' => 'USD'], $overrides));
        });
    }
}
