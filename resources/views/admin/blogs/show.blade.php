@extends('layouts.admin')
@section('page_title', $blog->title.' | Dashboard')
@section('page_content')
<div class="container w-full max-w-5xl p-6">
    <div class="flex flex-col lg:flex-row justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.blogs.index') }}" class="text-sm font-semibold text-[#0a5554] hover:underline">← Back to blogs</a>
            <div class="mt-4 flex flex-wrap items-center gap-2"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold capitalize', 'bg-green-100 text-green-800' => $blog->isPublished(), 'bg-amber-100 text-amber-800' => !$blog->isPublished()])>{{ $blog->status }}</span><span class="text-sm text-gray-500">{{ $blog->category }} · {{ $blog->readingMinutes() }} min read</span></div>
        </div>
        <div class="flex flex-wrap items-start gap-3">
            @if($blog->isPublished())<a href="{{ route('blogs.show', $blog->slug) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">View public ↗</a>@endif
            <a href="{{ route('admin.blogs.edit', $blog) }}" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Edit article</a>
        </div>
    </div>

    @if(session('success'))<div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-900">{{ session('success') }}</div>@endif

    <article class="rounded-xl border border-gray-100 bg-white px-6 py-8 shadow-sm sm:px-10">
        <header class="max-w-3xl border-b border-gray-100 pb-7">
            <h1 class="text-3xl font-extrabold leading-tight text-gray-950">{{ $blog->title }}</h1>
            @if($blog->excerpt)<p class="mt-4 text-lg leading-8 text-gray-600">{{ $blog->excerpt }}</p>@endif
            <div class="mt-5 text-sm text-gray-600"><strong class="text-gray-900">{{ $blog->author_name ?: 'Bangladesh Angels Network' }}</strong>@if($blog->author_role) · {{ $blog->author_role }}@endif @if($blog->author_organization) · {{ $blog->author_organization }}@endif<br>{{ $blog->published_at?->format('d M Y, H:i') ?: 'No publication date' }} · /{{ $blog->slug }}</div>
        </header>
        <div class="admin-blog-preview max-w-3xl pt-7">{!! $blog->content !!}</div>
    </article>

    <div class="mt-6 flex flex-wrap gap-3">
        <form method="post" action="{{ route('admin.blogs.status', $blog) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $blog->isPublished() ? 'archived' : 'published' }}"><button class="rounded-lg px-5 py-2.5 text-sm font-semibold {{ $blog->isPublished() ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-green-100 text-green-900 hover:bg-green-200' }}">{{ $blog->isPublished() ? 'Archive this post' : 'Publish this post' }}</button></form>
        <form method="post" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this blog post? It will no longer appear in the active library.');">@csrf @method('DELETE')<button class="rounded-lg bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">Delete</button></form>
    </div>
</div>
@endsection

@push('head_styles')
<style>.admin-blog-preview{color:#374151;font-size:1rem;line-height:1.8}.admin-blog-preview p{margin:0 0 1.15rem}.admin-blog-preview h2{color:#111827;font-size:1.55rem;font-weight:800;line-height:1.3;margin:2rem 0 .75rem}.admin-blog-preview h3{color:#111827;font-size:1.25rem;font-weight:800;margin:1.6rem 0 .6rem}.admin-blog-preview ul,.admin-blog-preview ol{margin:1rem 0 1.25rem;padding-left:1.5rem}.admin-blog-preview ul{list-style:disc}.admin-blog-preview ol{list-style:decimal}.admin-blog-preview li{margin:.35rem 0}.admin-blog-preview blockquote{border-left:4px solid #0a5554;background:#f0fdf4;padding:1rem 1.25rem;margin:1.5rem 0}.admin-blog-preview a{color:#0a5554;text-decoration:underline}</style>
@endpush
