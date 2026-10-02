<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_member_does_not_see_membership_acquisition_prompts(): void
    {
        $user = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'advanced',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="membership-plans"', false)
            ->assertDontSee('href="#membership-plans"', false)
            ->assertDontSee(route('investor.signup'), false)
            ->assertDontSee('>Dashboard</a>', false)
            ->assertDontSee('>Fundraise</a>', false)
            ->assertSee('<a href="'.route('startups').'" class="ban2-button ban2-button--primary">Active Deals</a>', false)
            ->assertSee('<a href="'.route('portfolio').'" class="ban2-button ban2-button--secondary">Portfolio</a>', false);

        foreach (['investors', 'team', 'angel-academy'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertDontSee(route('investor.signup'), false);
        }

        $this->get(route('investor.signup'))->assertRedirect(route('dashboard'));
        $this->get(route('plans'))->assertRedirect(route('dashboard'));
        $this->get(route('upgrade.page'))->assertRedirect(route('dashboard'));
        $this->post(route('checkout'))->assertRedirect(route('dashboard'));
        $this->post(route('checkout.process'))->assertRedirect(route('dashboard'));
        $this->post(route('member.apply'))->assertRedirect(route('dashboard'));
    }

    public function test_free_member_can_still_see_signup_and_membership_plans(): void
    {
        $user = User::factory()->create([
            'role' => 'investor',
            'account_status' => 'free',
            'is_approved' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="membership-plans"', false)
            ->assertSee(route('investor.signup'), false)
            ->assertDontSee('>Active Deals</a>', false)
            ->assertDontSee('<a href="'.route('portfolio').'" class="ban2-button ban2-button--secondary">Portfolio</a>', false);

        $this->get(route('investor.signup'))->assertOk();
        $this->get(route('plans'))->assertOk();
    }
}
