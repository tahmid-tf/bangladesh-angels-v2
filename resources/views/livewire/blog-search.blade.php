<div class="ban-blog-shell ban-blog-layout">
    <section class="ban-blog-feed" aria-labelledby="blog-feed-heading">
        <div class="ban-blog-feed__heading">
            <div>
                <p>Latest thinking</p>
                <h2 id="blog-feed-heading">
                    @if($category)
                        {{ $category }}
                    @elseif($search)
                        Search results
                    @else
                        All articles
                    @endif
                </h2>
            </div>
            @if($search || $category)
                <button type="button" wire:click="clearFilters">Clear filters ×</button>
            @endif
        </div>

        <div wire:loading.flex wire:target="applySearch,selectCategory,clearFilters" class="ban-blog-loading" role="status">
            Updating articles…
        </div>

        <div wire:loading.class="opacity-60" wire:target="applySearch,selectCategory,clearFilters" class="ban-blog-results">
            @forelse($blogs as $blog)
                <article class="ban-blog-card" wire:key="blog-{{ $blog->id }}">
                    <div class="ban-blog-card__meta">
                        <span>{{ $blog->category }}</span>
                        <time datetime="{{ $blog->displayDate()?->toDateString() }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
                            {{ $blog->displayDate()?->format('d M, Y') }}
                        </time>
                        <span>{{ $blog->readingMinutes() }} min read</span>
                    </div>
                    <h3><a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a></h3>
                    <p>{{ $blog->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($blog->content)), 220) }}</p>
                    <a href="{{ route('blogs.show', $blog->slug) }}" class="ban-blog-read-more">Read article <span aria-hidden="true">→</span></a>
                </article>
            @empty
                <div class="ban-blog-empty">
                    <span>Nothing matched</span>
                    <h3>No articles found.</h3>
                    <p>Try a different search or browse all published articles.</p>
                    <button type="button" wire:click="clearFilters">View all articles</button>
                </div>
            @endforelse

            @if($blogs->hasPages())
                <div class="ban-blog-pagination">{{ $blogs->links() }}</div>
            @endif
        </div>
    </section>

    <aside class="ban-blog-sidebar" aria-label="Blog tools">
        <section class="ban-blog-side-card">
            <h2>Search</h2>
            <form wire:submit.prevent="applySearch" class="ban-blog-search">
                <label for="blog-search" class="sr-only">Search articles</label>
                <input id="blog-search" name="search" type="search" wire:model="search" placeholder="Search articles" autocomplete="off">
                <button type="submit" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                </button>
            </form>
        </section>

        <section class="ban-blog-side-card">
            <h2>Categories</h2>
            <nav class="ban-blog-categories" aria-label="Blog categories">
                <button type="button" wire:click="selectCategory('')" @class(['is-active' => !$category])>
                    <span aria-hidden="true">›</span> All
                </button>
                @foreach($categories as $availableCategory)
                    <button type="button" wire:click="selectCategory(@js($availableCategory->category))" @class(['is-active' => $category === $availableCategory->category])>
                        <span aria-hidden="true">›</span> {{ $availableCategory->category }} <small>{{ $availableCategory->posts_count }}</small>
                    </button>
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
</div>
