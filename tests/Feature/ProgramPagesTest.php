<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProgramPagesTest extends TestCase
{
    public function test_homepage_exposes_the_requested_sections_and_program_routes(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('<h1 id="ban2-hero-heading">Join BAN</h1>', false)
            ->assertSee('<p class="ban2-action-prompt">I\'m looking To</p>', false)
            ->assertDontSee('<a href="#join" class="ban-site-nav__link">Join</a>', false)
            ->assertSee('<a href="#ban-events" class="ban-site-nav__link">Events</a>', false)
            ->assertSee('<a href="#membership-plans" class="ban-site-nav__link">Plans</a>', false)
            ->assertSee('<a href="'.route('startups').'" class="ban-site-nav__link">Featured Startups</a>', false)
            ->assertSee('<a href="'.route('portfolio').'" class="ban-site-nav__link">Portfolio</a>', false)
            ->assertSee('class="ban-site-nav__dropdown-button"', false)
            ->assertSee('<a href="'.route('team').'">Team</a>', false)
            ->assertDontSee('<a href="'.route('investor.signup').'" class="ban-site-nav__link">Investor Signup</a>', false)
            ->assertSee('<a href="'.route('investor.signup').'">Become an Investor</a>', false)
            ->assertSee('id="join"', false)
            ->assertDontSee('id="featured-startups"', false)
            ->assertDontSee('The companies in the room right now.')
            ->assertSee('id="portfolio"', false)
            ->assertSee('id="team"', false)
            ->assertSee('id="ban2-team-heading">Our Team</h2>', false)
            ->assertSee('id="ban-events"', false)
            ->assertSee('id="ban2-events-heading">BAN Events</h2>', false)
            ->assertSee('Book a call')
            ->assertSee('Book a')
            ->assertSee('demo')
            ->assertSee('class="ban2-program-card__copy"', false)
            ->assertDontSee('class="ban2-program-card__mark"', false)
            ->assertDontSee('class="ban2-program-card__monogram"', false)
            ->assertSee('id="membership-plans"', false)
            ->assertSee('id="ban2-membership-heading"', false)
            ->assertSee('Flexible plans for your investment needs')
            ->assertSee('action="'.route('checkout').'"', false)
            ->assertSee('id="our-partners"', false)
            ->assertSee('id="ban2-partners-heading">A stronger ecosystem is built together.</h2>', false)
            ->assertSee('id="faq"', false)
            ->assertSee('What sort of returns are you seeing or expecting?')
            ->assertSee('Are startups registered in the US, and do syndicates issue K-1 tax forms for US investors?')
            ->assertSee('<a href="'.route('faq').'">FAQ</a>', false)
            ->assertSee(route('bwin'), false)
            ->assertSee(route('angel-academy'), false)
            ->assertSee(route('resources'), false);

        $this->assertSame(2, substr_count($response->getContent(), 'href="#membership-plans"'));
    }

    public function test_plans_navigation_returns_to_the_homepage_section_from_other_pages(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('href="'.route('home').'#membership-plans" class="ban-site-nav__link">Plans</a>', false);
    }

    public function test_bwin_has_a_dedicated_public_page(): void
    {
        $this->get(route('bwin'))
            ->assertOk()
            ->assertSee('Bangladesh Women Investors Network')
            ->assertSee('Join the BWIN community');
    }

    public function test_angel_academy_uses_the_original_program_page(): void
    {
        $this->get(route('angel-academy'))
            ->assertOk()
            ->assertSee('Book a Meet')
            ->assertSee('CURRICULUM (15 SESSIONS)');
    }

    public function test_investor_signup_uses_the_original_application_page(): void
    {
        $this->get(route('investor.signup'))
            ->assertOk()
            ->assertSee('Become an Angel investor')
            ->assertSee('Investor Application')
            ->assertSee('Choose your tier')
            ->assertSee('action="'.route('member.apply').'"', false);
    }

    public function test_team_page_uses_the_editorial_leadership_layout(): void
    {
        $this->get(route('team'))
            ->assertOk()
            ->assertSee('Meet the people behind BAN.')
            ->assertDontSee('Back to homepage')
            ->assertSee('id="management"', false)
            ->assertSee('Team &amp; Management', false)
            ->assertSee('id="governing-board"', false)
            ->assertSee('Governing Board')
            ->assertSee('<a href="#about-us">About Us', false)
            ->assertSee('id="about-us"', false)
            ->assertSee('What is Bangladesh Angels Network?')
            ->assertSee('Bring your perspective to the table.');
    }

    public function test_startups_page_uses_the_updated_pitch_and_services_sections(): void
    {
        $this->get(route('startups'))
            ->assertOk()
            ->assertSee('class="ban-listing-hero ban-listing-hero--startups"', false)
            ->assertSee('Built for founders ready to move.')
            ->assertSee('href="#active-deals"', false)
            ->assertSee('class="ban-startup-pitch scroll-mt-32"', false)
            ->assertSee('action="'.route('startups.pitch').'"', false)
            ->assertSee('Make the opportunity clear')
            ->assertSee('class="ban-startup-services scroll-mt-32"', false)
            ->assertDontSee('ban-startup-service-card__topline')
            ->assertSee('View brochure')
            ->assertSee('Founder support');
    }

    public function test_portfolio_page_uses_the_editorial_listing_header(): void
    {
        $this->get(route('portfolio'))
            ->assertOk()
            ->assertSee('class="ban-listing-hero ban-listing-hero--portfolio"', false)
            ->assertSee('Backed by BAN. Built to endure.')
            ->assertSee('href="#portfolio-companies"', false)
            ->assertSee('id="portfolio-companies"', false)
            ->assertSee('class="ban2-portfolio__grid"', false)
            ->assertSee('ban2-portfolio-card')
            ->assertSee('Our portfolio');
    }

    public function test_faq_includes_the_contact_prompt(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Frequently Asked Questions about BAN')
            ->assertSee('20 essential answers')
            ->assertSee('What sort of returns are you seeing or expecting?')
            ->assertSee('Will BAN manage post-investment communications?')
            ->assertSee('Can’t find what you’re looking for?')
            ->assertSee('mailto:hello@bdangels.co', false);
    }
}
