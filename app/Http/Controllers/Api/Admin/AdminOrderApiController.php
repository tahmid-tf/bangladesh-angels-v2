<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\BanWealthOrder;
use App\Models\MembershipOrder;
use App\Models\MembershipOrderRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminOrderApiController extends BaseApiController
{
    // ==========================================
    // MEMBERSHIP ORDERS
    // ==========================================

    public function membershipOrders(Request $request): JsonResponse
    {
        $orders = MembershipOrder::with('payment')
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return $this->success($orders);
    }

    public function showMembershipOrder(MembershipOrder $membershipOrder): JsonResponse
    {
        $membershipOrder->load(['payment', 'records']);

        return $this->success($membershipOrder);
    }

    public function storeMembershipOrderRecord(Request $request, MembershipOrder $membershipOrder): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:customer_communication,delivery_document'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,txt', 'max:10240'],
        ]);

        $path = $request->file('attachment')?->store('membership-evidence', 'local');

        try {
            $record = $membershipOrder->records()->create([
                'kind' => $data['kind'],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'recorded_by' => $request->user()->id,
                'status' => 'recorded',
                'attachment_path' => $path,
                'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return $this->success($record, 'Supporting evidence record saved.', 201);
    }

    public function downloadMembershipOrderRecord(MembershipOrderRecord $record)
    {
        if (! $record->attachment_path || ! Storage::disk('local')->exists($record->attachment_path)) {
            return $this->error('File not found.', 404);
        }

        return Storage::disk('local')->download($record->attachment_path, $record->attachment_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ==========================================
    // BAN WEALTH ORDERS
    // ==========================================

    public function banWealthOrders(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = BanWealthOrder::with('investor:id,name,email')
            ->when($status && in_array($status, BanWealthOrder::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($inner) => $inner->where('reference', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('fund_name', 'like', "%{$search}%"));
            });

        $orders = $query->latest()->paginate((int) $request->query('per_page', 25));

        $counts = BanWealthOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->success([
            'orders' => $orders,
            'counts' => $counts,
        ]);
    }

    public function showBanWealthOrder(BanWealthOrder $banWealthOrder): JsonResponse
    {
        $banWealthOrder->load(['investor:id,name,email', 'reviewer:id,name']);

        return $this->success($banWealthOrder);
    }

    public function updateBanWealthOrder(Request $request, BanWealthOrder $banWealthOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(BanWealthOrder::STATUSES)],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $banWealthOrder->update($validated + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->success($banWealthOrder->fresh(), 'BAN Wealth order status updated.');
    }
}
