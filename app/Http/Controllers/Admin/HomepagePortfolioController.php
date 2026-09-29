<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\HomepagePortfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomepagePortfolioController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        // Automatically clean up any deals that are no longer listed in portfolio
        $invalid = HomepagePortfolio::query()
            ->whereDoesntHave('deal', fn ($q) => $q->inPortfolio())
            ->get();

        if ($invalid->isNotEmpty()) {
            HomepagePortfolio::whereIn('id', $invalid->pluck('id'))->delete();
            $this->normalizeSortOrders();
        }

        $selectedPortfolios = HomepagePortfolio::query()
            ->with(['deal.media'])
            ->ordered()
            ->get();

        $selectedDealIds = $selectedPortfolios->pluck('deal_id')->all();

        $availableDeals = Deal::query()
            ->with('media')
            ->inPortfolio()
            ->whereNotIn('id', $selectedDealIds)
            ->orderBy('title')
            ->get();

        $allPortfolioDeals = Deal::query()
            ->with('media')
            ->inPortfolio()
            ->orderBy('title')
            ->get();

        return view('admin.homepage-portfolios.index', [
            'selectedPortfolios' => $selectedPortfolios,
            'availableDeals' => $availableDeals,
            'allPortfolioDeals' => $allPortfolioDeals,
            'maxSelections' => HomepagePortfolio::MAX_SELECTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (HomepagePortfolio::query()->count() >= HomepagePortfolio::MAX_SELECTIONS) {
            return redirect()
                ->route('admin.homepage-portfolios')
                ->with('error', 'The homepage can show a maximum of ' . HomepagePortfolio::MAX_SELECTIONS . ' selected portfolios.');
        }

        $validated = $request->validate([
            'deal_id' => [
                'required',
                'integer',
                'exists:deals,id',
                Rule::unique('homepage_portfolios', 'deal_id'),
            ],
        ], [
            'deal_id.unique' => 'This portfolio company is already selected for the homepage.',
            'deal_id.exists' => 'The selected company was not found.',
        ]);

        $deal = Deal::findOrFail($validated['deal_id']);

        if (! $deal->isListedInPortfolio()) {
            return redirect()
                ->route('admin.homepage-portfolios')
                ->with('error', 'Only companies listed in the portfolio can be featured on the homepage.');
        }

        $nextOrder = (int) (HomepagePortfolio::query()->max('sort_order') ?? 0) + 1;

        HomepagePortfolio::query()->create([
            'deal_id' => $deal->id,
            'sort_order' => $nextOrder,
        ]);

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', "{$deal->title} has been added to homepage selected portfolios.");
    }

    public function batchDestroy(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:homepage_portfolios,id'],
        ], [
            'ids.required' => 'Please select at least one portfolio to remove.',
            'ids.min' => 'Please select at least one portfolio to remove.',
        ]);

        $count = HomepagePortfolio::query()->whereIn('id', $validated['ids'])->delete();

        $this->normalizeSortOrders();

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', "{$count} " . ($count === 1 ? 'portfolio company' : 'portfolio companies') . ' removed from homepage selection.');
    }

    public function destroy(HomepagePortfolio $homepagePortfolio): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $dealTitle = $homepagePortfolio->deal?->title ?? 'Portfolio company';
        $homepagePortfolio->delete();

        $this->normalizeSortOrders();

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', "{$dealTitle} removed from homepage selected portfolios.");
    }

    public function moveUp(HomepagePortfolio $homepagePortfolio): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $previous = HomepagePortfolio::query()
            ->where('sort_order', '<', $homepagePortfolio->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            $currentOrder = $homepagePortfolio->sort_order;
            $homepagePortfolio->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $currentOrder]);
        }

        $this->normalizeSortOrders();

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', 'Homepage display order updated.');
    }

    public function moveDown(HomepagePortfolio $homepagePortfolio): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $next = HomepagePortfolio::query()
            ->where('sort_order', '>', $homepagePortfolio->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            $currentOrder = $homepagePortfolio->sort_order;
            $homepagePortfolio->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $currentOrder]);
        }

        $this->normalizeSortOrders();

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', 'Homepage display order updated.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['orders'] as $id => $order) {
                HomepagePortfolio::query()
                    ->where('id', $id)
                    ->update(['sort_order' => (int) $order]);
            }
        });

        $this->normalizeSortOrders();

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', 'Homepage portfolio order saved.');
    }

    public function sync(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'deal_ids' => ['nullable', 'array', 'max:' . HomepagePortfolio::MAX_SELECTIONS],
            'deal_ids.*' => ['integer', 'exists:deals,id'],
        ], [
            'deal_ids.max' => 'You can select a maximum of ' . HomepagePortfolio::MAX_SELECTIONS . ' portfolios for the homepage.',
        ]);

        $dealIds = array_values(array_unique(array_filter($validated['deal_ids'] ?? [])));

        if (empty($dealIds)) {
            HomepagePortfolio::query()->delete();

            return redirect()
                ->route('admin.homepage-portfolios')
                ->with('success', 'Homepage custom selections cleared. The homepage will now show the latest ' . HomepagePortfolio::MAX_SELECTIONS . ' portfolio companies automatically.');
        }

        // Verify all chosen deals belong to portfolio
        $validDeals = Deal::query()
            ->whereIn('id', $dealIds)
            ->inPortfolio()
            ->pluck('id')
            ->all();

        if (count($validDeals) !== count($dealIds)) {
            return redirect()
                ->route('admin.homepage-portfolios')
                ->with('error', 'One or more selected deals are not valid portfolio companies.');
        }

        DB::transaction(function () use ($dealIds): void {
            // Delete deals that are no longer selected
            HomepagePortfolio::query()
                ->whereNotIn('deal_id', $dealIds)
                ->delete();

            // Insert newly selected deals and update sort orders based on the order passed
            foreach ($dealIds as $index => $dealId) {
                HomepagePortfolio::query()->updateOrCreate(
                    ['deal_id' => $dealId],
                    ['sort_order' => $index + 1]
                );
            }
        });

        $this->normalizeSortOrders();

        $count = count($dealIds);

        return redirect()
            ->route('admin.homepage-portfolios')
            ->with('success', "Homepage selections updated ({$count} portfolio " . ($count === 1 ? 'company' : 'companies') . ' selected).');
    }

    protected function normalizeSortOrders(): void
    {
        $items = HomepagePortfolio::query()->ordered()->get();

        foreach ($items as $index => $item) {
            $expectedOrder = $index + 1;
            if ($item->sort_order !== $expectedOrder) {
                $item->update(['sort_order' => $expectedOrder]);
            }
        }
    }
}
