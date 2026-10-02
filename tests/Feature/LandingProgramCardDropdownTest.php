<?php

namespace Tests\Feature;

use App\Models\LandingProgramCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the optional dropdown button feature on landing program cards.
 *
 * Uses RefreshDatabase so every test runs against the test database (bdangels_test)
 * with a clean slate — the production database is never touched.
 */
class LandingProgramCardDropdownTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function makeCard(array $overrides = []): LandingProgramCard
    {
        return LandingProgramCard::create(array_merge([
            'eyebrow'       => 'Test category',
            'title'         => 'Test Program',
            'description'   => 'A description.',
            'primary_label' => 'Open Program',
            'primary_link'  => '/test-program',
            'theme'         => 'white',
            'sort_order'    => 99,
        ], $overrides));
    }

    // ── Unit: Model ───────────────────────────────────────────────────────────

    public function test_hasDropdown_returns_false_when_dropdown_items_is_null(): void
    {
        $card = $this->makeCard(['dropdown_items' => null]);

        $this->assertFalse($card->hasDropdown());
    }

    public function test_hasDropdown_returns_false_when_label_is_empty_string(): void
    {
        $card = $this->makeCard([
            'dropdown_items' => ['label' => '', 'items' => [['title' => 'A', 'link' => '/a']]],
        ]);

        $this->assertFalse($card->hasDropdown());
    }

    public function test_hasDropdown_returns_true_when_label_is_set(): void
    {
        $card = $this->makeCard([
            'dropdown_items' => ['label' => 'View Services', 'items' => []],
        ]);

        $this->assertTrue($card->hasDropdown());
    }

    public function test_hasDropdown_returns_true_when_label_and_items_are_present(): void
    {
        $card = $this->makeCard([
            'dropdown_items' => [
                'label' => 'View Services',
                'items' => [['title' => 'Service A', 'link' => '/service-a']],
            ],
        ]);

        $this->assertTrue($card->hasDropdown());
    }

    public function test_dropdown_items_is_cast_as_array(): void
    {
        $data = ['label' => 'Menu', 'items' => [['title' => 'Foo', 'link' => '/foo']]];
        $card = $this->makeCard(['dropdown_items' => $data]);

        // Re-fetch from DB to confirm the cast round-trips correctly.
        $fresh = LandingProgramCard::find($card->id);
        $this->assertIsArray($fresh->dropdown_items);
        $this->assertSame('Menu', $fresh->dropdown_items['label']);
        $this->assertCount(1, $fresh->dropdown_items['items']);
        $this->assertSame('Foo', $fresh->dropdown_items['items'][0]['title']);
    }

    // ── Feature: Admin — store with dropdown ──────────────────────────────────

    public function test_admin_can_store_a_card_with_a_dropdown(): void
    {
        $this->actingAs($this->makeAdmin());

        $response = $this->post(route('admin.landing-program-cards.store'), [
            'eyebrow'               => 'Founder Services',
            'title'                 => 'Services',
            'description'           => 'Supporting founders.',
            'primary_label'         => 'View Services',
            'primary_link'          => '/services',
            'theme'                 => 'white',
            'sort_order'            => 10,
            'dropdown_label'        => 'Explore Services',
            'dropdown_item_title'   => ['Angel Syndicate', 'BWIN', '', '', '', ''],
            'dropdown_item_link'    => ['/angel-syndicate', '/bwin', '', '', '', ''],
        ]);

        $response->assertRedirect(route('admin.landing-program-cards'));

        $card = LandingProgramCard::where('title', 'Services')->first();
        $this->assertNotNull($card);
        $this->assertNotNull($card->dropdown_items);
        $this->assertSame('Explore Services', $card->dropdown_items['label']);
        // Only the two non-blank rows should be stored.
        $this->assertCount(2, $card->dropdown_items['items']);
        $this->assertSame('Angel Syndicate', $card->dropdown_items['items'][0]['title']);
        $this->assertSame('/bwin', $card->dropdown_items['items'][1]['link']);
    }

    public function test_admin_can_store_a_card_without_a_dropdown(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->post(route('admin.landing-program-cards.store'), [
            'eyebrow'       => 'AI-powered',
            'title'         => 'DeckVue',
            'description'   => 'Pitch decks.',
            'primary_label' => 'Open DeckVue',
            'primary_link'  => '/deckvue',
            'theme'         => 'mint',
            'sort_order'    => 20,
            // No dropdown fields submitted at all.
        ]);

        $card = LandingProgramCard::where('title', 'DeckVue')->first();
        $this->assertNotNull($card);
        $this->assertNull($card->dropdown_items);
    }

    public function test_storing_a_dropdown_label_with_no_items_preserves_label(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->post(route('admin.landing-program-cards.store'), [
            'eyebrow'               => 'Test Eyebrow',
            'title'                 => 'Empty Dropdown Card',
            'description'           => 'No items.',
            'primary_label'         => 'Go',
            'primary_link'          => '/go',
            'theme'                 => 'white',
            'dropdown_label'        => 'Click Me',
            'dropdown_item_title'   => ['', '', '', '', '', ''],
            'dropdown_item_link'    => ['', '', '', '', '', ''],
        ]);

        $card = LandingProgramCard::where('title', 'Empty Dropdown Card')->first();
        $this->assertNotNull($card->dropdown_items);
        $this->assertSame('Click Me', $card->dropdown_items['label']);
        $this->assertEmpty($card->dropdown_items['items']);
    }

    public function test_storing_a_dropdown_item_with_title_only_defaults_link_to_hash(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->post(route('admin.landing-program-cards.store'), [
            'eyebrow'               => 'Test Eyebrow',
            'title'                 => 'Partial Item Card',
            'description'           => 'Desc.',
            'primary_label'         => 'Go',
            'primary_link'          => '/go',
            'theme'                 => 'white',
            'dropdown_label'        => 'Menu',
            'dropdown_item_title'   => ['Syndicate', '', '', '', '', ''],
            'dropdown_item_link'    => ['', '', '', '', '', ''],
        ]);

        $card = LandingProgramCard::where('title', 'Partial Item Card')->first();
        $this->assertNotNull($card->dropdown_items);
        $this->assertCount(1, $card->dropdown_items['items']);
        $this->assertSame('Syndicate', $card->dropdown_items['items'][0]['title']);
        $this->assertSame('#', $card->dropdown_items['items'][0]['link']);
    }

    // ── Feature: Admin — update with dropdown ─────────────────────────────────

    public function test_admin_can_update_a_card_to_add_a_dropdown(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard(['dropdown_items' => null]);

        $this->put(route('admin.landing-program-cards.update', $card), [
            'eyebrow'               => $card->eyebrow,
            'title'                 => $card->title,
            'description'           => $card->description,
            'primary_label'         => $card->primary_label,
            'primary_link'          => $card->primary_link,
            'theme'                 => $card->theme,
            'dropdown_label'        => 'View Services',
            'dropdown_item_title'   => ['Legal', '', '', '', '', ''],
            'dropdown_item_link'    => ['/legal', '', '', '', '', ''],
        ])->assertRedirect(route('admin.landing-program-cards'));

        $card->refresh();
        $this->assertNotNull($card->dropdown_items);
        $this->assertSame('View Services', $card->dropdown_items['label']);
        $this->assertCount(1, $card->dropdown_items['items']);
    }

    public function test_admin_can_update_a_card_to_remove_a_dropdown(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard([
            'dropdown_items' => [
                'label' => 'Old Menu',
                'items' => [['title' => 'Item', 'link' => '/item']],
            ],
        ]);

        $this->put(route('admin.landing-program-cards.update', $card), [
            'eyebrow'        => $card->eyebrow,
            'title'          => $card->title,
            'description'    => $card->description,
            'primary_label'  => $card->primary_label,
            'primary_link'   => $card->primary_link,
            'theme'          => $card->theme,
            'dropdown_label' => '',   // cleared
        ])->assertRedirect(route('admin.landing-program-cards'));

        $card->refresh();
        $this->assertNull($card->dropdown_items);
    }

    // ── Feature: Admin — validation ───────────────────────────────────────────

    public function test_dropdown_label_max_length_is_validated(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard();

        $this->put(route('admin.landing-program-cards.update', $card), [
            'eyebrow'        => $card->eyebrow,
            'title'          => $card->title,
            'description'    => $card->description,
            'primary_label'  => $card->primary_label,
            'primary_link'   => $card->primary_link,
            'theme'          => $card->theme,
            'dropdown_label' => str_repeat('x', 121),   // exceeds max:120
        ])->assertSessionHasErrors('dropdown_label');
    }

    public function test_dropdown_item_title_max_length_is_validated(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard();

        $this->put(route('admin.landing-program-cards.update', $card), [
            'eyebrow'              => $card->eyebrow,
            'title'                => $card->title,
            'description'          => $card->description,
            'primary_label'        => $card->primary_label,
            'primary_link'         => $card->primary_link,
            'theme'                => $card->theme,
            'dropdown_label'       => 'Menu',
            'dropdown_item_title'  => [str_repeat('a', 121)],  // exceeds max:120
            'dropdown_item_link'   => ['/ok'],
        ])->assertSessionHasErrors('dropdown_item_title.0');
    }

    public function test_more_than_six_dropdown_items_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard();

        $this->put(route('admin.landing-program-cards.update', $card), [
            'eyebrow'              => $card->eyebrow,
            'title'                => $card->title,
            'description'          => $card->description,
            'primary_label'        => $card->primary_label,
            'primary_link'         => $card->primary_link,
            'theme'                => $card->theme,
            'dropdown_label'       => 'Menu',
            'dropdown_item_title'  => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],  // 7 items
            'dropdown_item_link'   => ['/a', '/b', '/c', '/d', '/e', '/f', '/g'],
        ])->assertSessionHasErrors('dropdown_item_title');
    }

    // ── Feature: Admin — access control ──────────────────────────────────────

    public function test_guest_cannot_access_the_edit_form(): void
    {
        $card = $this->makeCard();

        $this->get(route('admin.landing-program-cards.edit', $card))
            ->assertRedirect();
    }

    public function test_non_admin_cannot_access_the_edit_form(): void
    {
        $user = User::factory()->create(['role' => 'investor', 'email_verified_at' => now()]);
        $card = $this->makeCard();

        $this->actingAs($user)
            ->get(route('admin.landing-program-cards.edit', $card))
            ->assertForbidden();
    }

    // ── Feature: Admin — edit form renders saved dropdown ────────────────────

    public function test_edit_form_shows_existing_dropdown_label(): void
    {
        $this->actingAs($this->makeAdmin());
        $card = $this->makeCard([
            'dropdown_items' => [
                'label' => 'View Services',
                'items' => [
                    ['title' => 'Angel Syndicate', 'link' => '/angel-syndicate'],
                    ['title' => 'BWIN', 'link' => '/bwin'],
                ],
            ],
        ]);

        $this->get(route('admin.landing-program-cards.edit', $card))
            ->assertOk()
            ->assertSee('View Services')
            ->assertSee('Angel Syndicate')
            ->assertSee('/angel-syndicate')
            ->assertSee('BWIN')
            ->assertSee('/bwin');
    }

    // ── Feature: Homepage rendering ───────────────────────────────────────────

    public function test_homepage_shows_dropdown_button_when_card_has_one(): void
    {
        $this->makeCard([
            'sort_order'    => 1,
            'dropdown_items' => [
                'label' => 'View Services',
                'items' => [
                    ['title' => 'Angel Syndicate', 'link' => '/angel-syndicate'],
                    ['title' => 'BWIN', 'link' => '/bwin'],
                ],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ban2-program-card__dropdown', false)
            ->assertSee('ban2-program-card__dropdown-trigger', false)
            ->assertSee('View Services')
            ->assertSee('Angel Syndicate')
            ->assertSee('BWIN')
            ->assertSee('href="/angel-syndicate"', false)
            ->assertSee('href="/bwin"', false);
    }

    public function test_homepage_omits_dropdown_when_card_has_no_dropdown(): void
    {
        $this->makeCard(['dropdown_items' => null, 'sort_order' => 1]);

        $this->get('/')
            ->assertOk()
            // The wrapper div is only rendered by Blade when hasDropdown() is true.
            // (The trigger class string also appears in the <script> block, so we check the wrapper.)
            ->assertDontSee('class="ban2-program-card__dropdown ban2-program-card__action"', false);
    }

    public function test_homepage_omits_dropdown_when_label_is_cleared(): void
    {
        $this->makeCard([
            'sort_order'    => 1,
            'dropdown_items' => ['label' => '', 'items' => [['title' => 'A', 'link' => '/a']]],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('class="ban2-program-card__dropdown ban2-program-card__action"', false);
    }

    public function test_homepage_adds_external_link_indicator_for_http_items(): void
    {
        $this->makeCard([
            'sort_order'    => 1,
            'dropdown_items' => [
                'label' => 'Links',
                'items' => [
                    ['title' => 'External', 'link' => 'https://example.com'],
                    ['title' => 'Internal', 'link' => '/internal'],
                ],
            ],
        ]);

        $response = $this->get('/');
        $content  = $response->getContent();

        // External link should have target="_blank"
        $this->assertStringContainsString('target="_blank"', $content);
        // The ↗ arrow should appear for the external link
        $this->assertStringContainsString('↗', $content);
    }
}
