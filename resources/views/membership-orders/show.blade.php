@extends('layouts.guest')
@section('page_title', 'Membership Order '.$order->reference.' | Bangladesh Angels')
@section('page_content')
<main class="ban-policy-shell ban-policy-prose">
    <header class="ban-policy-header"><p class="ban-page-kicker">{{ $order->business_snapshot['name'] }}</p><h1>{{ $order->payment?->status === 'completed' ? 'Membership receipt' : 'Membership order' }}</h1><p>{{ $order->reference }}</p></header>
    <div class="ban-order-links ban-no-print"><a href="{{ auth()->user()->isAdmin() ? route('admin.membership-orders.index') : route('membership-orders.index') }}">All orders</a><button type="button" onclick="window.print()">Print / Save PDF</button><a href="{{ route('membership-orders.policies', $order) }}">Policies accepted with this order</a></div>
    @if(session('success'))<p class="ban-order-notice" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <h2>Supplier</h2>
    <p>{{ $order->business_snapshot['name'] }}<br>{{ $order->business_snapshot['address'] }}<br>{{ $order->business_snapshot['phone'] }} · {{ $order->business_snapshot['email'] }}<br>E-TIN: {{ $order->business_snapshot['e_tin'] }} · Trade licence: {{ $order->business_snapshot['trade_license'] }}</p>
    <h2>Customer</h2>
    <p>{{ $order->customer_snapshot['name'] }}<br>{{ $order->customer_snapshot['email'] }}<br>{{ $order->customer_snapshot['phone'] }}<br>{{ $order->customer_snapshot['address'] }}, {{ $order->customer_snapshot['primary_country'] }}</p>
    <div class="ban-policy-table-wrap"><table><thead><tr><th>Service</th><th>Quantity</th><th>Total</th></tr></thead><tbody><tr><td>{{ $order->plan_snapshot['name'] }} — annual digital membership</td><td>1</td><td>{{ $order->currency }} {{ number_format((float) $order->amount, 2) }}</td></tr></tbody></table></div>
    <ul>@foreach($order->plan_snapshot['features_included'] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
    <dl class="ban-business-details">
        <div><dt>Payment status</dt><dd>{{ $order->payment?->status ?? 'Not confirmed — this is not a payment receipt.' }}</dd></div>
        <div><dt>Merchant reference</dt><dd>{{ $order->merchant_txnid }}</dd></div>
        @if($order->payment)<div><dt>Gateway reference</dt><dd>{{ $order->payment->pg_txnid }}</dd></div><div><dt>Paid at</dt><dd>{{ $order->payment->payment_date }}</dd></div><div><dt>Term ends</dt><dd>{{ $order->payment->expiry_date }}</dd></div>@endif
        <div><dt>Checkout accepted</dt><dd>{{ $order->accepted_at->toIso8601String() }}</dd></div>
        <div><dt>Policy version</dt><dd>{{ $order->policy_version }}</dd></div>
        <div><dt>Electronic delivery</dt><dd>{{ $order->delivered_at?->toIso8601String() ?? 'Not yet confirmed' }}. No physical shipping applies.</dd></div>
        <div><dt>Member confirmation</dt><dd>{{ $order->delivery_confirmed_at?->toIso8601String() ?? 'Not yet provided' }}</dd></div>
    </dl>
    <p>{{ $order->acknowledgement }}</p>
    @if($order->delivery_confirmed_at)<p>{{ $order->delivery_acknowledgement }}</p>@endif
    @if((int) auth()->id() === (int) $order->user_id && $order->delivered_at && ! $order->delivery_confirmed_at)
        <section class="ban-order-notice ban-no-print"><h2>Can you access your membership?</h2>
            <p>Check your membership access first. Confirm only when you can use the purchased service. If anything is missing, <a href="mailto:{{ config('business.email') }}">contact support</a> instead.</p>
            <form method="POST" action="{{ route('membership-orders.confirm-delivery', $order) }}">@csrf
                <label class="ban-checkout-consent"><input type="checkbox" name="delivery_consent" value="1" required><span>{{ \App\Support\MembershipPolicies::DELIVERY_ACKNOWLEDGEMENT }}</span></label>
                <button class="ban2-button ban2-button--primary mt-4" type="submit">Confirm membership access</button>
            </form>
        </section>
    @endif
    @if(auth()->user()->isAdmin())
        <section><h2>Audit information</h2><p>Acceptance IP: {{ $order->accepted_ip }}<br>User agent: {{ $order->accepted_user_agent }}<br>Checkbox time reported by browser (not independently verified): {{ $order->client_accepted_at?->toIso8601String() ?? 'Not supplied' }}<br>Delivery confirmation IP: {{ $order->delivery_confirmed_ip ?? 'Not supplied' }}<br>Delivery confirmation user agent: {{ $order->delivery_confirmed_user_agent ?? 'Not supplied' }}</p>
            <h2>Communication &amp; delivery records</h2>
            @forelse($order->records as $record)
                <article class="ban-order-record"><h3>{{ $record->subject }}</h3><p>{{ str_replace('_', ' ', $record->kind) }} · {{ $record->status }} · {{ $record->created_at->toIso8601String() }} @if($record->recipient) · {{ $record->recipient }} @endif</p>
                    <pre>{{ $record->kind === 'confirmation_email' ? html_entity_decode(strip_tags($record->body)) : $record->body }}</pre>
                    @if($record->attachment_path)<a href="{{ route('admin.membership-orders.records.download', $record) }}">Download {{ $record->attachment_name }}</a>@endif
                </article>
            @empty<p>No supporting communication records yet.</p>@endforelse
            <form class="ban-order-form ban-no-print" action="{{ route('admin.membership-orders.records.store', $order) }}" method="POST" enctype="multipart/form-data">@csrf
                <h2>Add supporting evidence</h2><p>Record email exchanges, support calls, or additional digital-delivery evidence. Do not upload card numbers, passwords, or unrelated personal information.</p>
                <label>Record type<select name="kind"><option value="customer_communication">Customer communication</option><option value="delivery_document">Delivery document</option></select></label>
                <label>Subject<input name="subject" required maxlength="255" value="{{ old('subject') }}"></label>
                <label>Communication or delivery details<textarea name="body" required rows="5" maxlength="20000">{{ old('body') }}</textarea></label>
                <label>Attachment (optional, PDF, PNG, JPG, or TXT; up to 10 MB)<input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.txt"></label>
                <button class="ban2-button ban2-button--primary" type="submit">Save supporting record</button>
            </form>
        </section>
    @endif
</main>
@endsection
