<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Commit;
use App\Models\Deal;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDealApiController extends BaseApiController
{
    /**
     * List all deals for administration.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $query = Deal::with('media')
            ->withCount([
                'investments as interested_count' => fn ($q) => $q->where('type', 'interested'),
                'investments as unviewed_interested_count' => fn ($q) => $q->where('type', 'interested')->whereNull('viewed_at'),
            ])
            ->when($type, function ($q) use ($type) {
                if ($type === 'portfolio') {
                    $q->inPortfolio();
                } elseif ($type === 'invest-portfolio') {
                    $q->where('type', 'invest')->where('is_portfolio', true);
                } else {
                    $q->where('type', $type);
                }
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('sector', 'like', "%{$search}%");
                });
            });

        $deals = $query->latest()->paginate((int) $request->query('per_page', 20))
            ->through(fn (Deal $deal) => $this->formatDeal($deal, true));

        $counts = [
            'all' => Deal::count(),
            'invest' => Deal::where('type', 'invest')->count(),
            'commit' => Deal::where('type', 'commit')->count(),
            'review' => Deal::where('type', 'review')->count(),
            'portfolio' => Deal::inPortfolio()->count(),
            'invest_portfolio' => Deal::where('type', 'invest')->where('is_portfolio', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $deals,
            'counts' => $counts,
        ]);
    }

    /**
     * Single deal details.
     */
    public function show(Deal $deal): JsonResponse
    {
        $deal->loadMissing('media');

        return $this->success($this->formatDeal($deal, true));
    }

    /**
     * Create a new deal.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('key_metrics') && is_string($request->input('key_metrics'))) {
            $decoded = json_decode($request->input('key_metrics'), true);
            if (is_array($decoded)) {
                $request->merge(['key_metrics' => $decoded]);
            }
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'type' => 'required|in:commit,invest,review,portfolio',
            'is_portfolio' => 'nullable|boolean',
            'investment_stage' => 'nullable|in:Pre Seed,Seed,Series A,Series B,Series C,Series D',
            'amount_seeking' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'company_logo' => 'nullable|image|max:5120',
            'company_cover' => 'nullable|image|max:5120',
            'pitch_deck_url' => 'nullable|max:255',
            'substack_link' => 'nullable|max:255',
            'invest_link' => 'nullable|max:255',
            'commit_link' => 'nullable|max:255',
            'groupchat_invite_link' => 'nullable|max:255',
            'growth_rate' => 'nullable|string|max:255',
            'revenue_model' => 'nullable|string',
            'future_plans' => 'nullable|string',
            'partnerships' => 'nullable|string',
            'video_url' => 'nullable|string|max:255',
            'image_gallery.*' => 'nullable|image|max:5120',
            'status' => 'nullable|in:active,closed,draft',
            'key_metrics' => 'nullable|array',
            'key_metrics.*.name' => 'nullable|string|max:255',
            'key_metrics.*.value' => 'nullable|string|max:255',
        ]);

        $slug = Deal::makeUniqueSlug(Deal::slugBaseFromTitle($validatedData['title']));
        $isPortfolio = $validatedData['type'] === 'portfolio' ? true : $request->boolean('is_portfolio');

        $deal = Deal::create([
            'title' => $validatedData['title'],
            'slug' => $slug,
            'sector' => $validatedData['sector'],
            'type' => $validatedData['type'],
            'is_portfolio' => $isPortfolio,
            'investment_stage' => $validatedData['investment_stage'] ?? null,
            'amount_seeking' => $validatedData['amount_seeking'] ?? null,
            'description' => $validatedData['description'],
            'pitch_deck_url' => $validatedData['pitch_deck_url'] ?? null,
            'substack_link' => $validatedData['substack_link'] ?? null,
            'invest_link' => $validatedData['invest_link'] ?? null,
            'commit_link' => $validatedData['commit_link'] ?? null,
            'groupchat_invite_link' => $validatedData['groupchat_invite_link'] ?? null,
            'growth_rate' => $validatedData['growth_rate'] ?? null,
            'revenue_model' => $validatedData['revenue_model'] ?? null,
            'future_plans' => $validatedData['future_plans'] ?? null,
            'partnerships' => $validatedData['partnerships'] ?? null,
            'video_url' => $validatedData['video_url'] ?? null,
            'status' => $validatedData['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'key_metrics' => isset($validatedData['key_metrics']) ? json_encode($validatedData['key_metrics']) : null,
        ]);

        if ($request->hasFile('company_logo')) {
            $deal->addMediaFromRequest('company_logo')->toMediaCollection('company_logo');
        }

        if ($request->hasFile('company_cover')) {
            $deal->addMediaFromRequest('company_cover')->toMediaCollection('company_cover');
        }

        if ($request->hasFile('image_gallery')) {
            foreach ($request->file('image_gallery') as $image) {
                $deal->addMedia($image)->toMediaCollection('image_gallery');
            }
        }

        return $this->success($this->formatDeal($deal->fresh(), true), 'Deal created successfully.', 201);
    }

    /**
     * Update an existing deal.
     */
    public function update(Request $request, Deal $deal): JsonResponse
    {
        if ($request->has('key_metrics') && is_string($request->input('key_metrics'))) {
            $decoded = json_decode($request->input('key_metrics'), true);
            if (is_array($decoded)) {
                $request->merge(['key_metrics' => $decoded]);
            }
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'type' => 'required|in:commit,invest,review,portfolio',
            'is_portfolio' => 'nullable|boolean',
            'investment_stage' => 'nullable|in:Pre Seed,Seed,Series A,Series B,Series C,Series D',
            'amount_seeking' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'company_logo' => 'nullable|image|max:5120',
            'company_cover' => 'nullable|image|max:5120',
            'pitch_deck_url' => 'nullable|max:255',
            'substack_link' => 'nullable|max:255',
            'invest_link' => 'nullable|max:255',
            'commit_link' => 'nullable|max:255',
            'groupchat_invite_link' => 'nullable|max:255',
            'growth_rate' => 'nullable|string|max:255',
            'revenue_model' => 'nullable|string',
            'future_plans' => 'nullable|string',
            'partnerships' => 'nullable|string',
            'video_url' => 'nullable|string|max:255',
            'image_gallery.*' => 'nullable|image|max:5120',
            'status' => 'nullable|in:active,closed,draft',
            'key_metrics' => 'nullable|array',
            'key_metrics.*.name' => 'nullable|string|max:255',
            'key_metrics.*.value' => 'nullable|string|max:255',
        ]);

        $isPortfolio = $validatedData['type'] === 'portfolio' ? true : $request->boolean('is_portfolio');

        $deal->update([
            'title' => $validatedData['title'],
            'sector' => $validatedData['sector'],
            'type' => $validatedData['type'],
            'is_portfolio' => $isPortfolio,
            'investment_stage' => $validatedData['investment_stage'] ?? null,
            'amount_seeking' => $validatedData['amount_seeking'] ?? null,
            'description' => $validatedData['description'],
            'pitch_deck_url' => $validatedData['pitch_deck_url'] ?? null,
            'substack_link' => $validatedData['substack_link'] ?? null,
            'invest_link' => $validatedData['invest_link'] ?? null,
            'commit_link' => $validatedData['commit_link'] ?? null,
            'groupchat_invite_link' => $validatedData['groupchat_invite_link'] ?? null,
            'growth_rate' => $validatedData['growth_rate'] ?? null,
            'revenue_model' => $validatedData['revenue_model'] ?? null,
            'future_plans' => $validatedData['future_plans'] ?? null,
            'partnerships' => $validatedData['partnerships'] ?? null,
            'video_url' => $validatedData['video_url'] ?? null,
            'status' => $validatedData['status'] ?? $deal->status,
            'key_metrics' => isset($validatedData['key_metrics']) ? json_encode($validatedData['key_metrics']) : $deal->key_metrics,
        ]);

        if ($request->boolean('remove_logo')) {
            $deal->clearMediaCollection('company_logo');
        } elseif ($request->hasFile('company_logo')) {
            $deal->clearMediaCollection('company_logo');
            $deal->addMediaFromRequest('company_logo')->toMediaCollection('company_logo');
        }

        if ($request->boolean('remove_cover')) {
            $deal->clearMediaCollection('company_cover');
        } elseif ($request->hasFile('company_cover')) {
            $deal->clearMediaCollection('company_cover');
            $deal->addMediaFromRequest('company_cover')->toMediaCollection('company_cover');
        }

        if ($request->hasFile('image_gallery')) {
            foreach ($request->file('image_gallery') as $image) {
                $deal->addMedia($image)->toMediaCollection('image_gallery');
            }
        }

        return $this->success($this->formatDeal($deal->fresh(), true), 'Deal updated successfully.');
    }

    /**
     * Delete a deal.
     */
    public function destroy(Deal $deal): JsonResponse
    {
        $deal->delete();

        return $this->success(null, 'Deal deleted successfully.');
    }

    /**
     * Member activity (interested, investments, commits) for a deal.
     */
    public function memberActivity(Deal $deal): JsonResponse
    {
        $investments = Investment::with(['user', 'viewer'])
            ->where('deal_id', $deal->id)
            ->latest()
            ->get();

        $commits = Commit::with('user')
            ->where('deal_id', $deal->id)
            ->latest()
            ->get();

        $interested = $investments->where('type', 'interested')->values();
        $investRows = $investments->where('type', 'invest')->values();
        $reviewRows = $investments->where('type', 'review')->values();

        return $this->success([
            'deal' => $this->formatDeal($deal, true),
            'investments' => $investments,
            'interested' => $interested,
            'invest_rows' => $investRows,
            'review_rows' => $reviewRows,
            'commits' => $commits,
            'summary' => [
                'interested_count' => $interested->count(),
                'unviewed_interested_count' => $interested->whereNull('viewed_at')->count(),
                'invest_count' => $investRows->count(),
                'review_count' => $reviewRows->count(),
                'commits_count' => $commits->count(),
                'total_committed_amount' => (float) $commits->sum('amount'),
            ],
        ]);
    }

    /**
     * Toggle viewed flag for an investment on a deal.
     */
    public function toggleInvestmentViewed(Deal $deal, Investment $investment, Request $request): JsonResponse
    {
        abort_unless($investment->deal_id === $deal->id, 404);

        if ($investment->isViewed()) {
            $investment->markAsUnviewed();
            $viewed = false;
        } else {
            $investment->markAsViewed($request->user()->id);
            $viewed = true;
        }

        return $this->success([
            'viewed' => $viewed,
            'viewed_at' => $investment->fresh()->viewed_at?->toIso8601String(),
        ], $viewed ? 'Marked as viewed.' : 'Marked as unviewed.');
    }

    /**
     * Mark all investments on a deal as viewed.
     */
    public function markAllInterestedViewed(Deal $deal, Request $request): JsonResponse
    {
        $updated = Investment::where('deal_id', $deal->id)
            ->whereNull('viewed_at')
            ->update([
                'viewed_at' => now(),
                'viewed_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return $this->success(['updated_count' => $updated], 'All activity marked as viewed.');
    }
}
