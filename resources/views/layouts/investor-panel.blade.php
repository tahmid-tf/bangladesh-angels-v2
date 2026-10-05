<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page_title', 'Investor Dashboard') | Bangladesh Angels</title>
    <link rel="icon" type="image/webp" href="{{ asset('icon.webp') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <livewire:styles />
    @stack('head')
</head>
<body class="investor-shell">
    <button class="investor-mobile-toggle" id="investor-menu-toggle" type="button" aria-controls="investor-sidebar" aria-expanded="false">
        <span class="sr-only">Open investor navigation</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
    </button>

    <aside class="investor-sidebar" id="investor-sidebar">
        <a href="{{ route('home') }}" class="investor-sidebar__brand">
            <img src="{{ asset('bdangels_white.png') }}" alt="Bangladesh Angels">
        </a>
        {{-- <div class="investor-sidebar__identity">
            <img src="{{ auth()->user()->getProfilePhotoUrl() }}" alt="" class="investor-sidebar__avatar">
            <div>
                <strong>{{ auth()->user()->name }}</strong>
                <span>Investor account</span>
            </div>
        </div> --}}
        <nav class="investor-sidebar__nav" aria-label="Investor navigation">
            <p>Workspace</p>
            <a href="{{ route('investor.dashboard') }}" class="{{ request()->routeIs('investor.dashboard') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z" /></svg>
                Dashboard
            </a>
            <a href="{{ route('investor.dashboard') }}#my-investments">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM8 9h8M8 13h5" /></svg>
                My investments
            </a>
            <a href="{{ route('ban-wealth.index') }}" class="{{ request()->routeIs('ban-wealth.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m6 14v-7m5 7V3" /></svg>
                BAN Wealth
            </a>
            <p>Account</p>
            <a href="{{ route('membership-orders.index') }}" class="{{ request()->routeIs('membership-orders.*') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h12v18l-3-2-3 2-3-2-3 2V3zM9 7h6M9 11h6" /></svg>
                Membership receipts
            </a>
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 100-8 4 4 0 000 8zm7 8a7 7 0 00-14 0" /></svg>
                Profile settings
            </a>
            <a href="{{ route('home') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 11l9-8 9 8v9H3v-9z" /></svg>
                Back to website
            </a>
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="investor-sidebar__logout">
            @csrf
            <button type="submit">Sign out <span aria-hidden="true">→</span></button>
        </form>
    </aside>

    <div class="investor-sidebar-backdrop" id="investor-sidebar-backdrop"></div>

    <main class="investor-main">
        <header class="investor-topbar">
            <h1>
                <strong>Hi {{ auth()->user()->name }},</strong>
                <span>Welcome back!</span>
            </h1>
            <div class="investor-topbar__account">
                <button type="button" id="investor-account-toggle" aria-controls="investor-account-menu" aria-expanded="false">
                    <span>{{ auth()->user()->email }}</span>
                    <img src="{{ auth()->user()->getProfilePhotoUrl() }}" alt="User avatar">
                </button>
                <div id="investor-account-menu" class="investor-account-menu" hidden>
                    <a href="{{ route('profile.edit') }}">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </header>
        @yield('page_content')
    </main>

    <script>
        const investorMenu = document.getElementById('investor-sidebar');
        const investorToggle = document.getElementById('investor-menu-toggle');
        const investorBackdrop = document.getElementById('investor-sidebar-backdrop');
        const closeInvestorMenu = () => {
            investorMenu.classList.remove('is-open');
            investorBackdrop.classList.remove('is-open');
            investorToggle.setAttribute('aria-expanded', 'false');
        };
        investorToggle.addEventListener('click', () => {
            const open = investorMenu.classList.toggle('is-open');
            investorBackdrop.classList.toggle('is-open', open);
            investorToggle.setAttribute('aria-expanded', String(open));
        });
        investorBackdrop.addEventListener('click', closeInvestorMenu);
        document.addEventListener('keydown', event => { if (event.key === 'Escape') closeInvestorMenu(); });

        const investorAccountToggle = document.getElementById('investor-account-toggle');
        const investorAccountMenu = document.getElementById('investor-account-menu');
        investorAccountToggle.addEventListener('click', () => {
            const isOpen = investorAccountMenu.hidden;
            investorAccountMenu.hidden = !isOpen;
            investorAccountToggle.setAttribute('aria-expanded', String(isOpen));
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.investor-topbar__account')) {
                investorAccountMenu.hidden = true;
                investorAccountToggle.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
    <livewire:scripts />
    @stack('scripts')
</body>
</html>
