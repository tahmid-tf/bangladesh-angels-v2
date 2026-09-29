<div>
    <div class="investor-ledger__filters">
        <label class="investor-ledger__search">
            <span class="sr-only">Search investments</span>
            <input
                type="search"
                wire:model.defer="search"
                wire:keydown.enter="applySearch"
                placeholder="Search startup, amount, currency or date"
            >
        </label>

        <label class="investor-ledger__currency">
            <span class="sr-only">Filter by currency</span>
            <select wire:model.live="currency">
                <option value="">All currencies</option>
                @foreach ($currencies as $availableCurrency)
                    <option value="{{ $availableCurrency }}">{{ $availableCurrency }}</option>
                @endforeach
            </select>
        </label>

        <button type="button" wire:click="applySearch" class="investor-ledger__search-button">
            Search
        </button>

        <span wire:loading wire:target="applySearch,currency" class="investor-ledger__loading">Updating...</span>
    </div>

    <div class="investor-ledger__table-wrap">
        <table>
            <thead><tr><th>Startup</th><th>Amount</th><th>Currency</th><th>Completed</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($investments as $investment)
                    <tr wire:key="investor-investment-{{ $investment->id }}">
                        <td><span class="investor-startup-mark" aria-hidden="true">{{ Str::upper(Str::substr($investment->startup_name, 0, 1)) }}</span><strong>{{ $investment->startup_name }}</strong></td>
                        <td><strong>{{ number_format((float) $investment->amount, 2) }}</strong></td>
                        <td><span class="investor-currency">{{ $investment->currency }}</span></td>
                        <td>{{ $investment->completed_at->format('d M Y') }}</td>
                        <td><span class="investor-status"><i></i> Completed</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="investor-ledger__empty">No investments match your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($investments->hasPages())
        <div class="investor-ledger__pagination">{{ $investments->links() }}</div>
    @endif
</div>
