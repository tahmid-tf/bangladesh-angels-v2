@extends('layouts.guest')
@section('page_title', 'Publications | Bangladesh Angels Network')

@push('head_meta')
    <x-seo-meta
        title="Publications | Bangladesh Angels Network"
        description="Reports, research, and publications from Bangladesh Angels Network."
        :canonical="route('publications.index')"
        :image="asset('og.png')"
    />
@endpush

@section('page_content')
<main class="ban-blog-page">
    <header class="ban-blog-hero">
        <div class="ban-blog-shell">
            <p>Research and resources</p>
            <h1>Publications for the startup ecosystem.</h1>
            <span>Reports, guides, and practical perspectives from Bangladesh Angels Network.</span>
        </div>
    </header>

    <livewire:publication-search />
</main>
@endsection
