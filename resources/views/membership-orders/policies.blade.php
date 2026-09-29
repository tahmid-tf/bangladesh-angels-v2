@extends('layouts.guest')
@section('page_title', 'Accepted Policies | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-shell ban-policy-prose">
    <header class="ban-policy-header"><h1>Policies accepted with your order</h1><p>{{ $order->reference }} · {{ $order->accepted_at->toIso8601String() }}</p></header>
    <div class="ban-order-links ban-no-print"><a href="{{ route('membership-orders.show', $order) }}">Back to order</a><button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <p>This is the retained policy and purchased-plan snapshot, not the current website policy.</p>
    @foreach($order->policy_snapshot as $page)<section><h2>{{ $page['title'] }}</h2>@include('policies.blocks', ['blocks' => $page['blocks']])</section>@endforeach
</main>
@endsection
