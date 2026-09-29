@extends('layouts.guest')
@section('page_title', 'Insights & Blog | Bangladesh Angels Network')

@push('head_meta')
    <x-seo-meta
        title="Insights & Blog | Bangladesh Angels Network"
        description="Ideas, educational resources, announcements, and perspectives from Bangladesh Angels Network."
        :canonical="route('blogs.index')"
        :image="asset('og.png')"
    />
@endpush

@section('page_content')
<main class="ban-blog-page">
    <header class="ban-blog-hero">
        <div class="ban-blog-shell">
            <p>Ideas from the ecosystem</p>
            <h1>Insights for investors and founders.</h1>
            <span>Perspectives, practical guidance, announcements, and stories shaping Bangladesh’s startup ecosystem.</span>
        </div>
    </header>

    <livewire:blog-search />
</main>
@endsection
