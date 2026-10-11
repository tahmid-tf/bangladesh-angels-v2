<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    /**
     * Return a standardized success JSON response.
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function error(string $message = 'An error occurred', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Standardized representation of a User model.
     */
    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'primary_country' => $user->primary_country,
            'country' => $user->country,
            'address' => $user->address,
            'company_name' => $user->company_name,
            'designation' => $user->designation,
            'organization' => $user->organization,
            'role' => $user->role,
            'is_admin' => $user->isAdmin(),
            'is_investor' => $user->isInvestor(),
            'is_approved' => (bool) $user->is_approved,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'account_status' => $user->account_status,
            'status' => $user->status ?? 'active',
            'is_active' => $user->isActive(),
            'tier_name' => $user->tierName(),
            'payment_status' => $user->payment_status,
            'investment_expertise' => $user->investment_expertise,
            'expertise_level' => $user->expertise_level,
            'preference_sector' => $user->preference_sector,
            'secondary_contact' => $user->secondary_contact,
            'linkedin' => $user->linkedin,
            'profile_photo_url' => $user->getProfilePhotoUrl(),
            'can_view_paywalled_deals' => $user->canViewPaywalledDeals(),
            'has_paid_membership' => $user->hasPaidMembership(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * Standardized representation of a Deal model.
     */
    protected function formatDeal(Deal $deal, bool $detailed = false): array
    {
        $payload = [
            'id' => $deal->id,
            'title' => $deal->title,
            'slug' => $deal->slug,
            'sector' => $deal->sector,
            'type' => $deal->type,
            'is_portfolio' => (bool) $deal->is_portfolio,
            'status' => $deal->status,
            'investment_stage' => $deal->investment_stage,
            'amount_seeking' => (float) $deal->amount_seeking,
            'amount_seeking_formatted' => $deal->amountSeeking(),
            'description' => $deal->description,
            'excerpt' => $deal->getExcerpt(),
            'logo_url' => $deal->getLogoUrl(),
            'cover_url' => $deal->getCoverUrl(),
            'investors_count' => $deal->investorsCount(),
            'commits_count' => $deal->commitCount(),
            'review_count' => $deal->reviewCount(),
            'created_at' => $deal->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $gallery = $deal->getMedia('image_gallery')->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'name' => $m->name,
                'mime_type' => $m->mime_type,
            ])->values();

            $payload = array_merge($payload, [
                'pitch_deck_url' => $deal->pitch_deck_url,
                'substack_link' => $deal->substack_link,
                'invest_link' => $deal->invest_link,
                'commit_link' => $deal->commit_link,
                'groupchat_invite_link' => $deal->groupchat_invite_link,
                'monthly_revenue' => $deal->monthly_revenue,
                'growth_rate' => $deal->growth_rate,
                'revenue_model' => $deal->revenue_model,
                'future_plans' => $deal->future_plans,
                'partnerships' => $deal->partnerships,
                'video_url' => $deal->video_url,
                'key_metrics' => $deal->getKeyMetrics(),
                'gallery' => $gallery,
            ]);
        }

        return $payload;
    }
}
