@extends('layouts.investor-panel')

@section('page_title', 'Investor Dashboard')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush

@section('page_content')
<div class="investor-dashboard">
    <header class="investor-dashboard__header">
        <div>
            <p class="investor-eyebrow">Private portfolio workspace</p>
            <h1>Welcome back, {{ Str::before($user->name, ' ') }}.</h1>
            <p>Track your completed investments and portfolio activity in one place.</p>
        </div>
        <div class="investor-dashboard__date">
            <span>As of</span>
            <strong>{{ now()->format('d M Y') }}</strong>
        </div>
    </header>

    <section class="investor-metrics" aria-label="Portfolio summary">
        <article class="investor-metric investor-metric--primary">
            <span class="investor-metric__icon" aria-hidden="true">↗</span>
            <div>
                <p>Invested value</p>
                @forelse ($currencyTotals as $total)
                    <strong>{{ $total->currency }} {{ number_format((float) $total->total, 2) }}</strong>
                @empty
                    <strong>—</strong>
                @endforelse
                <small>Shown separately by currency</small>
            </div>
        </article>
        <article class="investor-metric">
            <span class="investor-metric__icon" aria-hidden="true">#</span>
            <div><p>Investments</p><strong>{{ number_format($investmentCount) }}</strong><small>Completed transactions</small></div>
        </article>
        <article class="investor-metric">
            <span class="investor-metric__icon" aria-hidden="true">◇</span>
            <div><p>Startups</p><strong>{{ number_format($startupCount) }}</strong><small>Portfolio companies</small></div>
        </article>
        <article class="investor-metric">
            <span class="investor-metric__icon" aria-hidden="true">✓</span>
            <div><p>Latest activity</p><strong class="investor-metric__date-value">{{ $lastCompletedAt ? \Illuminate\Support\Carbon::parse($lastCompletedAt)->format('d M Y') : 'No activity' }}</strong><small>Last completion date</small></div>
        </article>
    </section>

    @if ($investmentCount > 0)
        <section class="investor-charts" aria-label="Portfolio analytics">
            <article class="investor-panel investor-panel--trend">
                <header><div><p class="investor-eyebrow">Capital deployment</p><h2>12-month investment trend</h2></div><span>By currency</span></header>
                <div class="investor-chart-wrap"><canvas id="investment-trend-chart" aria-label="Monthly investment amounts by currency" role="img"></canvas></div>
            </article>
            <article class="investor-panel investor-panel--allocation">
                <header><div><p class="investor-eyebrow">Diversification</p><h2>Startup activity</h2></div><span>By transactions</span></header>
                <div class="investor-chart-wrap"><canvas id="startup-allocation-chart" aria-label="Investment transaction count by startup" role="img"></canvas></div>
            </article>
        </section>
    @else
        <section class="investor-empty">
            <span aria-hidden="true">↗</span>
            <div><p class="investor-eyebrow">Portfolio overview</p><h2>Your investment dashboard is ready.</h2><p>Completed investments recorded by the Bangladesh Angels team will appear here automatically, including currency-separated totals and portfolio charts.</p></div>
        </section>
    @endif

    <section class="investor-panel investor-ledger" id="my-investments">
        <header>
            <div><p class="investor-eyebrow">Personal ledger</p><h2>My investments</h2></div>
            <span>{{ $investmentCount }} {{ Str::plural('record', $investmentCount) }}</span>
        </header>
        <livewire:investor-investments-table />
    </section>

    <footer class="investor-dashboard__footer">Investment records are maintained by Bangladesh Angels administrators. Contact the team if a record needs correction.</footer>
</div>
@endsection

@if ($investmentCount > 0)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.font.family = 'Manrope, ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.color = '#6b7d77';
    const colors = ['#15966f', '#d7a348', '#2d6f8e', '#8769c7', '#d66d5d', '#54a6a4'];
    const monthlyDatasets = @json($monthlyDatasets);
    const currencyFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });

    new Chart(document.getElementById('investment-trend-chart'), {
        type: 'bar',
        data: {
            labels: @json($monthlyLabels),
            datasets: monthlyDatasets.map((dataset, index) => ({
                ...dataset,
                backgroundColor: colors[index % colors.length],
                borderRadius: 5,
                borderSkipped: false,
                maxBarThickness: 24,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 7, boxHeight: 7 } },
                tooltip: { callbacks: { label: context => `${context.dataset.label} ${currencyFormatter.format(context.parsed.y)}` } },
            },
            scales: {
                x: { stacked: false, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6 } },
                y: { beginAtZero: true, grid: { color: 'rgba(9, 47, 41, 0.07)' }, ticks: { callback: value => currencyFormatter.format(value) } },
            },
        },
    });

    new Chart(document.getElementById('startup-allocation-chart'), {
        type: 'doughnut',
        data: {
            labels: @json($startupAllocation->pluck('startup_name')),
            datasets: [{ data: @json($startupAllocation->pluck('investment_count')), backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 5 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '67%',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, boxHeight: 7, padding: 14 } } },
        },
    });
});
</script>
@endpush
@endif
