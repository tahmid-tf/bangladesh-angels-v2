<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAN Wealth | Bangladesh Angels</title>
    <meta name="description" content="Choose and invest in BAN Wealth funds through one guided investor journey.">
    <link rel="icon" type="image/webp" href="{{ asset('icon.webp') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|source-serif-4:400,600&display=swap" rel="stylesheet">
    <livewire:styles />
    @vite(['resources/css/app.css', 'resources/css/ban-wealth.css', 'resources/js/app.js'])
</head>
<body class="bw-page">
<livewire:navigation-bar />
@php
    $viewerName = $investor?->name;
    $viewerInitials = $viewerName ? collect(explode(' ', $viewerName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') : '—';
@endphp
<div class="bw-shell">
    <header class="bw-nav">
        <a class="bw-brand" href="{{ route('ban-wealth.index') }}" aria-label="BAN Wealth home"><span>B</span><strong>BAN <em>Wealth</em></strong></a>
        <nav aria-label="BAN Wealth navigation">
            <button type="button" id="bw-nav-invest" class="is-active">Invest</button>
            <button type="button" id="bw-nav-portfolio">Portfolio</button>
            <button type="button" id="bw-nav-documents">Documents</button>
            <a href="mailto:info@bangladeshangels.com">Support</a>
        </nav>
        <div class="bw-user">
            @if ($investor)
                <span>{{ $viewerName }}</span><b>{{ $viewerInitials }}</b>
            @else
                <a href="{{ route('login') }}">Sign in</a><b>—</b>
            @endif
        </div>
    </header>

    <div class="bw-rail" id="bw-rail" aria-label="Investment progress">
        <div data-step="1"><em>1</em><span>What it’s for</span></div>
        <div data-step="2"><em>2</em><span>Choose your fund</span></div>
        <div data-step="3"><em>3</em><span>Set the amount</span></div>
        <div data-step="4"><em>4</em><span>Open your account</span></div>
        <div data-step="5"><em>5</em><span>Pay</span></div>
        <div data-step="6"><em>6</em><span>Confirmed</span></div>
    </div>

    @if ($errors->any())
        <div class="bw-alert bw-alert--error" id="bw-server-errors" role="alert">
            <strong>We couldn’t submit your order.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <main id="bw-stage" aria-live="polite"></main>

    <footer class="bw-footer">
        <p>Fund names and availability remain subject to registration and manager agreement. Investments can fall as well as rise.</p>
        <a href="{{ route('home') }}">Bangladesh Angels</a>
    </footer>
</div>

<script>
(() => {
    const funds = @json($funds->values());
    const orders = @json($ordersPayload);
    const authenticated = @json((bool) $investor);
    const resumeAfterLogin = @json(request()->boolean('resume'));
    const submittedReference = @json(session('ban_wealth_success'));
    const hasServerErrors = @json($errors->any());
    const defaults = {
        full_name: @json(old('full_name', $investor?->name ?? '')),
        nid_number: @json(old('nid_number', '')),
        date_of_birth: @json(old('date_of_birth', '')),
        mobile: @json(old('mobile', $investor?->phone ?? '')),
        email: @json(old('email', $investor?->email ?? '')),
        present_address: @json(old('present_address', $investor?->address ?? '')),
        bank_name: @json(old('bank_name', '')),
        bank_branch: @json(old('bank_branch', '')),
        bank_account_number: @json(old('bank_account_number', '')),
        routing_number: @json(old('routing_number', '')),
        tin: @json(old('tin', '')),
        bo_account: @json(old('bo_account', '')),
        source_of_funds: @json(old('source_of_funds', '')),
        investment_experience: @json(old('investment_experience', '')),
        loss_response: @json(old('loss_response', '')),
        politically_exposed: @json((bool) old('politically_exposed', false)),
        prospectus_consent: @json((bool) old('prospectus_consent', false)),
        submission_consent: @json((bool) old('submission_consent', false)),
    };
    let stored = {};
    try { stored = JSON.parse(localStorage.getItem('banWealthJourney') || '{}'); } catch (_) {}
    const state = {
        horizon: @json(old('horizon')) || stored.horizon || null,
        preference: @json(old('shariah_preference')) || stored.preference || null,
        fund: null,
        amount: Number(@json(old('amount')) || stored.amount || 500000),
        monthly: @json((bool) old('monthly', false)) || stored.monthly || false,
        kycStep: 1,
        profile: hasServerErrors ? {...(stored.profile || {}), ...defaults} : {...defaults, ...(stored.profile || {})},
    };
    const oldSlug = @json(old('fund_slug')) || stored.fundSlug;
    state.fund = funds.find(f => f.slug === oldSlug) || null;

    const stage = document.getElementById('bw-stage');
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const money = value => `৳ ${Number(value || 0).toLocaleString('en-IN', {maximumFractionDigits: 0})}`;
    const units = () => state.fund ? state.amount / Number(state.fund.nav) : 0;
    const save = () => localStorage.setItem('banWealthJourney', JSON.stringify({horizon: state.horizon, preference: state.preference, fundSlug: state.fund?.slug, amount: state.amount, monthly: state.monthly, profile: state.profile}));
    const horizonLabel = value => ({short:'Under 2 years', medium:'2 to 5 years', long:'5 years or more'})[value] || '';
    const statusLabel = value => value === 'accepted' ? 'Accepted' : value === 'rejected' ? 'Rejected' : 'Pending review';

    function rail(step) {
        document.querySelectorAll('#bw-rail [data-step]').forEach(item => {
            const number = Number(item.dataset.step);
            item.className = number === step ? 'is-current' : number < step ? 'is-complete' : '';
        });
    }
    function nav(section) {
        document.getElementById('bw-nav-invest').classList.toggle('is-active', section === 'invest');
        document.getElementById('bw-nav-portfolio').classList.toggle('is-active', section === 'portfolio');
        document.getElementById('bw-nav-documents').classList.toggle('is-active', section === 'documents');
    }
    function render(html) { stage.innerHTML = html; window.scrollTo({top: 0, behavior: 'smooth'}); }
    function choices(items) {
        return items.map(item => `<button type="button" class="bw-choice" data-value="${item.value}"><strong>${item.title}</strong><span>${item.body}</span>${item.note ? `<em>${item.note}</em>` : ''}</button>`).join('');
    }

    function showHorizon() {
        rail(1); nav('invest');
        render(`<section class="bw-content bw-content--narrow"><span class="bw-tag">Step 1 of 2</span><h1>When might you need this money back?</h1><p class="bw-lead">This is the biggest factor in what suits you. Everything after this narrows from your answer.</p><div class="bw-choices" id="horizon-choices">${choices([
            {value:'short', title:'Under 2 years', body:'A cushion, a planned expense, or money parked between decisions. Stability matters more than growth.', note:'Lower risk funds'},
            {value:'medium', title:'2 to 5 years', body:'A deposit, a business plan, or school fees on the horizon. Some growth, without a rough ride.', note:'Medium risk funds'},
            {value:'long', title:'5 years or more', body:'Building wealth over time. You can sit through a bad year to get the good ones.', note:'Higher risk funds'},
        ])}</div><p class="bw-note">Nothing is committed here. You can change your answer at any point before you pay.</p></section>`);
        document.querySelectorAll('#horizon-choices button').forEach(button => button.onclick = () => { state.horizon = button.dataset.value; save(); showPreference(); });
    }

    function showPreference() {
        rail(1); nav('invest');
        render(`<section class="bw-content bw-content--narrow"><button class="bw-back" type="button" id="back-horizon">← Back</button><span class="bw-tag">Step 2 of 2</span><h1>How would you like it managed?</h1><p class="bw-lead">You said ${esc(horizonLabel(state.horizon).toLowerCase())}. This is the last thing we need before showing what fits.</p><div class="bw-choices" id="preference-choices">${choices([
            {value:'shariah', title:'Shariah-compliant', body:'Screened holdings, no interest-bearing instruments, overseen by an independent Shariah board.'},
            {value:'conventional', title:'Conventional', body:'The full investable universe, with no religious screening applied.'},
            {value:'any', title:'No preference', body:'Show me everything that fits my timeframe and let me compare.'},
        ])}</div></section>`);
        document.getElementById('back-horizon').onclick = showHorizon;
        document.querySelectorAll('#preference-choices button').forEach(button => button.onclick = () => { state.preference = button.dataset.value; save(); showFunds(); });
    }

    function showFunds() {
        state.horizon ||= 'long'; state.preference ||= 'any'; save(); rail(2); nav('invest');
        const matches = funds.filter(f => f.horizon === state.horizon && (state.preference === 'any' || (state.preference === 'shariah') === Boolean(f.shariah)));
        render(`<section class="bw-content">
            <div class="bw-filters"><label>When you might need it back<select id="fund-horizon"><option value="short">Under 2 years</option><option value="medium">2 to 5 years</option><option value="long">5 years or more</option></select></label><label>How it should be managed<select id="fund-preference"><option value="shariah">Shariah</option><option value="conventional">Conventional</option><option value="any">Either</option></select></label></div>
            <div class="bw-fund-list">${matches.map(f => `<button class="bw-fund" type="button" data-fund="${f.slug}"><span class="bw-badge">BAN Wealth Selection</span><small>${esc(horizonLabel(f.horizon))} · ${f.shariah ? 'Shariah-compliant' : 'Conventional'}</small><h2>${esc(f.name)}</h2><p class="bw-manager">Managed by ${esc(f.manager)}</p><dl><div><dt>Sale price</dt><dd>${Number(f.nav).toFixed(2)}</dd></div><div><dt>12 months</dt><dd class="positive">${esc(f.return_12m)}</dd></div><div><dt>3 years p.a.</dt><dd class="positive">${esc(f.return_3y)}</dd></div><div><dt>Annual fee</dt><dd>${esc(f.fee)}</dd></div></dl><em>See the full picture and invest →</em></button>`).join('') || '<p class="bw-empty">Nothing matches that combination yet. Widen the preference or change the timeframe.</p>'}</div>
            </section>`);
        const horizon = document.getElementById('fund-horizon'); horizon.value = state.horizon; horizon.onchange = () => { state.horizon = horizon.value; showFunds(); };
        const preference = document.getElementById('fund-preference'); preference.value = state.preference; preference.onchange = () => { state.preference = preference.value; showFunds(); };
        document.querySelectorAll('[data-fund]').forEach(button => button.onclick = () => { state.fund = funds.find(f => f.slug === button.dataset.fund); save(); showFund(); });
    }

    function showFund() {
        if (!state.fund) return showFunds();
        const f = state.fund; rail(2); nav('invest');
        render(`<section class="bw-content"><button class="bw-back" id="back-funds" type="button">← Back to the shelf</button><span class="bw-tag">${esc(horizonLabel(f.horizon))} · ${f.shariah ? 'Shariah-compliant' : 'Conventional'}</span><h1>${esc(f.name)}</h1><p class="bw-manager">Managed by ${esc(f.manager)}, under a BAN Wealth mandate</p><p class="bw-lead">${esc(f.blurb)}</p>
            <aside class="bw-take"><h3>Why we built this fund</h3><p>${esc(f.take)} We set the mandate and review the fee and record every quarter.</p><small>BAN Wealth investment team · Our view, not the fund manager’s.</small></aside>
            <div class="bw-stats"><div><strong>${Number(f.nav).toFixed(2)}</strong><span>Sale price per unit</span></div><div><strong>${(Number(f.nav)-.30).toFixed(2)}</strong><span>Surrender price</span></div><div><strong>${esc(f.return_12m)}</strong><span>12 months</span></div><div><strong>${esc(f.return_3y)}</strong><span>3 years p.a.</span></div></div>
            <div class="bw-two"><div><h2 class="bw-section-title">Fund facts</h2><dl class="bw-facts"><div><dt>Fund manager</dt><dd>${esc(f.manager)}</dd></div><div><dt>Fund size</dt><dd>${esc(f.size)}</dd></div><div><dt>Annual management fee</dt><dd>${esc(f.fee)}</dd></div><div><dt>Risk band</dt><dd>${esc(f.risk)}</dd></div><div><dt>Structure</dt><dd>Open-ended, BSEC-registered</dd></div><div><dt>Minimum</dt><dd>${money(f.minimum)}</dd></div></dl></div><aside class="bw-panel"><h2>Invest through BAN Wealth</h2><p>Prices are set weekly, so your units are allocated at the next published price.</p><button class="bw-primary" id="choose-amount" type="button">Choose an amount</button></aside></div></section>`);
        document.getElementById('back-funds').onclick = showFunds; document.getElementById('choose-amount').onclick = showAmount;
    }

    function showAmount() {
        if (!state.fund) return showFunds(); rail(3); nav('invest'); const f = state.fund;
        render(`<section class="bw-content"><button class="bw-back" id="back-fund" type="button">← Back to the fund</button><h1>How much would you like to invest?</h1><p class="bw-lead">Into <strong>${esc(f.name)}</strong>. Minimum ${money(f.minimum)}.</p><div class="bw-two"><div><label class="bw-field">Amount (BDT)<input id="order-amount" type="number" min="${f.minimum}" step="1000" value="${state.amount}"></label><div class="bw-quick">${[100000,500000,1000000,2500000].map(v => `<button type="button" data-amount="${v}">${money(v)}</button>`).join('')}</div><label class="bw-check"><input id="monthly" type="checkbox" ${state.monthly ? 'checked' : ''}><span>Add the same amount every month. Stop or change it whenever you like.</span></label><p class="bw-note">Prices are set weekly. Your units are allocated at the next published price, so the estimate may change.</p></div><aside class="bw-panel"><h2>Your order</h2><dl class="bw-summary"><div><dt>Fund</dt><dd>${esc(f.name)}</dd></div><div><dt>Managed by</dt><dd>${esc(f.manager)}</dd></div><div><dt>NAV today</dt><dd>${Number(f.nav).toFixed(2)}</dd></div><div><dt>Estimated units</dt><dd id="unit-output">${units().toLocaleString('en-IN',{maximumFractionDigits:2})}</dd></div><div><dt>You invest</dt><dd id="amount-output">${money(state.amount)}</dd></div></dl><button class="bw-primary" id="continue-amount" type="button">Continue</button><p class="bw-error" id="amount-error"></p></aside></div></section>`);
        const amount = document.getElementById('order-amount');
        const update = () => { state.amount = Number(amount.value || 0); state.monthly = document.getElementById('monthly').checked; document.getElementById('unit-output').textContent = units().toLocaleString('en-IN',{maximumFractionDigits:2}); document.getElementById('amount-output').textContent = money(state.amount); save(); };
        amount.oninput = update; document.getElementById('monthly').onchange = update; document.querySelectorAll('[data-amount]').forEach(button => button.onclick = () => { amount.value = button.dataset.amount; update(); });
        document.getElementById('back-fund').onclick = showFund;
        document.getElementById('continue-amount').onclick = () => { update(); if (state.amount < Number(f.minimum)) { document.getElementById('amount-error').textContent = `The minimum for this fund is ${money(f.minimum)}.`; return; } authenticated ? showKyc(1) : showSignIn(); };
    }

    function showSignIn() {
        save(); rail(4);
        render(`<section class="bw-content bw-content--narrow bw-auth"><span class="bw-icon">↗</span><h1>Sign in to open your BAN Wealth account</h1><p class="bw-lead">Your fund choice and amount are saved on this device. Sign in with an investor account, or create one, to continue securely.</p><div class="bw-actions"><a class="bw-primary" href="{{ route('ban-wealth.continue') }}">Sign in and continue</a><a class="bw-secondary" href="{{ route('register') }}">Create investor account</a></div><button class="bw-back" id="auth-back" type="button">← Back to amount</button></section>`);
        document.getElementById('auth-back').onclick = showAmount;
    }

    function collectProfile() {
        stage.querySelectorAll('[data-profile]').forEach(input => state.profile[input.name] = input.type === 'checkbox' ? input.checked : input.value);
        save();
    }
    function input(name, label, type = 'text', required = true) {
        return `<label class="bw-field">${label}${required ? ' *' : ''}<input data-profile name="${name}" type="${type}" value="${esc(state.profile[name])}" ${required ? 'required' : ''}></label>`;
    }
    function select(name, label, options) {
        return `<label class="bw-field">${label} *<select data-profile name="${name}" required><option value="">Select one</option>${options.map(option => `<option value="${esc(option)}" ${state.profile[name] === option ? 'selected' : ''}>${esc(option)}</option>`).join('')}</select></label>`;
    }
    function showKyc(step = state.kycStep) {
        state.kycStep = step; rail(4); nav('invest');
        const labels = ['Identity', 'Bank and TIN', 'Suitability', 'Review and sign'];
        let title, lead, fields;
        if (step === 1) { title = 'Let’s open your account'; lead = 'Build your BAN Wealth profile once. We use it for this and future fund applications.'; fields = `<div class="bw-grid">${input('full_name','Full name, as on your NID')}${input('nid_number','NID number')}${input('date_of_birth','Date of birth','date')}${input('mobile','Mobile','tel')}${input('email','Email','email')}${input('present_address','Present address')}</div>`; }
        if (step === 2) { title = 'Your bank account and TIN'; lead = 'Redemptions are paid to this account. It must be in your own name.'; fields = `<div class="bw-grid">${input('bank_name','Bank')}${input('bank_branch','Branch')}${input('bank_account_number','Account number')}${input('routing_number','Routing number')}${input('tin','TIN','text',false)}${input('bo_account','BO account, if you have one','text',false)}</div>`; }
        if (step === 3) { title = 'A few questions about your plans'; lead = 'These checks help ensure the selected fund is suitable for you.'; fields = `<div class="bw-grid"><label class="bw-field">When might you need this money?<input value="${esc(horizonLabel(state.horizon))}" disabled></label>${input('source_of_funds','Source of the funds')}${select('investment_experience','Experience with market investments',['None yet','I have held mutual funds','I have held listed shares','Experienced investor'])}${select('loss_response','If this fell 20% in a year, you would',['Sell immediately','Wait for recovery','Hold and keep adding'])}</div><label class="bw-check"><input data-profile name="politically_exposed" type="checkbox" ${state.profile.politically_exposed ? 'checked' : ''}><span>I am, or am closely related to, a politically exposed person.</span></label>`; }
        if (step === 4) { title = 'Check this over, then sign'; lead = 'One confirmation opens your BAN Wealth account and prepares your first order.'; fields = `<dl class="bw-summary bw-review"><div><dt>Account holder</dt><dd>${esc(state.profile.full_name)}</dd></div><div><dt>Fund</dt><dd>${esc(state.fund.name)}</dd></div><div><dt>Managed by</dt><dd>${esc(state.fund.manager)}</dd></div><div><dt>Estimated units</dt><dd>${units().toLocaleString('en-IN',{maximumFractionDigits:2})}</dd></div><div><dt>Amount</dt><dd>${money(state.amount)}</dd></div></dl><label class="bw-check"><input data-profile name="prospectus_consent" type="checkbox" ${state.profile.prospectus_consent ? 'checked' : ''}><span>I have read the prospectus and fact sheet and understand that the value of my units can fall.</span></label><label class="bw-check"><input data-profile name="submission_consent" type="checkbox" ${state.profile.submission_consent ? 'checked' : ''}><span>I authorise BAN Wealth to submit this application in my name and hold my details for future orders.</span></label>`; }
        render(`<section class="bw-content"><button class="bw-back" id="kyc-back" type="button">← Back</button><h1>${title}</h1><p class="bw-lead">${lead}</p><div class="bw-stepper">${labels.map((label,index) => `<div class="${index+1===step?'is-current':index+1<step?'is-complete':''}"><b>${index+1<step?'✓':index+1}</b><span>${label}</span></div>`).join('')}</div><form id="kyc-form">${fields}<p class="bw-error" id="kyc-error"></p><div class="bw-actions"><button class="bw-primary" type="submit">${step === 4 ? 'Continue to bank transfer' : 'Continue'}</button><button class="bw-secondary" id="save-later" type="button">Save and finish later</button></div></form></section>`);
        document.getElementById('kyc-back').onclick = () => { collectProfile(); step === 1 ? showAmount() : showKyc(step - 1); };
        document.getElementById('save-later').onclick = () => { collectProfile(); showPortfolio(); };
        document.getElementById('kyc-form').onsubmit = event => { event.preventDefault(); if (!event.currentTarget.reportValidity()) return; collectProfile(); if (step === 4 && (!state.profile.prospectus_consent || !state.profile.submission_consent)) { document.getElementById('kyc-error').textContent = 'Please accept both confirmations to continue.'; return; } step < 4 ? showKyc(step + 1) : showPayment(); };
    }

    function hidden(name, value) { return `<input type="hidden" name="${name}" value="${esc(value)}">`; }
    function showPayment() {
        rail(5); nav('invest'); const p = state.profile;
        const hiddenFields = Object.entries(p).filter(([key]) => !['politically_exposed','prospectus_consent','submission_consent'].includes(key)).map(([key,value]) => hidden(key,value)).join('');
        render(`<section class="bw-content"><button class="bw-back" id="payment-back" type="button">← Back</button><h1>Send ${money(state.amount)}</h1><p class="bw-lead">Your money goes from your bank into the fund’s own account. BAN Wealth never holds client money.</p><div class="bw-two"><div><div class="bw-protect"><strong>Bank transfer only</strong><p>Transfer to the scheme account and upload the receipt or transaction confirmation below. The BAN team will review it.</p></div><dl class="bw-account"><div><dt>Account name</dt><dd>${esc(state.fund.name)}</dd></div><div><dt>Operated by</dt><dd>${esc(state.fund.manager)}</dd></div><div><dt>Bank</dt><dd>{{ e(config('ban-wealth.bank.name')) }}</dd></div><div><dt>Account number</dt><dd>{{ e(config('ban-wealth.bank.account_number')) }}</dd></div></dl></div><aside class="bw-panel"><h2>Your order</h2><dl class="bw-summary"><div><dt>Fund</dt><dd>${esc(state.fund.name)}</dd></div><div><dt>Estimated units</dt><dd>${units().toLocaleString('en-IN',{maximumFractionDigits:2})}</dd></div><div><dt>Amount</dt><dd>${money(state.amount)}</dd></div></dl><form method="POST" action="{{ route('ban-wealth.orders.store') }}" enctype="multipart/form-data" id="payment-form">@csrf${hidden('fund_slug',state.fund.slug)}${hidden('horizon',state.horizon)}${hidden('shariah_preference',state.preference)}${hidden('amount',state.amount)}${hidden('monthly',state.monthly?1:0)}${hidden('politically_exposed',p.politically_exposed?1:0)}${hidden('prospectus_consent',p.prospectus_consent?1:0)}${hidden('submission_consent',p.submission_consent?1:0)}${hiddenFields}<label class="bw-upload">Payment proof *<input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,.webp" required><span>PDF, JPG, PNG or WebP · max 5 MB</span></label><button class="bw-primary" type="submit">Submit transfer for review</button></form></aside></div></section>`);
        document.getElementById('payment-back').onclick = () => showKyc(4);
        document.getElementById('payment-form').onsubmit = event => { const button = event.currentTarget.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Submitting…'; };
    }

    function showConfirmation(reference) {
        localStorage.removeItem('banWealthJourney'); rail(6); nav('invest'); const order = orders.find(item => item.reference === reference);
        render(`<section class="bw-content bw-content--narrow bw-confirm"><div class="bw-success">✓</div><h1>Your transfer is awaiting review</h1><p class="bw-lead">Reference <strong>${esc(reference)}</strong>${order ? ` for ${money(order.amount)} into ${esc(order.fund_name)}` : ''}. You can follow its status from your BAN Wealth portfolio.</p><ol class="bw-timeline"><li class="is-current"><strong>Submitted</strong><span>Your order and payment proof are securely recorded.</span></li><li><strong>BAN review</strong><span>An administrator checks the bank transfer attachment.</span></li><li><strong>Accepted or rejected</strong><span>The result and any review note appear in your portfolio.</span></li><li><strong>Unit allocation</strong><span>Accepted transfers proceed at the fund’s applicable published price.</span></li></ol><div class="bw-actions"><button class="bw-primary" id="confirmation-portfolio" type="button">Go to my portfolio</button><button class="bw-secondary" id="confirmation-invest" type="button">Invest in something else</button></div></section>`);
        document.getElementById('confirmation-portfolio').onclick = showPortfolio; document.getElementById('confirmation-invest').onclick = () => { state.horizon=null; state.preference=null; state.fund=null; state.amount=500000; state.profile={...defaults}; showHorizon(); };
    }

    function showPortfolio(documentsOnly = false) {
        rail(0); nav(documentsOnly ? 'documents' : 'portfolio');
        if (!authenticated) return render(`<section class="bw-content bw-content--narrow bw-auth"><h1>${documentsOnly ? 'Your documents' : 'Your BAN Wealth portfolio'}</h1><p class="bw-lead">Sign in with your investor account to see submitted transfers and their review status.</p><a class="bw-primary" href="{{ route('ban-wealth.continue') }}">Sign in</a></section>`);
        render(`<section class="bw-content"><div class="bw-heading-row"><div><span class="bw-tag">Investor workspace</span><h1>${documentsOnly ? 'Payment documents' : 'Your BAN Wealth portfolio'}</h1><p class="bw-lead">${documentsOnly ? 'Receipts attached to your BAN Wealth orders.' : 'Track every bank transfer from submission through review.'}</p></div><button class="bw-primary" id="portfolio-invest" type="button">Invest more</button></div>${orders.length ? `<div class="bw-table-wrap"><table class="bw-table"><thead><tr><th>Reference</th><th>Fund</th><th>Submitted</th><th>Amount</th><th>Status</th><th>Document</th></tr></thead><tbody>${orders.map(order => `<tr><td><strong>${esc(order.reference)}</strong></td><td>${esc(order.fund_name)}<small>Managed by ${esc(order.fund_manager)}</small>${order.review_note ? `<small class="bw-review-note">Note: ${esc(order.review_note)}</small>` : ''}</td><td>${esc(order.created_at)}</td><td>${money(order.amount)}</td><td><span class="bw-status bw-status--${esc(order.status)}">${statusLabel(order.status)}</span></td><td><a href="${esc(order.proof_url)}">Download proof</a></td></tr>`).join('')}</tbody></table></div>` : '<div class="bw-empty"><h2>No BAN Wealth orders yet</h2><p>Your submitted bank transfers will appear here.</p></div>'}</section>`);
        document.getElementById('portfolio-invest').onclick = () => state.horizon ? showFunds() : showHorizon();
    }

    document.getElementById('bw-nav-invest').onclick = () => state.horizon ? showFunds() : showHorizon();
    document.getElementById('bw-nav-portfolio').onclick = () => showPortfolio(false);
    document.getElementById('bw-nav-documents').onclick = () => showPortfolio(true);

    if (submittedReference) showConfirmation(submittedReference);
    else if (hasServerErrors && state.fund && authenticated) showPayment();
    else if (resumeAfterLogin && state.fund && authenticated) showKyc(1);
    else showHorizon();
})();
</script>
<livewire:scripts />
</body>
</html>
