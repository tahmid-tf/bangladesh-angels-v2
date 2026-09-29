<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBanWealthOrderRequest;
use App\Models\BanWealthOrder;
use App\Support\BanWealthFunds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BanWealthController extends Controller
{
    public function landing(): View
    {
        return view('ban-wealth.home');
    }

    public function invest(Request $request): View
    {
        $orders = $request->user()?->isInvestor()
            ? BanWealthOrder::query()->where('investor_id', $request->user()->id)->latest()->get()
            : collect();

        $ordersPayload = $orders->map(fn (BanWealthOrder $order) => [
            'reference' => $order->reference,
            'fund_name' => $order->fund_name,
            'fund_manager' => $order->fund_manager,
            'amount' => (float) $order->amount,
            'estimated_units' => (float) $order->estimated_units,
            'nav' => (float) $order->nav_at_order,
            'status' => $order->status,
            'review_note' => $order->review_note,
            'proof_url' => route('ban-wealth.orders.proof', $order),
            'created_at' => $order->created_at->format('d M Y'),
        ])->values();

        return view('ban-wealth.index', [
            'funds' => BanWealthFunds::all(),
            'orders' => $orders,
            'ordersPayload' => $ordersPayload,
            'investor' => $request->user()?->isInvestor() ? $request->user() : null,
        ]);
    }

    public function store(StoreBanWealthOrderRequest $request): RedirectResponse
    {
        $fund = BanWealthFunds::find($request->string('fund_slug')->toString());
        abort_unless($fund, 422);

        abort_if($fund['horizon'] !== $request->string('horizon')->toString(), 422, 'The selected fund does not match your timeframe.');
        if ($request->string('shariah_preference')->toString() === 'shariah') {
            abort_unless($fund['shariah'], 422, 'The selected fund does not match your preference.');
        }
        if ($request->string('shariah_preference')->toString() === 'conventional') {
            abort_if($fund['shariah'], 422, 'The selected fund does not match your preference.');
        }

        $amount = (float) $request->input('amount');
        abort_if($amount < (float) $fund['minimum'], 422, 'The amount is below this fund’s minimum.');

        $proof = $request->file('payment_proof');
        $path = $proof->store('ban-wealth/payment-proofs', 'local');

        try {
            $order = BanWealthOrder::query()->create([
                ...$request->safe()->except(['payment_proof', 'prospectus_consent', 'submission_consent', 'shariah_preference']),
                'reference' => $this->nextReference(),
                'investor_id' => $request->user()->id,
                'fund_name' => $fund['name'],
                'fund_manager' => $fund['manager'],
                'shariah' => $fund['shariah'],
                'nav_at_order' => $fund['nav'],
                'estimated_units' => $amount / $fund['nav'],
                'monthly' => $request->boolean('monthly'),
                'politically_exposed' => $request->boolean('politically_exposed'),
                'payment_method' => 'bank_transfer',
                'payment_proof_path' => $path,
                'payment_proof_name' => $proof->getClientOriginalName(),
                'status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('ban-wealth.invest', ['order' => $order->reference])
            ->with('ban_wealth_success', $order->reference);
    }

    public function proof(Request $request, BanWealthOrder $banWealthOrder)
    {
        abort_unless($request->user()?->isAdmin() || $banWealthOrder->investor_id === $request->user()?->id, 403);
        abort_unless(Storage::disk('local')->exists($banWealthOrder->payment_proof_path), 404);

        return Storage::disk('local')->download($banWealthOrder->payment_proof_path, $banWealthOrder->payment_proof_name);
    }

    private function nextReference(): string
    {
        do {
            $reference = 'BW-'.now()->format('ymd').'-'.str()->upper(str()->random(5));
        } while (BanWealthOrder::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
