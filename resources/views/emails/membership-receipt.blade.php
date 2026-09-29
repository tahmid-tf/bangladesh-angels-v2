<h1>Membership payment confirmed</h1>
<p>Hello {{ $order->customer_snapshot['name'] }},</p>
<p>Your {{ $order->plan_snapshot['name'] }} membership payment has been confirmed and your account has been upgraded.</p>
<p>Order: {{ $order->reference }}<br>Merchant reference: {{ $order->merchant_txnid }}<br>Gateway reference: {{ $payment->pg_txnid }}<br>Amount paid: {{ $order->currency }} {{ number_format((float) $order->amount, 2) }}<br>Term ends: {{ $payment->expiry_date }}</p>
<p>Membership is delivered electronically. Any additional linked Institutional accounts may require manual review, taking up to 2 business days.</p>
<p><a href="{{ route('membership-orders.show', $order) }}">Sign in to view or print your receipt and confirm membership access</a>. Confirm only after checking that you can access the purchased service.</p>
<p>For delivery issues, refunds, or cancellation requests, contact {{ $order->business_snapshot['email'] }} with your order reference.</p>
<p>{{ $order->business_snapshot['name'] }}<br>{{ $order->business_snapshot['address'] }}<br>{{ $order->business_snapshot['phone'] }}<br>E-TIN: {{ $order->business_snapshot['e_tin'] }} · Trade licence: {{ $order->business_snapshot['trade_license'] }}</p>
