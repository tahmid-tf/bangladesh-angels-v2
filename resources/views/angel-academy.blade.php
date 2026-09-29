@extends('layouts.guest')
@section('page_title', 'BAN Angel Academy | Bangladesh Angels Network')

@php
    $aaCanonical = route('angel-academy', [], true);
    $aaSeoTitle = 'BAN Angel Academy | Bangladesh Angels Network';
    $aaSeoDescription = '3-month BAN Angel Academy: live and online sessions, 1:1 BAN analyst coaching, pod-based assignments, and BAN membership benefits. Learn angel investing in Bangladesh with Bangladesh Angels Network.';
    $aaKeywords = 'BAN Angel Academy, angel investing course Bangladesh, angel investor training, Bangladesh Angels Network, venture capital training Dhaka, startup investing, early-stage investing, angel network';
    $aaImageAlt = 'BAN Angel Academy by Bangladesh Angels Network, training for new angel investors';
    $aaJsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                '@id' => $aaCanonical.'#webpage',
                'url' => $aaCanonical,
                'name' => $aaSeoTitle,
                'description' => $aaSeoDescription,
                'inLanguage' => 'en',
                'isPartOf' => ['@id' => url('/').'#website'],
                'mainEntity' => ['@id' => $aaCanonical.'#course'],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'name' => 'Bangladesh Angels Network Limited',
                'url' => url('/'),
            ],
            [
                '@type' => 'Course',
                '@id' => $aaCanonical.'#course',
                'name' => 'BAN Angel Academy',
                'description' => $aaSeoDescription,
                'url' => $aaCanonical,
                'educationalLevel' => 'Beginner to intermediate',
                'availableLanguage' => 'en',
                'provider' => [
                    '@type' => 'Organization',
                    'name' => 'Bangladesh Angels Network Limited',
                    'url' => url('/'),
                    'logo' => url(asset('logo.webp')),
                ],
            ],
        ],
    ];
@endphp

@push('head_meta')
    <x-seo-meta
        :title="$aaSeoTitle"
        :description="$aaSeoDescription"
        :keywords="$aaKeywords"
        :canonical="$aaCanonical"
        :image="asset('investor_cover.webp')"
        :imageAlt="$aaImageAlt"
        :jsonLd="$aaJsonLd"
    />
@endpush

@push('head_styles')
<style>
    .angel-academy .aa-hero-blob { animation: aa-float 22s ease-in-out infinite; }
    .angel-academy .aa-hero-blob--2 { animation: aa-float 28s ease-in-out infinite reverse; animation-delay: -4s; }
    @keyframes aa-float {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.2; }
        50% { transform: translate(3%, -2%) scale(1.05); opacity: 0.28; }
    }
    .angel-academy [data-aa-hero] > * {
        animation: aa-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .angel-academy [data-aa-hero] > *:nth-child(1) { animation-delay: 0.05s; }
    .angel-academy [data-aa-hero] > *:nth-child(2) { animation-delay: 0.14s; }
    .angel-academy [data-aa-hero] > *:nth-child(3) { animation-delay: 0.22s; }
    .angel-academy [data-aa-hero] > *:nth-child(4) { animation-delay: 0.3s; }
    @keyframes aa-fade-up {
        from { opacity: 0; transform: translateY(1.1rem); }
        to { opacity: 1; transform: translateY(0); }
    }
    .angel-academy .aa-reveal {
        opacity: 0;
        transform: translateY(1.25rem);
        transition: opacity 0.65s cubic-bezier(0.22, 1, 0.36, 1), transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
        will-change: opacity, transform;
    }
    .angel-academy .aa-reveal.aa-reveal--in {
        opacity: 1;
        transform: translateY(0);
        will-change: auto;
    }
    .angel-academy .aa-curric-header {
        background: linear-gradient(120deg, #042f28 0%, #063d36 45%, #0a5c50 100%);
    }
    /* Table column + phase bars: same BAN palette as marketing headers, not neutral black */
    .angel-academy .aa-table-bar {
        background: linear-gradient(118deg, #052a24 0%, #0a423a 42%, #0c5248 100%);
        color: #f0fdf7;
    }
    .angel-academy .aa-table-bar th {
        color: #ecfdf4;
    }
    .angel-academy .aa-curric-header-shine {
        position: relative;
        overflow: hidden;
    }
    .angel-academy .aa-curric-header-shine::after {
        content: "";
        position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.06) 50%, transparent 60%);
        transform: translateX(-60%);
    }
    @media (hover: hover) and (pointer: fine) {
        .angel-academy .aa-curric-header-shine:hover::after { animation: aa-curric-shine 1.1s ease-out forwards; }
    }
    @keyframes aa-curric-shine {
        to { transform: translateX(60%); }
    }
    .angel-academy .aa-pill {
        display: inline-block; border-radius: 9999px; padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 500;
        background: linear-gradient(180deg, #eef8f4 0%, #e0f2eb 100%);
        color: #0f3d34; border: 1px solid #c5e6d8;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .angel-academy .aa-pill:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px -4px rgba(15, 61, 52, 0.2);
        border-color: rgba(54, 179, 126, 0.45);
    }
    .angel-academy .aa-snapshot-row,
    .angel-academy .aa-pillar-row {
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }
    .angel-academy .aa-snapshot-row:hover,
    .angel-academy .aa-pillar-row:hover { box-shadow: inset 3px 0 0 0 #36b37e; }
    .angel-academy .aa-table-scroll { -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; }
    @media (max-width: 639.98px) {
        .angel-academy { -webkit-text-size-adjust: 100%; }
        /* Wider tables: cell utilities use text-xs; override for 15px body on small viewports */
        .angel-academy .aa-table-scroll table.aa-curric-table th,
        .angel-academy .aa-table-scroll table.aa-curric-table td { font-size: 0.9375rem !important; line-height: 1.55; }
        .angel-academy .aa-table-scroll tr.aa-table-bar th { font-size: 0.8125rem !important; line-height: 1.45; }
        .angel-academy .aa-pill { font-size: 0.75rem !important; padding: 0.2rem 0.5rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .angel-academy .aa-hero-blob,
        .angel-academy .aa-hero-blob--2 { animation: none !important; opacity: 0.2 !important; }
        .angel-academy [data-aa-hero] > * { animation: none !important; opacity: 1 !important; transform: none !important; }
        .angel-academy .aa-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
        .angel-academy .aa-curric-header-shine::after { animation: none !important; transform: none !important; }
    }
</style>
@endpush

@section('page_content')
<div class="angel-academy w-full min-w-0 overflow-x-clip text-[#0f3d34]">
    {{-- Hero: distinct academy marketing band on top of BAN palette --}}
    <section
        class="relative overflow-hidden border-b border-emerald-900/20 bg-gradient-to-br from-[#021a16] via-[#06352e] to-[#0a4a42]"
        aria-labelledby="academy-hero-heading"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 48px 48px;"
            aria-hidden="true"
        ></div>
        <div class="aa-hero-blob pointer-events-none absolute -right-24 top-0 h-96 w-96 rounded-full bg-[#36b37e]/20 blur-3xl" aria-hidden="true"></div>
        <div class="aa-hero-blob aa-hero-blob--2 pointer-events-none absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-20 md:py-24" data-aa-hero>
            <p class="mx-auto max-w-md text-center text-xs font-semibold uppercase leading-relaxed tracking-[0.2em] text-emerald-200/90 min-[400px]:tracking-[0.3em] sm:max-w-none sm:text-xs sm:tracking-[0.35em]">
                <span class="sm:whitespace-nowrap">Programme strategy</span>
                <span class="mx-1 inline text-emerald-400/80 sm:mx-2">|</span>
                <br class="min-[400px]:hidden" aria-hidden="true" />
                <span class="text-emerald-100/90 sm:whitespace-nowrap">Bangladesh Angels Network</span>
            </p>
            <h1 id="academy-hero-heading" class="mt-5 text-center text-[1.65rem] font-extrabold leading-[1.12] tracking-tight text-white min-[400px]:text-3xl sm:mt-6 sm:text-4xl md:text-5xl">
                <span class="block bg-gradient-to-r from-white via-[#d1fae5] to-white/90 bg-clip-text text-transparent">BAN Angel Academy</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-center text-base leading-relaxed text-emerald-100/95 sm:mt-5 sm:text-base">
                A compact, high-touch programme to build confident, disciplined early-stage investors, blending live instruction, BAN membership benefits, and applied learning alongside our analyst team.
            </p>

            <div class="mx-auto mt-8 flex w-full min-w-0 max-w-md flex-col items-stretch justify-center gap-3 sm:mt-10 sm:max-w-none sm:flex-row sm:items-center sm:gap-4">
                <a
                    href="{{ route('angel-academy.apply') }}"
                    class="inline-flex min-h-[48px] w-full min-w-0 items-center justify-center rounded-full bg-white px-6 text-base font-bold text-[#042f28] shadow-lg shadow-emerald-950/40 transition duration-300 ease-out hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-xl active:scale-[0.98] sm:w-auto sm:min-w-[12rem] sm:px-8 sm:text-sm"
                >
                    Apply to the Network
                </a>
                <a
                    href="https://calendar.google.com/calendar/u/0/appointments/schedules/AcZssZ1oFoHy0m9As4PtSfm4Ee-nVyoZRNNyZ38doHCWpVwDfo5u3qxnesRT_LqD_Fv5nUlbE3EvxyYN"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-[48px] w-full min-w-0 items-center justify-center rounded-full border border-white/30 bg-white/5 px-6 text-base font-semibold text-white backdrop-blur-sm transition duration-300 ease-out hover:-translate-y-0.5 hover:border-white/50 hover:bg-white/10 active:scale-[0.98] sm:w-auto sm:min-w-[12rem] sm:px-8 sm:text-sm"
                >
                    Book a Meet
                </a>
            </div>
        </div>
    </section>

    {{-- Programme overview --}}
    <section class="aa-reveal mx-auto max-w-4xl px-4 py-12 sm:px-5 sm:py-20 md:px-6" data-aa-reveal aria-labelledby="strategic-intent-heading">
        <div class="border-l-4 border-[#36b37e] pl-4 sm:pl-5 md:pl-6">
            <h2 id="strategic-intent-heading" class="text-[1.35rem] font-bold leading-snug tracking-tight text-[#0f3d34] min-[400px]:text-2xl sm:text-3xl">
                Strategic intent
            </h2>
            <p class="mt-2 text-sm font-medium uppercase tracking-wide text-[#18736a] sm:text-base">
                Features of BAN Angel Academy
            </p>
        </div>
        <p class="mt-5 max-w-3xl text-base leading-[1.65] text-gray-700 sm:mt-6 sm:text-base">
            BAN Angel Academy is designed to move participants from <strong class="font-semibold text-gray-800">theory to practice</strong>: the same deal rhythm, language, and diligence habits our network uses in live opportunities. Cohort size stays intentionally limited so you get real access to BAN analysts and your pod.
        </p>

        <div class="mt-8 overflow-hidden rounded-xl border border-[#c5e6d8]/80 bg-gradient-to-b from-white to-[#f7fdfb] shadow-[0_8px_40px_-12px_rgba(15,61,52,0.1)] transition-shadow duration-500 ease-out hover:shadow-[0_12px_48px_-8px_rgba(15,61,52,0.14)] sm:mt-10 sm:rounded-2xl">
            <div class="border-b border-[#e2f0ea] bg-[#eef8f4]/60 px-3 py-3 sm:px-5 md:px-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-[#0f3d34] sm:text-base">Programme at a glance</h3>
            </div>

            <div class="hidden md:block">
                <table class="w-full min-w-0 table-fixed text-left text-sm" role="table" aria-label="BAN Angel Academy programme details">
                    <tbody class="divide-y divide-[#e2f0ea]">
                        <tr class="align-top transition-colors duration-200 hover:bg-[#f9fdfb]/90">
                            <th scope="row" class="w-[32%] shrink-0 bg-white/50 px-3 py-3 font-semibold text-[#0f3d34] sm:px-4 sm:py-4 md:px-6">Sessions</th>
                            <td class="px-3 py-3 break-words text-gray-700 sm:px-4 sm:py-4 md:px-6">
                                <span class="font-semibold text-gray-800">15 sessions</span> × 2 hours each
                                <span class="mt-1 block text-gray-600">In-person: <strong class="font-medium text-gray-800">2 sessions</strong> in Dhaka ·
                                <strong class="font-medium text-gray-800">13 online</strong> (Google Meet)</span>
                            </td>
                        </tr>
                        <tr class="align-top transition-colors duration-200 hover:bg-[#f9fdfb]/90">
                            <th scope="row" class="bg-[#f9fdfb] px-3 py-3 font-semibold text-[#0f3d34] sm:px-4 sm:py-4 md:px-6">Duration</th>
                            <td class="px-3 py-3 break-words text-gray-700 sm:px-4 sm:py-4 md:px-6">3 months</td>
                        </tr>
                        <tr class="align-top transition-colors duration-200 hover:bg-[#f9fdfb]/90">
                            <th scope="row" class="bg-white/50 px-3 py-3 font-semibold text-[#0f3d34] sm:px-4 sm:py-4 md:px-6">1:1 with BAN analysts</th>
                            <td class="px-3 py-3 break-words text-gray-700 sm:px-4 sm:py-4 md:px-6">
                                2 × 45-minute private 1:1s per participant with a BAN senior analyst
                            </td>
                        </tr>
                        <tr class="align-top transition-colors duration-200 hover:bg-[#f9fdfb]/90">
                            <th scope="row" class="bg-[#f9fdfb] px-3 py-3 font-semibold text-[#0f3d34] sm:px-4 sm:py-4 md:px-6">Assignments</th>
                            <td class="px-3 py-3 break-words text-gray-700 sm:px-4 sm:py-4 md:px-6">
                                4 assignments across the programme: 2 individual, 2 group (pod-based)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <dl class="divide-y divide-[#e2f0ea] md:hidden">
                <div class="px-4 py-4 sm:px-5">
                    <dt class="text-sm font-bold uppercase tracking-wide text-[#18736a]">Sessions</dt>
                    <dd class="mt-2 break-words text-base leading-relaxed text-gray-800">
                        15 sessions × 2 hours: 2 in-person in Dhaka + 13 online (Google Meet)
                    </dd>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <dt class="text-sm font-bold uppercase tracking-wide text-[#18736a]">Duration</dt>
                    <dd class="mt-2 break-words text-base leading-relaxed text-gray-800">3 months</dd>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <dt class="text-sm font-bold uppercase tracking-wide text-[#18736a]">1:1 with BAN analysts</dt>
                    <dd class="mt-2 break-words text-base leading-relaxed text-gray-800">2 × 45-min private 1:1s per participant with a BAN senior analyst</dd>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <dt class="text-sm font-bold uppercase tracking-wide text-[#18736a]">Assignments</dt>
                    <dd class="mt-2 break-words text-base leading-relaxed text-gray-800">4 assignments (2 individual, 2 group / pod-based)</dd>
                </div>
            </dl>
        </div>

        <p class="mt-8 text-center text-sm leading-relaxed text-gray-600 sm:mt-10 sm:text-sm">
            Programme dates and cohort format may vary by intake. The Bangladesh Angels team will share pricing and final terms with you directly by email.
        </p>
    </section>

    {{-- Who is this for? --}}
    <section class="w-full" aria-labelledby="who-for-heading">
        <div class="aa-reveal border-t border-[#c5e6d8]/60 bg-gradient-to-b from-[#f7fdfb] via-white to-[#f0faf7] px-4 py-12 sm:px-5 sm:py-16 md:px-6" data-aa-reveal>
            <p class="text-center text-xs font-bold uppercase leading-relaxed tracking-[0.25em] text-[#18736a] min-[400px]:tracking-[0.3em] sm:text-xs">Angel Academy · BAN</p>
            <h2 id="who-for-heading" class="mx-auto mt-4 max-w-3xl text-center text-[1.35rem] font-bold leading-snug tracking-tight text-[#0f3d34] min-[400px]:text-2xl sm:text-3xl md:text-[2rem]">
                This programme was built for your journey
            </h2>
            <p class="mx-auto mt-4 max-w-3xl text-center text-base leading-relaxed text-gray-700 sm:mt-5 sm:text-base">
                Whether you’re building a startup, writing your first cheque, or simply following Bangladesh’s innovation story, Angel Academy gives you the edge to move with confidence.
            </p>
        </div>

        <div class="border-t border-[#1a5c4e]/20 bg-gradient-to-b from-[#0f3d34] via-[#0c342e] to-[#082520] text-white">
            <div class="mx-auto max-w-7xl">
                <ul class="grid grid-cols-1 divide-y divide-white/10 sm:grid-cols-2 sm:divide-y-0 sm:[&>li:nth-child(odd)]:border-r sm:[&>li:nth-child(odd)]:border-white/10 xl:grid-cols-4 xl:[&>li]:border-r-0 xl:divide-x xl:divide-white/10 xl:divide-y-0">
                    <li class="group aa-reveal relative z-0 flex min-h-0 flex-col px-4 py-8 pr-20 transition-colors duration-300 ease-out sm:px-6 sm:py-10 sm:pr-8 md:px-8 md:py-12 xl:min-h-[22rem] xl:hover:bg-white/[0.03]" data-aa-reveal>
                        <span class="pointer-events-none absolute right-2 top-5 select-none text-5xl font-extrabold leading-none text-white/[0.07] min-[400px]:right-3 min-[400px]:text-6xl sm:right-4 sm:top-6 sm:text-7xl" aria-hidden="true">01</span>
                        <div class="relative z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 text-[#a7e9d0] transition-transform duration-300 ease-out group-hover:scale-105 group-hover:border-white/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m15-3A2.25 2.25 0 0119.5 9v.878m0 0a2.25 2.25 0 01-.75 1.72l-6.5 4.125a2.25 2.25 0 01-2.5 0l-6.5-4.125A2.25 2.25 0 014.5 9" />
                            </svg>
                        </div>
                        <span class="relative z-10 mt-5 inline-flex w-max max-w-full rounded-full border border-[#36b37e]/50 bg-[#36b37e]/10 px-3 py-1.5 text-[0.6rem] font-bold uppercase leading-tight tracking-wider text-emerald-100 min-[400px]:text-[0.65rem]">Startup founder</span>
                        <span class="relative z-10 mt-4 h-0.5 w-12 rounded-full bg-[#36b37e]"></span>
                        <h3 class="relative z-10 mt-4 text-base font-bold leading-snug text-white sm:text-lg md:text-xl">You’re building something. Now learn how investors think</h3>
                        <p class="relative z-10 mt-3 text-base leading-[1.65] text-emerald-50/95 sm:text-base">Understand how VCs and angels evaluate deals, structure term sheets, and decide who to back. Build a fundable business from day one.</p>
                    </li>
                    <li class="group aa-reveal relative z-0 flex min-h-0 flex-col px-4 py-8 pr-20 transition-colors duration-300 ease-out sm:px-6 sm:py-10 sm:pr-8 md:px-8 md:py-12 xl:min-h-[22rem] xl:hover:bg-white/[0.03]" data-aa-reveal>
                        <span class="pointer-events-none absolute right-2 top-5 select-none text-5xl font-extrabold leading-none text-white/[0.07] min-[400px]:right-3 min-[400px]:text-6xl sm:right-4 sm:top-6 sm:text-7xl" aria-hidden="true">02</span>
                        <div class="relative z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 text-[#a7e9d0] transition-transform duration-300 ease-out group-hover:scale-105 group-hover:border-white/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872M7.5 18.75V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25M7.5 18.75h5.25M7.5 18.75H6.75a2.25 2.25 0 01-2.25-2.25v-6.75a2.25 2.25 0 012.25-2.25h.75" />
                            </svg>
                        </div>
                        <span class="relative z-10 mt-5 inline-flex w-max max-w-full rounded-full border border-[#36b37e]/50 bg-[#36b37e]/10 px-3 py-1.5 text-[0.6rem] font-bold uppercase leading-tight tracking-wider text-emerald-100 min-[400px]:text-[0.65rem]">Aspiring angel</span>
                        <span class="relative z-10 mt-4 h-0.5 w-12 rounded-full bg-[#36b37e]"></span>
                        <h3 class="relative z-10 mt-4 text-base font-bold leading-snug text-white sm:text-lg md:text-xl">Ready to write your first cheque? Start here</h3>
                        <p class="relative z-10 mt-3 text-base leading-[1.65] text-emerald-50/95 sm:text-base">Learn how to identify high-potential startups, evaluate risk, and build a personal angel portfolio in Bangladesh’s rapidly growing ecosystem.</p>
                    </li>
                    <li class="group aa-reveal relative z-0 flex min-h-0 flex-col px-4 py-8 pr-20 transition-colors duration-300 ease-out sm:px-6 sm:py-10 sm:pr-8 md:px-8 md:py-12 xl:min-h-[22rem] xl:hover:bg-white/[0.03]" data-aa-reveal>
                        <span class="pointer-events-none absolute right-2 top-5 select-none text-5xl font-extrabold leading-none text-white/[0.07] min-[400px]:right-3 min-[400px]:text-6xl sm:right-4 sm:top-6 sm:text-7xl" aria-hidden="true">03</span>
                        <div class="relative z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 text-[#a7e9d0] transition-transform duration-300 ease-out group-hover:scale-105 group-hover:border-white/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#36b37e] text-[0.65rem] font-bold leading-none text-[#042f28]" aria-hidden="true">+</span>
                        </div>
                        <span class="relative z-10 mt-5 inline-flex w-max max-w-full rounded-full border border-[#36b37e]/50 bg-[#36b37e]/10 px-3 py-1.5 text-[0.6rem] font-bold uppercase leading-tight tracking-wider text-emerald-100 min-[400px]:text-[0.65rem]">Career switcher</span>
                        <span class="relative z-10 mt-4 h-0.5 w-12 rounded-full bg-[#36b37e]"></span>
                        <h3 class="relative z-10 mt-4 text-base font-bold leading-snug text-white sm:text-lg md:text-xl">Break into VC from wherever you are today</h3>
                        <p class="relative z-10 mt-3 text-base leading-[1.65] text-emerald-50/95 sm:text-base">Discover how VC firms around the world hire, what analysts and associates actually do, and how to position yourself for roles across venture capital, startup operations, and ecosystem building.</p>
                    </li>
                    <li class="group aa-reveal relative z-0 flex min-h-0 flex-col px-4 py-8 pr-20 transition-colors duration-300 ease-out sm:px-6 sm:py-10 sm:pr-8 md:px-8 md:py-12 xl:min-h-[22rem] xl:hover:bg-white/[0.03]" data-aa-reveal>
                        <span class="pointer-events-none absolute right-2 top-5 select-none text-5xl font-extrabold leading-none text-white/[0.07] min-[400px]:right-3 min-[400px]:text-6xl sm:right-4 sm:top-6 sm:text-7xl" aria-hidden="true">04</span>
                        <div class="relative z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 text-[#a7e9d0] transition-transform duration-300 ease-out group-hover:scale-105 group-hover:border-white/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <span class="relative z-10 mt-5 inline-flex w-max max-w-full rounded-full border border-[#36b37e]/50 bg-[#36b37e]/10 px-3 py-1.5 text-[0.6rem] font-bold uppercase leading-tight tracking-wider text-emerald-100 min-[400px]:text-[0.65rem]">Curious observer</span>
                        <span class="relative z-10 mt-4 h-0.5 w-12 rounded-full bg-[#36b37e]"></span>
                        <h3 class="relative z-10 mt-4 text-base font-bold leading-snug text-white sm:text-lg md:text-xl">Curious about where Bangladesh’s startup wave is headed?</h3>
                        <p class="relative z-10 mt-3 text-base leading-[1.65] text-emerald-50/95 sm:text-base">Get an insider’s view of the Bangladesh innovation economy: the players, the deals, the trends, and the opportunities shaping the decade ahead.</p>
                    </li>
                </ul>
            </div>

            <div class="aa-reveal border-t border-white/10 bg-[#0a2e28]/80 px-4 py-7 sm:px-6 sm:py-8 md:px-8" data-aa-reveal>
                <div class="mx-auto flex max-w-5xl flex-col items-stretch justify-between gap-4 sm:flex-row sm:items-center sm:gap-6 md:gap-8">
                    <p class="text-center text-base font-medium leading-relaxed text-emerald-50/95 sm:flex-1 sm:text-left sm:text-base">Not sure which path fits you? We’ll help you find it.</p>
                    <a
                        href="{{ auth()->check() && auth()->user()->hasPaidMembership() ? route('dashboard') : route('investor.signup') }}"
                        class="inline-flex min-h-[48px] w-full min-w-0 shrink-0 items-center justify-center gap-2 self-center rounded-full border border-white/40 bg-transparent px-6 py-3.5 text-sm font-semibold text-white transition duration-300 ease-out hover:-translate-y-0.5 hover:border-white/70 hover:bg-white/10 active:scale-[0.98] sm:w-auto sm:min-w-[11rem] sm:self-auto"
                    >
                        <span>{{ auth()->check() && auth()->user()->hasPaidMembership() ? 'Open dashboard' : 'Find your path' }}</span>
                        <span aria-hidden="true" class="text-base leading-none">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Pre-programme process --}}
    <section class="w-full border-t border-[#c5e6d8]/50 bg-gradient-to-b from-white via-[#fbfefe] to-[#f0faf7] px-4 py-14 sm:px-5 sm:py-20 md:px-6" aria-labelledby="pre-programme-heading">
        <div class="mx-auto max-w-4xl">
            <div class="aa-reveal border-l-4 border-[#36b37e] pl-4 sm:pl-5 md:pl-6" data-aa-reveal>
                <h2 id="pre-programme-heading" class="text-[1.35rem] font-bold leading-snug tracking-tight text-[#0f3d34] min-[400px]:text-2xl sm:text-3xl">Pre-programme process</h2>
                <p class="mt-2 max-w-2xl text-base leading-relaxed text-gray-700 sm:text-base">How we prepare you and your cohort before Session 1: alignment, pod placement, and tailored analyst time.</p>
            </div>

            <ol class="mt-10 list-none space-y-0 p-0 sm:mt-12">
                <li class="aa-reveal grid grid-cols-[0.75rem_1fr] items-start gap-x-3 min-[400px]:grid-cols-[0.9rem_1fr] sm:grid-cols-[1rem_1fr] sm:gap-x-4" data-aa-reveal>
                    <div class="flex min-h-0 w-full max-w-[0.75rem] flex-col items-center self-stretch sm:max-w-[1rem]">
                        <span class="z-[1] mt-0.5 h-3 w-3 shrink-0 rounded-full border-2 border-[#36b37e] bg-white shadow sm:h-3.5 sm:w-3.5" aria-hidden="true"></span>
                        <div class="w-px min-h-10 flex-1 self-stretch bg-gradient-to-b from-[#36b37e] via-[#c5e6d8] to-[#c5e6d8]" aria-hidden="true"></div>
                    </div>
                    <div class="min-w-0 pb-8 sm:pb-10">
                        <div class="rounded-2xl border border-[#c5e6d8]/80 bg-white/90 p-6 shadow-sm transition duration-300 ease-out hover:border-[#36b37e]/45 hover:shadow-md sm:p-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <h3 class="text-xl font-bold text-[#0f3d34] sm:text-xl">Intake interview</h3>
                                <span class="inline-flex w-max max-w-full shrink-0 rounded-full bg-[#eef8f4] px-3 py-1 text-xs font-semibold text-[#18736a] ring-1 ring-[#c5e6d8]/80">30 min · 1:1 with BAN team</span>
                            </div>
                            <p class="mt-4 text-base leading-relaxed text-gray-700 sm:text-base">
                                It will be completed <strong class="font-semibold text-gray-800">before Session 1</strong>. It’s not a screening. It’s a <strong class="font-semibold text-gray-800">personalisation</strong> call covering investment appetite, sector interests, motivations, and learning objectives for the Academy. <strong class="font-semibold text-gray-800">Outputs:</strong> thematic pod assignment and identification of participants who need early support.
                            </p>
                        </div>
                    </div>
                </li>

                <li class="aa-reveal grid grid-cols-[0.75rem_1fr] items-start gap-x-3 min-[400px]:grid-cols-[0.9rem_1fr] sm:grid-cols-[1rem_1fr] sm:gap-x-4" data-aa-reveal>
                    <div class="flex min-h-0 w-full max-w-[0.75rem] flex-col items-center self-stretch sm:max-w-[1rem]">
                        <span class="z-[1] mt-0.5 h-3 w-3 shrink-0 rounded-full border-2 border-[#36b37e] bg-white shadow sm:h-3.5 sm:w-3.5" aria-hidden="true"></span>
                        <div class="w-px min-h-10 flex-1 self-stretch bg-[#c5e6d8]" aria-hidden="true"></div>
                    </div>
                    <div class="min-w-0 pb-8 sm:pb-10">
                        <div class="rounded-2xl border border-[#c5e6d8]/80 bg-white/90 p-6 shadow-sm transition duration-300 ease-out hover:border-[#36b37e]/45 hover:shadow-md sm:p-6">
                            <h3 class="text-xl font-bold text-[#0f3d34] sm:text-xl">Thematic pod formation</h3>
                            <p class="mt-3 text-base leading-relaxed text-gray-700 sm:text-base">
                                Participants are placed in pods of <strong class="font-semibold text-gray-800">roughly 3–5</strong> based on <strong class="font-semibold text-gray-800">sector focus</strong> and <strong class="font-semibold text-gray-800">risk appetite</strong>. Pods collaborate on group assignments and deliver the final IC pitch together.
                            </p>
                            <h4 class="mt-6 text-sm font-bold uppercase tracking-wider text-[#18736a] sm:text-sm">Indicative pod tracks (Cohort 1)</h4>
                            <ul class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3">
                                <li class="flex min-w-0 items-center gap-2 rounded-xl border border-[#d8efe4] bg-gradient-to-r from-[#f7fdfb] to-white px-3 py-2.5 text-base text-[#0f3d34] transition duration-200 ease-out hover:border-[#36b37e]/45 hover:shadow-sm sm:px-4 sm:text-sm">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#36b37e]" aria-hidden="true"></span>
                                    Fintech, digital assets &amp; financial inclusion
                                </li>
                                <li class="flex min-w-0 items-center gap-2 rounded-xl border border-[#d8efe4] bg-gradient-to-r from-[#f7fdfb] to-white px-3 py-2.5 text-base text-[#0f3d34] transition duration-200 ease-out hover:border-[#36b37e]/45 hover:shadow-sm sm:px-4 sm:text-sm">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#36b37e]" aria-hidden="true"></span>
                                    AI, SaaS &amp; deep tech
                                </li>
                                <li class="flex min-w-0 items-center gap-2 rounded-xl border border-[#d8efe4] bg-gradient-to-r from-[#f7fdfb] to-white px-3 py-2.5 text-base text-[#0f3d34] transition duration-200 ease-out hover:border-[#36b37e]/45 hover:shadow-sm sm:px-4 sm:text-sm">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#36b37e]" aria-hidden="true"></span>
                                    HealthTech, AgriTech &amp; climate
                                </li>
                                <li class="flex min-w-0 items-center gap-2 rounded-xl border border-[#d8efe4] bg-gradient-to-r from-[#f7fdfb] to-white px-3 py-2.5 text-base text-[#0f3d34] transition duration-200 ease-out hover:border-[#36b37e]/45 hover:shadow-sm sm:px-4 sm:text-sm">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#36b37e]" aria-hidden="true"></span>
                                    Cybersecurity
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>

                <li class="aa-reveal grid grid-cols-[0.75rem_1fr] items-start gap-x-3 min-[400px]:grid-cols-[0.9rem_1fr] sm:grid-cols-[1rem_1fr] sm:gap-x-4" data-aa-reveal>
                    <div class="flex w-full max-w-[0.75rem] flex-col items-center sm:max-w-[1rem]">
                        <span class="z-[1] mt-0.5 h-3 w-3 rounded-full border-2 border-[#36b37e] bg-white shadow sm:h-3.5 sm:w-3.5" aria-hidden="true"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="rounded-2xl border border-[#c5e6d8]/80 bg-white/90 p-6 shadow-sm transition duration-300 ease-out hover:border-[#36b37e]/45 hover:shadow-md sm:p-6">
                            <h3 class="text-xl font-bold text-[#0f3d34] sm:text-xl">One-to-one analyst sessions</h3>
                            <p class="mt-2 text-base text-gray-600">Two private checkpoints across the programme.</p>
                            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                                <div class="flex min-w-0 flex-col rounded-xl border border-[#e2f0ea] bg-gradient-to-b from-[#f7fdfb] to-white p-4 transition duration-300 ease-out hover:border-[#36b37e]/35 hover:shadow-md sm:p-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-[#0f3d34]">Session A</span>
                                        <span class="rounded-full bg-[#eef8f4] px-2.5 py-0.5 text-xs font-medium text-[#18736a] ring-1 ring-[#c5e6d8]/70">Weeks 2–4</span>
                                    </div>
                                    <p class="mt-3 text-sm leading-relaxed text-gray-700 sm:text-base">
                                        Personal <strong class="text-gray-900">investment thesis</strong>: sector, stage, cheque size, and risk appetite.
                                    </p>
                                </div>
                                <div class="flex min-w-0 flex-col rounded-xl border border-[#e2f0ea] bg-gradient-to-b from-[#f7fdfb] to-white p-4 transition duration-300 ease-out hover:border-[#36b37e]/35 hover:shadow-md sm:p-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-[#0f3d34]">Session B</span>
                                        <span class="rounded-full bg-[#eef8f4] px-2.5 py-0.5 text-xs font-medium text-[#18736a] ring-1 ring-[#c5e6d8]/70">Weeks 7–10</span>
                                    </div>
                                    <p class="mt-3 text-sm leading-relaxed text-gray-700 sm:text-base">
                                        <strong class="text-gray-900">Portfolio strategy</strong>: construction, diversification, follow-on allocation, and your post-graduation sourcing plan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    {{-- Curriculum (15 sessions) --}}
    <section class="w-full border-t border-[#c5e6d8]/50 bg-white px-4 py-14 sm:px-5 sm:py-20 md:px-6" aria-labelledby="curriculum-heading">
        <div class="aa-reveal mx-auto max-w-5xl" data-aa-reveal>
            <div class="border-l-4 border-[#36b37e] pl-4 sm:pl-5 md:pl-6">
                <h2 id="curriculum-heading" class="text-[1.4rem] font-bold leading-snug tracking-tight text-[#0f3d34] min-[400px]:text-2xl sm:text-3xl">CURRICULUM (15 SESSIONS)</h2>
            </div>
            <div class="mt-5 max-w-3xl space-y-3 text-base leading-relaxed text-gray-700 sm:mt-6">
                <p>Assignments are marked with <span class="font-semibold text-[#0f3d34]">■</span> in the session where they are due. Sessions 6–8 are Phase 2: three deep dives (technical, commercial, financial &amp; legal) that build toward the <strong class="font-semibold text-gray-800">full pod due-diligence report</strong>, with <strong class="font-semibold text-gray-800">Assignment 3</strong> due after <strong class="font-semibold text-gray-800">Session 9</strong>. The two In-Person sessions anchor programme launch and IC graduation.</p>
            </div>

            {{-- Programme snapshot: 5 phases at a glance --}}
            <div id="programme-snapshot" class="mt-10 scroll-mt-20 sm:mt-12">
                <h3 class="text-xl font-bold tracking-tight text-[#0f3d34] sm:text-xl">Programme snapshot</h3>
                <p class="mt-1 text-base leading-relaxed text-gray-700 sm:text-sm">Five phases across fifteen sessions: content themes, key assignments, and how experiential work maps to the IC.</p>
                <div class="aa-table-scroll mt-4 overflow-x-auto rounded-xl border border-[#c5e6d8]/90 shadow-sm sm:rounded-2xl">
                    <div class="aa-curric-header-shine aa-curric-header flex min-w-[56rem] flex-col gap-2 px-4 py-4 text-white sm:min-w-0 sm:flex-row sm:items-end sm:justify-between sm:px-5 sm:py-5">
                        <div>
                            <p class="text-xs font-bold uppercase leading-tight tracking-[0.2em] text-emerald-100/90 sm:text-xs">BAN Angel Academy</p>
                            <p class="mt-1.5 text-xl font-extrabold leading-tight sm:text-xl">Programme snapshot</p>
                            <p class="mt-1.5 text-sm text-emerald-200/90 sm:text-sm">15 sessions · 3 months · 5 phases · In-person + online</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold uppercase tracking-[0.12em] text-[#a7f3d0] sm:text-sm">Curriculum overview</p>
                    </div>
                    <table class="aa-curric-table w-full min-w-[56rem] table-fixed border-collapse text-left text-sm text-gray-800" role="table" aria-label="BAN Angel Academy five-phase programme snapshot">
                        <thead>
                            <tr class="aa-table-bar">
                                <th scope="col" class="w-[14%] px-2 py-2.5 text-xs font-bold uppercase tracking-wide sm:px-3 sm:py-3 sm:text-[0.7rem]">Phase</th>
                                <th scope="col" class="w-[12%] px-2 py-2.5 text-xs font-bold uppercase tracking-wide sm:px-3 sm:text-[0.7rem]">Sessions</th>
                                <th scope="col" class="w-[32%] px-2 py-2.5 text-xs font-bold uppercase tracking-wide sm:px-3 sm:text-[0.7rem]">Content</th>
                                <th scope="col" class="w-[21%] px-2 py-2.5 text-xs font-bold uppercase tracking-wide sm:px-3 sm:text-[0.7rem]">Experiential</th>
                                <th scope="col" class="w-[21%] px-2 py-2.5 pr-3 text-xs font-bold uppercase tracking-wide sm:px-3 sm:pr-4 sm:text-[0.7rem]">IC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e2f0ea]">
                            <tr class="aa-snapshot-row bg-white">
                                <th scope="row" class="px-2 py-3 align-top text-xs font-bold uppercase leading-snug text-[#0f3d34] sm:px-3 sm:py-4 sm:text-[0.7rem]">Phase 1 · Foundations <span class="mt-0.5 block font-normal text-[0.65rem] text-gray-500 sm:text-xs">(1 in-person, 4 online)</span></th>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">1–5</td>
                                <td class="px-2 py-3 align-top sm:px-3 sm:py-4">
                                    <p class="flex flex-wrap gap-1.5">
                                        <span class="aa-pill">Angel mindset</span><span class="aa-pill">Thesis &amp; sourcing</span><span class="aa-pill">Valuation</span><span class="aa-pill">Venture economics</span><span class="aa-pill">Startup evaluation</span>
                                    </p>
                                    <p class="mt-2.5 text-xs leading-relaxed text-gray-600 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■</span> Personal investor profile + investment thesis &amp; deal sourcing</p>
                                </td>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">Investor profile &amp; deal sourcing (pods)</td>
                                <td class="px-2 py-3 pr-2 align-top text-gray-700 sm:px-3 sm:py-4 sm:pr-4">1:1 analyst session A</td>
                            </tr>
                            <tr class="aa-snapshot-row bg-[#f9fdfb]/90">
                                <th scope="row" class="px-2 py-3 align-top text-xs font-bold uppercase leading-snug text-[#0f3d34] sm:px-3 sm:py-4 sm:text-[0.7rem]">Phase 2 · Due diligence <span class="mt-0.5 block font-normal text-[0.65rem] text-gray-500 sm:text-xs">(3 online)</span></th>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">6–8</td>
                                <td class="px-2 py-3 align-top sm:px-3 sm:py-4">
                                    <p class="flex flex-wrap gap-1.5">
                                        <span class="aa-pill">Technical DD</span><span class="aa-pill">Commercial DD</span><span class="aa-pill">Financial &amp; legal DD</span>
                                    </p>
                                    <p class="mt-2.5 text-xs leading-relaxed text-gray-600 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■</span> Full due-diligence report (pod)</p>
                                </td>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">Thematic due diligence</td>
                                <td class="px-2 py-3 pr-2 align-top text-gray-700 sm:px-3 sm:py-4 sm:pr-4">1:1 analyst session B</td>
                            </tr>
                            <tr class="aa-snapshot-row bg-white">
                                <th scope="row" class="px-2 py-3 align-top text-xs font-bold uppercase leading-snug text-[#0f3d34] sm:px-3 sm:py-4 sm:text-[0.7rem]">Phase 3 · Deal execution <span class="mt-0.5 block font-normal text-[0.65rem] text-gray-500 sm:text-xs">(3 online)</span></th>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">9–11</td>
                                <td class="px-2 py-3 align-top sm:px-3 sm:py-4">
                                    <p class="flex flex-wrap gap-1.5">
                                        <span class="aa-pill">Term sheets</span><span class="aa-pill">Governance &amp; rights</span><span class="aa-pill">Portfolio construction</span>
                                    </p>
                                    <p class="mt-2.5 text-xs leading-relaxed text-gray-600 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■</span> DD report submission &amp; deal-proceed recommendation</p>
                                </td>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">Deal structuring &amp; portfolio</td>
                                <td class="px-2 py-3 pr-2 align-top text-gray-700 sm:px-3 sm:py-4 sm:pr-4">Portfolio construction</td>
                            </tr>
                            <tr class="aa-snapshot-row bg-[#f9fdfb]/90">
                                <th scope="row" class="px-2 py-3 align-top text-xs font-bold uppercase leading-snug text-[#0f3d34] sm:px-3 sm:py-4 sm:text-[0.7rem]">Phase 4 · Value creation <span class="mt-0.5 block font-normal text-[0.65rem] text-gray-500 sm:text-xs">(3 online)</span></th>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">12–14</td>
                                <td class="px-2 py-3 align-top sm:px-3 sm:py-4">
                                    <p class="flex flex-wrap gap-1.5">
                                        <span class="aa-pill">Founder support</span><span class="aa-pill">Exits &amp; returns</span><span class="aa-pill">SE Asia markets</span>
                                    </p>
                                    <p class="mt-2.5 text-xs leading-relaxed text-gray-600 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■</span> IC dry-run with faculty (Session 14)</p>
                                </td>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">Value creation &amp; IC prep</td>
                                <td class="px-2 py-3 pr-2 align-top text-gray-700 sm:px-3 sm:py-4 sm:pr-4">IC dry-run</td>
                            </tr>
                            <tr class="aa-snapshot-row bg-white">
                                <th scope="row" class="px-2 py-3 align-top text-xs font-bold uppercase leading-snug text-[#0f3d34] sm:px-3 sm:py-4 sm:text-[0.7rem]">Phase 5 · IC graduation <span class="mt-0.5 block font-normal text-[0.65rem] text-gray-500 sm:text-xs">(1 in-person)</span></th>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">15</td>
                                <td class="px-2 py-3 align-top sm:px-3 sm:py-4">
                                    <p class="flex flex-wrap gap-1.5">
                                        <span class="aa-pill">Live IC pitch</span><span class="aa-pill">Q&amp;A</span><span class="aa-pill">Investment memo</span><span class="aa-pill">Certificate</span>
                                    </p>
                                    <p class="mt-2.5 text-xs leading-relaxed text-gray-600 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■</span> IC pitch &amp; investment memo: programme capstone</p>
                                </td>
                                <td class="px-2 py-3 align-top text-gray-700 sm:px-3 sm:py-4">Live IC pitch &amp; graduation</td>
                                <td class="px-2 py-3 pr-2 align-top text-gray-700 sm:px-3 sm:py-4 sm:pr-4">BAN partners &amp; NRB angels</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h3 class="mt-10 text-lg font-bold text-[#0f3d34] sm:mt-12 sm:text-lg">Session list</h3>
            <p class="mt-1 text-base leading-relaxed text-gray-700 sm:text-sm">Format and focus by session.</p>

            <div class="aa-table-scroll mt-5 overflow-x-auto rounded-xl border border-[#c5e6d8]/90 shadow-sm sm:mt-6 sm:rounded-2xl">
                <table class="aa-curric-table w-full min-w-[42rem] border-collapse text-left text-sm text-gray-800" role="table" aria-label="BAN Angel Academy curriculum by session">
                    <thead>
                        <tr class="aa-table-bar">
                            <th scope="col" class="w-10 px-2 py-3 font-bold sm:px-3 sm:py-3.5">#</th>
                            <th scope="col" class="w-8 px-1 py-3 text-center font-bold sm:px-2" title="Assignment due"><span class="sr-only">Assignment</span>■</th>
                            <th scope="col" class="w-[5.5rem] px-2 py-3 font-bold sm:w-28 sm:px-3">Format</th>
                            <th scope="col" class="px-2 py-3 pr-3 font-bold sm:px-4 sm:pr-5">Session title &amp; focus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr class="aa-table-bar">
                            <th colspan="4" scope="colgroup" class="px-3 py-2.5 text-xs font-semibold uppercase italic leading-snug tracking-wide sm:px-4 sm:text-sm">Phase 1: Foundations (sourcing, market/team/product evaluation, venture economics &amp; fund mechanics, Sessions 1–5)</th>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">1</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">In-Person</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Orientation &amp; the Angel Mindset</span>: Cohort introductions; thematic pod formation; BD startup ecosystem; angel vs. passive investor mindset.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">2</td>
                            <td class="px-1 py-3 text-center align-top font-bold text-[#0f3d34] sm:px-2" title="Assignment 1 due">■</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Venture Economics &amp; Fund Mechanics</span>: Power law; return math; cap table fundamentals; SAFEs, convertible notes, equity rounds; dilution modelling; different instruments.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">3</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Thesis-Driven Sourcing</span>: Building a personal investment thesis; sector and stage focus; BD accelerators, and deal rooms.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">4</td>
                            <td class="px-1 py-3 text-center align-top font-bold text-[#0f3d34] sm:px-2" title="Assignment 2 due">■</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Startup Evaluation: Team, Market &amp; Product</span>: Founder assessment frameworks; TAM/SAM/SOM for emerging markets; PMF signals; structured scoring criteria.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">5</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Financial Analysis &amp; Valuation</span>: Pre-revenue valuation methods; comparable transactions; term sheet economics; cap table red flags.</td>
                        </tr>

                        <tr class="aa-table-bar">
                            <th colspan="4" scope="colgroup" class="px-3 py-2.5 text-xs font-semibold uppercase italic leading-snug tracking-wide sm:px-4 sm:text-sm">Phase 2: Thematic due diligence (technical, commercial &amp; legal, Sessions 6–8)</th>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">6</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Technical Due Diligence</span>: Tech stack and architecture review; engineering team quality signals; IP and build-vs-buy risk; AI/ML model risk.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">7</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Commercial &amp; Market Due Diligence</span>: Primary research: customer interviews, reference checks, competitor mapping; validating founder assumptions; unit economics stress-testing.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">8</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Financial &amp; Legal Due Diligence</span>: Data room review; revenue quality and cohort analysis; BIDA and Bangladesh Bank regulatory compliance; NRB cross-border deal structuring.</td>
                        </tr>

                        <tr class="aa-table-bar">
                            <th colspan="4" scope="colgroup" class="px-3 py-2.5 text-xs font-semibold uppercase italic leading-snug tracking-wide sm:px-4 sm:text-sm">Phase 3: Deal execution (Sessions 9–11)</th>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">9</td>
                            <td class="px-1 py-3 text-center align-top font-bold text-[#0f3d34] sm:px-2" title="Assignment 3 due">■</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Term Sheets, Negotiation &amp; Deal Structuring</span>: Founder-friendly vs. investor-protective terms; pro-rata rights; anti-dilution; lead vs. follow dynamics; syndication mechanics.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">10</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Portfolio Construction &amp; Risk Management</span>: Sizing bets; diversification in a small portfolio; follow-on allocation; concentration risk across BD and SE Asia.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">11</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Corporate Governance &amp; Investor Rights</span>: Board vs. observer rights; information rights; protective provisions; managing the investor-founder relationship post-close.</td>
                        </tr>

                        <tr class="aa-table-bar">
                            <th colspan="4" scope="colgroup" class="px-3 py-2.5 text-xs font-semibold uppercase italic leading-snug tracking-wide sm:px-4 sm:text-sm">Phase 4: Value creation &amp; exits (Sessions 12–14)</th>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">12</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Value Creation &amp; Founder Support</span>: Post-investment engagement; NRB network leverage; strategic introductions; when to intervene vs. step back.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">13</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Southeast Asia: Markets &amp; Co-Investment</span>: SE Asia venture landscape; deal access for BD/NRB angels; co-investing alongside established funds like IDLC VC Fund 1; cross-border structures.</td>
                        </tr>
                        <tr class="aa-pillar-row bg-white">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">14</td>
                            <td class="px-1 py-3 text-center align-top text-gray-300 sm:px-2">·</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">Online</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Exits, Returns &amp; Building an Investor Brand</span>: M&amp;A, secondary sales, IPO pathways; DPI optimisation; building a public thesis and investor reputation. IC dry-run with faculty.</td>
                        </tr>

                        <tr class="aa-table-bar">
                            <th colspan="4" scope="colgroup" class="px-3 py-2.5 text-xs font-semibold uppercase italic leading-snug tracking-wide sm:px-4 sm:text-sm">Phase 5: IC &amp; graduation (Session 15)</th>
                        </tr>
                        <tr class="aa-pillar-row bg-gray-50/90">
                            <td class="whitespace-nowrap px-2 py-3 align-top font-semibold text-[#0f3d34] sm:px-3">15</td>
                            <td class="px-1 py-3 text-center align-top font-bold text-[#0f3d34] sm:px-2" title="Assignment 4 due">■</td>
                            <td class="whitespace-nowrap px-2 py-3 align-top text-gray-600 sm:px-3">In-Person</td>
                            <td class="px-2 py-3 pr-2 align-top sm:pr-4"><span class="font-bold text-[#0f3d34]">Investment Committee Pitches &amp; Graduation</span>: Live IC presentations to BAN partners and NRB angel panel; structured Q&amp;A; certificate ceremony; BAN membership onboarding.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-gray-600 sm:mt-5 sm:text-sm">Session topics and order may be refined by cohort. <span class="font-medium text-gray-700">■</span> = assignment due after that session: Assignment 1 (Session 2), Assignment 2 (Session 4), Assignment 3 (Session 9), Assignment 4 (Session 15).</p>

            {{-- Programme curriculum: content, experiential, and IC pillars by session --}}
            <div id="programme-curriculum-pillars" class="mt-10 scroll-mt-20 sm:mt-12">
                <h3 class="text-xl font-bold tracking-tight text-[#0f3d34] sm:text-xl">Programme curriculum</h3>
                <p class="mt-1 text-base leading-relaxed text-gray-700 sm:text-sm">Content pillar, experiential track, and investment-committee work aligned across all fifteen sessions.</p>
                <div class="aa-table-scroll mt-4 overflow-x-auto rounded-xl border border-[#c5e6d8]/90 shadow-sm sm:rounded-2xl">
                    <div class="aa-curric-header-shine aa-curric-header flex min-w-[64rem] flex-col gap-1 px-4 py-4 sm:min-w-0 sm:flex-row sm:items-end sm:justify-between sm:px-5 sm:py-4">
                        <div>
                            <p class="text-xs font-bold uppercase leading-tight tracking-[0.2em] text-emerald-100/90 sm:text-xs">BAN Angel Academy</p>
                            <p class="mt-1.5 text-xl font-extrabold leading-tight text-white sm:text-xl">Programme curriculum</p>
                        </div>
                        <p class="shrink-0 text-sm text-emerald-200/90 sm:text-sm">15 sessions · 3 months · 5 phases</p>
                    </div>
                    <table class="aa-curric-table w-full min-w-[64rem] table-fixed border-collapse text-left text-sm text-gray-800" role="table" aria-label="BAN Angel Academy curriculum: sessions with experiential and investment committee pillars">
                        <thead>
                            <tr class="aa-table-bar">
                                <th scope="col" class="w-[9%] px-2 py-2.5 text-[0.65rem] font-bold uppercase leading-tight sm:px-2.5 sm:py-3 sm:text-xs">Phase</th>
                                <th scope="col" class="w-[18%] px-2 py-2.5 text-[0.65rem] font-bold uppercase leading-tight sm:px-2.5 sm:py-3 sm:text-xs">Session</th>
                                <th scope="col" class="w-[29%] px-2 py-2.5 text-[0.65rem] font-bold uppercase leading-tight sm:px-2.5 sm:py-3 sm:text-xs">Content pillar</th>
                                <th scope="col" class="w-[22%] px-2 py-2.5 text-[0.65rem] font-bold uppercase leading-tight sm:px-2.5 sm:py-3 sm:text-xs">Experiential</th>
                                <th scope="col" class="w-[22%] px-2 py-2.5 pr-2.5 text-[0.65rem] font-bold uppercase leading-tight sm:px-2.5 sm:py-3 sm:pr-4 sm:text-xs">Investment committee</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e2f0ea]">
                            <tr class="aa-table-bar">
                                <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase italic sm:px-4 sm:text-sm">Phase 1: Foundations (Sessions 1–5)</th>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <th rowspan="5" scope="rowgroup" class="bg-[#f0faf6] px-2 py-2 align-top text-xs font-bold leading-snug text-[#0f3d34] sm:px-2.5 sm:py-3">Phase 1</th>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">1</span>
                                        <span class="rounded-full bg-[#e8f7f1] px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[#0f3d34] ring-1 ring-[#c5e6d8]/80 sm:text-xs">In person</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Orientation &amp; the Angel Mindset</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">BD startup ecosystem · Angel vs. passive investor mindset · Thematic pod formation</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Launch session; cohort and pods. Sets expectations for the arc ahead.</td>
                                <td rowspan="2" class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Personal profile &amp; pod formation</span>: Intake interview · Pod assignment by sector &amp; risk appetite
                                </td>
                                <td rowspan="5" class="bg-white px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">1:1 analyst sessions</span> (Sessions 1–5)<br />
                                    <span class="text-gray-500">Wk 2–4</span>: Session A: personal investment thesis · <span class="text-gray-500">Wk 7–10</span>: Session B: portfolio strategy &amp; construction
                                </td>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">2</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Venture Economics &amp; Fund Mechanics</p>
                                    <p class="mt-2 border-l-2 border-[#36b37e] bg-[#f0faf6] pl-2 text-xs text-gray-800 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■ Assignment 1 due</span>: Personal investor profile (1 page)</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Power law, return math, instruments, dilution, cap table literacy.</td>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">3</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Thesis-Driven Sourcing</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Personal investment thesis · Sector &amp; stage focus · BD accelerators · Deal rooms</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Build thesis and a repeatable sourcing rhythm.</td>
                                <td rowspan="3" class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Thesis formation &amp; deal sourcing</span>: 1-page pod thesis · 3 sourced deal candidates with rationale
                                </td>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">4</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Startup Evaluation: Team, Market &amp; Product</p>
                                    <p class="mt-2 border-l-2 border-[#36b37e] bg-[#f0faf6] pl-2 text-xs text-gray-800 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■ Assignment 2 due</span>: Investment thesis &amp; deal sourcing (pod)</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">TAM / SAM / SOM, PMF signals, structured scoring.</td>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">5</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Financial Analysis &amp; Valuation</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Pre-revenue valuation · Comparables · Term sheet economics</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Valuation, comparables, and term-sheet economics in practice.</td>
                            </tr>

                            <tr class="aa-table-bar">
                                <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase italic sm:px-4 sm:text-sm">Phase 2: Due diligence (Sessions 6–8)</th>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <th rowspan="3" scope="rowgroup" class="bg-[#f0faf6] px-2 py-2 align-top text-xs font-bold leading-snug text-[#0f3d34] sm:px-2.5 sm:py-3">Phase 2</th>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">6</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Technical Due Diligence</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Tech stack &amp; architecture · Engineering quality · IP &amp; build vs. buy · AI/ML risk</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Structure technical risk questions and evidence.</td>
                                <td rowspan="3" class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Thematic due diligence</span>: Technical · Commercial · Financial &amp; legal review
                                </td>
                                <td rowspan="3" class="bg-white px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Pod collaboration</span>: Full DD report · Min. 1 reference call per member · Deal-proceed recommendation
                                </td>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">7</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Commercial &amp; Market Due Diligence</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Customer interviews · Reference checks · Competitor mapping · Unit economics</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Validate market, customers, and unit economics under stress.</td>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">8</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Financial &amp; Legal Due Diligence</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Data room · Revenue quality · BIDA &amp; Bangladesh Bank · NRB cross-border</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Legal, financial, and regulatory read for BD and cross-border context.</td>
                            </tr>

                            <tr class="aa-table-bar">
                                <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase italic sm:px-4 sm:text-sm">Phase 3: Deal execution (Sessions 9–11)</th>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <th rowspan="3" scope="rowgroup" class="bg-[#f0faf6] px-2 py-2 align-top text-xs font-bold leading-snug text-[#0f3d34] sm:px-2.5 sm:py-3">Phase 3</th>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">9</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Term Sheets, Negotiation &amp; Deal Structuring</p>
                                    <p class="mt-2 border-l-2 border-[#36b37e] bg-[#f0faf6] pl-2 text-xs text-gray-800 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■ Assignment 3 due</span>: Full due-diligence report (pod)</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Founder vs. investor terms, pro-rata, protection, lead vs. follow, syndicates.</td>
                                <td rowspan="3" class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Deal execution &amp; portfolio</span>: Term sheets · Governance · Risk management
                                </td>
                                <td rowspan="3" class="bg-white px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Portfolio construction</span>: Deal structuring · Governance rights · Syndication
                                </td>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">10</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Portfolio Construction &amp; Risk Management</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Sizing · Diversification · Follow-on · BD &amp; SE Asia concentration</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Build position sizing and portfolio-level risk discipline.</td>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">11</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Corporate Governance &amp; Investor Rights</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Board vs. observer · Information rights · Protective provisions · Post-close</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Board, information rights, and post-close relationships.</td>
                            </tr>

                            <tr class="aa-table-bar">
                                <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase italic sm:px-4 sm:text-sm">Phase 4: Value creation (Sessions 12–14)</th>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <th rowspan="3" scope="rowgroup" class="bg-[#f0faf6] px-2 py-2 align-top text-xs font-bold leading-snug text-[#0f3d34] sm:px-2.5 sm:py-3">Phase 4</th>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">12</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Value Creation &amp; Founder Support</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">Post-investment · NRB network · Strategic intros</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">When to help, when to get out of the way.</td>
                                <td rowspan="3" class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Value creation &amp; IC prep</span>: NRB network · SE Asia co-investment · IC dry-run (Session 14)
                                </td>
                                <td rowspan="3" class="bg-white px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">IC prep</span>: IC dry-run with faculty in Session 14
                                </td>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">13</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Southeast Asia: Markets &amp; Co-Investment</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">SE Asia landscape · BD/NRB access · Co-investing with funds</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Regional context and co-invest routes.</td>
                            </tr>
                            <tr class="aa-pillar-row bg-white">
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white text-xs font-bold text-[#0f3d34] ring-1 ring-gray-200">14</span>
                                        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 sm:text-xs">Online</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Exits, Returns &amp; Building an Investor Brand</p>
                                    <p class="mt-1 text-xs leading-relaxed text-gray-600 sm:text-sm">M&amp;A · Secondaries · IPO options · DPI · IC dry-run with faculty</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Exit paths, returns, and reputation, tied to the live dry-run.</td>
                            </tr>

                            <tr class="aa-table-bar">
                                <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase italic sm:px-4 sm:text-sm">Phase 5: IC graduation (Session 15)</th>
                            </tr>
                            <tr class="aa-pillar-row bg-gray-50/80">
                                <th scope="row" class="bg-[#f0faf6] px-2 py-2 align-top text-xs font-bold text-[#0f3d34] sm:px-2.5 sm:py-3">Phase 5</th>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-[#0f3d34] text-xs font-bold text-white">15</span>
                                        <span class="rounded-full bg-[#e8f7f1] px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-[#0f3d34] ring-1 ring-[#c5e6d8]/80 sm:text-xs">In person</span>
                                    </div>
                                    <p class="mt-1.5 font-bold leading-snug text-[#0f3d34]">Investment Committee Pitches &amp; Graduation</p>
                                    <p class="mt-2 border-l-2 border-[#36b37e] bg-[#f0faf6] pl-2 text-xs text-gray-800 sm:text-sm"><span class="font-semibold text-[#0f3d34]">■ Assignment 4</span>: IC pitch &amp; investment memo (capstone)</p>
                                </td>
                                <td class="px-2 py-2.5 align-top sm:px-2.5 sm:py-3">Public IC-style presentations and cohort graduation.</td>
                                <td class="bg-[#f7fdfb] px-2 py-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">Live IC &amp; graduation</span>: BAN partners · NRB panel · Certificate
                                </td>
                                <td class="px-2 py-2.5 pr-2.5 align-top text-xs text-gray-700 sm:px-2.5 sm:py-3 sm:pr-4 sm:text-sm">
                                    <span class="font-semibold text-[#0f3d34]">BAN partners</span> · <span class="font-semibold text-[#0f3d34]">NRB angel panel</span> · Notable Bangladesh angels
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-center text-sm leading-relaxed text-gray-600 sm:text-sm">Bangladesh Angels Network · Cohort size 12–15</p>
            </div>
        </div>
    </section>
</div>
<script>
(function () {
    var root = document.querySelector('.angel-academy');
    if (!root) return;
    var nodes = root.querySelectorAll('[data-aa-reveal]');
    if (!nodes.length) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        nodes.forEach(function (el) { el.classList.add('aa-reveal--in'); });
        return;
    }
    if (!('IntersectionObserver' in window)) {
        nodes.forEach(function (el) { el.classList.add('aa-reveal--in'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('aa-reveal--in');
            io.unobserve(entry.target);
        });
    }, { root: null, rootMargin: '0px 0px -4% 0px', threshold: 0.08 });
    nodes.forEach(function (el) { io.observe(el); });
})();
</script>
@endsection
