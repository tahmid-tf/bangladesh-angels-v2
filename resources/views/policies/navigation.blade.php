<nav class="ban-policy-nav" aria-label="Company information and policies">
    @foreach(\App\Support\MembershipPolicies::pages() as $slug => $item)
        <a href="{{ $slug === 'about-us' ? route('about-us') : route('policies.show', $slug) }}" @if(($policy ?? null) === $slug) aria-current="page" @endif>{{ $item['title'] }}</a>
    @endforeach
    <a href="{{ route('services') }}" @if(request()->routeIs('services')) aria-current="page" @endif>Services &amp; membership</a>
    <a href="{{ route('contact') }}" @if(request()->routeIs('contact')) aria-current="page" @endif>Contact &amp; business details</a>
</nav>
