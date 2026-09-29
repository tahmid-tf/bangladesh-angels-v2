@extends('layouts.admin')
@section('page_title', 'Subscriptions | Dashboard')
@section('page_content')

@php
    $subscriptionRows = $subscriptions->filter(fn ($subscription) => $subscription->user && $subscription->user->id !== auth()->id());
    $missingRows = $usersWithoutSubscriptions->filter(fn ($user) => $user->id !== auth()->id());
    $totalRows = $subscriptionRows->count() + $missingRows->count();
    $paidCount = $subscriptionRows->filter(fn ($subscription) => (bool) $subscription->payment_id)->count();
    $unpaidCount = $totalRows - $paidCount;
@endphp

<section class="container mx-auto p-4 md:p-6">
    <div class="bg-white rounded-xl shadow p-4 md:p-5 mb-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-lg md:text-xl font-bold text-gray-900">Subscriptions</h1>
                <p class="text-sm text-gray-600 mt-1">Manage membership tiers, pricing, and member subscription records in one place.</p>
                <a href="{{ route('admin.membership-orders.index') }}" class="text-sm text-green-700 underline">Order receipts, consent &amp; communication records</a>
            </div>
            <a href="{{ route('member.add') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0a5554] px-4 py-2 text-sm font-semibold text-white hover:bg-[#084646]">
                + Add New Member
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total records</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalRows }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Paid</p>
            <p class="text-2xl font-bold text-green-700 mt-1">{{ $paidCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Unpaid / Missing</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ $unpaidCount }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-4 md:p-5 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Membership Tiers & Pricing</h2>
                <p class="text-sm text-gray-600">Shown on the public plans page. Slugs remain fixed for payment mapping.</p>
            </div>
        </div>

        @if ($errors->has('tiers'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $errors->first('tiers') }}
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.subscription-tiers.update') }}">
            @csrf
            <div class="space-y-4">
                @foreach ($tiers as $tier)
                    <article class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $tier->name }}</p>
                            <span class="text-xs font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded">slug: {{ $tier->slug }}</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Display name</label>
                                <input type="text" name="tiers[{{ $tier->id }}][name]" value="{{ old('tiers.'.$tier->id.'.name', $tier->name) }}" class="w-full border-gray-300 rounded-lg shadow-sm" required maxlength="100">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Yearly price (USD)</label>
                                <input type="number" name="tiers[{{ $tier->id }}][price_yearly]" step="0.01" min="0" value="{{ old('tiers.'.$tier->id.'.price_yearly', $tier->price_yearly) }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Sort order</label>
                                <input type="number" name="tiers[{{ $tier->id }}][sort_order]" min="0" value="{{ old('tiers.'.$tier->id.'.sort_order', $tier->sort_order) }}" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                            </div>
                            <div class="flex flex-col gap-2 justify-center pt-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="tiers[{{ $tier->id }}][is_active]" value="0">
                                    <input type="checkbox" name="tiers[{{ $tier->id }}][is_active]" value="1" {{ old('tiers.'.$tier->id.'.is_active', $tier->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    Active on plans page
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="tiers[{{ $tier->id }}][is_highlighted]" value="0">
                                    <input type="checkbox" name="tiers[{{ $tier->id }}][is_highlighted]" value="1" {{ old('tiers.'.$tier->id.'.is_highlighted', $tier->is_highlighted ? '1' : '0') === '1' ? 'checked' : '' }}>
                                    Show "Popular" badge
                                </label>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Included features (one per line)</label>
                                <textarea name="tiers[{{ $tier->id }}][features_included]" rows="5" class="w-full border-gray-300 rounded-lg shadow-sm text-sm font-mono">{{ old('tiers.'.$tier->id.'.features_included', $tier->features_included) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Excluded features (one per line)</label>
                                <textarea name="tiers[{{ $tier->id }}][features_excluded]" rows="5" class="w-full border-gray-300 rounded-lg shadow-sm text-sm font-mono">{{ old('tiers.'.$tier->id.'.features_excluded', $tier->features_excluded) }}</textarea>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <button type="submit" class="mt-5 inline-flex items-center rounded-lg bg-[#0a5554] px-5 py-2 text-sm font-semibold text-white hover:bg-[#084646]">
                Save tiers & prices
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow p-4 md:p-5 mb-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="planFilter" class="block text-xs font-semibold text-gray-600 mb-1">Plan</label>
                <select id="planFilter" onchange="filterTable()" class="border-gray-300 rounded-lg shadow-sm text-gray-700">
                    <option value="all">All plans</option>
                    @foreach ($tiers->sortBy('sort_order') as $t)
                        <option value="{{ strtolower($t->slug) }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="paymentFilter" class="block text-xs font-semibold text-gray-600 mb-1">Payment status</label>
                <select id="paymentFilter" onchange="filterTable()" class="border-gray-300 rounded-lg shadow-sm text-gray-700">
                    <option value="all">All statuses</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>
            <div class="flex-1 min-w-[220px]">
                <label for="searchInput" class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Name, email, plan, transaction ID" class="w-full border-gray-300 rounded-lg shadow-sm px-4 py-2">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto max-h-[65vh] overflow-y-auto">
            <table id="subscriptionTable" class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-gray-700">Member</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Plan</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Start</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Expiry</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Amount</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Transaction</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Method</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($subscriptionRows as $subscription)
                        <tr class="hover:bg-gray-50" data-payment="{{ $subscription->payment_id ? 'paid' : 'unpaid' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $subscription->user->getProfilePhotoUrl() }}" alt="Profile" class="rounded-full w-10 h-10 object-cover">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $subscription->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $subscription->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="plan-value">{{ ucfirst($subscription->plan) }}</span></td>
                            <td class="px-4 py-3 text-gray-700">{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('M d, Y') : \Carbon\Carbon::parse($subscription->created_at)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($subscription->payment)
                                    {{ $subscription->payment->currency }} {{ number_format($subscription->payment->amount, 2) }}
                                @else
                                    ${{ number_format($subscription->price, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($subscription->payment_id)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Paid</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                {{ $subscription->payment ? ($subscription->payment->transaction_id ?? $subscription->payment->merchant_txnid ?? 'N/A') : 'No payment' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $subscription->payment ? ucfirst($subscription->payment->payment_method) : 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('member.edit', $subscription->user->id) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#0a5554] text-white text-xs font-semibold hover:bg-[#084646]">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                    @endforelse

                    @foreach ($missingRows as $user)
                        <tr class="hover:bg-gray-50" data-payment="unpaid">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->getProfilePhotoUrl() }}" alt="Profile" class="rounded-full w-10 h-10 object-cover">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="plan-value">{{ ucfirst($user->account_status) }}</span></td>
                            <td class="px-4 py-3 text-gray-700">{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M d, Y') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-700">N/A</td>
                            <td class="px-4 py-3 text-gray-700">N/A</td>
                            <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Unpaid</span></td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">No payment</td>
                            <td class="px-4 py-3 text-gray-500">N/A</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('member.edit', $user->id) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#0a5554] text-white text-xs font-semibold hover:bg-[#084646]">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($totalRows === 0)
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-600">No subscription records found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    function filterTable() {
        const planFilter = document.getElementById('planFilter').value;
        const paymentFilter = document.getElementById('paymentFilter').value;
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('subscriptionTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const planCell = row.querySelector('.plan-value');

            if (!planCell) continue;

            const planText = planCell.textContent.toLowerCase();
            const rowText = row.textContent.toLowerCase();
            const paymentStatus = row.getAttribute('data-payment');

            const planMatch = planFilter === 'all' || planText.includes(planFilter);
            const paymentMatch = paymentFilter === 'all' || paymentFilter === paymentStatus;
            const searchMatch = rowText.includes(searchInput);

            row.style.display = planMatch && paymentMatch && searchMatch ? '' : 'none';
        }
    }
</script>
@endsection
