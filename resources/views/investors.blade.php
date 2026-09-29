@extends('layouts.guest')
@section('page_title', 'Our Investors | Bangladesh Angels Network Limited')

@push('head_meta')
    <x-seo-meta
        title="Our Investors | Bangladesh Angels Network Limited"
        description="Join Bangladesh Angels’ global network of angel investors — executives and operators backing early-stage startups with capital and expertise."
        :canonical="route('investors')"
        :image="asset('investor_cover.webp')"
    />
@endpush

@section('page_content')
<div class="w-full max-w-6xl mx-auto px-4 sm:px-6">
    {{-- 1. Become an Investor CTA --}}
    <section class="bg-[#0a5554] rounded-3xl py-10 sm:py-12 text-white shadow-lg mb-8 sm:mb-10">
        <div class="container mx-auto px-6 lg:flex lg:items-center lg:justify-between lg:gap-12">
            <div class="lg:w-1/2">
                <h1 class="text-3xl sm:text-4xl font-extrabold mb-4 tracking-tight">Our Angel Investors</h1>
                <p class="text-base sm:text-lg leading-relaxed text-white/90">
                    Join a global network of executives and operators who have built and expanded companies all over the world.
                </p>
                <div class="mt-8">
                    <a href="{{ auth()->check() && auth()->user()->hasPaidMembership() ? route('dashboard') : route('investor.signup') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-sm sm:text-base bg-white text-[#0a5554] shadow-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#0a5554] transition">
                        {{ auth()->check() && auth()->user()->hasPaidMembership() ? 'Investor Dashboard' : 'Become an Investor' }}
                    </a>
                </div>
            </div>
            <div class="lg:w-1/2 mt-8 lg:mt-0">
                <img src="{{ asset('investor_cover.webp') }}" alt="Investors" class="w-full h-auto rounded-xl shadow-md" width="640" height="400" loading="eager">
            </div>
        </div>
    </section>
</div>

{{-- 2. Our Membership Plans (pricing) — hidden for logged-in paid members --}}
@if ($showMembershipPlans ?? true)
<section class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-12 border-t border-gray-100" aria-label="Our membership plans">
    @include('partials.subscription-plans', [
        'tiers' => $tiers,
        'tierSectionTitle' => 'Our Membership Plans',
        'tierSectionSubtitle' => 'Choose the tier that fits how you invest with Bangladesh Angels Network.',
    ])
</section>
@endif

{{-- 3. Investor highlights — unified spotlight layout --}}
<section class="relative border-t border-[#d4ebe3]" aria-labelledby="investor-highlight-heading">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-[#eef8f4] via-white to-[#f8fcfa]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[#36b37e]/35 to-transparent" aria-hidden="true"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
        <header class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.2em] text-[#18736a] mb-3">Community spotlight</p>
            <h2 id="investor-highlight-heading" class="text-3xl sm:text-4xl font-bold text-[#0f3d34] tracking-tight">Investor highlights</h2>
            <p class="mt-4 text-base sm:text-lg text-gray-600 leading-relaxed">
                Featured members of Bangladesh Angels Network — operators and angels backing the next generation of founders.
            </p>
        </header>

        <div id="investor-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 sm:gap-8">
            @forelse ($investors as $investor)
                @php
                    $hasQuote = $investor->hasPublicFeaturedTestimonial();
                @endphp
                <article class="relative flex flex-col h-full rounded-2xl border border-[#c5e6d8]/90 bg-white/90 backdrop-blur-sm shadow-[0_4px_24px_-4px_rgba(15,61,52,0.08)] hover:shadow-[0_20px_40px_-12px_rgba(15,61,52,0.12)] hover:border-[#36b37e]/45 transition-all duration-300 overflow-hidden">
                    <div class="h-1 w-full shrink-0 bg-gradient-to-r from-[#0a5554] via-[#36b37e] to-[#0a5554]" aria-hidden="true"></div>

                    @if ($hasQuote)
                        <div class="relative px-6 sm:px-8 pt-8 pb-6 flex-1">
                            <span class="absolute left-4 top-4 text-7xl sm:text-8xl font-serif text-[#36b37e]/20 leading-none select-none pointer-events-none" aria-hidden="true">&ldquo;</span>
                            <blockquote class="relative z-10">
                                <p class="text-[1.05rem] sm:text-lg text-gray-800 leading-relaxed font-medium text-center sm:text-left">
                                    {{ $investor->featured_testimonial }}
                                </p>
                            </blockquote>
                        </div>
                        <div class="mt-auto border-t border-[#e8f4ef] bg-gradient-to-br from-[#f7fdfb] to-white px-6 sm:px-8 py-6">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                                <div class="relative mx-auto sm:mx-0 shrink-0 aspect-square w-20 sm:w-[5.25rem] overflow-hidden rounded-full ring-2 ring-white shadow-md ring-offset-2 ring-offset-[#f0faf7]">
                                    <img src="{{ $investor->getProfilePhotoUrl() }}" alt="{{ $investor->name }}" width="84" height="84" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover object-center">
                                </div>
                                <div class="text-center sm:text-left flex-1 min-w-0">
                                    <h3 class="text-lg sm:text-xl font-bold text-[#0f3d34] leading-snug">{{ $investor->name }}</h3>
                                    <p class="mt-1 text-sm sm:text-base text-gray-600 leading-snug">{{ $investor->designation }}{{ $investor->company_name ? ', '.$investor->company_name : '' }}</p>
                                    <span class="mt-3 inline-flex items-center rounded-full bg-[#eef8f4] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#18736a] ring-1 ring-inset ring-[#c5e6d8]/80">Featured story</span>
                                    <div class="mt-4 flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-2">
                                        @if ($investor->joining_date)
                                            <span class="text-xs sm:text-sm text-gray-500 inline-flex items-center gap-1.5">
                                                <i class="far fa-calendar-alt text-[#36b37e]" aria-hidden="true"></i>
                                                Member since {{ $investor->joining_date }}
                                            </span>
                                        @endif
                                        @if ($investor->linkedin)
                                            <a href="{{ $investor->linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0a5554] hover:text-[#0f3d34] hover:underline underline-offset-2 transition">
                                                <i class="fab fa-linkedin text-lg" aria-hidden="true"></i>
                                                LinkedIn
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="px-6 sm:px-8 pt-8 pb-2 flex flex-col items-center text-center flex-1">
                            <div class="relative aspect-square w-28 sm:w-32 overflow-hidden rounded-full ring-2 ring-[#36b37e]/20 ring-offset-4 ring-offset-white shadow-lg">
                                <img src="{{ $investor->getProfilePhotoUrl() }}" alt="{{ $investor->name }}" width="128" height="128" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover object-center">
                            </div>
                            <h3 class="mt-6 text-xl sm:text-2xl font-bold text-[#0f3d34] leading-snug">{{ $investor->name }}</h3>
                            <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-xs leading-relaxed">{{ $investor->designation }}{{ ($investor->company_name) ? ', ' . $investor->company_name : '' }}</p>
                            <span class="mt-4 inline-flex items-center rounded-full border border-[#c5e6d8] bg-[#f7fdfb] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#18736a]">Featured investor</span>
                        </div>
                        <div class="mt-auto border-t border-[#e8f4ef] px-6 sm:px-8 py-5">
                            <div class="flex flex-wrap items-center justify-center gap-4">
                                @if ($investor->joining_date)
                                    <span class="text-xs sm:text-sm text-gray-500 inline-flex items-center gap-1.5">
                                        <i class="far fa-calendar-alt text-[#36b37e]" aria-hidden="true"></i>
                                        Member since {{ $investor->joining_date }}
                                    </span>
                                @endif
                                @if ($investor->linkedin)
                                    <a href="{{ $investor->linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0a5554] hover:text-[#0f3d34] hover:underline underline-offset-2 transition">
                                        <i class="fab fa-linkedin text-lg" aria-hidden="true"></i>
                                        LinkedIn
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="col-span-full max-w-lg mx-auto text-center rounded-2xl border border-dashed border-[#c5e6d8] bg-white/80 px-8 py-14 shadow-sm">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#eef8f4] text-[#18736a]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.813-4.146L15 19.128zM12 14a4 4 0 100-8 4 4 0 000 8z" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-[#0f3d34]">Highlights coming soon</p>
                    <p class="mt-2 text-sm sm:text-base text-gray-600 leading-relaxed">We showcase selected members here. Join the network to appear in future spotlights.</p>
                    <a href="{{ auth()->check() && auth()->user()->hasPaidMembership() ? route('dashboard') : route('investor.signup') }}" class="inline-flex mt-8 items-center justify-center rounded-full bg-[#0f3d34] px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-[#156755] transition">
                        {{ auth()->check() && auth()->user()->hasPaidMembership() ? 'Investor Dashboard' : 'Become an Investor' }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
