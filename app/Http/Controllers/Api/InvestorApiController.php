<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreBanWealthOrderRequest;
use App\Models\BanWealthOrder;
use App\Models\Commit;
use App\Models\CommitSubmission;
use App\Models\Deal;
use App\Models\Investment;
use App\Models\InvestorInvestment;
use App\Models\MembershipOrder;
use App\Support\BanWealthFunds;
use App\Support\MembershipPolicies;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvestorApiController extends BaseApiController
{
    /**
     * Investor personal dashboard overview.
     */
    public function dashboard(Request $request): JsonResponse
    {
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

        $activeDealsCount = Deal::where('type', '!=', 'portfolio')->where('status', 'active')->count();

        $myRecentInvestments = Investment::with('deal')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return $this->success([
            'summary' => [
                'total_investments' => $investmentCount,
                'startups_backed' => $startupCount,
                'last_investment_date' => $lastCompletedAt,
                'currency_totals' => $currencyTotals,
                'active_deals_available' => $activeDealsCount,
            ],
            'chart' => [
                'labels' => $monthlyLabels,
                'datasets' => $monthlyDatasets,
            ],
            'startup_allocation' => $startupAllocation,
            'recent_activity' => $myRecentInvestments,
        ]);
    }

    /**
     * Active & member-visible deals.
     */
    public function deals(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->canViewPaywalledDeals()) {
            return $this->error('Paywalled deals require an approved paid membership tier.', 403, [
                'account_status' => $user->account_status,
                'is_approved' => (bool) $user->is_approved,
                'is_email_verified' => $user->hasVerifiedEmail(),
            ]);
        }

        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));
        $stage = $request->query('stage');

        $query = Deal::with('media')
            ->where('type', '!=', 'portfolio')
            ->where('status', 'active')
            ->when($type && in_array($type, ['invest', 'commit', 'review']), fn ($q) => $q->where('type', $type))
            ->when($stage, fn ($q) => $q->where('investment_stage', $stage))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('sector', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        $deals = $query->latest()->paginate((int) $request->query('per_page', 12))
            ->through(fn (Deal $deal) => $this->formatDeal($deal));

        return $this->success($deals);
    }

    /**
     * Single deal details.
     */
    public function deal(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        $deal = Deal::where('slug', $slug)->first();

        if (! $deal) {
            return $this->error('Deal not found.', 404);
        }

        if (! $deal->isListedInPortfolio() && ! $user->canViewPaywalledDeals()) {
            return $this->error('Paywalled deals require an approved paid membership tier.', 403);
        }

        $deal->loadMissing('media');

        $hasInvested = Investment::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->exists();

        $hasCommitted = Commit::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->exists();

        $existingCommitSubmission = CommitSubmission::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->first();

        $data = $this->formatDeal($deal, true);
        $data['user_activity'] = [
            'has_invested' => $hasInvested,
            'has_committed' => $hasCommitted,
            'commit_submission' => $existingCommitSubmission,
        ];

        return $this->success($data);
    }

    /**
     * Express investment interest or join WhatsApp group.
     */
    public function invest(Request $request, Deal $deal): JsonResponse
    {
        $user = $request->user();

        if (! $user->canViewPaywalledDeals()) {
            return $this->error('Paywalled deals require an approved paid membership tier.', 403);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:invest,review,interested'],
        ]);

        $requestedType = $validated['type'] ?? null;

        // WhatsApp / Review group
        if ($requestedType === 'review' || $deal->type === 'review') {
            Investment::firstOrCreate([
                'deal_id' => $deal->id,
                'user_id' => $user->id,
                'type' => 'review',
            ]);

            return $this->success([
                'type' => 'review',
                'groupchat_invite_link' => $deal->groupchat_invite_link,
            ], 'Review activity recorded.');
        }

        if ($deal->type === 'commit') {
            if (Commit::where('deal_id', $deal->id)->where('user_id', $user->id)->exists()) {
                return $this->error('You have already committed to this deal.', 422);
            }

            return $this->success([
                'type' => 'commit_required',
                'deal_id' => $deal->id,
            ], 'Commitment required for this deal.');
        }

        $investmentType = match (true) {
            $requestedType === 'invest' && in_array($deal->type, ['invest', 'portfolio'], true) => 'invest',
            in_array($deal->type, ['invest', 'portfolio'], true) => 'interested',
            default => 'interested',
        };

        $duplicate = Investment::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->where('type', $investmentType)
            ->exists();

        if ($duplicate) {
            return $this->error('You have already recorded this activity for this deal.', 422);
        }

        $investment = Investment::create([
            'deal_id' => $deal->id,
            'user_id' => $user->id,
            'type' => $investmentType,
        ]);

        $msg = $investmentType === 'invest'
            ? 'Your investment interest has been recorded. You will receive details from the lead investment analyst shortly.'
            : 'Your interest has been recorded.';

        return $this->success([
            'investment' => $investment,
            'invest_link' => $deal->invest_link,
        ], $msg, 201);
    }

    /**
     * Submit commit pledge to a deal.
     */
    public function commit(Request $request, Deal $deal): JsonResponse
    {
        $user = $request->user();

        if (! $user->canViewPaywalledDeals()) {
            return $this->error('Paywalled deals require an approved paid membership tier.', 403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $existingCommit = Commit::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->first();

        if ($existingCommit) {
            return $this->error('You have already committed to this deal.', 422);
        }

        $commit = Commit::create([
            'deal_id' => $deal->id,
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'deadline' => $validated['deadline'],
        ]);

        return $this->success([
            'commit' => $commit,
            'commit_link' => $deal->commit_link,
        ], 'Commitment recorded successfully!', 201);
    }

    /**
     * Submit detailed formal commit document / submission.
     */
    public function commitSubmission(Request $request, Deal $deal): JsonResponse
    {
        $user = $request->user();

        if ($user->isFree()) {
            return $this->error('Commit submissions require a paid membership tier.', 403);
        }

        if ($deal->type === 'portfolio') {
            return $this->error('Cannot commit to portfolio companies.', 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:50', 'regex:/^[0-9+()\-\s.]+$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'currency' => ['required', Rule::in(['BDT', 'USD', 'GBP', 'EUR', 'SGD', 'AED'])],
        ]);

        $submission = CommitSubmission::updateOrCreate(
            [
                'deal_id' => $deal->id,
                'user_id' => $user->id,
            ],
            [
                'name' => trim($validated['name']),
                'email' => trim($validated['email']),
                'whatsapp_number' => trim($validated['whatsapp_number']),
                'company_name' => $deal->title,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
            ]
        );

        return $this->success($submission, 'Commit submission recorded successfully.');
    }

    /**
     * Get investor's own investments.
     */
    public function myInvestments(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->query('type');

        $investments = Investment::with(['deal.media'])
            ->where('user_id', $user->id)
            ->when($type && in_array($type, ['interested', 'invest', 'review']), fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success($investments);
    }

    /**
     * Get investor's own commits.
     */
    public function myCommits(Request $request): JsonResponse
    {
        $user = $request->user();

        $commits = Commit::with(['deal.media'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success($commits);
    }

    /**
     * Get investor's membership orders.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $orders = MembershipOrder::with('payment')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return $this->success($orders);
    }

    /**
     * Single membership order detail.
     */
    public function orderDetail(Request $request, MembershipOrder $membershipOrder): JsonResponse
    {
        abort_unless((int) $membershipOrder->user_id === (int) $request->user()->id || $request->user()->isAdmin(), 403);

        $membershipOrder->load('payment');

        return $this->success($membershipOrder);
    }

    /**
     * Membership order policy snapshot.
     */
    public function orderPolicies(Request $request, MembershipOrder $membershipOrder): JsonResponse
    {
        abort_unless((int) $membershipOrder->user_id === (int) $request->user()->id || $request->user()->isAdmin(), 403);

        return $this->success([
            'order_reference' => $membershipOrder->reference,
            'policy_version' => $membershipOrder->policy_version,
            'acknowledgement' => $membershipOrder->acknowledgement,
            'accepted_at' => $membershipOrder->accepted_at?->toIso8601String(),
            'policies' => MembershipPolicies::pages(),
        ]);
    }

    /**
     * Confirm delivery of membership access.
     */
    public function confirmDelivery(Request $request, MembershipOrder $membershipOrder): JsonResponse
    {
        abort_unless((int) $membershipOrder->user_id === (int) $request->user()->id, 403);

        $request->validate(['delivery_consent' => ['required', 'accepted']]);

        DB::transaction(function () use ($request, $membershipOrder) {
            $order = MembershipOrder::whereKey($membershipOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->delivered_at && $order->payment?->status === 'completed', 422, 'Service delivery has not been completed yet.');

            if (! $order->delivery_confirmed_at) {
                $order->update([
                    'delivery_confirmed_at' => now(),
                    'delivery_confirmed_ip' => $request->ip(),
                    'delivery_confirmed_user_agent' => mb_substr($request->userAgent() ?? '', 0, 2000),
                    'delivery_acknowledgement' => MembershipPolicies::DELIVERY_ACKNOWLEDGEMENT,
                ]);
            }
        });

        return $this->success(null, 'Your confirmation of membership access has been recorded.');
    }

    /**
     * Available BAN Wealth funds and user's orders.
     */
    public function banWealthFunds(Request $request): JsonResponse
    {
        $funds = BanWealthFunds::all();

        $userOrders = $request->user()->isInvestor()
            ? BanWealthOrder::where('investor_id', $request->user()->id)->latest()->get()
            : collect();

        return $this->success([
            'funds' => $funds,
            'orders' => $userOrders,
        ]);
    }

    /**
     * Submit BAN Wealth order.
     */
    public function submitBanWealthOrder(StoreBanWealthOrderRequest $request): JsonResponse
    {
        $fund = BanWealthFunds::find($request->string('fund_slug')->toString());
        abort_unless($fund, 422, 'Selected fund is invalid.');

        abort_if($fund['horizon'] !== $request->string('horizon')->toString(), 422, 'The selected fund does not match your timeframe.');

        if ($request->string('shariah_preference')->toString() === 'shariah') {
            abort_unless($fund['shariah'], 422, 'The selected fund does not match your preference.');
        }
        if ($request->string('shariah_preference')->toString() === 'conventional') {
            abort_if($fund['shariah'], 422, 'The selected fund does not match your preference.');
        }

        $amount = (float) $request->input('amount');
        abort_if($amount < (float) $fund['minimum'], 422, 'The amount is below this fund’s minimum.');

        $proof = $request->file('payment_proof');
        $path = $proof->store('ban-wealth/payment-proofs', 'local');

        do {
            $reference = 'BW-'.now()->format('ymd').'-'.str()->upper(str()->random(5));
        } while (BanWealthOrder::where('reference', $reference)->exists());

        try {
            $order = BanWealthOrder::create([
                ...$request->safe()->except(['payment_proof', 'prospectus_consent', 'submission_consent', 'shariah_preference']),
                'reference' => $reference,
                'investor_id' => $request->user()->id,
                'fund_name' => $fund['name'],
                'fund_manager' => $fund['manager'],
                'shariah' => $fund['shariah'],
                'nav_at_order' => $fund['nav'],
                'estimated_units' => $amount / $fund['nav'],
                'monthly' => $request->boolean('monthly'),
                'politically_exposed' => $request->boolean('politically_exposed'),
                'payment_method' => 'bank_transfer',
                'payment_proof_path' => $path,
                'payment_proof_name' => $proof->getClientOriginalName(),
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return $this->success($order, 'BAN Wealth order submitted successfully.', 201);
    }

    /**
     * Download BAN Wealth payment proof.
     */
    public function banWealthProof(Request $request, BanWealthOrder $banWealthOrder)
    {
        abort_unless($request->user()->isAdmin() || $banWealthOrder->investor_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($banWealthOrder->payment_proof_path), 404);

        return Storage::disk('local')->download($banWealthOrder->payment_proof_path, $banWealthOrder->payment_proof_name);
    }
}
