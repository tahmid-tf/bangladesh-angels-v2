@extends('layouts.admin')
@section('page_title', 'Publications | Dashboard')
@section('page_content')
<section class="container mx-auto w-full p-4 md:p-6">
    <div class="mb-5 flex flex-col items-start justify-between gap-5 rounded-xl bg-white p-4 shadow md:flex-row md:items-center md:p-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-green-700">Research library</p>
            <h1 class="mt-1 text-lg font-bold text-gray-950 md:text-xl">Publications</h1>
            <p class="mt-1 text-sm text-gray-600">Upload reports and make them available to the public as downloadable PDFs.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.publications.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700">+ New publication</a>
            <a href="{{ route('publications.index') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-[#0a5554] hover:underline">View public library →</a>
        </div>
    </div>

    @if(session('success'))<div class="mb-5 rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-green-900">{{ session('success') }}</div>@endif

    <form method="get" class="mb-5 flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm md:flex-row">
        <label class="flex-1"><span class="sr-only">Search publications</span><input type="search" name="search" value="{{ $search }}" placeholder="Search by title, summary, or category" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]"></label>
        <label><span class="sr-only">Filter by status</span><select name="status" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554] md:w-44"><option value="all" @selected($status === 'all')>All statuses</option><option value="published" @selected($status === 'published')>Published</option><option value="archived" @selected($status === 'archived')>Archived</option></select></label>
        <button class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
        @if($search || $status !== 'all')<a href="{{ route('admin.publications.index') }}" class="self-center text-sm font-semibold text-gray-600 hover:text-gray-950">Reset</a>@endif
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50"><tr><th class="px-5 py-3 font-semibold text-gray-700">Publication</th><th class="px-5 py-3 font-semibold text-gray-700">Category</th><th class="px-5 py-3 font-semibold text-gray-700">Status</th><th class="px-5 py-3 font-semibold text-gray-700">PDF</th><th class="px-5 py-3 text-right font-semibold text-gray-700">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($publications as $publication)
                    <tr class="align-top hover:bg-gray-50/80">
                        <td class="max-w-md px-5 py-4"><p class="font-bold text-gray-950">{{ $publication->title }}</p><p class="mt-1 text-xs text-gray-500">{{ $publication->excerpt ?: 'No summary added.' }}</p></td>
                        <td class="px-5 py-4 text-gray-700">{{ $publication->category }}</td>
                        <td class="px-5 py-4"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize', 'bg-green-100 text-green-800' => $publication->isPublished(), 'bg-amber-100 text-amber-800' => !$publication->isPublished()])>{{ $publication->status }}</span></td>
                        <td class="px-5 py-4 text-gray-600">{{ $publication->pdf_original_name }}</td>
                        <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-x-3 gap-y-2 whitespace-nowrap"><a href="{{ route('admin.publications.edit', $publication) }}" class="font-semibold text-[#0a5554] hover:underline">Edit</a><form method="post" action="{{ route('admin.publications.status', $publication) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $publication->isPublished() ? 'archived' : 'published' }}"><button class="font-semibold {{ $publication->isPublished() ? 'text-amber-700' : 'text-green-700' }} hover:underline">{{ $publication->isPublished() ? 'Archive' : 'Publish' }}</button></form><form method="post" action="{{ route('admin.publications.destroy', $publication) }}" onsubmit="return confirm('Remove this publication?');">@csrf @method('DELETE')<button class="font-semibold text-red-600 hover:underline">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-gray-600">No publications found. <a href="{{ route('admin.publications.create') }}" class="font-semibold text-[#0a5554] hover:underline">Add the first publication</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($publications->hasPages())<div class="mt-6">{{ $publications->links() }}</div>@endif
</section>
@endsection
