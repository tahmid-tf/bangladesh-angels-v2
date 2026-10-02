<div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Livewire Feedback Message -->
    @if ($feedbackMessage)
        <div class="p-4 bg-emerald-50 border-b border-emerald-200 flex items-center justify-between text-emerald-800 text-sm">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button type="button" wire:click="dismissFeedback" class="text-emerald-600 hover:text-emerald-800 text-xs font-semibold ml-4">
                Dismiss &times;
            </button>
        </div>
    @endif

    <!-- Header & Action Bar -->
    <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2.5">
                <h2 class="text-lg font-bold text-gray-900">Current Homepage Display Order</h2>
                <!-- Live indicator -->
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="Changes update instantly without page reloads">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Reorder
                </span>
                <!-- Loading indicator -->
                <div wire:loading wire:target="moveUp, moveDown, remove, batchRemove, clearAll" class="text-xs text-gray-500 flex items-center gap-1 font-medium">
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Updating...</span>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">Companies will be presented in this exact order on the homepage grid (left to right, top to bottom).</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Batch Delete Button (Visible when items selected) -->
            @if (count($selectedIds) > 0)
                <button type="button" 
                    wire:click="batchRemove" 
                    wire:confirm="Remove the {{ count($selectedIds) }} selected portfolio {{ count($selectedIds) === 1 ? 'company' : 'companies' }} from the homepage selection?"
                    wire:loading.attr="disabled"
                    class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>Remove Selected ({{ count($selectedIds) }})</span>
                </button>
            @endif

            @if ($selectedPortfolios->isNotEmpty())
                <button type="button" 
                    wire:click="clearAll" 
                    wire:confirm="Clear all custom selections? The homepage will revert to showing the latest 8 portfolio companies automatically."
                    wire:loading.attr="disabled"
                    class="text-xs text-red-600 hover:text-red-800 hover:underline font-semibold flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    <span>Clear All (Reset)</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm divide-y divide-gray-200">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 w-10 text-center">
                        <input type="checkbox" 
                            wire:model.live="selectAll" 
                            class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer" 
                            title="Select All for Removal">
                    </th>
                    <th class="px-5 py-3 font-semibold w-16 text-center">Position</th>
                    <th class="px-5 py-3 font-semibold">Company</th>
                    <th class="px-5 py-3 font-semibold">Sector</th>
                    <th class="px-5 py-3 font-semibold">Listing Type</th>
                    <th class="px-5 py-3 font-semibold text-center">Live Reorder</th>
                    <th class="px-5 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($selectedPortfolios as $index => $item)
                    @php
                        $deal = $item->deal;
                        $isSelected = in_array((string) $item->id, array_map('strval', $selectedIds), true);
                    @endphp
                    <tr wire:key="portfolio-row-{{ $item->id }}" class="hover:bg-gray-50/80 transition-colors {{ $isSelected ? 'bg-red-50/40' : '' }}">
                        <!-- Checkbox for Batch Deletion -->
                        <td class="px-4 py-4 text-center">
                            <input type="checkbox" 
                                value="{{ $item->id }}" 
                                wire:model.live="selectedIds" 
                                class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                        </td>

                        <!-- Position -->
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs">
                                #{{ $item->sort_order }}
                            </span>
                        </td>

                        <!-- Company -->
                        <td class="px-5 py-4">
                            @if ($deal)
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $deal->getLogoUrl() }}" alt="{{ $deal->title }} Logo" class="h-10 w-10 rounded-full object-cover border border-gray-200 shrink-0">
                                    <div>
                                        <a href="{{ route('deal.view', $deal) }}" target="_blank" class="font-semibold text-gray-900 hover:text-green-700 hover:underline">
                                            {{ $deal->title }}
                                        </a>
                                        @if ($deal->investment_stage)
                                            <p class="text-xs text-gray-400">{{ $deal->investment_stage }}</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-red-500 italic">Deleted Deal (ID: {{ $item->deal_id }})</span>
                            @endif
                        </td>

                        <!-- Sector -->
                        <td class="px-5 py-4 text-gray-600">
                            {{ $deal?->sector ?: 'N/A' }}
                        </td>

                        <!-- Listing Type -->
                        <td class="px-5 py-4">
                            @if ($deal)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $deal->type === 'portfolio' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $deal->type === 'portfolio' ? 'Portfolio only' : 'Invest & Portfolio' }}
                                </span>
                            @endif
                        </td>

                        <!-- Livewire Reorder (Move Up / Down) -->
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                <!-- Move Up -->
                                <button type="button" 
                                    wire:click="moveUp({{ $item->id }})" 
                                    wire:loading.attr="disabled"
                                    class="p-1.5 rounded hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 disabled:opacity-30 disabled:cursor-not-allowed transition" 
                                    title="Move Up (Instant)" 
                                    {{ $index === 0 ? 'disabled' : '' }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <!-- Move Down -->
                                <button type="button" 
                                    wire:click="moveDown({{ $item->id }})" 
                                    wire:loading.attr="disabled"
                                    class="p-1.5 rounded hover:bg-emerald-50 text-gray-600 hover:text-emerald-700 disabled:opacity-30 disabled:cursor-not-allowed transition" 
                                    title="Move Down (Instant)" 
                                    {{ $index === $selectedPortfolios->count() - 1 ? 'disabled' : '' }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-3">
                                @if ($deal)
                                    <a href="{{ route('edit.deal', $deal->id) }}" target="_blank" class="text-[#0a5554] hover:underline font-semibold text-xs">
                                        Edit Deal
                                    </a>
                                @endif
                                <button type="button" 
                                    wire:click="remove({{ $item->id }})" 
                                    wire:confirm="Remove {{ $deal?->title ?? 'this portfolio' }} from homepage selection?"
                                    wire:loading.attr="disabled"
                                    class="text-red-600 hover:text-red-800 hover:underline font-semibold text-xs">
                                    Remove
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <p class="text-base font-semibold text-gray-700">No custom portfolios selected</p>
                            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                                The homepage is currently showcasing the latest {{ $maxSelections }} portfolio companies automatically.
                                Use the selector above or the Batch Selection panel below to pick specific portfolios.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
