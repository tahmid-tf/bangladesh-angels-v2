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

    public function test_admin_can_open_livewire_edit_modal_and_update_member_reactively(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '+8801700000000',
            'company_name' => 'Old Company',
            'designation' => 'Old Role',
            'account_status' => 'free',
            'status' => 'active',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        // Test Livewire component
        $component = Livewire::test(MembersTable::class)
            ->call('openEditModal', $member->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingUserId', $member->id)
            ->assertSet('editForm.full_name', 'Original Name')
            ->assertSet('editForm.email', 'original@example.com')
            // Update form properties
            ->set('editForm.full_name', 'Updated Super Investor')
            ->set('editForm.organization', 'Acme Ventures')
            ->set('editForm.designation', 'Managing Partner')
            ->set('editForm.account_status', 'advanced')
            ->set('editForm.status', 'inactive')
            ->set('editForm.phone', '+8801999999999')
            ->call('saveMemberEdit')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false)
            ->assertSee('Updated Super Investor')
            ->assertSee('Acme Ventures')
            ->assertSee('Managing Partner');

        $member->refresh();
        $this->assertEquals('Updated Super Investor', $member->name);
        $this->assertEquals('Acme Ventures', $member->company_name);
        $this->assertEquals('Managing Partner', $member->designation);
        $this->assertEquals('advanced', $member->account_status);
        $this->assertEquals('inactive', $member->status);
        $this->assertEquals('+8801999999999', $member->phone);
    }

    public function test_member_edit_route_redirects_to_livewire_table_with_edit_param(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'name' => 'Direct Edit Member',
            'is_approved' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('member.edit', $member->id))
            ->assertRedirect(route('admin.members', ['edit' => $member->id]));

        // Visiting with query param automatically opens edit modal
        Livewire::withQueryParams(['edit' => (string) $member->id])
            ->test(MembersTable::class)
            ->assertSet('showEditModal', true)
            ->assertSet('editingUserId', $member->id)
            ->assertSet('editForm.full_name', 'Direct Edit Member');
    }

    public function test_admin_can_verify_email_directly_from_edit_modal(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $unverifiedMember = User::factory()->create([
            'name' => 'Unverified Person',
            'is_approved' => true,
            'email_verified_at' => null,
            'email_verified_by' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(MembersTable::class)
            ->call('openEditModal', $unverifiedMember->id)
            ->call('verifyEmailForEditModal');

        $unverifiedMember->refresh();
        $this->assertNotNull($unverifiedMember->email_verified_at);
        $this->assertEquals($admin->id, $unverifiedMember->email_verified_by);
    }

    public function test_switching_between_filter_tabs_keeps_edit_modal_functional(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $activeMember = User::factory()->create([
            'name' => 'Active Test Member',
            'status' => 'active',
            'is_approved' => true,
        ]);

        $inactiveMember = User::factory()->create([
            'name' => 'Inactive Test Member',
            'status' => 'inactive',
            'is_approved' => true,
        ]);

        $this->actingAs($admin);

        // Start on active tab -> transition to inactive tab -> edit modal must open for inactive member
        Livewire::withQueryParams(['filter' => 'active'])
            ->test(MembersTable::class)
            ->assertSet('filter', 'active')
            ->call('setFilter', 'inactive')
            ->assertSet('filter', 'inactive')
            ->assertSet('showEditModal', false)
            ->call('openEditModal', $inactiveMember->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingUserId', $inactiveMember->id)
            ->assertSet('editForm.full_name', 'Inactive Test Member')
            ->call('closeEditModal')
            ->assertDispatched('member-edit-closed')
            ->assertSet('showEditModal', false)
            ->call('setFilter', 'active')
            ->assertSet('filter', 'active')
            ->call('openEditModal', $activeMember->id)
            ->assertSet('showEditModal', true)
            ->assertSet('editingUserId', $activeMember->id)
            ->assertSet('editForm.full_name', 'Active Test Member');
    }
}
