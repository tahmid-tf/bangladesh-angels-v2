@extends('layouts.admin')
@section('page_title','Add a new member | Bangladesh Angels Network')
@section('page_content')
<section class="container mx-auto px-6 py-12 bg-white rounded-lg shadow-lg mt-8">
    <header class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold">Add New Deal</h1>
            <p class="text-gray-500">Dashboard &gt; Deals &gt; Add new deal</p>
        </div>
        <div class="flex space-x-4">
            <button type="submit" form="deal-form" class="px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add new deal</button>
        </div>
    </header>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="deal-form" action="{{ route('deal.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf

        <!-- Deal Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Upload Logo -->
            <div class="text-center border-dashed border-2 border-gray-300 rounded-lg p-6">
                <label class="block cursor-pointer">
                    <div class="mb-4">
                        <img 
                            id="logo-preview" 
                            src="{{ asset('upload_dealcover.png') }}" 
                            alt="Upload Placeholder" 
                            class="mx-auto rounded-full h-24 w-24 object-cover"
                        >
                    </div>
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logo-input">
                    <p class="text-gray-500 text-sm">Upload logo</p>
                    <p class="text-gray-400 text-xs">Allowed: *.jpeg, *.png, *.gif (Max: 3.1 MB)</p>
                </label>
            </div>

            <script>
                // Preview Logo
                const logoInput = document.getElementById('logo-input');
                const logoPreview = document.getElementById('logo-preview');

                logoInput.addEventListener('change', function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            logoPreview.src = e.target.result; // Set preview image source
                            logoPreview.style.display = 'block'; // Ensure the preview is visible
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // Reset to default placeholder if no file is selected
                        logoPreview.src = "{{ asset('upload_dealcover.png') }}";
                    }
                });
            </script>

            <style>
                /* Ensure the preview looks good */
                #logo-preview {
                    object-fit: cover; /* Ensures image fills the rounded area proportionally */
                    border: 2px solid #e2e8f0; /* Add a light border for better visibility */
                    padding: 0.25rem; /* Add space around the image */
                    background-color: #f9fafb; /* Matches the field background color */
                }
            </style>


            <!-- Input Fields -->
            <div class="md:col-span-2 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="block text-gray-700 font-semibold mb-2">Title *</label>
                        <input type="text" id="title" name="title" placeholder="Enter Deal Title" class="input-field" value="{{ old('title') }}" required>
                        @error('title')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <select name="type"  id="type" class="input-field" required>
                        <option value="">Deal Type</option>
                        <option value="commit" {{ old('type') === 'commit' ? 'selected' : '' }}>Commit</option>
                        <option value="invest" {{ old('type') === 'invest' ? 'selected' : '' }}>Invest</option>
                        <option value="review" {{ old('type') === 'review' ? 'selected' : '' }}>Review</option>
                        <option value="portfolio" {{ old('type') === 'portfolio' ? 'selected' : '' }}>Portfolio only</option>
                    </select>
                    <select name="status" id="status" class="input-field" required>
                        <option value="">Deal Status</option>
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <input type="hidden" name="is_portfolio" value="0">
                    <label for="is_portfolio" class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" id="is_portfolio" name="is_portfolio" value="1" class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500" {{ old('is_portfolio') ? 'checked' : '' }}>
                        <span>
                            <span class="block font-semibold text-gray-700">Also show in Portfolio</span>
                            <span class="block text-sm text-gray-500">Select Invest above and check this option to show the company in both Invest and Portfolio.</span>
                        </span>
                    </label>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3">
                    <input type="hidden" name="show_on_homepage" value="0">
                    <label for="show_on_homepage" class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" id="show_on_homepage" name="show_on_homepage" value="1" 
                            class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500" 
                            {{ old('show_on_homepage') ? 'checked' : '' }}
                            {{ (($homepageSelectionsCount ?? 0) >= \App\Models\HomepagePortfolio::MAX_SELECTIONS) ? 'disabled' : '' }}>
                        <div>
                            <span class="block font-semibold text-gray-800">Feature in Homepage Selected Portfolios</span>
                            <span class="block text-sm text-gray-600">
                                Showcase this company in the Portfolio section of the homepage (requires deal type 'Portfolio only' or 'Also show in Portfolio').
                                (Currently {{ $homepageSelectionsCount ?? 0 }} / {{ \App\Models\HomepagePortfolio::MAX_SELECTIONS }} selected)
                                @if (($homepageSelectionsCount ?? 0) >= \App\Models\HomepagePortfolio::MAX_SELECTIONS)
                                    <span class="text-amber-700 font-medium block mt-1">Maximum of {{ \App\Models\HomepagePortfolio::MAX_SELECTIONS }} portfolios already selected.</span>
                                @endif
                            </span>
                        </div>
                    </label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sector" class="block text-gray-700 font-semibold mb-2">Sector *</label>
                        <input type="text" id="sector" name="sector" placeholder="Sector" class="input-field" value="{{ old('sector') }}" required>
                        @error('sector')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="investment_stage" class="block text-gray-700 font-semibold mb-2">Investment Stage</label>
                        <select id="investment_stage" name="investment_stage" class="input-field">
                            <option value="">Select Investment Stage</option>
                            <option value="">Not Applicable</option>
                            <option value="Pre Seed" {{ old('investment_stage') == 'Pre Seed' ? 'selected' : '' }}>Pre Seed</option>
                            <option value="Seed" {{ old('investment_stage') == 'Seed' ? 'selected' : '' }}>Seed</option>
                            <option value="Series A" {{ old('investment_stage') == 'Series A' ? 'selected' : '' }}>Series A</option>
                            <option value="Series B" {{ old('investment_stage') == 'Series B' ? 'selected' : '' }}>Series B</option>
                            <option value="Series C" {{ old('investment_stage') == 'Series C' ? 'selected' : '' }}>Series C</option>
                            <option value="Series D" {{ old('investment_stage') == 'Series D' ? 'selected' : '' }}>Series D</option>
                        </select>
                        
                        @error('investment_stage')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div>
                    <label for="amount_seeking" class="block text-gray-700 font-semibold mb-2">Amount Seeking</label>
                    <input type="number" id="amount_seeking" name="amount_seeking" placeholder="Amount Seeking" class="input-field" value="{{ old('amount_seeking') }}">
                    @error('amount_seeking')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-gray-700 font-semibold mb-2">Description *</label>
                    <textarea id="description" name="description" placeholder="Description" rows="4" class="input-field" required>{{ old('description') }}</textarea>
                    @error('description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

        </div>

        <!-- Company Cover -->
        <div class="border-dashed border-2 border-gray-300 rounded-lg p-6">
            <label class="block cursor-pointer text-center">
                <div class="mb-4">
                    <img 
                        id="company-cover-preview" 
                        src="{{ asset('upload_dealcover.png') }}" 
                        alt="Upload Placeholder" 
                        class="mx-auto rounded-lg h-40 w-full object-cover"
                        style="display: block;"
                    >
                </div>
                <input type="file" name="company_cover" accept="image/*" class="hidden" id="company-cover-input">
                <p class="text-gray-500 text-sm">Select Deal Cover Image</p>
                <p class="text-gray-400 text-xs">Drop files here or click <span class="text-blue-500 underline">browse</span> through your machine</p>
            </label>
        </div>

        <script>
            // Preview Company Cover
            const companyCoverInput = document.getElementById('company-cover-input');
            const companyCoverPreview = document.getElementById('company-cover-preview');
            
            companyCoverInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        companyCoverPreview.src = e.target.result;
                        companyCoverPreview.style.display = 'block'; // Ensure image is displayed
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Reset to the default placeholder if no file is selected
                    companyCoverPreview.src = "{{ asset('upload_dealcover.png') }}";
                }
            });
        </script>

        <style>
            /* Add specific styling to ensure the preview looks good */
            #company-cover-preview {
                object-fit: cover; /* Ensures the image fills the area proportionally */
                border: 1px solid #e2e8f0; /* Adds a slight border for better visuals */
                padding: 0.25rem; /* Space around the image */
                background-color: #f9fafb; /* Background to match input fields */
            }
        </style>
        <div>
            <label for="pitch_deck_url" class="block text-gray-700 font-semibold mb-2">Pitch deck URL (public “View” on this deal)</label>
            <input type="url" id="pitch_deck_url" name="pitch_deck_url" placeholder="https://… (PDF, Google Drive, Dropbox, etc.)" class="input-field" value="{{ old('pitch_deck_url') }}">
            <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                This is the link behind the gray <strong>View</strong> button on the deal’s public page (members who can access deals). Paste a full <code class="text-[11px] bg-gray-100 px-1 rounded">https://</code> URL — there is no separate file upload here.
                The general network brochure (<strong>MoU.pdf</strong> on the Startups / Resources side) is not set on this form; that file lives in <code class="text-[11px] bg-gray-100 px-1 rounded">public/MoU.pdf</code> unless you change it in code or elsewhere.
            </p>
            @error('pitch_deck_url')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="substack_link" class="block text-gray-700 font-semibold mb-2">Substack Link</label>
            <input type="text" id="substack_link" name="substack_link" placeholder="Enter link here" class="input-field" value="{{ old('substack_link') }}">
            @error('substack_link')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="invest_link" class="block text-gray-700 font-semibold mb-2">Invest Link</label>
            <input type="text" id="invest_link" name="invest_link" placeholder="Enter link here (if Applicable)" class="input-field" value="{{ old('invest_link') }}">
            @error('invest_link')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="commit_link" class="block text-gray-700 font-semibold mb-2">Commit Link</label>
            <input type="text" id="commit_link" name="commit_link" placeholder="Enter link here (if Applicable)" class="input-field" value="{{ old('commit_link') }}">
            @error('commit_link')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="groupchat_invite_link" class="block text-gray-700 font-semibold mb-2">WhatsApp Groupchat Invite Link</label>
            <input type="text" id="groupchat_invite_link" name="groupchat_invite_link" placeholder="Enter link here (if Applicable)" class="input-field" value="{{ old('groupchat_invite_link') }}">
            @error('groupchat_invite_link')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <!-- Key Metrics Section -->
        <div id="key-metrics-section">
            <h2 class="text-lg font-bold mb-4">Key Metrics</h2>
                <div id="key-metrics-container" class="space-y-4">
                    @if(is_array(old('key_metrics')))
                        @foreach(old('key_metrics') as $index => $metric)
                            <div class="grid grid-cols-2 gap-4">
                                <input 
                                    type="text" 
                                    name="key_metrics[{{ $index }}][name]" 
                                    placeholder="Metric Name" 
                                    class="input-field" 
                                    value="{{ $metric['name'] ?? '' }}" 
                                    
                                >
                                <input 
                                    type="text" 
                                    name="key_metrics[{{ $index }}][value]" 
                                    placeholder="Metric Value" 
                                    class="input-field" 
                                    value="{{ $metric['value'] ?? '' }}" 
                                    
                                >
                            </div>
        </div>
            @endforeach
        @else
            <!-- Default empty field -->
            <div class="grid grid-cols-2 gap-4">
                <input 
                    type="text" 
                    name="key_metrics[0][name]" 
                    placeholder="Metric Name" 
                    class="input-field" 
            
                >
                <input 
                    type="text" 
                    name="key_metrics[0][value]" 
                    placeholder="Metric Value" 
                    class="input-field" 
                    
                >
            </div>
        @endif
    </div>

    <button 
        type="button" 
        id="add-key-metric-btn" 
        class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg mt-4">
        + Add Key Metric
    </button>
</div>

<script>
    // Initialize a counter for unique key metric IDs
    let metricCounter = document.querySelectorAll('#key-metrics-container .grid').length;

    // Add another key metric dynamically
    document.getElementById('add-key-metric-btn').addEventListener('click', function () {
        const container = document.getElementById('key-metrics-container');
        const newField = `
            <div class="grid grid-cols-2 gap-4">
                <input 
                    type="text" 
                    name="key_metrics[${metricCounter}][name]" 
                    placeholder="Metric Name" 
                    class="input-field" 
                >
                <input 
                    type="text" 
                    name="key_metrics[${metricCounter}][value]" 
                    placeholder="Metric Value" 
                    class="input-field" 
                >
            </div>`;
        container.insertAdjacentHTML('beforeend', newField);
        metricCounter++;
    });
</script>


    </form>
</section>

<style>
    .input-field {
        border: 1px solid #e2e8f0;
        padding: 0.5rem;
        border-radius: 0.375rem;
        width: 100%;
        background-color: #f9fafb;
    }

    .input-field:focus {
        outline: none;
        border-color: #34d399;
        box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.5);
    }
</style>


@endsection
