@extends('layouts.guest')

@section('page_title', 'BAN Wealth | Invest with clarity')

@push('head_meta')
    <x-seo-meta title="BAN Wealth | Invest with clarity"
        description="A guided way to choose BAN Wealth funds around your timeline, preferences and goals."
        :canonical="route('ban-wealth.index')" :image="asset('og.png')" />
@endpush

@section('page_content')
<main class="ban-subpage ban-wealth-home">
    <section class="ban-wealth-hero" aria-labelledby="ban-wealth-heading">
        <div class="ban-page-shell ban-wealth-hero__grid">
            <div class="ban-wealth-hero__copy">
                <a href="{{ route('home') }}#programs" class="ban-program-back"><span aria-hidden="true">←</span> Our programs</a>
                <p class="ban-page-kicker">A Bangladesh Angels platform</p>
                <h1 id="ban-wealth-heading">A clearer way to invest for what comes next.</h1>
                <p class="ban-wealth-hero__lede">Start with your timeframe and preferences. BAN Wealth narrows the shelf, explains every choice, and keeps your orders together in one investor workspace.</p>
                <div class="ban-page-actions">
                    <a href="{{ route('ban-wealth.invest') }}" class="ban2-button ban2-button--primary">Start investing <span aria-hidden="true">→</span></a>
                    <a href="#how-it-works" class="ban2-button ban2-button--secondary">See how it works</a>
                </div>
                <div class="ban-wealth-hero__signals" aria-label="BAN Wealth principles">
                    <div><strong>Goal-led</strong><span>Begin with when you need the money</span></div>
                    <div><strong>Transparent</strong><span>See the manager, fee and risk clearly</span></div>
                    <div><strong>Trackable</strong><span>Follow each transfer from one place</span></div>
                </div>
            </div>

            <figure class="ban-wealth-hero__visual">
                <div class="ban-wealth-hero__image">
                    <img src="{{ asset('ban-wealth-hero.png') }}" alt="Sculptural ascending forms representing steady investment growth" width="1536" height="1024">
                </div>
                <figcaption>
                    <span>Built around your goals</span>
                    <strong>Choose with context.<br>Track with confidence.</strong>
                    <small>One guided investor workspace</small>
                </figcaption>
            </figure>
        </div>
    </section>

    <section class="ban-wealth-intro" aria-labelledby="ban-wealth-intro-heading">
        <div class="ban-page-shell ban-wealth-intro__grid">
            <header><p class="ban-page-kicker">Invest with context</p><h2 id="ban-wealth-intro-heading">The fund comes after the question.</h2></header>
            <div><p>Good investing starts with what the money is for and when you may need it back—not a wall of products. BAN Wealth turns those answers into a focused shelf you can understand.</p><p>Each fund shows its manager, mandate, risk band, pricing, fee and BAN Wealth’s own view before you choose an amount.</p></div>
        </div>
    </section>

    <section id="how-it-works" class="ban-wealth-path" aria-labelledby="ban-wealth-path-heading">
        <div class="ban-page-shell">
            <header class="ban-wealth-section-heading"><p class="ban-page-kicker">How it works</p><h2 id="ban-wealth-path-heading">From intention to a tracked order.</h2><p>A focused process that keeps the decision, account opening, bank transfer and review connected.</p></header>
            <div class="ban-wealth-path__grid">
                <article><span>01</span><div><h3>Tell us the goal</h3><p>Choose your timeframe and whether you prefer Shariah-compliant or conventional management.</p></div></article>
                <article><span>02</span><div><h3>Compare what fits</h3><p>Review a smaller, relevant shelf with transparent fund facts and BAN Wealth’s investment view.</p></div></article>
                <article><span>03</span><div><h3>Open your profile once</h3><p>Complete identity, bank, TIN and suitability details in one guided investor account flow.</p></div></article>
                <article><span>04</span><div><h3>Transfer and track</h3><p>Send money directly to the scheme account, attach proof, and follow the review status online.</p></div></article>
            </div>
        </div>
    </section>

    <section class="ban-wealth-families" aria-labelledby="ban-wealth-families-heading">
        <div class="ban-page-shell">
            <header class="ban-wealth-section-heading ban-wealth-section-heading--split"><div><p class="ban-page-kicker">The fund families</p><h2 id="ban-wealth-families-heading">A name that tells you the job.</h2></div><p>Liquid, Income and Growth make the intended timeframe visible before you open a fund page.</p></header>
            <div class="ban-wealth-families__grid">
                <article class="ban-wealth-family ban-wealth-family--liquid"><span>Short horizon</span><h3>BAN Liquid</h3><p>For a cushion, a planned expense, or money parked between decisions.</p><small>Lower risk profile</small></article>
                <article class="ban-wealth-family ban-wealth-family--income"><span>Medium horizon</span><h3>BAN Income</h3><p>For goals two to five years away, balancing stability with measured growth.</p><small>Medium risk profile</small></article>
                <article class="ban-wealth-family ban-wealth-family--growth"><span>Long horizon</span><h3>BAN Growth</h3><p>For wealth you can leave invested through market cycles and difficult years.</p><small>Higher risk profile</small></article>
            </div>
            <p class="ban-wealth-families__note">Shariah-compliant counterparts are available within each family.</p>
        </div>
    </section>

    <section class="ban-wealth-trust" aria-labelledby="ban-wealth-trust-heading">
        <div class="ban-page-shell ban-wealth-trust__grid">
            <div><p class="ban-page-kicker">Built for clarity</p><h2 id="ban-wealth-trust-heading">Know who does what with your money.</h2><p>BAN Wealth guides the journey and presents a consolidated view. The appointed asset manager runs the fund, while the official unit register and tax documents remain with the manager or registrar.</p><a href="{{ route('ban-wealth.invest') }}">Explore the fund shelf <span aria-hidden="true">→</span></a></div>
            <dl>
                <div><dt>01</dt><dd><strong>Your transfer</strong><span>Moves from your bank directly to the scheme subscription account.</span></dd></div>
                <div><dt>02</dt><dd><strong>The manager</strong><span>Is named clearly wherever you compare or select a fund.</span></dd></div>
                <div><dt>03</dt><dd><strong>Your documents</strong><span>Stay connected to the order and available from your investor workspace.</span></dd></div>
            </dl>
        </div>
    </section>

    <section class="ban-wealth-cta" aria-labelledby="ban-wealth-cta-heading">
        <div class="ban-page-shell"><p>Start with one question.</p><h2 id="ban-wealth-cta-heading">When might you need this money back?</h2><a href="{{ route('ban-wealth.invest') }}">Find what fits <span aria-hidden="true">→</span></a><small>Fund names and availability remain subject to registration and manager agreement. Investments can fall as well as rise.</small></div>
    </section>
</main>
@endsection
