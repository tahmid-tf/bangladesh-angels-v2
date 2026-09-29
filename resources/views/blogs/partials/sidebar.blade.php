<aside class="ban-blog-sidebar" aria-label="Blog tools">
    <section class="ban-blog-side-card">
        <h2>Search</h2>
        <form action="{{ route('blogs.index') }}" method="get" class="ban-blog-search">
            <label for="blog-search" class="sr-only">Search articles</label>
            <input id="blog-search" name="search" type="search" value="{{ request('search') }}" placeholder="Search articles">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <button type="submit" aria-label="Search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            </button>
        </form>
    </section>

    <section class="ban-blog-side-card">
        <h2>Categories</h2>
        <nav class="ban-blog-categories" aria-label="Blog categories">
            <a href="{{ route('blogs.index', array_filter(['search' => request('search')])) }}" @class(['is-active' => !request('category')])>
                <span aria-hidden="true">›</span> All
            </a>
            @foreach($categories as $category)
                <a href="{{ route('blogs.index', array_filter(['category' => $category->category, 'search' => request('search')])) }}" @class(['is-active' => request('category') === $category->category])>
                    <span aria-hidden="true">›</span> {{ $category->category }} <small>{{ $category->posts_count }}</small>
                </a>
            @endforeach
        </nav>
    </section>

    @if($recentBlogs->isNotEmpty())
        <section class="ban-blog-side-card">
            <h2>Recent posts</h2>
            <div class="ban-blog-recent">
                @foreach($recentBlogs as $recent)
                    <article>
                        <a href="{{ route('blogs.show', $recent->slug) }}">{{ $recent->title }}</a>
                        <time datetime="{{ $recent->displayDate()?->toDateString() }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
                            {{ $recent->displayDate()?->format('d M, Y') }}
                        </time>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</aside>
