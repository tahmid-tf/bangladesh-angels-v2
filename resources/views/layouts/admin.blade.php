<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page_title')</title>
    <link rel="icon" type="image/webp" href="{{ asset('icon.webp') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <livewire:styles />
    @stack('head_styles')

    <style>
        /* Critical CSS */
        body {
            font-family: 'Figtree', sans-serif;
            margin: 0;
            padding: 0;
            overscroll-behavior: none;
        }
        
        /* Loading Screen Styles */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease-in-out;
            z-index: 9999;
        }

        #loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        
        .loading-icon {
            height: 4rem;
            width: auto;
            object-fit: contain;
        }
        
        @media (min-width: 768px) {
            .loading-icon {
                height: 6rem;
            }
        }
        
        @media (min-width: 1024px) {
            .loading-icon {
                height: 8rem;
            }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(() => {
                document.getElementById("loading-screen").classList.add("fade-out");
                setTimeout(() => {
                    document.getElementById("loading-screen").style.display = "none";
                }, 300);
            }, 300);
        });
    </script>
</head>

<body class="admin-shell h-full flex flex-col md:flex-row bg-gray-100">
    <!-- Loading Screen -->
    <div id="loading-screen" role="status" aria-label="Loading admin workspace">
        <div class="admin-loader">
            <img src="{{ asset('icon.webp') }}" alt="" class="loading-icon">
            <span class="admin-loader__line" aria-hidden="true"></span>
            <p>Loading workspace</p>
        </div>
    </div>

    <!-- Mobile Menu Button -->
    <button id="mobileMenuButton" type="button" aria-controls="sidebar" aria-expanded="false" class="md:hidden fixed top-4 right-4 z-50 p-2 bg-[#0a5554] rounded-lg text-white shadow-lg">
        <span class="sr-only">Toggle menu</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>
    </button>

    <!-- Sidebar -->
    <aside id="sidebar" class="flex flex-col w-64 bg-[#0a5554] text-white h-screen fixed md:relative md:translate-x-0 transform -translate-x-full transition-transform duration-300 z-40 overflow-y-auto">
        <a href="{{ route('home') }}" class="p-4 md:p-6 flex justify-center md:justify-start">
            <img src="{{ asset('bdangels_white.png') }}" alt="Bangladesh Angels Logo" class="h-[50px] w-auto">
        </a>

        <nav class="flex-1 flex flex-col pb-6 mt-4" aria-label="Admin navigation">
            <p class="admin-nav-label">Overview</p>
            <a href="{{ route('admin.dashboard') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.dashboard') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <p class="admin-nav-label">Network</p>
            <a href="{{ route('admin.members') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.members*', 'admin.*.members', 'member.*', 'update.account.status') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>
                <span>Members</span>
            </a>
            <p class="admin-nav-label">Content</p>
            <a href="{{ route('admin.blogs.index') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.blogs.*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0015.414 6L13 3.586A2 2 0 0011.586 3H4zm3 4a1 1 0 000 2h6a1 1 0 100-2H7zm0 4a1 1 0 100 2h6a1 1 0 100-2H7z" />
                </svg>
                <span>Blogs</span>
            </a>
            <a href="{{ route('admin.publications.index') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.publications.*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5 3a2 2 0 0 0-2 2v14l7-3 7 3V5a2 2 0 0 0-2-2H5z" /></svg>
                <span>Publications</span>
            </a>
            <a href="{{ route('admin.landing-page-stats') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.landing-page-stats*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3 3a1 1 0 000 2h10a1 1 0 100-2H3zM3 7a1 1 0 000 2h6a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 8a1 1 0 011 1v5h2a1 1 0 110 2h-6a1 1 0 110-2h2V9a1 1 0 011-1z" />
                </svg>
                <span>Homepage Statistics</span>
            </a>
            <a href="{{ route('admin.landing-program-cards') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.landing-program-cards*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h4V3H4zM10 3v14h6a2 2 0 002-2V5a2 2 0 00-2-2h-6z" />
                </svg>
                <span>Homepage Programs</span>
            </a>
            <a href="{{ route('admin.homepage-portfolios') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.homepage-portfolios*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                </svg>
                <span>Homepage Portfolios</span>
            </a>
            <a href="{{ route('admin.team-about-section.edit') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.team-about-section*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zM9 7a1 1 0 112 0 1 1 0 01-2 0zm0 3a1 1 0 011-1h.01a1 1 0 011 1v3a1 1 0 11-2 0v-3z" clip-rule="evenodd" />
                </svg>
                <span>Team About Section</span>
            </a>
            <a href="{{ route('admin.events') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.events*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 002-2h12a2 2 0 002 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                </svg>
                <span>Events</span>
            </a>
            {{-- <a href="{{ route('admin.resource-hub') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.resource-hub*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                </svg>
                <span>DeckVue Content Cards</span>
            </a> --}}
            <a href="{{ route('admin.startup-services') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.startup-services*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4h14v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm3 2a1 1 0 100 2h6a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
                <span>Startup Services</span>
            </a>
            {{-- <a href="{{ route('admin.what-we-do-cards') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.what-we-do-cards*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4zm8 0H8v2h2v-2zm0 3H8v2h2v-2z" />
                </svg>
                <span>What We Do Cards</span>
            </a> --}}
            <a href="{{ route('admin.team-members') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.team-members*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>
                <span>Team Directory</span>
            </a>
            <p class="admin-nav-label">Pipeline</p>
            <a href="{{ route('admin.deals') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.deals*', 'deal.add', 'edit.deal', 'update.deal', 'delete.deal') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                </svg>
                <span>Deals</span>
            </a>
            <a href="{{ route('admin.founder-pitches') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.founder-pitches*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                </svg>
                <span>Founder Pitch Submissions</span>
            </a>
            <a href="{{ route('admin.angel-academy-applications') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.angel-academy-applications*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                </svg>
                <span>Angel Academy Applications</span>
            </a>
            <a href="{{ route('admin.subscriptions') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.subscriptions*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4a2 2 0 00-2 2V4z" />
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                </svg>
                <span>Subscriptions</span>
            </a>
            <a href="{{ route('admin.investments') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.investments*', 'investment.type.*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>Investments</span>
            </a>
            <a href="{{ route('admin.investor-investments.index') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.investor-investments.*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M4 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V8.414A2 2 0 0016.414 7L13 3.586A2 2 0 0011.586 3H4zm2 4a1 1 0 011-1h3a1 1 0 010 2H7a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 010 2H7a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 010 2H7a1 1 0 01-1-1z" />
                </svg>
                <span>Investors Investments</span>
            </a>
            <a href="{{ route('admin.ban-wealth-orders.index') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.ban-wealth-orders.*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 2a1 1 0 011 1v1.07A7.002 7.002 0 0117 11v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a7.002 7.002 0 016-6.93V3a1 1 0 011-1zm0 5a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <span>BAN Wealth Orders</span>
            </a>
            <p class="admin-nav-label">Communication</p>
            <a href="{{ route('admin.mail') }}" class="py-3 px-6 hover:bg-green-600 transition-colors flex items-center space-x-2 {{ request()->routeIs('admin.mail*') ? 'bg-green-600' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                <span>Email Campaigns</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0 h-screen md:h-auto overflow-hidden">
        <!-- Navbar -->
        <header class="admin-topbar flex flex-col md:flex-row justify-between items-center p-4 bg-white shadow sticky top-0 z-30">
            <h1 class="text-lg font-bold mb-2 md:mb-0 text-center md:text-left">
                <strong>Hi {{ auth()->user()->name }},</strong>
                <span class="block md:inline">Welcome back!</span>
            </h1>
            <div class="relative">
                <button type="button" onclick="toggleDropdown()" aria-controls="dropdown-menu" aria-expanded="false" class="focus:outline-none flex items-center space-x-2 p-1 rounded-full hover:bg-gray-100">
                    <span class="hidden md:inline text-sm text-gray-700">{{ auth()->user()->email }}</span>
                    <img src="{{ auth()->user()->getProfilePhotoUrl() }}" 
                         alt="User Avatar" 
                         class="h-10 w-10 rounded-full border-2 border-[#0a5554] object-cover">
                </button>
                <div id="dropdown-menu" class="hidden absolute right-0 mt-2 bg-white border rounded-lg shadow-lg w-48 z-50">
                    <ul class="text-gray-700">
                        <li class="hover:bg-gray-100 rounded-t-lg"><a href="{{ route('profile.edit') }}" class="block px-4 py-2">Profile</a></li>
                        <li class="hover:bg-gray-100"><a href="{{ route('dashboard') }}" class="block px-4 py-2">Dashboard</a></li>
                        <li class="hover:bg-gray-100 rounded-b-lg">
                            <form method="POST" action="{{ route('logout') }}" class="block px-4 py-2">
                                @csrf
                                <button type="submit" class="w-full text-left">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Notification Banner -->
        @auth
            @if (!auth()->user()->email_verified_at)
            <div id="email-verification-banner" class="bg-[rgb(246,240,213)] border-l-4 mx-auto w-[95%] md:w-[95%] mt-4 text-[#494130] p-4 rounded-lg mb-4 relative">
                <p class="pr-8"><strong>Welcome!</strong> Please verify your email address by clicking <a href="{{route('verification.notice')}}" class="font-bold underline">here</a></p>
                <button onclick="dismissBanner()" class="absolute top-2 right-2 text-lg font-bold text-[#494130] hover:text-gray-800">×</button>
            </div>
            @endif
        @endauth

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-4 bg-gray-50">
            <div class="admin-page {{ request()->routeIs('admin.dashboard') || (request()->routeIs('profile.edit') && auth()->user()->isAdmin()) ? 'admin-page--dashboard' : '' }}">
                @yield('page_content')
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const mobileMenuButton = document.getElementById('mobileMenuButton');

        function initSidebar() {
            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
            } else {
                sidebar.classList.remove('-translate-x-full');
            }
        }

        initSidebar();

        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileMenuButton.setAttribute('aria-expanded', String(!sidebar.classList.contains('-translate-x-full')));
        });

        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        });

        // Dropdown Toggle
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-menu');
            const trigger = document.querySelector('[aria-controls="dropdown-menu"]');
            dropdown.classList.toggle('hidden');
            trigger.setAttribute('aria-expanded', String(!dropdown.classList.contains('hidden')));
        }

        document.addEventListener('click', (event) => {
            const dropdownMenu = document.getElementById('dropdown-menu');
            const isDropdownClick = event.target.closest('#dropdown-menu') || event.target.closest('.admin-topbar button');
            
            if (!isDropdownClick) {
                dropdownMenu.classList.add('hidden');
                document.querySelector('[aria-controls="dropdown-menu"]').setAttribute('aria-expanded', 'false');
            }

            if (window.innerWidth < 768 && !sidebar.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            if (window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
            }
            mobileMenuButton.setAttribute('aria-expanded', 'false');
            document.getElementById('dropdown-menu').classList.add('hidden');
            document.querySelector('[aria-controls="dropdown-menu"]').setAttribute('aria-expanded', 'false');
        });

        window.addEventListener('resize', () => {
            initSidebar();
            document.getElementById('dropdown-menu').classList.add('hidden');
            document.querySelector('[aria-controls="dropdown-menu"]').setAttribute('aria-expanded', 'false');
        });

        // Dismiss Email Verification Banner
        function dismissBanner() {
            document.getElementById('email-verification-banner').style.display = 'none';
        }
    </script>

    <livewire:scripts />
    @stack('scripts')
</body>
</html>
