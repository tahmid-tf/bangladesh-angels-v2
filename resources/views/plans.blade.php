@extends('layouts.guest')
@section('page_title', 'Subscription Plans | Bangladesh Angels Network Limited')

@push('head_meta')
    <x-seo-meta
        title="Subscription Plans | Bangladesh Angels Network Limited"
        description="Compare Bangladesh Angels Network membership tiers — unlock deals, events, and investor tools with a plan that fits you."
        :canonical="route('plans')"
        :image="asset('icon.webp')"
    />
@endpush

@section('page_content')
<main class="ban-plans-page">
    <section class="ban-plans-hero" aria-labelledby="ban-plans-title">
        <div class="ban-plans-shell ban-plans-hero__inner">
            <p class="ban-plans-eyebrow">Annual investor memberships</p>
            <h1 id="ban-plans-title">Choose your <em>membership.</em></h1>
            <p class="ban-plans-hero__copy">
                @if ($tiers->isNotEmpty())
                    Plans start at <strong>${{ number_format((float) $tiers->min('price_yearly'), 0) }}/year</strong>. Compare access levels and choose the one that fits how you invest.
                @else
                    Compare access levels and choose the membership that fits how you invest.
                @endif
            </p>
            <p class="mt-4"><a class="ban-plans-hero__service-link" href="{{ route('services') }}">Service availability, delivery and purchase information</a></p>
        </div>
    </section>

    <section class="ban-plans-pricing" aria-labelledby="ban-plans-pricing-heading">
        <div class="ban-plans-shell">
            @include('partials.subscription-plans', [
                'tiers' => $tiers,
                'showFreeTierOption' => $showFreeTierOption ?? false,
                'tierSectionKicker' => 'Choose your membership',
                'tierSectionTitle' => 'A plan for every kind of investor',
                'tierSectionSubtitle' => 'All memberships are billed annually in USD.',
                'tierSectionHeadingId' => 'ban-plans-pricing-heading',
                'planPageMode' => true,
            ])

            <aside class="ban-plans-support" aria-label="Membership support">
                <div>
                    <span class="ban-plans-support__mark" aria-hidden="true">BAN</span>
                    <div>
                        <h2>Not sure which membership fits?</h2>
                        <p>Tell us how you invest and our team will point you in the right direction.</p>
                    </div>
                </div>
                <a href="mailto:hello@bdangels.co">Talk to our membership team <span aria-hidden="true">↗</span></a>
            </aside>
        </div>
    </section>

    <section class="ban-plans-faq" aria-labelledby="ban-plans-faq-title">
        <div class="ban-plans-shell ban-plans-faq__layout">
            <header>
                <p class="ban-plans-eyebrow">Good to know</p>
                <h2 id="ban-plans-faq-title">A few questions,<br>answered.</h2>
                <p>Need anything else? Write to <a href="mailto:hello@bdangels.co">hello@bdangels.co</a>.</p>
            </header>
            <div class="ban-plans-faq__items">
                <details open>
                    <summary>Who are these memberships for? <span aria-hidden="true"></span></summary>
                    <p>They are designed for individual angels and investment teams who want curated deal access and a closer connection to Bangladesh's startup ecosystem.</p>
                </details>
                <details>
                    <summary>What happens after I choose a plan? <span aria-hidden="true"></span></summary>
                    <p>You will continue to checkout with your chosen membership. After successful payment, the plan is connected to your Bangladesh Angels account.</p>
                </details>
                <details>
                    <summary>Can I begin with a free account? <span aria-hidden="true"></span></summary>
                    <p>Yes. Approved investors can keep a free account and upgrade whenever they are ready for deeper deal and data-room access.</p>
                </details>
            </div>
        </div>
    </section>
</main>
@endsection
