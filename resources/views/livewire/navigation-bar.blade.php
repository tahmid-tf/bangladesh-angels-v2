@php
    $homeSection = static fn (string $id): string => request()->routeIs('home') ? '#'.$id : route('home').'#'.$id;
@endphp

<section class="ban-site-nav" aria-label="Site header">
    <div class="ban-site-nav__shell">
        <a href="{{ route('home') }}" class="ban-site-nav__brand" title="Bangladesh Angels Network — home">
            <img src="{{ asset('logo.webp') }}" alt="Bangladesh Angels Network" width="200" height="52">
        </a>

        <nav class="ban-site-nav__desktop" aria-label="Primary navigation">
            <ul class="ban-site-nav__list">
                <li><a href="{{ $homeSection('ban-events') }}" class="ban-site-nav__link">Events</a></li>
                @if (!auth()->check() || !auth()->user()->hasPaidMembership())
                    <li><a href="{{ $homeSection('membership-plans') }}" class="ban-site-nav__link">Plans</a></li>
                @endif
                <li><a href="{{ route('startups') }}" class="ban-site-nav__link">Featured Startups</a></li>
                <li><a href="{{ route('portfolio') }}" class="ban-site-nav__link">Portfolio</a></li>
                <li class="ban-site-nav__dropdown">
                    <button type="button" class="ban-site-nav__dropdown-button" aria-haspopup="true">
                        <span>About Us</span><span class="ban-site-nav__chevron" aria-hidden="true">▾</span>
                    </button>
                    <div class="ban-site-nav__dropdown-panel">
                        <a href="{{ route('team') }}">Team</a>
                        <a href="{{ route('about-us') }}" @if(request()->routeIs('about-us')) aria-current="page" @endif>About</a>
                    </div>
                </li>
                <li class="ban-site-nav__dropdown">
                    <button type="button" class="ban-site-nav__dropdown-button" aria-haspopup="true">
                        <span>Programs</span><span class="ban-site-nav__chevron" aria-hidden="true">▾</span>
                    </button>
                    <div class="ban-site-nav__dropdown-panel">
                        {{-- <a href="{{ route('ban-wealth.index') }}">BAN Wealth</a> --}}
                        <a href="{{ route('bwin') }}">BWIN</a>
                        <a href="{{ route('angel-academy') }}">Angel Academy</a>
                        <a href="{{ route('resources') }}">DeckVue</a>
                    </div>
                </li>
                <li class="ban-site-nav__dropdown">
                    <button type="button" class="ban-site-nav__dropdown-button" aria-haspopup="true" @if(request()->routeIs('blogs.*', 'faq', 'publications.*')) aria-current="page" @endif>
                        <span>Learn</span><span class="ban-site-nav__chevron" aria-hidden="true">▾</span>
                    </button>
                    <div class="ban-site-nav__dropdown-panel">
                        <a href="{{ route('blogs.index') }}" @if(request()->routeIs('blogs.*')) aria-current="page" @endif>Blogs</a>
                        <a href="{{ route('publications.index') }}" @if(request()->routeIs('publications.*')) aria-current="page" @endif>Publications</a>
                        <a href="{{ route('faq') }}" @if(request()->routeIs('faq')) aria-current="page" @endif>FAQ</a>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="ban-site-nav__actions">
            @auth
                <div class="ban-site-nav__account">
                    <button type="button" onclick="toggleDropdown(event)" class="ban-site-nav__avatar-button" aria-label="Open account menu" aria-expanded="false" aria-controls="dropdown-menu">
                        <img src="{{ auth()->user()->getProfilePhotoUrl() }}" alt="" class="ban-site-nav__avatar">
                    </button>
                    <div id="dropdown-menu" class="ban-site-nav__account-menu hidden">
                        <ul>
                            <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                            @if (auth()->user()->isAdmin())<li><a href="{{ route('admin.dashboard') }}">Admin Panel</a></li>@endif
                            @if (auth()->user()->isInvestor())<li><a href="{{ route('investor.dashboard') }}">Investor Dashboard</a></li>@endif
                            {{-- <li><a href="{{ route('dashboard') }}">Dashboard</a></li> --}}
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
                            </li>
                        </ul>
                    </div>
                </div>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="ban-site-nav__login">SIGN IN</a>
                <a href="{{ route('investor.signup') }}" class="ban-site-nav__register">REGISTER</a>
            @endguest
        </div>

        <button id="mobile-menu-button" type="button" onclick="toggleMobileMenu()" class="ban-site-nav__mobile-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
            @auth<img src="{{ auth()->user()->getProfilePhotoUrl() }}" alt="" class="ban-site-nav__avatar">@endauth
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>

        <div id="mobile-menu" class="ban-site-nav__mobile-panel">
            <ul>
                @auth
                    <li><span class="ban-site-nav__mobile-profile">{{ auth()->user()->name }}</span></li>
                    <li><a href="{{ auth()->user()->isInvestor() ? route('investor.dashboard') : route('dashboard') }}">Dashboard</a></li>
                    <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                @endauth
                @guest
                    <li><a href="{{ route('login') }}" class="ban-mobile-secondary">Login</a></li>
                    <li><a href="{{ route('investor.signup') }}" class="ban-mobile-primary">Register</a></li>
                @endguest
                <li><a href="{{ $homeSection('ban-events') }}">Events</a></li>
                @if (!auth()->check() || !auth()->user()->hasPaidMembership())
                    <li><a href="{{ $homeSection('membership-plans') }}">Plans</a></li>
                @endif
                <li><a href="{{ route('startups') }}">Featured Startups</a></li>
                <li><a href="{{ route('portfolio') }}">Portfolio</a></li>
                <li>
                    <span>About Us</span>
                    <div class="ban-site-nav__mobile-subnav">
                        <a href="{{ route('team') }}">Team</a>
                        <a href="{{ route('about-us') }}" @if(request()->routeIs('about-us')) aria-current="page" @endif>About</a>
                    </div>
                </li>
                <li>
                    <span>Programs</span>
                    <div class="ban-site-nav__mobile-subnav">
                        {{-- <a href="{{ route('ban-wealth.index') }}">BAN Wealth</a> --}}
                        <a href="{{ route('bwin') }}">BWIN</a>
                        <a href="{{ route('angel-academy') }}">Angel Academy</a>
                        <a href="{{ route('resources') }}">DeckVue</a>
                    </div>
                </li>
                <li>
                    <span>Learn</span>
                    <div class="ban-site-nav__mobile-subnav">
                        <a href="{{ route('blogs.index') }}" @if(request()->routeIs('blogs.*')) aria-current="page" @endif>Blogs</a>
                        <a href="{{ route('publications.index') }}" @if(request()->routeIs('publications.*')) aria-current="page" @endif>Publications</a>
                        <a href="{{ route('faq') }}" @if(request()->routeIs('faq')) aria-current="page" @endif>FAQ</a>
                    </div>
                </li>
                <li><a href="{{ $homeSection('pitch-form') }}" class="ban-mobile-pitch">Pitch your startup</a></li>
                @auth
                    <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form></li>
                @endauth
            </ul>
        </div>
    </div>

    @auth
        @if (!auth()->user()->email_verified_at)
            <div x-data="{ show: true }" x-show="show" class="ban-site-nav__verification">
                <button type="button" @click="show = false" class="ban-site-nav__verification-close" aria-label="Dismiss verification notice">×</button>
                <div>
                    <strong>Welcome!</strong> Please verify your email address by
                    <form method="POST" action="{{ route('verification.send') }}" class="inline">@csrf<button type="submit" class="font-bold underline cursor-pointer">clicking here</button>.</form>
                </div>
                @if (session('status') === 'verification-link-sent' || session('verification-notice'))
                    <p class="mt-2 text-sm font-medium">{{ session('verification-notice') ?? 'A new verification link has been sent to your email address.' }}</p>
                @endif
            </div>
        @endif
    @endauth

    <script>
        function toggleDropdown(event) {
            if (event) event.stopPropagation();
            const menu = document.getElementById('dropdown-menu');
            const trigger = document.querySelector('[aria-controls="dropdown-menu"]');
            if (!menu || !trigger) return;
            const willOpen = menu.classList.contains('hidden');
            menu.classList.toggle('hidden');
            trigger.setAttribute('aria-expanded', String(willOpen));
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const trigger = document.getElementById('mobile-menu-button');
            if (!menu || !trigger) return;
            const willOpen = !menu.classList.contains('is-open');
            menu.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', String(willOpen));
            trigger.setAttribute('aria-label', willOpen ? 'Close navigation menu' : 'Open navigation menu');
        }

        document.addEventListener('click', function (event) {
            const accountMenu = document.getElementById('dropdown-menu');
            const accountTrigger = document.querySelector('[aria-controls="dropdown-menu"]');
            if (accountMenu && accountTrigger && !accountMenu.contains(event.target) && !accountTrigger.contains(event.target)) {
                accountMenu.classList.add('hidden');
                accountTrigger.setAttribute('aria-expanded', 'false');
            }

            const mobileMenu = document.getElementById('mobile-menu');
            const mobileTrigger = document.getElementById('mobile-menu-button');
            if (mobileMenu && mobileTrigger && mobileMenu.classList.contains('is-open') && !mobileMenu.contains(event.target) && !mobileTrigger.contains(event.target)) {
                mobileMenu.classList.remove('is-open');
                mobileTrigger.setAttribute('aria-expanded', 'false');
                mobileTrigger.setAttribute('aria-label', 'Open navigation menu');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            document.getElementById('dropdown-menu')?.classList.add('hidden');
            document.getElementById('mobile-menu')?.classList.remove('is-open');
            document.querySelector('[aria-controls="dropdown-menu"]')?.setAttribute('aria-expanded', 'false');
            const mobileTrigger = document.getElementById('mobile-menu-button');
            if (mobileTrigger) {
                mobileTrigger.setAttribute('aria-expanded', 'false');
                mobileTrigger.setAttribute('aria-label', 'Open navigation menu');
            }
        });

        document.querySelectorAll('#mobile-menu a').forEach(function (link) {
            link.addEventListener('click', function () {
                document.getElementById('mobile-menu')?.classList.remove('is-open');
                const trigger = document.getElementById('mobile-menu-button');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.setAttribute('aria-label', 'Open navigation menu');
                }
            });
        });
    </script>
</section>
