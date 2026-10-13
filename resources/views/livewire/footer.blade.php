<footer class="ban-footer">
  <div class="ban-footer__inner">
    <div class="ban-footer__top">
      <div class="ban-footer__brand-column">
        <a href="{{ route('home') }}" class="ban-footer__brand" title="Bangladesh Angels Network — home">
          <img src="{{ asset('logo.webp') }}" width="240" height="62" alt="Bangladesh Angels Network Logo">
        </a>
        <p>Bangladesh's first and largest angel investment platform connecting early-stage startups with investors.</p>
        <div class="ban-footer__socials" aria-label="Social media">
          <a target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/company/bangladesh-angels/" aria-label="LinkedIn">
            <img src="{{ asset('linkedIn.webp') }}" width="20" height="20" alt="LinkedIn">
          </a>
          <a target="_blank" rel="noopener noreferrer" href="https://www.facebook.com/bdangels.co" aria-label="Facebook">
            <img src="{{ asset('fb_icon.webp') }}" width="20" height="20" alt="Facebook">
          </a>
          <a target="_blank" rel="noopener noreferrer" href="https://x.com/BDAngelsNetwork?t=XxABWIT-yVk-X8ujKnYorA&s=09" aria-label="Twitter">
            <img src="{{ asset('twitter.webp') }}" width="20" height="20" alt="Twitter">
          </a>
        </div>
      </div>

      <section class="ban-footer__links" aria-labelledby="ban-footer-explore-heading">
        <h2 id="ban-footer-explore-heading">Explore</h2>
        <ul>
          {{-- BAN Wealth is temporarily hidden from public navigation. --}}
          <li><a href="{{ route('angel-academy') }}">Angel Academy</a></li>
          <li><a href="{{ route('startups') }}">Startups</a></li>
          <li><a href="{{ route('investors') }}">Our Investors</a></li>
          <li><a href="{{ route('resources') }}">DeckVue</a></li>
          <li><a href="{{ route('blogs.index') }}">Blog &amp; Insights</a></li>
        </ul>
      </section>

      <section class="ban-footer__links" aria-labelledby="ban-footer-start-heading">
        <h2 id="ban-footer-start-heading">Get Started</h2>
        <ul>
          <li><a href="{{ route('services') }}">Services &amp; Membership</a></li>
          @if (!auth()->check() || !auth()->user()->hasPaidMembership())
            <li><a href="{{ route('investor.signup') }}">Become an Investor</a></li>
          @elseif (auth()->user()->isInvestor())
            <li><a href="{{ route('investor.dashboard') }}">Investor Dashboard</a></li>
          @endif
          <li><a href="{{ route('startups').'#send-pitch' }}">Pitch Your Startup</a></li>
          @guest
            <li><a href="{{ route('login') }}">Member Login</a></li>
          @endguest
          @auth
            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li><a href="{{ route('membership-orders.index') }}">Membership Orders &amp; Receipts</a></li>
          @endauth
        </ul>
      </section>

      <section class="ban-footer__links ban-footer__contact" aria-labelledby="ban-footer-contact-heading">
        <div class="ban-footer__contact-heading">
          <h2 id="ban-footer-contact-heading">Company &amp; Contact</h2>
        </div>
        <ul>
          <li><a href="{{ route('about-us') }}">About Us</a></li>
          <li><a href="{{ route('team') }}">Our Team</a></li>
          <li>
            <a href="mailto:hello@bdangels.co" class="ban-footer__email" title="Email Bangladesh Angels Network">
              <span>Contact Us</span>
              <small>hello@bdangels.co</small>
            </a>
          </li>
          <li>
            <a href="{{ route('faq') }}" title="Frequently asked questions about BAN">FAQ</a>
          </li>
          {{-- <li><a href="tel:{{ config('business.phone') }}">{{ config('business.phone') }}</a></li> --}}
          <li><a href="{{ route('contact') }}">Contact &amp; Business Details</a></li>
        </ul>
      </section>
    </div>

    <div class="ban-footer__payment" aria-label="Accepted payment methods and gateway verification" style="display: flex; justify-content: center; align-items: center; margin-top: 36px; margin-bottom: 8px;">
      <div class="ban-footer__payment-card" style="width: 100%; max-width: 1024px; background: #ffffff; border-radius: 14px; padding: 10px 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.16); border: 1px solid rgba(255, 255, 255, 0.2);">
        <picture style="width: 100%; display: flex; justify-content: center;">
          <source srcset="{{ asset('dgepay-banner.webp') }}" type="image/webp">
          <img src="{{ asset('dgepay-banner.jpg') }}" alt="Pay with Visa, Mastercard, American Express, NexusPay, bKash, Nagad, Rocket, CellFin, Upay, Internet Banking - Verified by DGePay" width="1024" height="194" loading="lazy" style="width: 100%; max-width: 1000px; height: auto; display: block; object-fit: contain;">
        </picture>
      </div>
    </div>

    <div class="ban-footer__business">
      <div>
      <p><strong>{{ config('business.name') }}</strong></p>
      <p>{{ config('business.address') }}</p>
      <p>E-TIN: {{ config('business.e_tin') }} &nbsp; · &nbsp; Trade licence: {{ config('business.trade_license') }}</p>
      </div>
      <button type="button" class="ban-footer__to-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });" aria-label="Back to top">
        <img src="{{ asset('up_arrow.webp') }}" alt="" width="28" height="28">
      </button>
    </div>
    <div class="ban-footer__bottom">
      <small>© Bangladesh Angels. All rights reserved.</small>
      <nav class="ban-footer__policy-links" aria-label="Website policies">
        @foreach(\App\Support\MembershipPolicies::checkoutPages() as $slug => $page)
          <a href="{{ route('policies.show', $slug) }}">{{ $page['title'] }}</a>
        @endforeach
      </nav>
    </div>
  </div>
</footer>
