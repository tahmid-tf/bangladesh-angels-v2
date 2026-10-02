<?php

namespace Tests\Feature;

use App\Livewire\HomepagePortfoliosTable;
use App\Models\Deal;
use App\Models\HomepagePortfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepagePortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_homepage_portfolios_management_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin, ['title' => 'Alpha Startup']);

        $response = $this->actingAs($admin)->get(route('admin.homepage-portfolios'));

        $response->assertOk()
            ->assertSee('Homepage — Selected Portfolios')
            ->assertSee('Alpha Startup')
            ->assertSee('0 / 8 Selected');
    }

    public function test_non_admin_cannot_access_homepage_portfolios_management_page(): void
    {
        $user = User::factory()->create(['role' => 'investor']);

        $this->actingAs($user)
            ->get(route('admin.homepage-portfolios'))
            ->assertForbidden();
    }

    public function test_admin_can_add_portfolio_to_homepage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin, ['title' => 'Alpha Startup']);

        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.store'), [
                'deal_id' => $deal->id,
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('homepage_portfolios', [
            'deal_id' => $deal->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_cannot_add_non_portfolio_deal_to_homepage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = Deal::create([
            'title' => 'Invest Only Startup',
            'slug' => 'invest-only-startup',
            'description' => 'Not a portfolio startup.',
            'sector' => 'Fintech',
            'type' => 'invest',
            'is_portfolio' => false,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.store'), [
                'deal_id' => $deal->id,
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('homepage_portfolios', [
            'deal_id' => $deal->id,
        ]);
    }

    public function test_admin_cannot_add_duplicate_portfolio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin);

        HomepagePortfolio::create([
            'deal_id' => $deal->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.store'), [
                'deal_id' => $deal->id,
            ])
            ->assertSessionHasErrors('deal_id');

        $this->assertCount(1, HomepagePortfolio::all());
    }

    public function test_admin_cannot_add_more_than_eight_portfolios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 8 selections
        for ($i = 1; $i <= 8; $i++) {
            $deal = $this->createPortfolioDeal($admin, ['title' => "Company {$i}"]);
            HomepagePortfolio::create([
                'deal_id' => $deal->id,
                'sort_order' => $i,
            ]);
        }

        // Create a 9th deal
        $deal9 = $this->createPortfolioDeal($admin, ['title' => 'Company 9']);

        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.store'), [
                'deal_id' => $deal9->id,
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('error');

        $this->assertCount(8, HomepagePortfolio::all());
    }

    public function test_admin_can_remove_portfolio_from_homepage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin);

        $selection = HomepagePortfolio::create([
            'deal_id' => $deal->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.homepage-portfolios.destroy', $selection))
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('homepage_portfolios', [
            'id' => $selection->id,
        ]);
    }

    public function test_admin_can_reorder_selected_portfolios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'First']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Second']);

        $item1 = HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        $item2 = HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);

        // Move down item 1
        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.move-down', $item1))
            ->assertRedirect(route('admin.homepage-portfolios'));

        $this->assertEquals(2, $item1->fresh()->sort_order);
        $this->assertEquals(1, $item2->fresh()->sort_order);

        // Move up item 1 (back to 1st)
        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.move-up', $item1))
            ->assertRedirect(route('admin.homepage-portfolios'));

        $this->assertEquals(1, $item1->fresh()->sort_order);
        $this->assertEquals(2, $item2->fresh()->sort_order);
    }

    public function test_admin_can_batch_sync_selected_portfolios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deals = [];
        for ($i = 1; $i <= 4; $i++) {
            $deals[] = $this->createPortfolioDeal($admin, ['title' => "Startup {$i}"]);
        }

        // Select 3 portfolios
        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.sync'), [
                'deal_ids' => [$deals[0]->id, $deals[1]->id, $deals[2]->id],
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('success');

        $this->assertCount(3, HomepagePortfolio::all());
        $this->assertEquals(1, HomepagePortfolio::where('deal_id', $deals[0]->id)->value('sort_order'));
        $this->assertEquals(2, HomepagePortfolio::where('deal_id', $deals[1]->id)->value('sort_order'));
        $this->assertEquals(3, HomepagePortfolio::where('deal_id', $deals[2]->id)->value('sort_order'));

        // Update to select 4 portfolios
        $this->actingAs($admin)
            ->post(route('admin.homepage-portfolios.sync'), [
                'deal_ids' => [$deals[3]->id, $deals[0]->id, $deals[1]->id, $deals[2]->id],
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('success');

        $this->assertCount(4, HomepagePortfolio::all());
        $this->assertEquals(1, HomepagePortfolio::where('deal_id', $deals[3]->id)->value('sort_order'));
    }

    public function test_homepage_shows_selected_portfolios_when_configured(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 6 portfolio deals
        $deals = [];
        for ($i = 1; $i <= 6; $i++) {
            $deals[$i] = $this->createPortfolioDeal($admin, ['title' => "Portfolio Company {$i}"]);
        }

        // Select 3 specific portfolios: 2, 4, 6
        HomepagePortfolio::create(['deal_id' => $deals[2]->id, 'sort_order' => 1]);
        HomepagePortfolio::create(['deal_id' => $deals[4]->id, 'sort_order' => 2]);
        HomepagePortfolio::create(['deal_id' => $deals[6]->id, 'sort_order' => 3]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Portfolio Company 2')
            ->assertSee('Portfolio Company 4')
            ->assertSee('Portfolio Company 6')
            ->assertDontSee('Portfolio Company 1')
            ->assertDontSee('Portfolio Company 3')
            ->assertDontSee('Portfolio Company 5');
    }

    public function test_homepage_shows_four_portfolios_when_four_are_selected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $deals = [];
        for ($i = 1; $i <= 6; $i++) {
            $deals[$i] = $this->createPortfolioDeal($admin, ['title' => "Deal {$i}"]);
        }

        // Select 4 portfolios: 1, 3, 4, 5
        HomepagePortfolio::create(['deal_id' => $deals[1]->id, 'sort_order' => 1]);
        HomepagePortfolio::create(['deal_id' => $deals[3]->id, 'sort_order' => 2]);
        HomepagePortfolio::create(['deal_id' => $deals[4]->id, 'sort_order' => 3]);
        HomepagePortfolio::create(['deal_id' => $deals[5]->id, 'sort_order' => 4]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Deal 1')
            ->assertSee('Deal 3')
            ->assertSee('Deal 4')
            ->assertSee('Deal 5')
            ->assertDontSee('Deal 2')
            ->assertDontSee('Deal 6');
    }

    public function test_homepage_falls_back_to_latest_portfolio_deals_when_none_selected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 10 portfolio deals
        for ($i = 1; $i <= 10; $i++) {
            $deal = $this->createPortfolioDeal($admin, [
                'title' => "Legacy Portfolio {$i}",
            ]);
            $deal->forceFill(['created_at' => now()->subMinutes(20 - $i)])->save();
        }

        $this->assertEquals(0, HomepagePortfolio::count());

        $homepageDeals = Deal::getHomepageDeals();

        // Must return 8 deals (limit 8)
        $this->assertCount(8, $homepageDeals);

        // The newest deal (10) should be included
        $this->assertTrue($homepageDeals->pluck('title')->contains('Legacy Portfolio 10'));
    }

    public function test_deal_edit_can_toggle_homepage_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin, ['title' => 'Toggle Deal']);

        $this->assertFalse($deal->isHomepageSelected());

        // Update with show_on_homepage = 1
        $payload = [
            'title' => $deal->title,
            'sector' => $deal->sector,
            'type' => 'portfolio',
            'description' => $deal->description,
            'status' => 'active',
            'show_on_homepage' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('update.deal', $deal), $payload)
            ->assertRedirect(route('admin.deals'));

        $this->assertTrue($deal->fresh()->isHomepageSelected());

        // Update with show_on_homepage = 0
        $payload['show_on_homepage'] = '0';

        $this->actingAs($admin)
            ->post(route('update.deal', $deal), $payload)
            ->assertRedirect(route('admin.deals'));

        $this->assertFalse($deal->fresh()->isHomepageSelected());
    }

    public function test_unmarking_deal_as_portfolio_removes_it_from_homepage_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin, ['type' => 'invest', 'is_portfolio' => true]);

        HomepagePortfolio::create(['deal_id' => $deal->id, 'sort_order' => 1]);
        $this->assertTrue($deal->isHomepageSelected());

        // Update deal to no longer be in portfolio
        $payload = [
            'title' => $deal->title,
            'sector' => $deal->sector,
            'type' => 'invest',
            'is_portfolio' => '0',
            'description' => $deal->description,
            'status' => 'active',
        ];

        $this->actingAs($admin)
            ->post(route('update.deal', $deal), $payload)
            ->assertRedirect(route('admin.deals'));

        $this->assertFalse($deal->fresh()->isHomepageSelected());
        $this->assertDatabaseMissing('homepage_portfolios', ['deal_id' => $deal->id]);
    }

    public function test_deleting_deal_removes_it_from_homepage_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin);

        HomepagePortfolio::create(['deal_id' => $deal->id, 'sort_order' => 1]);

        $this->actingAs($admin)
            ->delete(route('delete.deal', $deal))
            ->assertRedirect(route('admin.deals'));

        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
        $this->assertDatabaseMissing('homepage_portfolios', ['deal_id' => $deal->id]);
    }

    public function test_updating_deal_model_directly_to_non_portfolio_triggers_automatic_cleanup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal = $this->createPortfolioDeal($admin);

        HomepagePortfolio::create(['deal_id' => $deal->id, 'sort_order' => 1]);
        $this->assertTrue($deal->isHomepageSelected());

        // Update model directly (e.g. programmatically)
        $deal->update([
            'type' => 'commit',
            'is_portfolio' => false,
        ]);

        $this->assertDatabaseMissing('homepage_portfolios', ['deal_id' => $deal->id]);
        $this->assertFalse($deal->fresh()->isHomepageSelected());
    }

    public function test_admin_can_batch_delete_selected_portfolios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'Deal 1']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Deal 2']);
        $deal3 = $this->createPortfolioDeal($admin, ['title' => 'Deal 3']);
        $deal4 = $this->createPortfolioDeal($admin, ['title' => 'Deal 4']);

        $item1 = HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        $item2 = HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);
        $item3 = HomepagePortfolio::create(['deal_id' => $deal3->id, 'sort_order' => 3]);
        $item4 = HomepagePortfolio::create(['deal_id' => $deal4->id, 'sort_order' => 4]);

        $this->assertCount(4, HomepagePortfolio::all());

        // Batch delete item 1 and item 3
        $this->actingAs($admin)
            ->delete(route('admin.homepage-portfolios.batch-destroy'), [
                'ids' => [$item1->id, $item3->id],
            ])
            ->assertRedirect(route('admin.homepage-portfolios'))
            ->assertSessionHas('success');

        $this->assertCount(2, HomepagePortfolio::all());
        $this->assertDatabaseMissing('homepage_portfolios', ['id' => $item1->id]);
        $this->assertDatabaseMissing('homepage_portfolios', ['id' => $item3->id]);
        $this->assertDatabaseHas('homepage_portfolios', ['id' => $item2->id]);
        $this->assertDatabaseHas('homepage_portfolios', ['id' => $item4->id]);

        // Assert sort orders were re-normalized to 1 and 2
        $this->assertEquals(1, $item2->fresh()->sort_order);
        $this->assertEquals(2, $item4->fresh()->sort_order);
    }

    public function test_batch_delete_requires_at_least_one_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.homepage-portfolios.batch-destroy'), [
                'ids' => [],
            ])
            ->assertSessionHasErrors('ids');
    }

    public function test_livewire_component_renders_selected_portfolios(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'Livewire Deal 1']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Livewire Deal 2']);

        HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);

        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            ->assertSee('Livewire Deal 1')
            ->assertSee('Livewire Deal 2')
            ->assertSee('#1')
            ->assertSee('#2');
    }

    public function test_livewire_component_can_move_up_and_down_to_reorder(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'First Deal']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Second Deal']);

        $item1 = HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        $item2 = HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);

        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            // Move down first item
            ->call('moveDown', $item1->id)
            ->assertSet('feedbackMessage', 'Display order updated.');

        $this->assertEquals(2, $item1->fresh()->sort_order);
        $this->assertEquals(1, $item2->fresh()->sort_order);

        // Move it back up
        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            ->call('moveUp', $item1->id)
            ->assertSet('feedbackMessage', 'Display order updated.');

        $this->assertEquals(1, $item1->fresh()->sort_order);
        $this->assertEquals(2, $item2->fresh()->sort_order);
    }

    public function test_livewire_component_can_remove_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'Deal To Remove']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Deal To Keep']);

        $item1 = HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        $item2 = HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);

        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            ->call('remove', $item1->id)
            ->assertSee('removed from homepage selection.');

        $this->assertDatabaseMissing('homepage_portfolios', ['id' => $item1->id]);
        $this->assertDatabaseHas('homepage_portfolios', ['id' => $item2->id]);
        $this->assertEquals(1, $item2->fresh()->sort_order);
    }

    public function test_livewire_component_can_batch_remove_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'Batch 1']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Batch 2']);
        $deal3 = $this->createPortfolioDeal($admin, ['title' => 'Batch 3']);

        $item1 = HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        $item2 = HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);
        $item3 = HomepagePortfolio::create(['deal_id' => $deal3->id, 'sort_order' => 3]);

        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            ->set('selectedIds', [(string) $item1->id, (string) $item3->id])
            ->call('batchRemove')
            ->assertSee('2 portfolio companies removed from homepage selection.');

        $this->assertDatabaseMissing('homepage_portfolios', ['id' => $item1->id]);
        $this->assertDatabaseMissing('homepage_portfolios', ['id' => $item3->id]);
        $this->assertDatabaseHas('homepage_portfolios', ['id' => $item2->id]);
        $this->assertEquals(1, $item2->fresh()->sort_order);
    }

    public function test_livewire_component_can_clear_all(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $deal1 = $this->createPortfolioDeal($admin, ['title' => 'Item 1']);
        $deal2 = $this->createPortfolioDeal($admin, ['title' => 'Item 2']);

        HomepagePortfolio::create(['deal_id' => $deal1->id, 'sort_order' => 1]);
        HomepagePortfolio::create(['deal_id' => $deal2->id, 'sort_order' => 2]);

        Livewire::actingAs($admin)
            ->test(HomepagePortfoliosTable::class)
            ->call('clearAll')
            ->assertSee('All homepage selections cleared');

        $this->assertCount(0, HomepagePortfolio::all());
    }

    public function test_livewire_component_rejects_unauthorized_users(): void
    {
        $investor = User::factory()->create(['role' => 'investor']);
        $deal = $this->createPortfolioDeal(User::factory()->create(['role' => 'admin']));
        $item = HomepagePortfolio::create(['deal_id' => $deal->id, 'sort_order' => 1]);

        Livewire::actingAs($investor)
            ->test(HomepagePortfoliosTable::class)
            ->call('moveUp', $item->id)
            ->assertForbidden();
    }

    private function createPortfolioDeal(User $creator, array $attributes = []): Deal
    {
        return Deal::create(array_merge([
            'title' => 'Test Portfolio Startup ' . uniqid(),
            'slug' => 'test-portfolio-' . uniqid(),
            'description' => 'A backed portfolio company test description.',
            'sector' => 'Technology',
            'type' => 'portfolio',
            'is_portfolio' => false,
            'status' => 'active',
            'created_by' => $creator->id,
        ], $attributes));
    }
}
