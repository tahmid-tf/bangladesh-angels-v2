<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Subscription;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionApiController extends BaseApiController
{
    /**
     * Subscriptions list and tiers overview.
     */
    public function subscriptions(): JsonResponse
    {
        $subscriptions = Subscription::with(['user', 'payment'])
            ->whereHas('user', function ($query) {
                $query->where('account_status', '!=', 'free');
            })
            ->latest()
            ->get();

        $usersWithoutSubscriptions = User::where('account_status', '!=', 'free')
            ->whereNotIn('id', $subscriptions->pluck('user_id'))
            ->get();

        $tiers = SubscriptionTier::ordered()->get();

        return $this->success([
            'subscriptions' => $subscriptions,
            'users_without_subscriptions' => $usersWithoutSubscriptions,
            'tiers' => $tiers,
        ]);
    }

    /**
     * Update subscription tiers.
     */
    public function updateTiers(Request $request): JsonResponse
    {
        $tierIds = SubscriptionTier::query()->pluck('id')->all();

        $validated = $request->validate([
            'tiers' => ['required', 'array'],
            'tiers.*.id' => ['required', 'exists:subscription_tiers,id'],
            'tiers.*.name' => ['required', 'string', 'max:100'],
            'tiers.*.price_yearly' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'tiers.*.is_active' => ['nullable', 'boolean'],
            'tiers.*.sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'tiers.*.is_highlighted' => ['nullable', 'boolean'],
            'tiers.*.features_included' => ['nullable', 'string', 'max:20000'],
            'tiers.*.features_excluded' => ['nullable', 'string', 'max:20000'],
        ]);

        foreach ($validated['tiers'] as $row) {
            SubscriptionTier::where('id', $row['id'])->update([
                'name' => $row['name'],
                'price_yearly' => $row['price_yearly'],
                'is_active' => $row['is_active'] ?? true,
                'sort_order' => $row['sort_order'],
                'is_highlighted' => $row['is_highlighted'] ?? false,
                'features_included' => $row['features_included'] ?? null,
                'features_excluded' => $row['features_excluded'] ?? null,
            ]);
        }

        // Single highlight badge normalization
        $highlighted = SubscriptionTier::where('is_highlighted', true)->orderBy('id')->get();
        if ($highlighted->count() > 1) {
            SubscriptionTier::query()->update(['is_highlighted' => false]);
            $keep = $highlighted->last();
            SubscriptionTier::where('id', $keep->id)->update(['is_highlighted' => true]);
        }

        return $this->success(SubscriptionTier::ordered()->get(), 'Subscription tiers and prices updated.');
    }
}
