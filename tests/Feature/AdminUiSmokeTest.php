<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_pages_render_with_the_shared_shell(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $routes = [
            'admin.dashboard',
            'admin.members',
            'admin.events',
            'admin.blogs.index',
            'admin.resource-hub',
            'admin.startup-services',
            'admin.what-we-do-cards',
            'admin.team-members',
            'admin.deals',
            'admin.founder-pitches',
            'admin.angel-academy-applications',
            'admin.subscriptions',
            'admin.investments',
            'admin.investor-investments.index',
            'admin.mail',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('class="admin-shell', false)
                ->assertSee('class="admin-page', false);
        }

        $this->get(route('admin.investments'))
            ->assertSee('+ Add New Deal')
            ->assertSee(route('deal.add'), false);
    }
}
