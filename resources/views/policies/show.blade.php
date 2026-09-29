@extends('layouts.guest')
@section('page_title', $page['title'].' | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-page">
    @if($policy === 'about-us')
        <section class="ban-team-hero ban-about-hero" aria-labelledby="ban-about-title">
            <div class="ban-page-shell">
                <div class="ban-team-hero__grid">
                    <div class="ban-team-hero__copy">
                        <p class="ban-page-kicker">Bangladesh Angels Network</p>
                        <h1 id="ban-about-title">About Us</h1>
                    </div>
                    <div class="ban-team-hero__summary">
                        <p>Connecting early-stage startups with investors, and supporting the people building Bangladesh’s next generation of companies.</p>
                    </div>
                </div>
                <nav class="ban-team-hero__nav" aria-label="About page sections">
                    <a href="#our-story">Our story <span aria-hidden="true">↓</span></a>
                    <a href="{{ route('team') }}">Meet our team <span aria-hidden="true">→</span></a>
                    <a href="#business-details">Business details <span aria-hidden="true">↓</span></a>
                </nav>
            </div>
        </section>
    @endif
    <div class="ban-policy-shell">
    @if($policy !== 'about-us')
    <header class="ban-policy-header">
        <p class="ban-page-kicker">Bangladesh Angels Network Limited</p>
        <h1>{{ $page['title'] }}</h1>
        @if($policy !== 'about-us')<p>Website and annual membership policies.</p>@endif
    </header>
    @endif
    <div class="ban-policy-layout">
        @include('policies.navigation')
        <article class="ban-policy-prose" id="our-story">
            @include('policies.blocks', ['blocks' => $page['blocks']])
            @if($policy === 'about-us')
                <section id="business-details" aria-label="Business details">
                @include('policies.business-details')
                </section>
            @endif
        </article>
    </div>
    </div>
</main>
@endsection
