<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingProgramCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingProgramCardController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $cards = LandingProgramCard::query()->ordered()->get();

        return view('admin.landing-program-cards.index', compact('cards'));
    }

    public function create(): View|RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (LandingProgramCard::query()->count() >= LandingProgramCard::MAX_CARDS) {
            return redirect()
                ->route('admin.landing-program-cards')
                ->with('error', 'The homepage can show a maximum of five program cards.');
        }

        return view('admin.landing-program-cards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (LandingProgramCard::query()->count() >= LandingProgramCard::MAX_CARDS) {
            return redirect()
                ->route('admin.landing-program-cards')
                ->with('error', 'The homepage can show a maximum of five program cards.');
        }

        $validated = $this->validatedCard($request);
        $nextOrder = (int) (LandingProgramCard::query()->max('sort_order') ?? 0) + 1;

        LandingProgramCard::query()->create([
            'eyebrow' => $validated['eyebrow'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'primary_label' => $validated['primary_label'],
            'primary_link' => $validated['primary_link'],
            'secondary_label' => $validated['secondary_label'] ?? null,
            'secondary_link' => $validated['secondary_link'] ?? null,
            'dropdown_items' => $this->buildDropdownItems($request),
            'theme' => $validated['theme'],
            'sort_order' => (int) ($validated['sort_order'] ?? $nextOrder),
        ]);

        return redirect()
            ->route('admin.landing-program-cards')
            ->with('success', 'Homepage program card added.');
    }

    public function edit(LandingProgramCard $landingProgramCard): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('admin.landing-program-cards.edit', ['card' => $landingProgramCard]);
    }

    public function update(Request $request, LandingProgramCard $landingProgramCard): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validatedCard($request);

        $landingProgramCard->update([
            'eyebrow' => $validated['eyebrow'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'primary_label' => $validated['primary_label'],
            'primary_link' => $validated['primary_link'],
            'secondary_label' => $validated['secondary_label'] ?? null,
            'secondary_link' => $validated['secondary_link'] ?? null,
            'dropdown_items' => $this->buildDropdownItems($request),
            'theme' => $validated['theme'],
            'sort_order' => (int) ($validated['sort_order'] ?? $landingProgramCard->sort_order),
        ]);

        return redirect()
            ->route('admin.landing-program-cards')
            ->with('success', 'Homepage program card updated.');
    }

    public function destroy(LandingProgramCard $landingProgramCard): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $landingProgramCard->delete();

        return redirect()
            ->route('admin.landing-program-cards')
            ->with('success', 'Homepage program card removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCard(Request $request): array
    {
        $request->merge([
            'secondary_label' => $request->filled('secondary_label') ? trim((string) $request->input('secondary_label')) : null,
            'secondary_link' => $request->filled('secondary_link') ? trim((string) $request->input('secondary_link')) : null,
            'dropdown_label' => $request->filled('dropdown_label') ? trim((string) $request->input('dropdown_label')) : null,
            'sort_order' => $request->filled('sort_order') ? $request->input('sort_order') : null,
        ]);

        return $request->validate([
            'eyebrow' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1200'],
            'primary_label' => ['required', 'string', 'max:120'],
            'primary_link' => ['required', 'string', 'max:2048', $this->linkRule()],
            'secondary_label' => ['nullable', 'string', 'max:120', 'required_with:secondary_link'],
            'secondary_link' => ['nullable', 'string', 'max:2048', 'required_with:secondary_label', $this->linkRule()],
            'dropdown_label' => ['nullable', 'string', 'max:120'],
            'dropdown_item_title' => ['nullable', 'array', 'max:6'],
            'dropdown_item_title.*' => ['nullable', 'string', 'max:120'],
            'dropdown_item_link' => ['nullable', 'array', 'max:6'],
            'dropdown_item_link.*' => ['nullable', 'string', 'max:2048', $this->linkRule(nullable: true)],
            'theme' => ['required', Rule::in(array_keys(LandingProgramCard::THEMES))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    /**
     * @return array{label: string, items: array<int, array{title: string, link: string}>}|null
     */
    private function buildDropdownItems(Request $request): ?array
    {
        $label = $request->filled('dropdown_label') ? trim((string) $request->input('dropdown_label')) : null;

        if ($label === null || $label === '') {
            return null;
        }

        $titles = (array) $request->input('dropdown_item_title', []);
        $links = (array) $request->input('dropdown_item_link', []);
        $items = [];

        for ($i = 0; $i < 6; $i++) {
            $title = isset($titles[$i]) ? trim((string) $titles[$i]) : '';
            $link = isset($links[$i]) ? trim((string) $links[$i]) : '';

            if ($title !== '') {
                $items[] = [
                    'title' => $title,
                    'link' => $link !== '' ? $link : '#',
                ];
            }
        }

        return [
            'label' => $label,
            'items' => $items,
        ];
    }

    private function linkRule(bool $nullable = false): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($nullable): void {
            if ($nullable && ($value === null || $value === '')) {
                return;
            }

            if (! is_string($value)) {
                $fail('The '.$attribute.' must be a valid link.');

                return;
            }

            $isWebUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
            $isRelative = str_starts_with($value, '/');
            $isAnchor = str_starts_with($value, '#');
            $isContactLink = preg_match('/^(mailto|tel):/i', $value) === 1;

            if (! $isWebUrl && ! $isRelative && ! $isAnchor && ! $isContactLink) {
                $fail('The '.$attribute.' must be a full URL, relative path, anchor, email, or telephone link.');
            }
        };
    }
}
