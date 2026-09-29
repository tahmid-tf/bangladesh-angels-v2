<?php

namespace Tests\Feature;

use App\Models\BanWealthOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BanWealthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ban_wealth_is_hidden_from_public_navigation_but_pages_still_work(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('>BAN Wealth<', false)
            ->assertDontSee(route('ban-wealth.index'));

        $this->get(route('ban-wealth.index'))
            ->assertOk()
            ->assertSee('BAN Wealth')
            ->assertSee('class="ban-site-nav"', false)
            ->assertSee('Featured Startups')
            ->assertSee('A clearer way to invest for what comes next.')
            ->assertSee(route('ban-wealth.invest'));

        $this->get(route('ban-wealth.invest'))
            ->assertOk()
            ->assertSee('When might you need this money back?')
            ->assertSee('id="fund-horizon"', false)
            ->assertSee('id="fund-preference"', false)
            ->assertDontSee('What we would put this money in')
            ->assertDontSee('Every BAN Wealth fund is built to a mandate')
            ->assertDontSee('<strong>Our take.</strong>', false)
            ->assertDontSee('BAN Wealth is paid a selling-agent commission');
    }

    public function test_investor_can_submit_a_bank_transfer_with_private_proof(): void
    {
        Storage::fake('local');
        $investor = User::factory()->create([
            'role' => 'investor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($investor)->post(route('ban-wealth.orders.store'), $this->validPayload());

        $order = BanWealthOrder::query()->firstOrFail();
        $response->assertRedirect(route('ban-wealth.invest', ['order' => $order->reference]));
        $this->assertSame('pending', $order->status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame($investor->id, $order->investor_id);
        Storage::disk('local')->assertExists($order->payment_proof_path);
    }

    public function test_guest_and_non_investor_cannot_submit_orders(): void
    {
        $this->post(route('ban-wealth.orders.store'), $this->validPayload())->assertRedirect(route('login'));

        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($admin)->post(route('ban-wealth.orders.store'), $this->validPayload())->assertForbidden();
    }

    public function test_admin_can_review_an_order_and_investor_sees_the_result(): void
    {
        Storage::fake('local');
        $investor = User::factory()->create(['role' => 'investor', 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($investor)->post(route('ban-wealth.orders.store'), $this->validPayload());
        $order = BanWealthOrder::query()->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.ban-wealth-orders.update', $order), [
            'status' => 'accepted',
            'review_note' => 'Transfer verified.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ban_wealth_orders', [
            'id' => $order->id,
            'status' => 'accepted',
            'review_note' => 'Transfer verified.',
            'reviewed_by' => $admin->id,
        ]);

        $this->actingAs($investor)->get(route('ban-wealth.invest'))
            ->assertOk()
            ->assertSee('accepted')
            ->assertSee('Transfer verified.');
    }

    public function test_payment_proof_is_only_available_to_owner_or_admin(): void
    {
        Storage::fake('local');
        $investor = User::factory()->create(['role' => 'investor', 'email_verified_at' => now()]);
        $other = User::factory()->create(['role' => 'investor', 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($investor)->post(route('ban-wealth.orders.store'), $this->validPayload());
        $order = BanWealthOrder::query()->firstOrFail();

        $this->actingAs($other)->get(route('ban-wealth.orders.proof', $order))->assertForbidden();
        $this->actingAs($investor)->get(route('ban-wealth.orders.proof', $order))->assertOk();
        $this->actingAs($admin)->get(route('ban-wealth.orders.proof', $order))->assertOk();
    }

    private function validPayload(): array
    {
        return [
            'fund_slug' => 'ban-growth-fund-1',
            'horizon' => 'long',
            'shariah_preference' => 'conventional',
            'amount' => 100000,
            'monthly' => 0,
            'full_name' => 'Ayesha Rahman',
            'nid_number' => '1990447188213',
            'date_of_birth' => '1990-03-14',
            'mobile' => '+8801711204118',
            'email' => 'ayesha@example.com',
            'present_address' => 'Banani, Dhaka',
            'bank_name' => 'BRAC Bank',
            'bank_branch' => 'Gulshan',
            'bank_account_number' => '150120394471',
            'routing_number' => '060261726',
            'tin' => '413288902217',
            'bo_account' => '',
            'source_of_funds' => 'Salary and business income',
            'investment_experience' => 'I have held listed shares',
            'loss_response' => 'Hold and keep adding',
            'politically_exposed' => 0,
            'prospectus_consent' => 1,
            'submission_consent' => 1,
            'payment_proof' => UploadedFile::fake()->create('transfer.pdf', 120, 'application/pdf'),
        ];
    }
}
