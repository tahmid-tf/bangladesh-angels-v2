<?php

namespace App\Livewire;

use App\Models\InvestorInvestment;
use Livewire\Component;
use Livewire\WithPagination;

class InvestorInvestmentsTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $currency = '';

    protected $queryString = ['search', 'currency'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->resetPage();
    }

    public function updatingCurrency(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $userId = auth()->id();

        $query = InvestorInvestment::query()
            ->where('investor_id', $userId)
            ->when($this->search !== '', function ($query) {
                $term = trim($this->search);

                $query->where(function ($query) use ($term) {
                    $query->where('startup_name', 'like', "%{$term}%")
                        ->orWhere('currency', 'like', "%{$term}%")
                        ->orWhere('amount', 'like', "%{$term}%")
                        ->orWhereDate('completed_at', $term);
                });
            })
            ->when($this->currency !== '', fn ($query) => $query->where('currency', $this->currency))
            ->orderByDesc('completed_at')
            ->orderByDesc('id');

        return view('livewire.investor-investments-table', [
            'investments' => $query->paginate(12),
            'currencies' => InvestorInvestment::query()
                ->where('investor_id', $userId)
                ->distinct()
                ->orderBy('currency')
                ->pluck('currency'),
        ]);
    }
}
