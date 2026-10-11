<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\InvestorInvestmentRequest;
use App\Models\Deal;
use App\Models\InvestorInvestment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInvestorInvestmentApiController extends BaseApiController
{
    /**
     * Paginated list of completed investor investments.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = InvestorInvestment::query()
            ->with(['investor:id,name,email', 'startup:id,title'])
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $term = trim($filters['q']);
                $query->where(function ($query) use ($term) {
                    $query->where('investor_name', 'like', "%{$term}%")
                        ->orWhere('investor_email', 'like', "%{$term}%")
                        ->orWhere('startup_name', 'like', "%{$term}%");
                });
            })
            ->when(filled($filters['currency'] ?? null), fn ($q) => $q->where('currency', strtoupper($filters['currency'])))
            ->when(filled($filters['from'] ?? null), fn ($q) => $q->whereDate('completed_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn ($q) => $q->whereDate('completed_at', '<=', $filters['to']));

        $summaryQuery = clone $query;
        $totalRecords = (clone $summaryQuery)->count();
        $totalInvestors = (clone $summaryQuery)->distinct()->count('investor_id');
        $currencyTotals = (clone $summaryQuery)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $investments = $query->latest('completed_at')->latest('id')
            ->paginate((int) $request->query('per_page', 25));

        return $this->success([
            'investments' => $investments,
            'summary' => [
                'total_records' => $totalRecords,
                'total_investors' => $totalInvestors,
                'currency_totals' => $currencyTotals,
            ],
        ]);
    }

    /**
     * Record a new completed investor investment.
     */
    public function store(InvestorInvestmentRequest $request): JsonResponse
    {
        $investor = User::findOrFail($request->integer('investor_id'));
        $startup = Deal::findOrFail($request->integer('deal_id'));

        $investment = InvestorInvestment::create([
            'investor_id' => $investor->id,
            'deal_id' => $startup->id,
            'investor_name' => $investor->name,
            'investor_email' => $investor->email,
            'startup_name' => $startup->title,
            'amount' => $request->input('amount'),
            'currency' => strtoupper($request->string('currency')->toString()),
            'completed_at' => $request->date('completed_at'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return $this->success($investment, 'Investor investment recorded successfully.', 201);
    }

    /**
     * Show single completed investor investment.
     */
    public function show(InvestorInvestment $investorInvestment): JsonResponse
    {
        $investorInvestment->load(['investor:id,name,email', 'startup:id,title', 'creator:id,name']);

        return $this->success($investorInvestment);
    }

    /**
     * Update completed investor investment.
     */
    public function update(InvestorInvestmentRequest $request, InvestorInvestment $investorInvestment): JsonResponse
    {
        $investor = User::findOrFail($request->integer('investor_id'));
        $startup = Deal::findOrFail($request->integer('deal_id'));

        $investorInvestment->update([
            'investor_id' => $investor->id,
            'deal_id' => $startup->id,
            'investor_name' => $investor->name,
            'investor_email' => $investor->email,
            'startup_name' => $startup->title,
            'amount' => $request->input('amount'),
            'currency' => strtoupper($request->string('currency')->toString()),
            'completed_at' => $request->date('completed_at'),
            'updated_by' => $request->user()->id,
        ]);

        return $this->success($investorInvestment, 'Investor investment updated successfully.');
    }

    /**
     * Delete investor investment.
     */
    public function destroy(InvestorInvestment $investorInvestment): JsonResponse
    {
        $investorInvestment->delete();

        return $this->success(null, 'Investor investment deleted successfully.');
    }

    /**
     * Autocomplete suggestions for name, email, or startup.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'in:name,email,startup'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        if ($validated['field'] === 'startup') {
            $items = Deal::query()
                ->select(['id', 'title'])
                ->when($term !== '', fn ($q) => $q->where('title', 'like', "%{$term}%"))
                ->orderBy('title')
                ->limit(10)
                ->get()
                ->map(fn (Deal $deal) => [
                    'id' => $deal->id,
                    'label' => $deal->title,
                    'title' => $deal->title,
                ]);

            return $this->success($items);
        }

        $column = $validated['field'] === 'email' ? 'email' : 'name';
        $items = User::query()
            ->select(['id', 'name', 'email'])
            ->where('role', 'investor')
            ->when($term !== '', function ($q) use ($column, $term) {
                $q->where(function ($inner) use ($column, $term) {
                    $inner->where($column, 'like', "%{$term}%")
                        ->orWhere($column === 'email' ? 'name' : 'email', 'like', "%{$term}%");
                });
            })
            ->orderBy($column)
            ->limit(10)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $validated['field'] === 'email' ? $user->email : $user->name,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return $this->success($items);
    }
}
