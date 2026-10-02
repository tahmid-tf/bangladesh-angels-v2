<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Deal;

class DealsTable extends Component
{
    use WithPagination;

    public $search = '';

    public string $filter = 'all';
    
    protected $queryString = ['search']; // Keeps search in the URL

    public function mount(string $filter = 'all'): void
    {
        $this->filter = in_array($filter, ['all', 'invest', 'commit', 'review', 'portfolio', 'invest-portfolio'], true)
            ? $filter
            : 'all';
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination on new search
    }

    public function searchDeals()
    {
        $this->resetPage(); // Reset pagination
    }

    public function render()
    {
        $deals = Deal::with(['media', 'homepagePortfolio'])
            ->when($this->filter === 'invest', fn ($query) => $query->where('type', 'invest'))
            ->when($this->filter === 'commit', fn ($query) => $query->where('type', 'commit'))
            ->when($this->filter === 'review', fn ($query) => $query->where('type', 'review'))
            ->when($this->filter === 'portfolio', fn ($query) => $query->inPortfolio())
            ->when($this->filter === 'invest-portfolio', fn ($query) => $query
                ->where('type', 'invest')
                ->where('is_portfolio', true))
            ->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('sector', 'like', "%{$this->search}%");
            })
            ->paginate(10);

        return view('livewire.deals-table', [
            'deals' => $deals
        ]);
    }
}
