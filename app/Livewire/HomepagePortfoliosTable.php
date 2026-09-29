<?php

namespace App\Livewire;

use App\Models\HomepagePortfolio;
use Livewire\Component;

class HomepagePortfoliosTable extends Component
{
    /** @var array<int, int|string> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    public ?string $feedbackMessage = null;

    public int $maxSelections = HomepagePortfolio::MAX_SELECTIONS;

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedIds = HomepagePortfolio::pluck('id')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds(): void
    {
        $allIds = HomepagePortfolio::pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectAll = ! empty($allIds) && count(array_intersect($allIds, array_map('intval', $this->selectedIds))) === count($allIds);
    }

    public function moveUp(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $item = HomepagePortfolio::find($id);
        if (! $item) {
            return;
        }

        $previous = HomepagePortfolio::query()
            ->where('sort_order', '<', $item->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            $currentOrder = $item->sort_order;
            $item->update(['sort_order' => $previous->sort_order]);
            $previous->update(['sort_order' => $currentOrder]);
            $this->normalizeSortOrders();
            $this->showFeedback('Display order updated.');
        }
    }

    public function moveDown(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $item = HomepagePortfolio::find($id);
        if (! $item) {
            return;
        }

        $next = HomepagePortfolio::query()
            ->where('sort_order', '>', $item->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            $currentOrder = $item->sort_order;
            $item->update(['sort_order' => $next->sort_order]);
            $next->update(['sort_order' => $currentOrder]);
            $this->normalizeSortOrders();
            $this->showFeedback('Display order updated.');
        }
    }

    public function remove(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $item = HomepagePortfolio::with('deal')->find($id);
        if ($item) {
            $title = $item->deal?->title ?? 'Portfolio company';
            $item->delete();
            $this->normalizeSortOrders();
            $this->selectedIds = array_values(array_filter($this->selectedIds, fn ($selectedId) => (int) $selectedId !== $id));
            $this->showFeedback("{$title} removed from homepage selection.");
        }
    }

    public function batchRemove(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (empty($this->selectedIds)) {
            return;
        }

        $count = HomepagePortfolio::query()->whereIn('id', $this->selectedIds)->delete();
        $this->normalizeSortOrders();
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->showFeedback("{$count} " . ($count === 1 ? 'portfolio company' : 'portfolio companies') . ' removed from homepage selection.');
    }

    public function clearAll(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        HomepagePortfolio::query()->delete();
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->showFeedback('All homepage selections cleared. Reverted to default latest portfolios.');
    }

    protected function normalizeSortOrders(): void
    {
        $items = HomepagePortfolio::query()->ordered()->get();

        foreach ($items as $index => $item) {
            $expectedOrder = $index + 1;
            if ($item->sort_order !== $expectedOrder) {
                $item->update(['sort_order' => $expectedOrder]);
            }
        }
    }

    protected function showFeedback(string $message): void
    {
        $this->feedbackMessage = $message;
    }

    public function dismissFeedback(): void
    {
        $this->feedbackMessage = null;
    }

    public function render()
    {
        $selectedPortfolios = HomepagePortfolio::query()
            ->with(['deal.media'])
            ->ordered()
            ->get();

        return view('livewire.homepage-portfolios-table', [
            'selectedPortfolios' => $selectedPortfolios,
        ]);
    }
}
