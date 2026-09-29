<?php

namespace App\Http\Controllers;

use App\Models\MembershipOrder;
use App\Models\MembershipOrderRecord;
use App\Support\MembershipPolicies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MembershipOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = MembershipOrder::with('payment')->where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('membership-orders.index', ['orders' => $orders, 'admin' => false]);
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $orders = MembershipOrder::with('payment')->latest()->paginate(30);

        return view('membership-orders.index', ['orders' => $orders, 'admin' => true]);
    }

    public function show(Request $request, MembershipOrder $membershipOrder)
    {
        $this->authorizeOrder($request, $membershipOrder);
        $membershipOrder->load('payment');
        if ($request->user()->isAdmin()) {
            $membershipOrder->load('records');
        }

        return view('membership-orders.show', ['order' => $membershipOrder]);
    }

    public function policies(Request $request, MembershipOrder $membershipOrder)
    {
        $this->authorizeOrder($request, $membershipOrder);

        return view('membership-orders.policies', ['order' => $membershipOrder]);
    }

    public function confirmDelivery(Request $request, MembershipOrder $membershipOrder)
    {
        // An administrator may retain communications, but cannot consent on a member's behalf.
        abort_unless((int) $membershipOrder->user_id === (int) $request->user()->id, 403);
        $request->validate(['delivery_consent' => ['required', 'accepted']]);
        DB::transaction(function () use ($request, $membershipOrder) {
            $order = MembershipOrder::whereKey($membershipOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->delivered_at && $order->payment?->status === 'completed', 422, 'Service delivery has not been confirmed.');
            if (! $order->delivery_confirmed_at) {
                $order->update([
                    'delivery_confirmed_at' => now(), 'delivery_confirmed_ip' => $request->ip(),
                    'delivery_confirmed_user_agent' => mb_substr($request->userAgent() ?? '', 0, 2000),
                    'delivery_acknowledgement' => MembershipPolicies::DELIVERY_ACKNOWLEDGEMENT,
                ]);
            }
        });

        return back()->with('success', 'Thank you. Your confirmation of membership access has been recorded.');
    }

    public function storeRecord(Request $request, MembershipOrder $membershipOrder)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'kind' => ['required', 'in:customer_communication,delivery_document'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,txt', 'max:10240'],
        ]);
        $path = $request->file('attachment')?->store('membership-evidence', 'local');
        abort_if($path === false, 503, 'The attachment could not be saved. Please try again.');
        try {
            $membershipOrder->records()->create([
                'kind' => $data['kind'], 'subject' => $data['subject'], 'body' => $data['body'],
                'recorded_by' => $request->user()->id, 'status' => 'recorded',
                'attachment_path' => $path, 'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
            ]);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Supporting record saved.');
    }

    public function downloadRecord(Request $request, MembershipOrderRecord $record)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($record->attachment_path && Storage::disk('local')->exists($record->attachment_path), 404);

        return Storage::disk('local')->download($record->attachment_path, $record->attachment_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeOrder(Request $request, MembershipOrder $order): void
    {
        abort_unless($request->user()->isAdmin() || (int) $request->user()->id === (int) $order->user_id, 403);
    }
}
