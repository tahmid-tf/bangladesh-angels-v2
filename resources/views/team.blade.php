@extends('layouts.guest')
@section('page_title', 'Our Team | Bangladesh Angels Network')

@push('head_meta')
    <x-seo-meta title="Our Team | Bangladesh Angels Network"
        description="Meet the team and governing board behind Bangladesh Angels Network, the country’s first and largest angel-investing platform."
        :canonical="route('team')" :image="asset('DI4A6345.jpg')" />
@endpush

@section('page_content')
    <main class="ban-subpage ban-team-page">
        <section class="ban-team-hero" aria-labelledby="ban-team-title">
            <div class="ban-page-shell">
                <div class="ban-team-hero__grid">
                    <div class="ban-team-hero__copy">
                        <p class="ban-page-kicker">The people of BAN</p>
                        <h1 id="ban-team-title">Meet the people behind BAN.</h1>
                    </div>

                    <div class="ban-team-hero__summary">
                        <p>Operators, investors, and ecosystem leaders working together to help exceptional founders build
                            enduring companies.</p>
                    </div>
                </div>

                <nav class="ban-team-hero__nav" aria-label="Team page sections">
                    <a href="#management">Team &amp; Management <span aria-hidden="true">↓</span></a>
                    <a href="#governing-board">Governing Board <span aria-hidden="true">↓</span></a>
                    <a href="#about-us">About Us <span aria-hidden="true">↓</span></a>
                </nav>
            </div>
        </section>

        <section id="management" class="ban-team-roster ban-team-roster--management" aria-labelledby="management-heading">
            <div class="ban-page-shell">
                <header class="ban-team-roster__heading">
                    <div>
                        <p class="ban-page-kicker">Day-to-day leadership</p>
                        <h2 id="management-heading">Team &amp; Management</h2>
                    </div>
                    <p>The people shaping BAN’s programs, partnerships, founder support, and network operations.</p>
                </header>

                @if ($management->isEmpty())
                    <div class="ban-team-empty" role="status">Team profiles will appear here soon.</div>
                @else
                    <div class="ban-team-grid">
                        @foreach ($management as $member)
                            <x-team-member-card :member="$member" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="governing-board" class="ban-team-roster ban-team-roster--board"
            aria-labelledby="governing-board-heading">
            <div class="ban-page-shell">
                <header class="ban-team-roster__heading">
                    <div>
                        <p class="ban-page-kicker">Governance &amp; direction</p>
                        <h2 id="governing-board-heading">Governing Board</h2>
                    </div>
                    <p>Experienced leaders who help steward the network’s governance, standards, and long-term direction.
                    </p>
                </header>

                @if ($governingBoard->isEmpty())
                    <div class="ban-team-empty" role="status">Governing board profiles will appear here soon.</div>
                @else
                    <div class="ban-team-grid">
                        @foreach ($governingBoard as $member)
                            <x-team-member-card :member="$member" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="about-us" class="ban-team-about" aria-labelledby="ban-team-about-heading">
            <div class="ban-page-shell ban-team-about__grid">
                <figure class="ban-team-about__image">
                    <img src="{{ $teamAboutSection->imageUrl() }}"
                        alt="{{ $teamAboutSection->image_alt }}" width="1920"
                        height="1280" loading="lazy">
                    <figcaption><span></span> {{ $teamAboutSection->image_badge }}</figcaption>
                </figure>

                <div class="ban-team-about__copy">
                    <p class="ban-page-kicker">{{ $teamAboutSection->kicker }}</p>
                    <h2 id="ban-team-about-heading">{{ $teamAboutSection->heading }}</h2>
                    @foreach ($teamAboutSection->paragraphs() as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="ban-team-cta" aria-labelledby="ban-team-cta-heading">
            <div class="ban-page-shell ban-team-cta__inner">
                <div>
                    <p class="ban-page-kicker">Build with the network</p>
                    <h2 id="ban-team-cta-heading">Bring your perspective to the table.</h2>
                </div>
                <div>
                    <p>Whether you invest, operate, or build, there is a place to contribute to Bangladesh’s next generation
                        of enduring companies.</p>
                    <div class="ban-page-actions">
                        <a href="{{ auth()->check() && auth()->user()->hasPaidMembership() ? route('dashboard') : route('investor.signup') }}" class="ban-page-button ban-page-button--primary">{{ auth()->check() && auth()->user()->hasPaidMembership() ? 'Dashboard' : 'Become an investor' }} <span aria-hidden="true">→</span></a>
                        <a href="{{ route('home') }}#pitch-form" class="ban-team-cta__secondary">Pitch your startup <span
                                aria-hidden="true">↗</span></a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
