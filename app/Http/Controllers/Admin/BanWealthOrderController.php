<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BanWealthOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BanWealthOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(BanWealthOrder::STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $orders = BanWealthOrder::query()
            ->with('investor:id,name,email')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(fn ($query) => $query->where('reference', 'like', "%{$term}%")
                    ->orWhere('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('fund_name', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = BanWealthOrder::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.ban-wealth-orders.index', compact('orders', 'counts', 'filters'));
    }

    public function show(Request $request, BanWealthOrder $banWealthOrder): View
    {
        $this->authorizeAdmin($request);
        $banWealthOrder->load(['investor:id,name,email', 'reviewer:id,name']);

        return view('admin.ban-wealth-orders.show', compact('banWealthOrder'));
    }

    public function update(Request $request, BanWealthOrder $banWealthOrder): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'status' => ['required', Rule::in(BanWealthOrder::STATUSES)],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $banWealthOrder->update($validated + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'BAN Wealth order status updated.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
