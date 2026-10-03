<?php

namespace Tests\Feature;

use App\Livewire\MembersTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembersTableStatusAndModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_and_mode_dropdowns_are_independent(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'status' => 'active',
            'account_status' => 'core',
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'role' => 'investor',
            'is_approved' => true,
            'status' => 'active',
            'account_status' => 'core',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        // 1. Changing Tier (Status) to 'free' must NOT change Mode (status remains 'active')
        Livewire::test(MembersTable::class)
            ->call('updateAccountStatus', $member->id, 'free');

        $member->refresh();
        $this->assertEquals('free', $member->account_status);
        $this->assertEquals('active', $member->status);

        // 2. Changing Mode to 'inactive' must NOT change Tier (account_status remains 'free')
        Livewire::test(MembersTable::class)
            ->call('setActiveMode', $member->id, 'inactive');

        $member->refresh();
        $this->assertEquals('free', $member->account_status);
        $this->assertEquals('inactive', $member->status);

        // 3. Changing Tier to 'advanced' must NOT change Mode (status remains 'inactive')
        Livewire::test(MembersTable::class)
            ->call('updateAccountStatus', $member->id, 'advanced');

        $member->refresh();
        $this->assertEquals('advanced', $member->account_status);
        $this->assertEquals('inactive', $member->status);

        // 4. Changing Mode back to 'active' must NOT change Tier (account_status remains 'advanced')
        Livewire::test(MembersTable::class)
            ->call('setActiveMode', $member->id, 'active');

        $member->refresh();
        $this->assertEquals('advanced', $member->account_status);
        $this->assertEquals('active', $member->status);
    }

    public function test_active_and_inactive_tabs_filter_by_mode_and_dropdowns_work(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'status' => 'active',
            'account_status' => 'core',
            'email_verified_at' => now(),
        ]);

        $activeMember = User::factory()->create([
            'name' => 'Active Member One',
            'role' => 'investor',
            'is_approved' => true,
            'status' => 'active',
            'account_status' => 'free', // Free tier, but Active mode!
            'email_verified_at' => now(),
        ]);

        $inactiveMember = User::factory()->create([
            'name' => 'Inactive Member One',
            'role' => 'investor',
            'is_approved' => true,
            'status' => 'inactive',
            'account_status' => 'core', // Core tier, but Inactive mode!
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        // On active tab: Active member is visible, Inactive member is not
        Livewire::withQueryParams(['filter' => 'active'])
            ->test(MembersTable::class)
            ->assertSee('Active Member One')
            ->assertDontSee('Inactive Member One')
            // Changing active member tier to 'advanced' on active tab keeps member visible
            ->call('updateAccountStatus', $activeMember->id, 'advanced')
            ->assertSee('Active Member One');

        $activeMember->refresh();
        $this->assertEquals('advanced', $activeMember->account_status);
        $this->assertEquals('active', $activeMember->status);

        // On inactive tab: Inactive member is visible, Active member is not
        Livewire::withQueryParams(['filter' => 'inactive'])
            ->test(MembersTable::class)
            ->assertSee('Inactive Member One')
            ->assertDontSee('Active Member One')
            // Changing inactive member tier to 'free' on inactive tab keeps member visible
            ->call('updateAccountStatus', $inactiveMember->id, 'free')
            ->assertSee('Inactive Member One');

        $inactiveMember->refresh();
        $this->assertEquals('free', $inactiveMember->account_status);
        $this->assertEquals('inactive', $inactiveMember->status);
    }

    public function test_legacy_member_tabs_redirect_to_livewire_table(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.active.members'))
            ->assertRedirect(route('admin.members', ['filter' => 'active']));

        $this->get(route('admin.inactive.members'))
            ->assertRedirect(route('admin.members', ['filter' => 'inactive']));

        $this->get(route('admin.pending.members'))
            ->assertRedirect(route('admin.members', ['filter' => 'pending']));
    }
}
