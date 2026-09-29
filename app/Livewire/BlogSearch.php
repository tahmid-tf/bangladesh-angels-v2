<?php

namespace App\Livewire;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class BlogSearch extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);
        $category = trim($this->category);

        $blogs = Blog::query()
            ->published()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            })
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(8);

        return view('livewire.blog-search', [
            'blogs' => $blogs,
            'categories' => Blog::query()
                ->published()
                ->selectRaw('category, COUNT(*) as posts_count')
                ->groupBy('category')
                ->orderBy('category')
                ->get(),
            'recentBlogs' => Blog::query()
                ->published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
