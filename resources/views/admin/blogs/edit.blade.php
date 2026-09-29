@extends('layouts.admin')
@section('page_title', 'Edit blog post | Dashboard')
@section('page_content')
<section class="container mx-auto w-full max-w-5xl p-4 md:p-6">
    <div class="mb-5 rounded-xl bg-white p-4 shadow md:p-5">
        <a href="{{ route('admin.blogs.show', $blog) }}" class="text-sm font-semibold text-[#0a5554] hover:underline">← Back to article</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-green-700">Edit article</p>
        <h1 class="mt-1 text-lg font-bold text-gray-950 md:text-xl">{{ $blog->title }}</h1>
        <p class="mt-1 text-sm text-gray-600">Update the writing, author details, URL, or visibility.</p>
    </div>

    @if($errors->any())<div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800"><p class="font-semibold">Please correct the highlighted fields.</p></div>@endif

    <form method="post" action="{{ route('admin.blogs.update', $blog) }}" class="space-y-7 rounded-xl bg-white p-4 shadow md:p-6">
        @csrf @method('PUT')
        @include('admin.blogs.form-fields', ['blog' => $blog])
        <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-6">
            <button type="submit" class="rounded-lg bg-green-600 px-6 py-2.5 font-semibold text-white hover:bg-green-700">Save changes</button>
            <a href="{{ route('admin.blogs.show', $blog) }}" class="rounded-lg border border-gray-300 px-6 py-2.5 font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</section>
@endsection
