<h2>Business details</h2>
<dl class="ban-business-details">
    <div><dt>Legal name</dt><dd>{{ config('business.name') }}</dd></div>
    <div><dt>Registered office</dt><dd>{{ config('business.address') }}</dd></div>
    <div><dt>Phone</dt><dd><a href="tel:{{ config('business.phone') }}">{{ config('business.phone') }}</a></dd></div>
    <div><dt>Email</dt><dd><a href="mailto:{{ config('business.email') }}">{{ config('business.email') }}</a></dd></div>
    <div><dt>E-TIN</dt><dd>{{ config('business.e_tin') }}</dd></div>
    <div><dt>Trade licence</dt><dd>{{ config('business.trade_license') }}</dd></div>
</dl>
