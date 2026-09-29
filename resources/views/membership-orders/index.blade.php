@extends('layouts.guest')
@section('page_title', 'Membership Orders & Receipts | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-shell">
    <header class="ban-policy-header"><p class="ban-page-kicker">{{ $admin ? 'Administration' : 'Your membership' }}</p><h1>Orders &amp; receipts</h1><p>{{ $admin ? 'Checkout acknowledgements, delivery confirmations, and supporting communication records.' : 'View your order, print a receipt, and confirm that you can access your membership.' }}</p></header>
    <div class="ban-order-links"><a href="{{ $admin ? route('admin.subscriptions') : route('dashboard') }}">Back to {{ $admin ? 'subscriptions' : 'dashboard' }}</a></div>
    <div class="ban-policy-table-wrap"><table>
        <thead><tr><th>Order</th><th>Member / plan</th><th>Amount</th><th>Payment</th><th>Access confirmation</th></tr></thead>
        <tbody>@forelse($orders as $order)
            <tr><td><a class="underline" href="{{ route('membership-orders.show', $order) }}">{{ $order->reference }}</a><br>{{ $order->created_at->format('d M Y') }}</td><td>{{ $order->customer_snapshot['name'] }}<br>{{ $order->plan_snapshot['name'] }}</td><td>{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</td><td>{{ $order->payment?->status ?? 'Not confirmed' }}</td><td>{{ $order->delivery_confirmed_at ? 'Confirmed by member' : ($order->delivered_at ? 'Awaiting member' : 'Not yet delivered') }}</td></tr>
        @empty<tr><td colspan="5">No orders recorded yet. This register covers checkouts submitted after the policy acknowledgement feature was introduced.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $orders->links() }}
</main>
@endsection
