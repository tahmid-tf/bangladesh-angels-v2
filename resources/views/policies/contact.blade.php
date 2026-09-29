@extends('layouts.guest')
@section('page_title', 'Contact & Business Details | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-shell">
    <header class="ban-policy-header"><p class="ban-page-kicker">Here to help</p><h1>Contact Bangladesh Angels</h1><p>For membership, payment, delivery, refund, cancellation, or privacy questions, contact our team using the details below.</p></header>
    <div class="ban-policy-layout">
    @include('policies.navigation')
    <article class="ban-policy-prose">
        @include('policies.business-details')
        <h2>Membership support</h2>
        <p>Please include your registered email address and transaction reference when asking about a payment or your membership access. Never send card numbers, CVV codes, or passwords.</p>
        <p><a href="{{ route('policies.show', 'refund-return-policy') }}">Refund/Return Policy</a> · <a href="{{ route('policies.show', 'cancellation-policy') }}">Cancellation Policy</a> · <a href="{{ route('policies.show', 'delivery-policy') }}">Delivery Policy</a></p>
    </article>
    </div>
</main>
@endsection
