@php
    /** @var \App\Models\Blog|null $blog */
    $blog = $blog ?? null;
    $editorContent = \App\Support\BlogContentSanitizer::clean((string) old('content', $blog?->content));
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="title" class="block text-sm font-semibold text-gray-800 mb-1.5">Article title <span class="text-red-600">*</span></label>
        <input id="title" name="title" type="text" required maxlength="255" value="{{ old('title', $blog?->title) }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]" placeholder="A clear, specific headline">
        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-semibold text-gray-800 mb-1.5">URL slug</label>
        <input id="slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $blog?->slug) }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]" placeholder="generated-from-the-title">
        <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, and hyphens only. Leave blank to generate it.</p>
        @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="category" class="block text-sm font-semibold text-gray-800 mb-1.5">Category <span class="text-red-600">*</span></label>
        <input id="category" name="category" type="text" required maxlength="100" list="blog-category-options" value="{{ old('category', $blog?->category ?? 'Educational') }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]">
        <datalist id="blog-category-options"><option value="Announcement"><option value="Educational"><option value="Media appearances"><option value="Investment"><option value="Misc"></datalist>
        @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label for="excerpt" class="block text-sm font-semibold text-gray-800 mb-1.5">Summary</label>
        <textarea id="excerpt" name="excerpt" rows="3" maxlength="2000" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]" placeholder="A concise introduction used on the blog listing and in search results.">{{ old('excerpt', $blog?->excerpt) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">If empty, the public listing will generate a short preview from the article.</p>
        @error('excerpt')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-800 mb-1.5">Article content <span class="text-red-600">*</span></label>
        <div class="overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:border-[#0a5554] focus-within:ring-1 focus-within:ring-[#0a5554]">
            <div class="flex flex-wrap gap-1 border-b border-gray-200 bg-gray-50 p-2" role="toolbar" aria-label="Article formatting">
                <button type="button" data-editor-command="formatBlock" data-editor-value="p" class="blog-editor-button">Paragraph</button>
                <button type="button" data-editor-command="formatBlock" data-editor-value="h2" class="blog-editor-button">Heading 2</button>
                <button type="button" data-editor-command="formatBlock" data-editor-value="h3" class="blog-editor-button">Heading 3</button>
                <button type="button" data-editor-command="bold" class="blog-editor-button"><strong>Bold</strong></button>
                <button type="button" data-editor-command="italic" class="blog-editor-button"><em>Italic</em></button>
                <button type="button" data-editor-command="insertUnorderedList" class="blog-editor-button">• List</button>
                <button type="button" data-editor-command="insertOrderedList" class="blog-editor-button">1. List</button>
                <button type="button" id="blog-editor-link" class="blog-editor-button">Link</button>
                <button type="button" data-editor-command="removeFormat" class="blog-editor-button">Clear format</button>
            </div>
            <div id="blog-editor" contenteditable="true" class="min-h-[420px] px-5 py-4 text-base leading-7 text-gray-900 focus:outline-none" aria-label="Article content">{!! $editorContent !!}</div>
        </div>
        <textarea name="content" id="content" class="sr-only" tabindex="-1">{{ old('content', $blog?->content) }}</textarea>
        <p class="mt-2 text-xs text-gray-500">Designed for long-form writing. Use headings, short paragraphs, bold text, links, and lists for readability.</p>
        @error('content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="author_name" class="block text-sm font-semibold text-gray-800 mb-1.5">Author name</label>
        <input id="author_name" name="author_name" type="text" maxlength="255" value="{{ old('author_name', $blog?->author_name ?? auth()->user()->name) }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]">
        @error('author_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="author_role" class="block text-sm font-semibold text-gray-800 mb-1.5">Author title / role</label>
        <input id="author_role" name="author_role" type="text" maxlength="255" value="{{ old('author_role', $blog?->author_role) }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]" placeholder="Investment Associate">
        @error('author_role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="author_organization" class="block text-sm font-semibold text-gray-800 mb-1.5">Author organization</label>
        <input id="author_organization" name="author_organization" type="text" maxlength="255" value="{{ old('author_organization', $blog?->author_organization ?? 'Bangladesh Angels Network') }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]">
        @error('author_organization')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="published_at" class="block text-sm font-semibold text-gray-800 mb-1.5">Publication date & time</label>
        <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', $blog?->published_at?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]">
        <p class="mt-1 text-xs text-gray-500">If blank when publishing, the current time is used.</p>
        @error('published_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <fieldset class="md:col-span-2">
        <legend class="block text-sm font-semibold text-gray-800 mb-2">Visibility <span class="text-red-600">*</span></legend>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer gap-3 rounded-xl border border-gray-200 p-4 hover:border-green-400">
                <input type="radio" name="status" value="published" class="mt-1 text-green-600 focus:ring-green-600" @checked(old('status', $blog?->status ?? 'archived') === 'published')>
                <span><strong class="block text-gray-950">Published</strong><small class="text-gray-600">Visible to everyone on the public blog.</small></span>
            </label>
            <label class="flex cursor-pointer gap-3 rounded-xl border border-gray-200 p-4 hover:border-amber-400">
                <input type="radio" name="status" value="archived" class="mt-1 text-amber-600 focus:ring-amber-600" @checked(old('status', $blog?->status ?? 'archived') === 'archived')>
                <span><strong class="block text-gray-950">Archived</strong><small class="text-gray-600">Saved in admin, hidden from the public.</small></span>
            </label>
        </div>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </fieldset>
</div>

@push('head_styles')
<style>.blog-editor-button{border:1px solid #d1d5db;border-radius:.4rem;background:#fff;padding:.35rem .6rem;font-size:.75rem;font-weight:600;color:#374151}.blog-editor-button:hover{background:#ecfdf5;color:#065f46}.blog-editor-button:focus{outline:2px solid #0a5554;outline-offset:1px}#blog-editor h2{font-size:1.5rem;font-weight:800;margin:1.4rem 0 .5rem}#blog-editor h3{font-size:1.2rem;font-weight:800;margin:1.2rem 0 .4rem}#blog-editor p{margin:.6rem 0}#blog-editor ul{list-style:disc;margin:.7rem 0;padding-left:1.5rem}#blog-editor ol{list-style:decimal;margin:.7rem 0;padding-left:1.5rem}#blog-editor a{color:#0a5554;text-decoration:underline}</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('blog-editor');
    const input = document.getElementById('content');
    const form = editor && editor.closest('form');
    if (!editor || !input || !form) return;
    document.execCommand('defaultParagraphSeparator', false, 'p');

    document.querySelectorAll('[data-editor-command]').forEach(function (button) {
        button.addEventListener('click', function () {
            editor.focus();
            document.execCommand(button.dataset.editorCommand, false, button.dataset.editorValue || null);
        });
    });

    document.getElementById('blog-editor-link')?.addEventListener('click', function () {
        const url = window.prompt('Enter a full URL (https://…)');
        if (url) { editor.focus(); document.execCommand('createLink', false, url); }
    });

    form.addEventListener('submit', function () { input.value = editor.innerHTML.trim(); });
});
</script>
@endpush
