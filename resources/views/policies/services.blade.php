@extends('layouts.guest')
@section('page_title', 'Services & Membership | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-shell">
    <header class="ban-policy-header"><p class="ban-page-kicker">Annual investor memberships</p><h1>Services &amp; membership</h1><p>Digital access to BAN’s deal flow, investor community, and membership services. No physical goods are shipped.</p></header>
    <div class="ban-policy-layout">
    @include('policies.navigation')
    <article class="ban-policy-prose">
        <h2>Availability and purchase options</h2>
        <p>The active memberships below are available for purchase online. Choose a plan, complete your account and billing details, acknowledge the policies, and pay through aamarPay using the methods available on its secure payment page. Fees are billed annually in USD. International payment availability is subject to gateway and banking approval.</p>
        @include('policies.blocks', ['blocks' => [['type' => 'plans']]])
        <p><a class="ban2-button ban2-button--primary" href="{{ route('plans') }}">Compare plans &amp; purchase</a></p>
        <h2>Electronic delivery</h2>
        <p>Membership is activated after successful payment confirmation, typically within 24 hours. If manual review is needed, including additional linked Institutional accounts, activation may take up to 2 business days.</p>
        <p>Member benefits depend on the selected plan and applicable account or data-room approvals. If access has not been activated within 2 business days, contact {{ config('business.email') }} with your transaction reference.</p>
        <h2>Before you purchase</h2>
        <p>Membership pays for BAN’s services; it is not an investment in a fund or startup. BAN does not pool member capital or provide investment advice. Startup investments are high-risk and may be lost in full.</p>
        <p>For eligibility, refund timelines, and cancellation details, read the linked policies before checkout.</p>
        <ul>@foreach(\App\Support\MembershipPolicies::checkoutPages() as $slug => $page)<li><a href="{{ route('policies.show', $slug) }}">{{ $page['title'] }}</a></li>@endforeach</ul>
    </article>
    </div>
</main>
@endsection
