@extends('layouts.guest')
@section('page_title', $blog->title.' | Bangladesh Angels Network')

@push('head_meta')
    <x-seo-meta
        :title="$blog->title.' | Bangladesh Angels Network'"
        :description="$blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($blog->content), 160)"
        :canonical="route('blogs.show', $blog->slug)"
        :image="asset('og.png')"
    />
@endpush

@section('page_content')
<main class="ban-blog-page ban-blog-article-page">
    <div class="ban-blog-shell ban-blog-layout ban-blog-layout--article">
        <article class="ban-blog-article">
            <a href="{{ route('blogs.index') }}" class="ban-blog-back">← Back to all articles</a>
            <header>
                <p class="ban-blog-article__category">{{ $blog->category }}</p>
                <h1>{{ $blog->title }}</h1>
                @if($blog->excerpt)<p class="ban-blog-article__standfirst">{{ $blog->excerpt }}</p>@endif

                <div class="ban-blog-byline">
                    <div class="ban-blog-byline__mark" aria-hidden="true">BAN</div>
                    <div>
                        <strong>{{ $blog->author_name ?: 'Bangladesh Angels Network' }}</strong>
                        @if($blog->author_role)<span>{{ $blog->author_role }}</span>@endif
                        @if($blog->author_organization)<span>{{ $blog->author_organization }}</span>@endif
                        <time datetime="{{ $blog->displayDate()?->toDateString() }}">Published {{ $blog->displayDate()?->format('d M, Y') }} · {{ $blog->readingMinutes() }} min read</time>
                    </div>
                </div>
            </header>

            <div class="ban-blog-article__content">
                {!! $blog->content !!}
            </div>

            <footer class="ban-blog-article__footer">
                <span>{{ $blog->category }}</span>
                <a href="{{ route('blogs.index') }}">Explore more insights <span aria-hidden="true">→</span></a>
            </footer>
        </article>

        @include('blogs.partials.sidebar')
    </div>
</main>
@endsection
