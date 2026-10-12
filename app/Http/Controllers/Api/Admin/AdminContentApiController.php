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
            'stats.*.value' => ['nullable', 'string', 'max:100'],
            'stats.*.stat_value' => ['nullable', 'string', 'max:100'],
            'stats.*.label' => ['nullable', 'string', 'max:255'],
            'stats.*.stat_label' => ['nullable', 'string', 'max:255'],
            'stats.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ($validated['stats'] as $item) {
            $value = $item['value'] ?? $item['stat_value'] ?? null;
            $label = $item['label'] ?? $item['stat_label'] ?? null;
            $order = $item['sort_order'] ?? null;

            $updateData = [];
            if ($value !== null) {
                $updateData['value'] = trim($value);
            }
            if ($label !== null) {
                $updateData['label'] = trim($label);
            }
            if ($order !== null) {
                $updateData['sort_order'] = (int) $order;
            }

            if (! empty($updateData)) {
                LandingPageStat::where('id', $item['id'])->update($updateData);
            }
        }

        return $this->success(LandingPageStat::query()->ordered()->get(), 'Homepage statistics updated successfully.');
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
        if (LandingProgramCard::query()->count() >= LandingProgramCard::MAX_CARDS) {
            return $this->error('The homepage can show a maximum of five program cards.', 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1200'],
            'primary_label' => ['nullable', 'string', 'max:120'],
            'link_label' => ['nullable', 'string', 'max:120'],
            'primary_link' => ['nullable', 'string', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'secondary_label' => ['nullable', 'string', 'max:120'],
            'secondary_link' => ['nullable', 'string', 'max:2048'],
            'theme' => ['nullable', 'string', 'in:blush,dark,mint,sand,white'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'dropdown_items' => ['nullable'],
        ]);

        $eyebrow = $validated['eyebrow'] ?? $validated['badge'] ?? 'Program';
        $primaryLabel = $validated['primary_label'] ?? $validated['link_label'] ?? 'Learn more';
        $primaryLink = $validated['primary_link'] ?? $validated['link_url'] ?? '#';

        $nextOrder = (int) (LandingProgramCard::query()->max('sort_order') ?? 0) + 1;

        $dropdownItems = $validated['dropdown_items'] ?? null;
        if (is_string($dropdownItems)) {
            $dropdownItems = json_decode($dropdownItems, true);
        }

        $card = LandingProgramCard::create([
            'eyebrow' => $eyebrow,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'primary_label' => $primaryLabel,
            'primary_link' => $primaryLink,
            'secondary_label' => $validated['secondary_label'] ?? null,
            'secondary_link' => $validated['secondary_link'] ?? null,
            'dropdown_items' => $dropdownItems,
            'theme' => $validated['theme'] ?? 'white',
            'sort_order' => (int) ($validated['sort_order'] ?? $nextOrder),
        ]);

        return $this->success($card, 'Homepage program card created successfully.', 201);
    }

    public function updateLandingProgramCard(Request $request, LandingProgramCard $landingProgramCard): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'badge' => ['nullable', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'max:1200'],
            'primary_label' => ['nullable', 'string', 'max:120'],
            'link_label' => ['nullable', 'string', 'max:120'],
            'primary_link' => ['nullable', 'string', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'secondary_label' => ['nullable', 'string', 'max:120'],
            'secondary_link' => ['nullable', 'string', 'max:2048'],
            'theme' => ['nullable', 'string', 'in:blush,dark,mint,sand,white'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'dropdown_items' => ['nullable'],
        ]);

        $updateData = [];

        if ($request->has('title')) {
            $updateData['title'] = $validated['title'];
        }
        if ($request->has('eyebrow') || $request->has('badge')) {
            $updateData['eyebrow'] = $validated['eyebrow'] ?? $validated['badge'] ?? $landingProgramCard->eyebrow;
        }
        if ($request->has('description')) {
            $updateData['description'] = $validated['description'];
        }
        if ($request->has('primary_label') || $request->has('link_label')) {
            $updateData['primary_label'] = $validated['primary_label'] ?? $validated['link_label'] ?? $landingProgramCard->primary_label;
        }
        if ($request->has('primary_link') || $request->has('link_url')) {
            $updateData['primary_link'] = $validated['primary_link'] ?? $validated['link_url'] ?? $landingProgramCard->primary_link;
        }
        if ($request->has('secondary_label')) {
            $updateData['secondary_label'] = $validated['secondary_label'];
        }
        if ($request->has('secondary_link')) {
            $updateData['secondary_link'] = $validated['secondary_link'];
        }
        if ($request->has('theme')) {
            $updateData['theme'] = $validated['theme'] ?? 'white';
        }
        if ($request->has('sort_order')) {
            $updateData['sort_order'] = (int) $validated['sort_order'];
        }
        if ($request->has('dropdown_items')) {
            $dropdownItems = $validated['dropdown_items'];
            if (is_string($dropdownItems)) {
                $dropdownItems = json_decode($dropdownItems, true);
            }
            $updateData['dropdown_items'] = $dropdownItems;
        }

        $landingProgramCard->update($updateData);

        return $this->success($landingProgramCard->fresh(), 'Homepage program card updated successfully.');
    }

    public function destroyLandingProgramCard(LandingProgramCard $landingProgramCard): JsonResponse
    {
        $landingProgramCard->delete();

        return $this->success(null, 'Homepage program card deleted successfully.');
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
