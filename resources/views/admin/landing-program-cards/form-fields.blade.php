@php
    $programCard = $card ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="eyebrow" class="block text-sm font-semibold text-gray-700 mb-1">Category label</label>
        <input type="text" name="eyebrow" id="eyebrow" value="{{ old('eyebrow', $programCard?->eyebrow) }}" required maxlength="120"
               placeholder="Women-led investment"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
    </div>
    <div>
        <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Program title</label>
        <input type="text" name="title" id="title" value="{{ old('title', $programCard?->title) }}" required maxlength="255"
               placeholder="BWIN"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
    </div>
</div>

<div>
    <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
    <textarea name="description" id="description" rows="5" required maxlength="1200"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">{{ old('description', $programCard?->description) }}</textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="primary_label" class="block text-sm font-semibold text-gray-700 mb-1">Primary button label</label>
        <input type="text" name="primary_label" id="primary_label" value="{{ old('primary_label', $programCard?->primary_label) }}" required maxlength="120"
               placeholder="Explore program"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
    </div>
    <div>
        <label for="primary_link" class="block text-sm font-semibold text-gray-700 mb-1">Primary button link</label>
        <input type="text" name="primary_link" id="primary_link" value="{{ old('primary_link', $programCard?->primary_link) }}" required maxlength="2048"
               placeholder="/bwin or https://example.com"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
    </div>
</div>

<div>
    <h2 class="text-sm font-bold text-gray-800">Secondary button <span class="font-normal text-gray-500">(optional)</span></h2>
    <p class="text-xs text-gray-500 mt-1 mb-3">Fill in both fields to display a second action.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label for="secondary_label" class="block text-sm font-semibold text-gray-700 mb-1">Button label</label>
            <input type="text" name="secondary_label" id="secondary_label" value="{{ old('secondary_label', $programCard?->secondary_label) }}" maxlength="120"
                   placeholder="Book a call"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
        </div>
        <div>
            <label for="secondary_link" class="block text-sm font-semibold text-gray-700 mb-1">Button link</label>
            <input type="text" name="secondary_link" id="secondary_link" value="{{ old('secondary_link', $programCard?->secondary_link) }}" maxlength="2048"
                   placeholder="https://calendar.example.com"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
        </div>
    </div>
</div>

<div class="border-t border-gray-200 pt-5">
    <h2 class="text-sm font-bold text-gray-800">Dropdown button <span class="font-normal text-gray-500">(optional)</span></h2>
    <p class="text-xs text-gray-500 mt-1 mb-2">Add a dropdown button to show up to six menu options on the card. If the button label is cleared, the dropdown button won't be shown on the homepage.</p>
    <div class="mb-3">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Dropdown button label is required first to add dropdown options.
        </span>
    </div>
    <div class="mb-4">
        <label for="dropdown_label" class="block text-sm font-semibold text-gray-700 mb-1">Dropdown button label</label>
        <input type="text" name="dropdown_label" id="dropdown_label"
               value="{{ old('dropdown_label', $programCard?->dropdown_items['label'] ?? '') }}" maxlength="120"
               placeholder="View Services"
               class="w-full md:w-1/2 rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
        @error('dropdown_label')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-600">Dropdown options (up to 6)</span>
            <span class="text-xs text-gray-400">Leave blank if unused</span>
        </div>

        @php
            $savedItems = $programCard?->dropdown_items['items'] ?? [];
        @endphp

        <div class="space-y-3">
            @for ($i = 0; $i < 6; $i++)
                @php
                    $itemTitle = old("dropdown_item_title.$i", $savedItems[$i]['title'] ?? '');
                    $itemLink = old("dropdown_item_link.$i", $savedItems[$i]['link'] ?? '');
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-1 text-xs font-bold text-gray-400">#{{ $i + 1 }}</div>
                    <div class="md:col-span-5">
                        <input type="text" name="dropdown_item_title[]" value="{{ $itemTitle }}" maxlength="120"
                               placeholder="Title (e.g. Angel Syndicate)"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
                        @error("dropdown_item_title.$i")
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-6">
                        <input type="text" name="dropdown_item_link[]" value="{{ $itemLink }}" maxlength="2048"
                               placeholder="Link (e.g. /angel-syndicate or https://...)"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
                        @error("dropdown_item_link.$i")
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="theme" class="block text-sm font-semibold text-gray-700 mb-1">Card appearance</label>
        <select name="theme" id="theme" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
            @foreach (\App\Models\LandingProgramCard::THEMES as $value => $label)
                <option value="{{ $value }}" @selected(old('theme', $programCard?->theme ?? 'white') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="sort_order" class="block text-sm font-semibold text-gray-700 mb-1">Sort order</label>
        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $programCard?->sort_order) }}" min="0" max="999" step="1"
               placeholder="Automatically placed last"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-green-600 focus:ring-1 focus:ring-green-600">
    </div>
</div>
