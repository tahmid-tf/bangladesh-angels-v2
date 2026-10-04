<?php

namespace App\Http\Controllers\Api;

use App\Models\Deal;
use App\Models\HomepagePortfolio;
use App\Models\LandingPageStat;
use App\Models\LandingProgramCard;
use App\Models\Resource;
use App\Models\ResourceHubCard;
use App\Models\StartupService;
use App\Models\SubscriptionTier;
use App\Models\TeamAboutSection;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WhatWeDoCard;
use App\Support\BanFaqs;
use App\Support\MembershipPolicies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends BaseApiController
{
    /**
     * Homepage comprehensive data bundle.
     */
    public function home(): JsonResponse
    {
        $portfolioDeals = Deal::getHomepageDeals()->map(fn ($deal) => $this->formatDeal($deal));

        $managementTeam = TeamMember::query()
            ->forSection(TeamMember::SECTION_MANAGEMENT)
            ->ordered()
            ->get()
            ->map(fn (TeamMember $m) => $this->formatTeamMember($m));

        $faqs = collect(BanFaqs::all())->take(6)->values();

        $landingPageStats = LandingPageStat::query()
            ->ordered()
            ->get()
            ->map(fn (LandingPageStat $stat) => [
                'id' => $stat->id,
                'stat_value' => $stat->stat_value,
                'stat_label' => $stat->stat_label,
                'sort_order' => $stat->sort_order,
            ]);

        $landingProgramCards = LandingProgramCard::query()
            ->ordered()
            ->limit(LandingProgramCard::MAX_CARDS)
            ->get()
            ->map(fn (LandingProgramCard $card) => [
                'id' => $card->id,
                'title' => $card->title,
                'badge' => $card->badge,
                'description' => $card->description,
                'link_url' => $card->link_url,
                'link_label' => $card->link_label,
                'is_external' => (bool) $card->is_external,
                'sort_order' => $card->sort_order,
                'dropdown_items' => $card->dropdown_items,
            ]);

        $landingResourceEvents = Resource::query()
            ->with('media')
            ->whereIn('type', ['event', 'webinar'])
            ->orderByDesc('show_on_landing')
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Resource $res) => $this->formatResource($res));

        $tiers = SubscriptionTier::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (SubscriptionTier $tier) => $this->formatTier($tier));

        return $this->success([
            'portfolio_deals' => $portfolioDeals,
            'team_members' => $managementTeam,
            'faqs' => $faqs,
            'landing_stats' => $landingPageStats,
            'landing_programs' => $landingProgramCards,
            'landing_events' => $landingResourceEvents,
            'membership_tiers' => $tiers,
        ]);
    }

    /**
     * Active membership pricing plans / tiers.
     */
    public function plans(): JsonResponse
    {
        $tiers = SubscriptionTier::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (SubscriptionTier $tier) => $this->formatTier($tier));

        return $this->success([
            'tiers' => $tiers,
            'policy_version' => MembershipPolicies::version(),
        ]);
    }

    /**
     * FAQs list.
     */
    public function faqs(): JsonResponse
    {
        return $this->success(BanFaqs::all());
    }

    /**
     * Team members grouped by section.
     */
    public function team(): JsonResponse
    {
        $management = TeamMember::query()
            ->forSection(TeamMember::SECTION_MANAGEMENT)
            ->ordered()
            ->get()
            ->map(fn (TeamMember $m) => $this->formatTeamMember($m));

        $governingBoard = TeamMember::query()
            ->forSection(TeamMember::SECTION_GOVERNING_BOARD)
            ->ordered()
            ->get()
            ->map(fn (TeamMember $m) => $this->formatTeamMember($m));

        $aboutSection = TeamAboutSection::current();

        return $this->success([
            'management' => $management,
            'governing_board' => $governingBoard,
            'about_section' => $aboutSection ? [
                'id' => $aboutSection->id,
                'kicker' => $aboutSection->kicker,
                'heading' => $aboutSection->heading,
                'body' => $aboutSection->body,
                'paragraphs' => $aboutSection->paragraphs(),
                'image_alt' => $aboutSection->image_alt,
                'image_badge' => $aboutSection->image_badge,
                'image_url' => $aboutSection->imageUrl(),
            ] : null,
        ]);
    }

    /**
     * Team about section info.
     */
    public function teamAboutSection(): JsonResponse
    {
        return $this->success(TeamAboutSection::current());
    }

    /**
     * Startup services listing.
     */
    public function services(): JsonResponse
    {
        $services = StartupService::query()
            ->with('media')
            ->ordered()
            ->get()
            ->map(fn (StartupService $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'description' => $s->description,
                'sort_order' => $s->sort_order,
                'cta_label' => $s->cta_label,
                'cta_url' => $s->cta_url,
                'brochure_url' => $s->brochure_url,
                'icon_url' => $s->getFirstMedia('icon')?->getUrl(),
            ]);

        $tiers = SubscriptionTier::active()->ordered()->get()->map(fn ($t) => $this->formatTier($t));

        return $this->success([
            'services' => $services,
            'tiers' => $tiers,
        ]);
    }

    /**
     * What we do cards.
     */
    public function whatWeDoCards(): JsonResponse
    {
        $cards = WhatWeDoCard::query()
            ->ordered()
            ->get()
            ->map(fn (WhatWeDoCard $card) => [
                'id' => $card->id,
                'key' => $card->key,
                'title' => $card->title,
                'description' => $card->description,
                'cta_label' => $card->cta_label,
                'cta_url' => $card->cta_url,
                'secondary_cta_label' => $card->secondary_cta_label,
                'secondary_cta_url' => $card->secondary_cta_url,
                'sort_order' => $card->sort_order,
            ]);

        return $this->success($cards);
    }

    /**
     * Landing program cards.
     */
    public function landingProgramCards(): JsonResponse
    {
        $cards = LandingProgramCard::query()
            ->ordered()
            ->get()
            ->map(fn (LandingProgramCard $card) => [
                'id' => $card->id,
                'title' => $card->title,
                'badge' => $card->badge,
                'description' => $card->description,
                'link_url' => $card->link_url,
                'link_label' => $card->link_label,
                'is_external' => (bool) $card->is_external,
                'sort_order' => $card->sort_order,
                'dropdown_items' => $card->dropdown_items,
            ]);

        return $this->success($cards);
    }

    /**
     * Landing page statistics.
     */
    public function landingPageStats(): JsonResponse
    {
        return $this->success(LandingPageStat::query()->ordered()->get());
    }

    /**
     * Resource hub cards.
     */
    public function resourceHubCards(): JsonResponse
    {
        return $this->success(ResourceHubCard::query()->ordered()->get());
    }

    /**
     * Portfolio showcase deals.
     */
    public function portfolio(): JsonResponse
    {
        $portfolioDeals = Deal::with('media')
            ->inPortfolio()
            ->latest()
            ->get()
            ->map(fn (Deal $deal) => $this->formatDeal($deal));

        return $this->success($portfolioDeals);
    }

    /**
     * Curated homepage portfolio companies.
     */
    public function homepagePortfolios(): JsonResponse
    {
        $items = HomepagePortfolio::query()
            ->with(['deal.media'])
            ->ordered()
            ->get()
            ->map(fn (HomepagePortfolio $item) => [
                'id' => $item->id,
                'deal_id' => $item->deal_id,
                'sort_order' => $item->sort_order,
                'deal' => $item->deal ? $this->formatDeal($item->deal) : null,
            ]);

        return $this->success($items);
    }

    /**
     * Public Investors directory & featured testimonials.
     */
    public function investors(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('role', 'investor')
            ->where('is_approved', true)
            ->where('account_status', '!=', 'disabled')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('preference_sector', 'like', "%{$search}%");
                });
            });

        $investors = $query->latest()->paginate(24)->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'designation' => $user->designation,
            'company_name' => $user->company_name,
            'preference_sector' => $user->preference_sector,
            'country' => $user->primary_country ?? $user->country,
            'profile_photo_url' => $user->getProfilePhotoUrl(),
            'linkedin' => $user->linkedin,
            'featured' => (bool) $user->featured,
            'featured_testimonial' => $user->hasPublicFeaturedTestimonial() ? $user->featured_testimonial : null,
        ]);

        $featuredTestimonials = User::query()
            ->where('role', 'investor')
            ->where('featured_testimonial_public', true)
            ->whereNotNull('featured_testimonial')
            ->where('featured_testimonial', '!=', '')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation,
                'company_name' => $user->company_name,
                'testimonial' => $user->featured_testimonial,
                'profile_photo_url' => $user->getProfilePhotoUrl(),
            ]);

        return $this->success([
            'investors' => $investors,
            'featured_testimonials' => $featuredTestimonials,
        ]);
    }

    /**
     * Available website policies listing.
     */
    public function policies(): JsonResponse
    {
        $pages = MembershipPolicies::pages();
        $list = [];

        foreach ($pages as $slug => $page) {
            $list[] = [
                'slug' => $slug,
                'title' => $page['title'],
                'meta_description' => $page['meta_description'] ?? null,
            ];
        }

        return $this->success([
            'version' => MembershipPolicies::version(),
            'policies' => $list,
        ]);
    }

    /**
     * Single policy content.
     */
    public function policy(string $slug): JsonResponse
    {
        $pages = MembershipPolicies::pages();

        if (! isset($pages[$slug])) {
            return $this->error('Policy page not found.', 404);
        }

        return $this->success([
            'slug' => $slug,
            'version' => MembershipPolicies::version(),
            'policy' => $pages[$slug],
        ]);
    }

    private function formatTeamMember(TeamMember $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'title' => $member->title,
            'subtitle' => $member->subtitle,
            'section' => $member->section,
            'linkedin_url' => $member->linkedin_url,
            'sort_order' => $member->sort_order,
            'photo_url' => $member->getFirstMedia('photo')?->getUrl() ?? asset('default_pfp.jpg'),
        ];
    }

    private function formatResource(Resource $res): array
    {
        return [
            'id' => $res->id,
            'title' => $res->title,
            'slug' => $res->slug,
            'type' => $res->type,
            'location' => $res->location,
            'date' => $res->date?->format('Y-m-d'),
            'start_time' => $res->start_time,
            'end_time' => $res->end_time,
            'registration_fee' => (float) $res->registration_fee,
            'description' => $res->description,
            'cta_link' => $res->cta_link,
            'banner_url' => $res->getFirstMedia('banner')?->getUrl(),
            'speakers' => $res->speakers,
            'benefits' => $res->benefits,
            'event_highlights' => $res->event_highlights,
            'target_audience' => $res->target_audience,
        ];
    }

    private function formatTier(SubscriptionTier $tier): array
    {
        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'slug' => $tier->slug,
            'price_yearly' => (float) $tier->price_yearly,
            'price_formatted' => '$'.number_format((float) $tier->price_yearly, 2),
            'is_highlighted' => (bool) $tier->is_highlighted,
            'sort_order' => $tier->sort_order,
            'features_included' => $tier->features_included ? array_filter(array_map('trim', explode("\n", $tier->features_included))) : [],
            'features_excluded' => $tier->features_excluded ? array_filter(array_map('trim', explode("\n", $tier->features_excluded))) : [],
        ];
    }
}
