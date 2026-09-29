<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestorInvestmentRequest;
use App\Models\Deal;
use App\Models\InvestorInvestment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestorInvestmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $filters = $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date', 'after_or_equal:from'],
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
            ->when(filled($filters['currency'] ?? null), fn($query) => $query->where('currency', strtoupper($filters['currency'])))
            ->when(filled($filters['from'] ?? null), fn($query) => $query->whereDate('completed_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn($query) => $query->whereDate('completed_at', '<=', $filters['to']));

        $summaryQuery   = clone $query;
        $totalRecords   = (clone $summaryQuery)->count();
        $totalInvestors = (clone $summaryQuery)->distinct()->count('investor_id');
        $currencyTotals = (clone $summaryQuery)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $investments = $query
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $availableCurrencies = InvestorInvestment::query()
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency');

        return view('admin.investor-investments.index', compact(
            'investments',
            'filters',
            'totalRecords',
            'totalInvestors',
            'currencyTotals',
            'availableCurrencies',
        ));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.investor-investments.create', [
            'investorInvestment' => new InvestorInvestment,
        ]);
    }

    public function store(InvestorInvestmentRequest $request): RedirectResponse
    {
        $investor = User::query()->findOrFail($request->integer('investor_id'));
        $startup  = Deal::query()->findOrFail($request->integer('deal_id'));

        InvestorInvestment::create($this->payload($request, $investor, $startup) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.investor-investments.create')
            ->with('success', 'Investor investment recorded successfully. Add another record.');
    }

    public function show(Request $request, InvestorInvestment $investorInvestment): View
    {
        $this->authorizeAdmin($request);
        $investorInvestment->load(['investor:id,name,email', 'startup:id,title', 'creator:id,name', 'updater:id,name']);

        return view('admin.investor-investments.show', compact('investorInvestment'));
    }

    public function edit(Request $request, InvestorInvestment $investorInvestment): View
    {
        $this->authorizeAdmin($request);

        return view('admin.investor-investments.edit', compact('investorInvestment'));
    }

    public function update(InvestorInvestmentRequest $request, InvestorInvestment $investorInvestment): RedirectResponse
    {
        $investor = User::query()->findOrFail($request->integer('investor_id'));
        $startup  = Deal::query()->findOrFail($request->integer('deal_id'));

        $investorInvestment->update($this->payload($request, $investor, $startup) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.investor-investments.show', $investorInvestment)
            ->with('success', 'Investor investment updated successfully.');
    }

    public function destroy(Request $request, InvestorInvestment $investorInvestment): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $investorInvestment->delete();

        return redirect()
            ->route('admin.investor-investments.index')
            ->with('success', 'Investor investment deleted successfully.');
    }

    public function suggestions(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'field' => ['required', 'in:name,email,startup'],
            'q'     => ['nullable', 'string', 'max:100'],
        ]);
        $term = trim((string) ($validated['q'] ?? ''));

        if ($validated['field'] === 'startup') {
            $items = Deal::query()
                ->select(['id', 'title'])
                ->when($term !== '', fn($query) => $query->where('title', 'like', "%{$term}%"))
                ->orderBy('title')
                ->limit(10)
                ->get()
                ->map(fn(Deal $deal) => [
                    'id'    => $deal->id,
                    'label' => $deal->title,
                    'title' => $deal->title,
                ]);

            return response()->json(['data' => $items]);
        }

        $column = $validated['field'] === 'email' ? 'email' : 'name';
        $items  = User::query()
            ->select(['id', 'name', 'email'])
            ->where('role', 'investor')
            ->when($term !== '', function ($query) use ($column, $term) {
                $query->where(function ($query) use ($column, $term) {
                    $query->where($column, 'like', "%{$term}%")
                        ->orWhere($column === 'email' ? 'name' : 'email', 'like', "%{$term}%");
                });
            })
            ->orderBy($column)
            ->limit(10)
            ->get()
            ->map(fn(User $user) => [
                'id'    => $user->id,
                'label' => $validated['field'] === 'email' ? $user->email : $user->name,
                'name'  => $user->name,
                'email' => $user->email,
            ]);

        return response()->json(['data' => $items]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function payload(InvestorInvestmentRequest $request, User $investor, Deal $startup): array
    {
        return [
            'investor_id'    => $investor->id,
            'deal_id'        => $startup->id,
            'investor_name'  => $investor->name,
            'investor_email' => $investor->email,
            'startup_name'   => $startup->title,
            'amount'         => $request->input('amount'),
            'currency'       => strtoupper($request->string('currency')->toString()),
            'completed_at'   => $request->date('completed_at'),
        ];
    }
}
