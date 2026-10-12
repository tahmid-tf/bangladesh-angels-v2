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

        $subscriptions->transform(function (Subscription $subscription) {
            if ($subscription->user) {
                $subscription->user->setAttribute('profile_photo_url', $subscription->user->getProfilePhotoUrl());
            }
            return $subscription;
        });

        $usersWithoutSubscriptions->transform(function (User $user) {
            $user->setAttribute('profile_photo_url', $user->getProfilePhotoUrl());
            return $user;
        });

        $tiers = SubscriptionTier::ordered()->get();

        $totalRows = $subscriptions->count() + $usersWithoutSubscriptions->count();
        $paidCount = $subscriptions->filter(fn ($s) => (bool) $s->payment_id)->count();
        $unpaidCount = $totalRows - $paidCount;

        return $this->success([
            'subscriptions' => $subscriptions,
            'users_without_subscriptions' => $usersWithoutSubscriptions,
            'tiers' => $tiers,
            'counts' => [
                'total' => $totalRows,
                'paid' => $paidCount,
                'unpaid' => $unpaidCount,
            ],
        ]);
    }

    /**
     * Update subscription tiers.
     */
    public function updateTiers(Request $request): JsonResponse
    {
        // Support both single tier update ({ id: 1, name: '...', ... } or { tier: { id: 1, ... } })
        // and multiple tiers update ({ tiers: [ ... ] })
        if ($request->has('tier') && is_array($request->input('tier'))) {
            $tiers = [$request->input('tier')];
        } elseif ($request->has('tiers') && is_array($request->input('tiers'))) {
            $tiers = $request->input('tiers');
        } elseif ($request->filled('id') && $request->filled('name')) {
            $tiers = [$request->all()];
        } else {
            $tiers = $request->input('tiers', []);
        }

        // If $tiers is an associative array keyed by ID (like web form), convert to list
        if (is_array($tiers) && !empty($tiers) && !isset($tiers[0])) {
            $normalizedTiers = [];
            foreach ($tiers as $k => $v) {
                if (is_array($v)) {
                    if (!isset($v['id'])) {
                        $v['id'] = $k;
                    }
                    $normalizedTiers[] = $v;
                }
            }
            $tiers = $normalizedTiers;
        }

        $request->merge(['tiers' => $tiers]);

        $validated = $request->validate([
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.id' => ['required', 'exists:subscription_tiers,id'],
            'tiers.*.name' => ['required', 'string', 'max:100'],
            'tiers.*.price_yearly' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'tiers.*.is_active' => ['nullable'],
            'tiers.*.sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'tiers.*.is_highlighted' => ['nullable'],
            'tiers.*.features_included' => ['nullable', 'string', 'max:20000'],
            'tiers.*.features_excluded' => ['nullable', 'string', 'max:20000'],
        ]);

        foreach ($validated['tiers'] as $row) {
            $isActive = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $isHighlighted = filter_var($row['is_highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            SubscriptionTier::where('id', $row['id'])->update([
                'name' => $row['name'],
                'price_yearly' => $row['price_yearly'],
                'is_active' => $isActive ?? true,
                'sort_order' => (int) $row['sort_order'],
                'is_highlighted' => $isHighlighted ?? false,
                'features_included' => $row['features_included'] ?? null,
                'features_excluded' => $row['features_excluded'] ?? null,
            ]);
        }

        // Single highlight badge normalization: if any tier was highlighted, ensure at most one remains highlighted
        $hasAnyHighlighted = collect($validated['tiers'])->contains(function ($t) {
            return filter_var($t['is_highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
        });

        if ($hasAnyHighlighted) {
            $highlightedRow = collect($validated['tiers'])->last(function ($t) {
                return filter_var($t['is_highlighted'] ?? false, FILTER_VALIDATE_BOOLEAN);
            });
            if ($highlightedRow && isset($highlightedRow['id'])) {
                SubscriptionTier::where('id', '!=', $highlightedRow['id'])->update(['is_highlighted' => false]);
                SubscriptionTier::where('id', $highlightedRow['id'])->update(['is_highlighted' => true]);
            }
        }

        return $this->success(SubscriptionTier::ordered()->get(), 'Subscription tiers and prices updated successfully.');
    }
}
