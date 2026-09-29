@extends('layouts.admin')
@section('page_title', 'Blogs | Dashboard')
@section('page_content')
<div class="container w-full p-6">
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-5 p-5 bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-green-700">Content library</p>
            <h1 class="text-2xl font-bold text-gray-950 mt-1">Blogs</h1>
            <p class="text-sm text-gray-600 mt-1">Create long-form articles and control exactly what appears on the public blog.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.blogs.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition">+ New blog post</a>
            <a href="{{ route('blogs.index') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-[#0a5554] hover:underline">View public blog →</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-300 text-green-900 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <form method="get" class="flex flex-col md:flex-row gap-3 mb-5 bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <label class="flex-1">
            <span class="sr-only">Search blogs</span>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search by title…" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554]">
        </label>
        <label>
            <span class="sr-only">Filter by status</span>
            <select name="status" class="w-full rounded-lg border-gray-300 px-3 py-2 text-gray-950 focus:border-[#0a5554] focus:ring-[#0a5554] md:w-44">
                <option value="all" @selected($status === 'all')>All statuses</option>
                <option value="published" @selected($status === 'published')>Published</option>
                <option value="archived" @selected($status === 'archived')>Archived</option>
            </select>
        </label>
        <button class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
        @if($search || $status !== 'all')<a href="{{ route('admin.blogs.index') }}" class="self-center text-sm font-semibold text-gray-600 hover:text-gray-950">Reset</a>@endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-3 font-semibold text-gray-700">Article</th>
                    <th class="px-5 py-3 font-semibold text-gray-700">Category</th>
                    <th class="px-5 py-3 font-semibold text-gray-700">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-700">Publication date</th>
                    <th class="px-5 py-3 font-semibold text-gray-700 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($blogs as $blog)
                    <tr class="hover:bg-gray-50/80 align-top">
                        <td class="px-5 py-4 max-w-md">
                            <a href="{{ route('admin.blogs.show', $blog) }}" class="font-bold text-gray-950 hover:text-[#0a5554]">{{ $blog->title }}</a>
                            <p class="mt-1 text-xs text-gray-500">/{{ $blog->slug }} · {{ $blog->readingMinutes() }} min read</p>
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $blog->category }}</td>
                        <td class="px-5 py-4">
                            <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize', 'bg-green-100 text-green-800' => $blog->isPublished(), 'bg-amber-100 text-amber-800' => !$blog->isPublished()])>{{ $blog->status }}</span>
                        </td>
                        <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ $blog->published_at?->format('d M Y, H:i') ?: 'Not published' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-x-3 gap-y-2 whitespace-nowrap">
                                <a href="{{ route('admin.blogs.show', $blog) }}" class="font-semibold text-gray-700 hover:underline">Read</a>
                                <a href="{{ route('admin.blogs.edit', $blog) }}" class="font-semibold text-[#0a5554] hover:underline">Edit</a>
                                <form method="post" action="{{ route('admin.blogs.status', $blog) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $blog->isPublished() ? 'archived' : 'published' }}">
                                    <button class="font-semibold {{ $blog->isPublished() ? 'text-amber-700' : 'text-green-700' }} hover:underline">{{ $blog->isPublished() ? 'Archive' : 'Publish' }}</button>
                                </form>
                                <form method="post" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this blog post? It will no longer appear in the active library.');">
                                    @csrf @method('DELETE')
                                    <button class="font-semibold text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-gray-600">No blog posts found. <a href="{{ route('admin.blogs.create') }}" class="font-semibold text-[#0a5554] hover:underline">Write the first article</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($blogs->hasPages())<div class="mt-6">{{ $blogs->links() }}</div>@endif
</div>
@endsection
