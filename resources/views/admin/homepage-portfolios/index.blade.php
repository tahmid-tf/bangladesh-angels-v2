@extends('layouts.admin')
@section('page_title', 'Homepage Selected Portfolios | Dashboard')
@section('page_content')
<div class="container w-full p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-5 bg-white rounded-lg shadow">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">Homepage — Selected Portfolios</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $selectedPortfolios->count() > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                    {{ $selectedPortfolios->count() }} / {{ $maxSelections }} Selected
                </span>
            </div>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                Choose the portfolio companies to showcase in the homepage Portfolio section. You can select between 1 and {{ $maxSelections }} portfolios (e.g. 3, 4, or up to {{ $maxSelections }}).
                When custom portfolios are selected, they will appear on the homepage in your chosen order.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('home') }}#portfolio" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-gray-100 text-[#0a5554] text-sm font-semibold rounded-lg hover:bg-gray-200 transition flex items-center gap-1.5">
                <span>View on Homepage</span>
                <span aria-hidden="true">&nearr;</span>
            </a>
            <a href="{{ route('admin.deals.portfolio') }}" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">
                All Portfolio Deals
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- KPI / Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-emerald-600">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Homepage Selections</span>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-2xl font-bold text-gray-900">{{ $selectedPortfolios->count() }} <span class="text-sm font-normal text-gray-500">/ {{ $maxSelections }} max</span></div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $selectedPortfolios->count() > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $selectedPortfolios->count() > 0 ? 'Custom Active' : 'Default Mode' }}
                </span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3 overflow-hidden">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300" style="width: {{ ($selectedPortfolios->count() / $maxSelections) * 100 }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                @if ($selectedPortfolios->count() >= $maxSelections)
                    Maximum number of portfolios reached.
                @elseif ($selectedPortfolios->count() > 0)
                    You can add {{ $maxSelections - $selectedPortfolios->count() }} more {{ $maxSelections - $selectedPortfolios->count() === 1 ? 'company' : 'companies' }}.
                @else
                    Showing latest {{ $maxSelections }} companies by default.
                @endif
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-600">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Homepage Display Mode</span>
            <div class="mt-2">
                @if ($selectedPortfolios->count() > 0)
                    <p class="text-lg font-bold text-gray-900 flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        Selected Portfolios
                    </p>
                    <p class="text-xs text-gray-500 mt-2">The homepage displays only the {{ $selectedPortfolios->count() }} custom selected companies in the exact order below.</p>
                @else
                    <p class="text-lg font-bold text-gray-900 flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-gray-400 inline-block"></span>
                        Default (Latest Deals)
                    </p>
                    <p class="text-xs text-gray-500 mt-2">No selections active yet. The homepage automatically shows the latest {{ $maxSelections }} published portfolio companies.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-teal-600">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Available Pool</span>
            <div class="mt-2 flex items-baseline justify-between">
                <div class="text-2xl font-bold text-gray-900">{{ $allPortfolioDeals->count() }}</div>
                <span class="text-xs text-gray-500">{{ $availableDeals->count() }} unselected</span>
            </div>
            <p class="text-xs text-gray-500 mt-3">Total companies listed in portfolio (either marked 'Portfolio only' or 'Also show in Portfolio').</p>
        </div>
    </div>

    <!-- Quick Add Form -->
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-base font-semibold text-gray-900 mb-2">Add a Portfolio Company to Homepage</h2>
        @if ($selectedPortfolios->count() >= $maxSelections)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>Maximum limit of <strong>{{ $maxSelections }} portfolios</strong> has been reached. Remove a portfolio from the list below or use the Batch Selection panel to replace selections.</span>
            </div>
        @elseif ($availableDeals->isEmpty())
            <div class="bg-gray-50 border border-gray-200 text-gray-600 text-sm px-4 py-3 rounded-lg">
                All available portfolio companies are already selected for the homepage.
            </div>
        @else
            <form action="{{ route('admin.homepage-portfolios.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                @csrf
                <div class="flex-1">
                    <label for="deal_id" class="sr-only">Choose a portfolio company</label>
                    <select name="deal_id" id="deal_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm py-2.5 px-3">
                        <option value="">-- Select a portfolio company to add --</option>
                        @foreach ($availableDeals as $deal)
                            <option value="{{ $deal->id }}">
                                {{ $deal->title }} — {{ $deal->sector ?: 'Portfolio' }} ({{ $deal->type === 'portfolio' ? 'Portfolio only' : 'Invest & Portfolio' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm text-sm transition flex items-center justify-center gap-2 whitespace-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span>Add to Homepage</span>
                </button>
            </form>
        @endif
    </div>

    <!-- Selected Portfolios List (Livewire Component for instant reordering and management) -->
    <livewire:homepage-portfolios-table />

    <!-- Batch Selection / Quick Select Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Batch Selection (Select up to {{ $maxSelections }} Portfolios)</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Quickly check/uncheck portfolio companies to show on the homepage. You can select 3, 4, or up to {{ $maxSelections }} companies.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="batchCountBadge" class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
                        <span id="batchCount">{{ $selectedPortfolios->count() }}</span> / {{ $maxSelections }} selected
                    </span>
                </div>
            </div>
        </div>

        <form id="batchForm" action="{{ route('admin.homepage-portfolios.sync') }}" method="POST" class="p-5">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto p-1">
                @forelse ($allPortfolioDeals as $deal)
                    @php
                        $isSelected = $selectedPortfolios->contains('deal_id', $deal->id);
                    @endphp
                    <label class="portfolio-item-card flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition {{ $isSelected ? 'border-emerald-500 bg-emerald-50/40 shadow-sm' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}">
                        <input type="checkbox" 
                            name="deal_ids[]" 
                            value="{{ $deal->id }}" 
                            class="deal-checkbox mt-1 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500" 
                            {{ $isSelected ? 'checked' : '' }}
                            onchange="updateSelectionCount()">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img src="{{ $deal->getLogoUrl() }}" alt="{{ $deal->title }}" class="h-8 w-8 rounded-full object-cover border border-gray-200 shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $deal->title }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $deal->sector ?: 'Portfolio' }}</p>
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="col-span-full py-6 text-center text-sm text-gray-500">
                        No portfolio deals found. Please create deals with type 'Portfolio' or 'Also show in Portfolio' first.
                    </div>
                @endforelse
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p id="batchWarning" class="text-xs text-amber-700 hidden font-medium">
                    Maximum of {{ $maxSelections }} portfolios reached. Uncheck a company to select another.
                </p>
                <div class="flex items-center gap-3 ml-auto">
                    <button type="submit" id="saveBatchBtn" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm text-sm transition">
                        Save Selected Portfolios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function updateSelectionCount() {
        const checkboxes = document.querySelectorAll('.deal-checkbox');
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        const count = checked.length;
        const max = {{ $maxSelections }};

        const countEl = document.getElementById('batchCount');
        const warningEl = document.getElementById('batchWarning');
        const badgeEl = document.getElementById('batchCountBadge');

        if (countEl) countEl.textContent = count;

        if (count >= max) {
            checkboxes.forEach(cb => {
                if (!cb.checked) {
                    cb.disabled = true;
                    cb.closest('label').classList.add('opacity-50', 'cursor-not-allowed');
                }
            });
            if (warningEl) warningEl.classList.remove('hidden');
            if (badgeEl) {
                badgeEl.className = 'text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800';
            }
        } else {
            checkboxes.forEach(cb => {
                cb.disabled = false;
                cb.closest('label').classList.remove('opacity-50', 'cursor-not-allowed');
            });
            if (warningEl) warningEl.classList.add('hidden');
            if (badgeEl) {
                badgeEl.className = 'text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800';
            }
        }

        // Update card styles
        checkboxes.forEach(cb => {
            const label = cb.closest('label');
            if (cb.checked) {
                label.classList.add('border-emerald-500', 'bg-emerald-50/40', 'shadow-sm');
                label.classList.remove('border-gray-200');
            } else {
                label.classList.remove('border-emerald-500', 'bg-emerald-50/40', 'shadow-sm');
                label.classList.add('border-gray-200');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateSelectionCount();
    });
</script>
@endsection
