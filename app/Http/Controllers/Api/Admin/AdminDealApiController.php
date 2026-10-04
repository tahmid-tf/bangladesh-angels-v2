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
            ->when($type, function ($q) use ($type) {
                if ($type === 'portfolio') {
                    $q->inPortfolio();
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

        return $this->success($deals);
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
            'video_url' => 'nullable|url|max:255',
            'image_gallery.*' => 'nullable|image|max:5120',
            'status' => 'nullable|in:active,closed,draft',
            'key_metrics' => 'nullable|array',
            'key_metrics.*.name' => 'nullable|string|max:255',
            'key_metrics.*.value' => 'nullable|string|max:255',
        ]);

        $slug = Deal::makeUniqueSlug(Deal::slugBaseFromTitle($validatedData['title']));

        $deal = Deal::create([
            'title' => $validatedData['title'],
            'slug' => $slug,
            'sector' => $validatedData['sector'],
            'type' => $validatedData['type'],
            'is_portfolio' => $request->boolean('is_portfolio'),
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
            'video_url' => 'nullable|url|max:255',
            'image_gallery.*' => 'nullable|image|max:5120',
            'status' => 'nullable|in:active,closed,draft',
            'key_metrics' => 'nullable|array',
            'key_metrics.*.name' => 'nullable|string|max:255',
            'key_metrics.*.value' => 'nullable|string|max:255',
        ]);

        $deal->update([
            'title' => $validatedData['title'],
            'sector' => $validatedData['sector'],
            'type' => $validatedData['type'],
            'is_portfolio' => $request->boolean('is_portfolio'),
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

        if ($request->hasFile('company_logo')) {
            $deal->clearMediaCollection('company_logo');
            $deal->addMediaFromRequest('company_logo')->toMediaCollection('company_logo');
        }

        if ($request->hasFile('company_cover')) {
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
        $investments = Investment::with('user')
            ->where('deal_id', $deal->id)
            ->latest()
            ->get();

        $commits = Commit::with('user')
            ->where('deal_id', $deal->id)
            ->latest()
            ->get();

        return $this->success([
            'deal' => $this->formatDeal($deal),
            'investments' => $investments,
            'commits' => $commits,
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
