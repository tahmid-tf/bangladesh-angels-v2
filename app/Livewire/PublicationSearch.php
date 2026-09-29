<?php

namespace App\Livewire;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PublicationSearch extends Component
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

        $publications = Publication::query()
            ->published()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('category', 'like', '%'.$search.'%');
                });
            })
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(8);

        return view('livewire.publication-search', [
            'publications' => $publications,
            'categories' => Publication::query()
                ->published()
                ->selectRaw('category, COUNT(*) as publications_count')
                ->groupBy('category')
                ->orderBy('category')
                ->get(),
        ]);
    }
}
