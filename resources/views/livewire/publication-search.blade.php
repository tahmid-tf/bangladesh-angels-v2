<div class="ban-blog-shell ban-blog-layout">
    <section class="ban-blog-feed" aria-labelledby="publication-feed-heading">
        <div class="ban-blog-feed__heading">
            <div>
                <p>Library</p>
                <h2 id="publication-feed-heading">
                    @if($category)
                        {{ $category }}
                    @elseif($search)
                        Search results
                    @else
                        All publications
                    @endif
                </h2>
            </div>
            @if($search || $category)
                <button type="button" wire:click="clearFilters">Clear filters ×</button>
            @endif
        </div>

        <div wire:loading.flex wire:target="applySearch,selectCategory,clearFilters" class="ban-blog-loading" role="status">Updating publications…</div>

        <div wire:loading.class="opacity-60" wire:target="applySearch,selectCategory,clearFilters" class="ban-blog-results">
            @forelse($publications as $publication)
                <article class="ban-blog-card ban-publication-card" wire:key="publication-{{ $publication->id }}">
                    <div class="ban-blog-card__meta">
                        <span>{{ $publication->category }}</span>
                        <time datetime="{{ $publication->displayDate()?->toDateString() }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
                            {{ $publication->displayDate()?->format('d M, Y') }}
                        </time>
                    </div>
                    <h3>{{ $publication->title }}</h3>
                    <p>{{ $publication->excerpt ?: 'Download this publication from Bangladesh Angels Network.' }}</p>
                    <a href="{{ route('publications.download', $publication) }}" class="ban-publication-download" download>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"></path></svg>
                        Download PDF
                    </a>
                </article>
            @empty
                <div class="ban-blog-empty">
                    <span>Nothing matched</span>
                    <h3>No publications found.</h3>
                    <p>Try a different search or browse all publications.</p>
                    <button type="button" wire:click="clearFilters">View all publications</button>
                </div>
            @endforelse

            @if($publications->hasPages())
                <div class="ban-blog-pagination">{{ $publications->links() }}</div>
            @endif
        </div>
    </section>

    <aside class="ban-blog-sidebar" aria-label="Publication tools">
        <section class="ban-blog-side-card">
            <h2>Search</h2>
            <form wire:submit.prevent="applySearch" class="ban-blog-search">
                <label for="publication-search" class="sr-only">Search publications</label>
                <input id="publication-search" name="search" type="search" wire:model="search" placeholder="Search publications" autocomplete="off">
                <button type="submit" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                </button>
            </form>
        </section>

        <section class="ban-blog-side-card">
            <h2>Categories</h2>
            <nav class="ban-blog-categories" aria-label="Publication categories">
                <button type="button" wire:click="selectCategory('')" @class(['is-active' => !$category])><span aria-hidden="true">›</span> All</button>
                @foreach($categories as $availableCategory)
                    <button type="button" wire:click="selectCategory(@js($availableCategory->category))" @class(['is-active' => $category === $availableCategory->category])>
                        <span aria-hidden="true">›</span> {{ $availableCategory->category }} <small>{{ $availableCategory->publications_count }}</small>
                    </button>
                @endforeach
            </nav>
        </section>
    </aside>
</div>
