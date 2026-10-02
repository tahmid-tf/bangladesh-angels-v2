<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_sign_in_and_register_nav_items(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<a href="'.route('login').'" class="ban-site-nav__login">SIGN IN</a>', false)
            ->assertSee('<a href="'.route('investor.signup').'" class="ban-site-nav__register">REGISTER</a>', false)
            ->assertSee('<a href="'.route('investor.signup').'" class="ban-mobile-primary">Register</a>', false);
    }

    public function test_register_nav_item_leads_to_investor_signup_page(): void
    {
        $response = $this->get(route('investor.signup'));

        $response->assertOk()
            ->assertSee('Become an Angel investor');
    }

    public function test_authenticated_paid_member_does_not_see_sign_in_or_register_nav_items(): void
    {
        $user = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'advanced',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk()
            ->assertDontSee('class="ban-site-nav__login"', false)
            ->assertDontSee('class="ban-site-nav__register"', false);
    }
}
