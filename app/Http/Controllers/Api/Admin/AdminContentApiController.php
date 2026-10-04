<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Deal;
use App\Models\HomepagePortfolio;
use App\Models\LandingPageStat;
use App\Models\LandingProgramCard;
use App\Models\ResourceHubCard;
use App\Models\StartupService;
use App\Models\TeamAboutSection;
use App\Models\TeamMember;
use App\Models\WhatWeDoCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminContentApiController extends BaseApiController
{
    // ==========================================
    // RESOURCE HUB CARDS
    // ==========================================

    public function resourceHubCards(): JsonResponse
    {
        return $this->success(ResourceHubCard::query()->ordered()->get());
    }

    public function storeResourceHubCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_link' => ['nullable', 'string', 'max:2048'],
        ]);

        $card = ResourceHubCard::create($validated);

        return $this->success($card, 'Card created successfully.', 201);
    }

    public function updateResourceHubCard(Request $request, ResourceHubCard $resourceHubCard): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_link' => ['nullable', 'string', 'max:2048'],
        ]);

        $resourceHubCard->update($validated);

        return $this->success($resourceHubCard->fresh(), 'Card updated successfully.');
    }

    public function destroyResourceHubCard(ResourceHubCard $resourceHubCard): JsonResponse
    {
        $resourceHubCard->delete();

        return $this->success(null, 'Card deleted successfully.');
    }

    // ==========================================
    // STARTUP SERVICES
    // ==========================================

    public function startupServices(): JsonResponse
    {
        $services = StartupService::with('media')->ordered()->get()->map(fn ($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'description' => $s->description,
            'sort_order' => $s->sort_order,
            'cta_label' => $s->cta_label,
            'cta_url' => $s->cta_url,
            'brochure_url' => $s->brochure_url,
            'icon_url' => $s->getFirstMedia('icon')?->getUrl(),
        ]);

        return $this->success($services);
    }

    public function storeStartupService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'brochure_url' => ['nullable', 'string', 'max:2048'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        $service = StartupService::create($validated);

        if ($request->hasFile('icon')) {
            $service->addMediaFromRequest('icon')->toMediaCollection('icon');
        }

        return $this->success($service, 'Startup service created.', 201);
    }

    public function updateStartupService(Request $request, StartupService $startupService): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'brochure_url' => ['nullable', 'string', 'max:2048'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ]);

        $startupService->update($validated);

        if ($request->hasFile('icon')) {
            $startupService->clearMediaCollection('icon');
            $startupService->addMediaFromRequest('icon')->toMediaCollection('icon');
        }

        return $this->success($startupService->fresh(), 'Startup service updated.');
    }

    public function destroyStartupService(StartupService $startupService): JsonResponse
    {
        $startupService->delete();

        return $this->success(null, 'Startup service deleted.');
    }

    // ==========================================
    // WHAT WE DO CARDS
    // ==========================================

    public function whatWeDoCards(): JsonResponse
    {
        return $this->success(WhatWeDoCard::query()->ordered()->get());
    }

    public function updateWhatWeDoCard(Request $request, WhatWeDoCard $whatWeDoCard): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'secondary_cta_label' => ['nullable', 'string', 'max:100'],
            'secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $whatWeDoCard->update($validated);

        return $this->success($whatWeDoCard->fresh(), 'What We Do card updated.');
    }

    // ==========================================
    // LANDING PAGE STATS
    // ==========================================

    public function landingPageStats(): JsonResponse
    {
        return $this->success(LandingPageStat::query()->ordered()->get());
    }

    public function updateLandingPageStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stats' => ['required', 'array'],
            'stats.*.id' => ['required', 'exists:landing_page_stats,id'],
            'stats.*.stat_value' => ['required', 'string', 'max:100'],
            'stats.*.stat_label' => ['required', 'string', 'max:255'],
            'stats.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['stats'] as $item) {
            LandingPageStat::where('id', $item['id'])->update([
                'stat_value' => $item['stat_value'],
                'stat_label' => $item['stat_label'],
                'sort_order' => $item['sort_order'],
            ]);
        }

        return $this->success(LandingPageStat::query()->ordered()->get(), 'Landing page stats updated.');
    }

    // ==========================================
    // LANDING PROGRAM CARDS
    // ==========================================

    public function landingProgramCards(): JsonResponse
    {
        return $this->success(LandingProgramCard::query()->ordered()->get());
    }

    public function storeLandingProgramCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'link_url' => ['required', 'string', 'max:2048'],
            'link_label' => ['required', 'string', 'max:100'],
            'is_external' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'dropdown_items' => ['nullable', 'array'],
        ]);

        $card = LandingProgramCard::create($validated);

        return $this->success($card, 'Landing program card created.', 201);
    }

    public function updateLandingProgramCard(Request $request, LandingProgramCard $landingProgramCard): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'link_url' => ['required', 'string', 'max:2048'],
            'link_label' => ['required', 'string', 'max:100'],
            'is_external' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'dropdown_items' => ['nullable', 'array'],
        ]);

        $landingProgramCard->update($validated);

        return $this->success($landingProgramCard->fresh(), 'Landing program card updated.');
    }

    public function destroyLandingProgramCard(LandingProgramCard $landingProgramCard): JsonResponse
    {
        $landingProgramCard->delete();

        return $this->success(null, 'Landing program card deleted.');
    }

    // ==========================================
    // HOMEPAGE PORTFOLIOS
    // ==========================================

    public function homepagePortfolios(): JsonResponse
    {
        $items = HomepagePortfolio::with('deal.media')->ordered()->get();

        return $this->success($items);
    }

    public function storeHomepagePortfolio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deal_id' => ['required', 'exists:deals,id', 'unique:homepage_portfolios,deal_id'],
        ]);

        $maxOrder = (int) HomepagePortfolio::max('sort_order');
        $item = HomepagePortfolio::create([
            'deal_id' => $validated['deal_id'],
            'sort_order' => $maxOrder + 1,
        ]);

        return $this->success($item->load('deal.media'), 'Portfolio added to homepage.', 201);
    }

    public function reorderHomepagePortfolios(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:homepage_portfolios,id'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            HomepagePortfolio::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return $this->success(HomepagePortfolio::with('deal.media')->ordered()->get(), 'Order updated.');
    }

    public function destroyHomepagePortfolio(HomepagePortfolio $homepagePortfolio): JsonResponse
    {
        $homepagePortfolio->delete();

        return $this->success(null, 'Homepage portfolio removed.');
    }

    // ==========================================
    // TEAM ABOUT SECTION
    // ==========================================

    public function teamAboutSection(): JsonResponse
    {
        $section = TeamAboutSection::current();

        return $this->success($section ? [
            'id' => $section->id,
            'kicker' => $section->kicker,
            'heading' => $section->heading,
            'body' => $section->body,
            'paragraphs' => $section->paragraphs(),
            'image_alt' => $section->image_alt,
            'image_badge' => $section->image_badge,
            'image_url' => $section->imageUrl(),
        ] : null);
    }

    public function updateTeamAboutSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kicker' => ['required', 'string', 'max:160'],
            'heading' => ['required', 'string', 'max:255'],
            'body_text' => ['nullable', 'string', 'max:12000'],
            'body' => ['nullable', 'array'],
            'image_alt' => ['required', 'string', 'max:255'],
            'image_badge' => ['required', 'string', 'max:160'],
            'image' => ['nullable', 'image', 'max:8192'],
        ]);

        $paragraphs = $validated['body'] ?? (filled($validated['body_text'] ?? null)
            ? collect(preg_split('/(?:\r\n|\r|\n){2,}/', trim($validated['body_text'])))->map(fn ($p) => trim($p))->filter()->values()->all()
            : []);

        $section = TeamAboutSection::first();

        $attributes = [
            'kicker' => trim($validated['kicker']),
            'heading' => trim($validated['heading']),
            'body' => $paragraphs,
            'image_alt' => trim($validated['image_alt']),
            'image_badge' => trim($validated['image_badge']),
        ];

        if ($section) {
            $section->update($attributes);
        } else {
            $section = TeamAboutSection::create($attributes);
        }

        if ($request->hasFile('image')) {
            $section->clearMediaCollection(TeamAboutSection::MEDIA_IMAGE);
            $section->addMediaFromRequest('image')->toMediaCollection(TeamAboutSection::MEDIA_IMAGE);
        }

        return $this->success($section->fresh(), 'Team About section updated.');
    }

    // ==========================================
    // TEAM MEMBERS
    // ==========================================

    public function teamMembers(): JsonResponse
    {
        $management = TeamMember::forSection(TeamMember::SECTION_MANAGEMENT)->ordered()->get()->map(fn ($m) => $this->formatMember($m));
        $governing = TeamMember::forSection(TeamMember::SECTION_GOVERNING_BOARD)->ordered()->get()->map(fn ($m) => $this->formatMember($m));

        return $this->success([
            'management' => $management,
            'governing_board' => $governing,
        ]);
    }

    public function storeTeamMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['required', Rule::in([TeamMember::SECTION_MANAGEMENT, TeamMember::SECTION_GOVERNING_BOARD])],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $nextOrder = (int) (TeamMember::forSection($validated['section'])->max('sort_order') ?? 0) + 1;

        $member = TeamMember::create([
            'section' => $validated['section'],
            'name' => $validated['name'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $nextOrder,
        ]);

        if ($request->hasFile('photo')) {
            $member->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        return $this->success($this->formatMember($member), 'Team member created.', 201);
    }

    public function updateTeamMember(Request $request, TeamMember $teamMember): JsonResponse
    {
        $validated = $request->validate([
            'section' => ['required', Rule::in([TeamMember::SECTION_MANAGEMENT, TeamMember::SECTION_GOVERNING_BOARD])],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $teamMember->update([
            'section' => $validated['section'],
            'name' => $validated['name'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $teamMember->sort_order,
        ]);

        if ($request->hasFile('photo')) {
            $teamMember->clearMediaCollection('photo');
            $teamMember->addMediaFromRequest('photo')->toMediaCollection('photo');
        }

        return $this->success($this->formatMember($teamMember->fresh()), 'Team member updated.');
    }

    public function destroyTeamMember(TeamMember $teamMember): JsonResponse
    {
        $teamMember->delete();

        return $this->success(null, 'Team member deleted.');
    }

    private function formatMember(TeamMember $m): array
    {
        return [
            'id' => $m->id,
            'section' => $m->section,
            'name' => $m->name,
            'title' => $m->title,
            'subtitle' => $m->subtitle,
            'linkedin_url' => $m->linkedin_url,
            'sort_order' => $m->sort_order,
            'photo_url' => $m->getFirstMedia('photo')?->getUrl() ?? asset('default_pfp.jpg'),
        ];
    }
}
