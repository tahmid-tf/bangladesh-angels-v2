<?php

namespace App\Http\Controllers;

use App\Models\InvestorInvestment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestorDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isInvestor(), 403);

        $user = $request->user();
        $baseQuery = InvestorInvestment::query()->where('investor_id', $user->id);

        $currencyTotals = (clone $baseQuery)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $investmentCount = (clone $baseQuery)->count();
        $startupCount = (clone $baseQuery)->distinct()->count('deal_id');
        $lastCompletedAt = (clone $baseQuery)->max('completed_at');

        $chartStart = CarbonImmutable::now()->startOfMonth()->subMonths(11);
        $monthlyInvestments = (clone $baseQuery)
            ->whereDate('completed_at', '>=', $chartStart->toDateString())
            ->get(['amount', 'currency', 'completed_at']);

        $months = collect(range(0, 11))->map(fn (int $offset) => $chartStart->addMonths($offset));
        $monthlyLabels = $months->map(fn (CarbonImmutable $month) => $month->format('M Y'));
        $currencies = $monthlyInvestments->pluck('currency')->unique()->sort()->values();
        $monthlyDatasets = $currencies->map(function (string $currency) use ($months, $monthlyInvestments) {
            return [
                'label' => $currency,
                'data' => $months->map(function (CarbonImmutable $month) use ($currency, $monthlyInvestments) {
                    return (float) $monthlyInvestments
                        ->filter(fn (InvestorInvestment $investment) => $investment->currency === $currency
                            && $investment->completed_at->format('Y-m') === $month->format('Y-m'))
                        ->sum('amount');
                })->values(),
            ];
        })->values();

        $startupAllocation = (clone $baseQuery)
            ->selectRaw('startup_name, COUNT(*) as investment_count')
            ->groupBy('startup_name')
            ->orderByDesc('investment_count')
            ->limit(8)
            ->get();

        return view('investor.dashboard', compact(
            'user',
            'currencyTotals',
            'investmentCount',
            'startupCount',
            'lastCompletedAt',
            'monthlyLabels',
            'monthlyDatasets',
            'startupAllocation',
        ));
    }
}
