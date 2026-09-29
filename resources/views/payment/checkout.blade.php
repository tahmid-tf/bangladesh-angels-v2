@extends('layouts.guest')
@section('page_title', 'Checkout | Bangladesh Angels Network Limited')

@push('head_meta')
    <x-seo-meta
        title="Checkout | Bangladesh Angels Network Limited"
        description="Complete your Bangladesh Angels Network membership checkout securely."
        :canonical="route('checkout')"
        :image="asset('icon.webp')"
    />
@endpush

@section('page_content')
@php
    $cp = session('checkout.plan', []);
    if (! is_array($cp)) {
        $cp = ['slug' => is_string($cp) ? $cp : '', 'name' => 'Default Plan', 'price' => 0];
    }
    $cp += ['slug' => '', 'name' => 'Default Plan', 'price' => 0];
    $planSlug = (string) ($cp['slug'] ?? '');
    $planName = (string) ($cp['name'] ?? 'Default Plan');
    $planPrice = $cp['price'] ?? 0;
    $checkoutUser = auth()->user();
    $isLoggedInCheckout = (bool) $checkoutUser;
    $knownCodes = ['880', '44', '1', '971', '91', '92', '61', '65', '81'];
    $savedPhoneRaw = $checkoutUser?->phone ? preg_replace('/\D+/', '', (string) $checkoutUser->phone) : '';
    $savedCountryCode = old('country_code', '44');
    $savedPhoneLocal = old('phone', '');
    if ($savedPhoneRaw) {
        foreach ($knownCodes as $code) {
            if (str_starts_with($savedPhoneRaw, $code) && strlen($savedPhoneRaw) > strlen($code)) {
                $savedCountryCode = $code;
                $savedPhoneLocal = substr($savedPhoneRaw, strlen($code));
                break;
            }
        }
        if ($savedPhoneLocal === '' && $savedPhoneRaw !== '') {
            $savedPhoneLocal = $savedPhoneRaw;
        }
    }
@endphp

<!-- Page Container -->
<div class="container mx-auto px-4 py-8 md:py-12 max-w-7xl">
    
    <!-- Header -->
    <div class="text-center mb-8 md:mb-12">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Let's finish powering you up!</h1>
        <p class="text-gray-600">Complete your membership registration below.</p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mt-4 mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                ✖
            </button>
        </div>
    @endif
    
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content -->
    <form action="{{ route('checkout.process') }}" method="POST" class="animate-fade-in">
        @csrf
        <!-- Hidden Inputs for Plan and Price -->
        <input type="hidden" name="plan" value="{{ $planSlug }}">
        <input type="hidden" name="price" value="{{ $planPrice }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Selected Plan Section -->
            <div class="bg-white shadow-md rounded-lg p-6 border border-gray-100 lg:col-span-3 transition hover:shadow-lg">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    Selected Plan
                </h2>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
                    <div>
                        <p class="text-xl font-bold text-gray-800">{{ $planName }}</p>
                        <p class="text-green-600 font-semibold">${{ number_format((float) $planPrice, 2) }} /yr</p>
                    </div>
                    <a href="{{ route('plans') }}" class="text-green-600 hover:text-green-800 transition flex items-center text-sm font-medium hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Change Plan
                    </a>
                </div>
            </div>
              
        
            <!-- Left: Billing Address -->
            <div class="lg:col-span-2 bg-white shadow-md rounded-lg p-6 border border-gray-100 transition hover:shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <!-- Billing Address -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            Billing Information
                        </h2>
                        @if($isLoggedInCheckout)
                            <p class="mb-3 text-xs text-gray-500">Using details already saved on your investor profile. Only missing fields are shown.</p>
                        @endif
                        <!-- Full Name -->
                        @if($isLoggedInCheckout && filled($checkoutUser->name))
                            <input type="hidden" name="name" value="{{ old('name', $checkoutUser->name) }}">
                        @else
                            <div class="flex">
                                <div class="flex flex-col w-full">
                                    <label class="block text-gray-700 font-semibold mb-1 text-sm" for="name">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="name" placeholder="Enter your full name" name="name" value="{{ old('name', $checkoutUser?->name ?? '') }}" class="w-full p-3 rounded-lg border border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" required>
                                </div>
                            </div>
                        @endif
                <div class="mt-3">
                    <!-- Email -->
                    @if($isLoggedInCheckout && filled($checkoutUser->email))
                        <input type="hidden" name="email" value="{{ old('email', $checkoutUser->email) }}">
                    @else
                        <div>
                            <label class="block text-gray-700 font-semibold mb-1 text-sm" for="email">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" placeholder="Your email address" class="w-full border rounded-lg p-3 text-gray-700 border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" value="{{ old('email', $checkoutUser?->email ?? '') }}" required>
                        </div>
                    @endif
                </div>
                <div class="mt-3">
                    @if($isLoggedInCheckout && filled($checkoutUser->address))
                        <input type="hidden" name="address" value="{{ old('address', $checkoutUser->address) }}">
                    @else
                        <div>    
                            <label class="block text-gray-700 font-semibold mb-1 text-sm" for="address">Billing Address <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="address"
                                name="address" 
                                placeholder="Street address" 
                                value="{{ old('address', $checkoutUser?->address ?? '') }}" 
                                class="w-full border rounded-lg p-3 text-gray-700 border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition"
                            >
                        </div>
                    @endif
                </div>
                <div class="mt-3">
                    @if($isLoggedInCheckout && filled($savedPhoneLocal))
                        <input type="hidden" name="country_code" value="{{ $savedCountryCode }}">
                        <input type="hidden" name="phone" value="{{ $savedPhoneLocal }}">
                    @else
                    <label class="block text-gray-700 font-semibold mb-1 text-sm" for="phone">Phone Number <span class="text-red-500">*</span></label>
                    <div class="flex space-x-2">
                        <select name="country_code" class="w-1/3 p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" id="country_code" style="font-size: 16px; padding-right: 30px;">
                            <option data-countryCode="BD" value="880" {{ $savedCountryCode === '880' ? 'selected' : '' }} style="display: flex; align-items: center;">🇧🇩 (+880)</option>
                            <option data-countryCode="GB" value="44" {{ $savedCountryCode === '44' ? 'selected' : '' }} style="display: flex; align-items: center;">🇬🇧 (+44)</option>
                            <option data-countryCode="US" value="1" {{ $savedCountryCode === '1' ? 'selected' : '' }} style="display: flex; align-items: center;">🇺🇸 (+1)</option>
                            <optgroup label="Other countries">
                                <option data-countryCode="DZ" value="213" style="display: flex; align-items: center;">🇩🇿 (+213)</option>
                                <option data-countryCode="AD" value="376" style="display: flex; align-items: center;">🇦🇩 (+376)</option>
                                <option data-countryCode="AO" value="244" style="display: flex; align-items: center;">🇦🇴 (+244)</option>
                                <option data-countryCode="AI" value="1264" style="display: flex; align-items: center;">🇦🇮 (+1264)</option>
                                <option data-countryCode="AG" value="1268" style="display: flex; align-items: center;">🇦🇬 (+1268)</option>
                                <option data-countryCode="AR" value="54" style="display: flex; align-items: center;">🇦🇷 (+54)</option>
                                <option data-countryCode="AM" value="374" style="display: flex; align-items: center;">🇦🇲 (+374)</option>
                                <option data-countryCode="AW" value="297" style="display: flex; align-items: center;">🇦🇼 (+297)</option>
                                <option data-countryCode="AU" value="61" style="display: flex; align-items: center;">🇦🇺 (+61)</option>
                                <option data-countryCode="AT" value="43" style="display: flex; align-items: center;">🇦🇹 (+43)</option>
                                <option data-countryCode="AZ" value="994" style="display: flex; align-items: center;">🇦🇿 (+994)</option>
                                <option data-countryCode="BS" value="1242" style="display: flex; align-items: center;">🇧🇸 (+1242)</option>
                                <option data-countryCode="BH" value="973" style="display: flex; align-items: center;">🇧🇭 (+973)</option>
                                <option data-countryCode="BB" value="1246" style="display: flex; align-items: center;">🇧🇧 (+1246)</option>
                                <option data-countryCode="BY" value="375" style="display: flex; align-items: center;">🇧🇾 (+375)</option>
                                <option data-countryCode="BE" value="32" style="display: flex; align-items: center;">🇧🇪 (+32)</option>
                                <option data-countryCode="BZ" value="501" style="display: flex; align-items: center;">🇧🇿 (+501)</option>
                                <option data-countryCode="BJ" value="229" style="display: flex; align-items: center;">🇧🇯 (+229)</option>
                                <option data-countryCode="BM" value="1441" style="display: flex; align-items: center;">🇧🇲 (+1441)</option>
                                <option data-countryCode="BT" value="975" style="display: flex; align-items: center;">🇧🇹 (+975)</option>
                                <option data-countryCode="BO" value="591" style="display: flex; align-items: center;">🇧🇴 (+591)</option>
                                <option data-countryCode="BA" value="387" style="display: flex; align-items: center;">🇧🇦 (+387)</option>
                                <option data-countryCode="BW" value="267" style="display: flex; align-items: center;">🇧🇼 (+267)</option>
                                <option data-countryCode="BR" value="55" style="display: flex; align-items: center;">🇧🇷 (+55)</option>
                                <option data-countryCode="BN" value="673" style="display: flex; align-items: center;">🇧🇳 (+673)</option>
                                <option data-countryCode="BG" value="359" style="display: flex; align-items: center;">🇧🇬 (+359)</option>
                                <option data-countryCode="BF" value="226" style="display: flex; align-items: center;">🇧🇫 (+226)</option>
                                <option data-countryCode="BI" value="257" style="display: flex; align-items: center;">🇧🇮 (+257)</option>
                                <option data-countryCode="KH" value="855" style="display: flex; align-items: center;">🇰🇭 (+855)</option>
                                <option data-countryCode="CM" value="237" style="display: flex; align-items: center;">🇨🇲 (+237)</option>
                                <option data-countryCode="CA" value="1" style="display: flex; align-items: center;">🇨🇦 (+1)</option>
                                <option data-countryCode="CL" value="56" style="display: flex; align-items: center;">🇨🇱 (+56)</option>
                                <option data-countryCode="CN" value="86" style="display: flex; align-items: center;">🇨🇳 (+86)</option>
                                <option data-countryCode="CO" value="57" style="display: flex; align-items: center;">🇨🇴 (+57)</option>
                                <option data-countryCode="CR" value="506" style="display: flex; align-items: center;">🇨🇷 (+506)</option>
                                <option data-countryCode="HR" value="385" style="display: flex; align-items: center;">🇭🇷 (+385)</option>
                                <option data-countryCode="CU" value="53" style="display: flex; align-items: center;">🇨🇺 (+53)</option>
                                <option data-countryCode="CY" value="357" style="display: flex; align-items: center;">🇨🇾 (+357)</option>
                                <option data-countryCode="CZ" value="420" style="display: flex; align-items: center;">🇨🇿 (+420)</option>
                                <option data-countryCode="DK" value="45" style="display: flex; align-items: center;">🇩🇰 (+45)</option>
                                <option data-countryCode="DO" value="1809" style="display: flex; align-items: center;">🇩🇴 (+1809)</option>
                                <option data-countryCode="EG" value="20" style="display: flex; align-items: center;">🇪🇬 (+20)</option>
                                <option data-countryCode="SV" value="503" style="display: flex; align-items: center;">🇸🇻 (+503)</option>
                                <option data-countryCode="ET" value="251" style="display: flex; align-items: center;">🇪🇹 (+251)</option>
                                <option data-countryCode="FI" value="358" style="display: flex; align-items: center;">🇫🇮 (+358)</option>
                                <option data-countryCode="FR" value="33" style="display: flex; align-items: center;">🇫🇷 (+33)</option>
                                <option data-countryCode="DE" value="49" style="display: flex; align-items: center;">🇩🇪 (+49)</option>
                                <option data-countryCode="GH" value="233" style="display: flex; align-items: center;">🇬🇭 (+233)</option>
                                <option data-countryCode="GR" value="30" style="display: flex; align-items: center;">🇬🇷 (+30)</option>
                                <option data-countryCode="HK" value="852" style="display: flex; align-items: center;">🇭🇰 (+852)</option>
                                <option data-countryCode="HU" value="36" style="display: flex; align-items: center;">🇭🇺 (+36)</option>
                                <option data-countryCode="IS" value="354" style="display: flex; align-items: center;">🇮🇸 (+354)</option>
                                <option data-countryCode="IN" value="91" style="display: flex; align-items: center;">🇮🇳 (+91)</option>
                                <option data-countryCode="ID" value="62" style="display: flex; align-items: center;">🇮🇩 (+62)</option>
                                <option data-countryCode="IE" value="353" style="display: flex; align-items: center;">🇮🇪 (+353)</option>
                                <option data-countryCode="IL" value="972" style="display: flex; align-items: center;">🇮🇱 (+972)</option>
                                <option data-countryCode="IT" value="39" style="display: flex; align-items: center;">🇮🇹 (+39)</option>
                                <option data-countryCode="JP" value="81" style="display: flex; align-items: center;">🇯🇵 (+81)</option>
                                <option data-countryCode="MY" value="60" style="display: flex; align-items: center;">🇲🇾 (+60)</option>
                                <option data-countryCode="MX" value="52" style="display: flex; align-items: center;">🇲🇽 (+52)</option>
                                <option data-countryCode="NL" value="31" style="display: flex; align-items: center;">🇳🇱 (+31)</option>
                                <option data-countryCode="NZ" value="64" style="display: flex; align-items: center;">🇳🇿 (+64)</option>
                                <option data-countryCode="PK" value="92" style="display: flex; align-items: center;">🇵🇰 (+92)</option>
                                <option data-countryCode="PH" value="63" style="display: flex; align-items: center;">🇵🇭 (+63)</option>
                                <option data-countryCode="PL" value="48" style="display: flex; align-items: center;">🇵🇱 (+48)</option>
                                <option data-countryCode="PT" value="351" style="display: flex; align-items: center;">🇵🇹 (+351)</option>
                                <option data-countryCode="RU" value="7" style="display: flex; align-items: center;">🇷🇺 (+7)</option>
                                <option data-countryCode="SA" value="966" style="display: flex; align-items: center;">🇸🇦 (+966)</option>
                                <option data-countryCode="ZA" value="27" style="display: flex; align-items: center;">🇿🇦 (+27)</option>
                                <option data-countryCode="ES" value="34" style="display: flex; align-items: center;">🇪🇸 (+34)</option>
                                <option data-countryCode="SE" value="46" style="display: flex; align-items: center;">🇸🇪 (+46)</option>
                                <option data-countryCode="CH" value="41" style="display: flex; align-items: center;">🇨🇭 (+41)</option>
                                <option data-countryCode="TH" value="66" style="display: flex; align-items: center;">🇹🇭 (+66)</option>
                                <option data-countryCode="TR" value="90" style="display: flex; align-items: center;">🇹🇷 (+90)</option>
                                <option data-countryCode="AE" value="971" style="display: flex; align-items: center;">🇦🇪 (+971)</option>
                                <option data-countryCode="GB" value="44" style="display: flex; align-items: center;">🇬🇧 (+44)</option>
                                <option data-countryCode="US" value="1" style="display: flex; align-items: center;">🇺🇸 (+1)</option>
                            </optgroup>
                        </select>
                        <input type="text" id="phone" name="phone" placeholder="Phone number" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" value="{{ $savedPhoneLocal }}" required>
                    </div>
                    @endif
                </div>
                
                @guest
                <div class="mt-4">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm" for="password">Password <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Create a secure password" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" required>
                    <p class="text-sm text-gray-500 mt-1">Password must contain at least 8 characters, including uppercase, lowercase, number, and special character.</p>
                </div>
    
                <!-- Re-enter Password -->
                <div class="mt-3">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm" for="re_password">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" id="re_password" name="password_confirmation" placeholder="Confirm your password" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" required>
                </div>
                @endguest
            <!-- Password -->
            
            @if($isLoggedInCheckout && filled($checkoutUser->investment_expertise))
                <input type="hidden" name="investment_expertise" value="{{ old('investment_expertise', $checkoutUser->investment_expertise) }}">
            @else
                <div class="mt-3">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm" for="investment_expertise">
                        Level of Investment Expertise <span class="text-red-500">*</span>
                    </label>
                    <select id="investment_expertise" name="investment_expertise"
                            class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition"
                            required>
                        <option value="">Select your expertise level</option>
                        
                        <option value="beginner"
                            {{ ($checkoutUser && $checkoutUser->investment_expertise == 'beginner') ? 'selected' : '' }}>
                            Beginner
                        </option>
                        
                        <option value="intermediate"
                            {{ ($checkoutUser && $checkoutUser->investment_expertise == 'intermediate') ? 'selected' : '' }}>
                            Intermediate
                        </option>
                        
                        <option value="expert"
                            {{ ($checkoutUser && $checkoutUser->investment_expertise == 'expert') ? 'selected' : '' }}>
                            Expert
                        </option>
                    </select>
                </div>
            @endif
            
            </div>  

                    <!-- Professional Information -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 md:mt-0 mt-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                                <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                            </svg>
                            Professional Information
                        </h2>
                        
                        <!-- Company Name -->
                        @if($isLoggedInCheckout && filled($checkoutUser->company_name))
                            <input type="hidden" name="company_name" value="{{ old('company_name', $checkoutUser->company_name) }}">
                        @else
                            <div>
                                <label class="block text-gray-700 font-semibold mb-1 text-sm" for="company_name">Company Name <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="company_name" 
                                    name="company_name"
                                    value="{{ old('company_name', $checkoutUser?->company_name ?? '') }}"
                                    placeholder="Your company name"
                                    class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition"
                                    required
                                >
                            </div>
                        @endif

                        <!-- Designation -->
                        @if($isLoggedInCheckout && filled($checkoutUser->designation))
                            <input type="hidden" name="designation" value="{{ old('designation', $checkoutUser->designation) }}">
                        @else
                            <div class="mt-3">
                                <label class="block text-gray-700 font-semibold mb-1 text-sm" for="designation">Designation <span class="text-red-500">*</span></label>
                                <input type="text" id="designation" name="designation" placeholder="Your job title" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" value="{{ old('designation', $checkoutUser?->designation ?? '') }}" required>
                            </div>
                        @endif

                        @if($isLoggedInCheckout && filled($checkoutUser->primary_country))
                            <input type="hidden" name="primary_country" value="{{ old('primary_country', $checkoutUser->primary_country) }}">
                        @else
                        <div class="mt-3">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm" for="primary_country">Country <span class="text-red-500">*</span></label>
                            <select id="primary_country" name="primary_country" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" required>
                                <option value="">Select your country</option>
                                @foreach ([
                                    "Afghanistan", "Åland Islands", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", 
                                    "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", 
                                    "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", 
                                    "Bolivia", "Bosnia and Herzegovina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", 
                                    "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde",
                                    "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands",
                                    "Colombia", "Comoros", "Congo", "Congo, The Democratic Republic of The", "Cook Islands", "Costa Rica", "Cote D'ivoire",
                                    "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "Ecuador",
                                    "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)",
                                    "Faroe Islands", "Fiji", "Finland", "France", "French Guiana", "French Polynesia", "French Southern Territories",
                                    "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam",
                                    "Guatemala", "Guernsey", "Guinea", "Guinea-bissau", "Guyana", "Haiti", "Honduras", "Hong Kong", "Hungary", "Iceland",
                                    "India", "Indonesia", "Iran, Islamic Republic of", "Iraq", "Ireland", "Isle of Man", "Israel", "Italy", "Jamaica",
                                    "Japan", "Jersey", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Republic of", "Kuwait", "Kyrgyzstan",
                                    "Lao People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya",
                                    "Liechtenstein", "Lithuania", "Luxembourg", "Macao", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta",
                                    "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of",
                                    "Monaco", "Mongolia", "Montenegro", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal",
                                    "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands",
                                    "Norway", "Oman", "Pakistan", "Palau", "Palestinian Territory, Occupied", "Panama", "Papua New Guinea", "Paraguay",
                                    "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Romania", "Russian Federation",
                                    "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and The Grenadines", "Samoa", "San Marino",
                                    "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia",
                                    "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden",
                                    "Switzerland", "Syrian Arab Republic", "Taiwan", "Tajikistan", "Tanzania, United Republic of", "Thailand",
                                    "Timor-leste", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan",
                                    "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom",
                                    "United States", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Viet Nam", "Zambia", "Zimbabwe"
                                ] as $country)
                                    <option value="{{ $country }}" {{ (auth()->user() && auth()->user()->primary_country == $country) ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <!-- Gender -->
                        @if($isLoggedInCheckout && filled($checkoutUser->gender))
                            <input type="hidden" name="gender" value="{{ old('gender', $checkoutUser->gender) }}">
                        @else
                        <div class="mt-3">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm" for="gender">Gender <span class="text-red-500">*</span></label>
                            <select id="gender" name="gender" class="w-full p-3 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" required>
                                <option value="">Select gender</option>
                                
                                <option value="male" 
                                    {{ (old('gender') ? old('gender') : (auth()->check() ? auth()->user()->gender : '')) == 'male' ? 'selected' : '' }}>
                                    Male
                                </option>
                            
                                <option value="female" 
                                    {{ (old('gender') ? old('gender') : (auth()->check() ? auth()->user()->gender : '')) == 'female' ? 'selected' : '' }}>
                                    Female
                                </option>
                            
                                <option value="other" 
                                    {{ (old('gender') ? old('gender') : (auth()->check() ? auth()->user()->gender : '')) == 'other' ? 'selected' : '' }}>
                                    Other
                                </option>
                            </select>
                        </div>
                        @endif
                        
                        <!-- LinkedIn -->
                        <div class="mt-3">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm" for="linkedin">LinkedIn <span class="text-gray-500 font-normal">(optional)</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                                    <svg class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z"/>
                                    </svg>
                                </span>
                                <input type="text" id="linkedin" name="linkedin" placeholder="LinkedIn profile URL" class="w-full p-3 pl-10 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition" value="{{ old('linkedin', $checkoutUser?->linkedin ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="bg-white shadow-md rounded-lg p-6 border border-gray-100 h-fit sticky top-4 transition hover:shadow-lg">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                    </svg>
                    Order Summary
                </h2>
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <ul class="space-y-4">
                        <li class="flex justify-between text-gray-700">
                            <span>Subscription</span>
                            <span class="bg-green-100 text-green-600 text-sm font-semibold px-2 py-1 rounded-full">
                                {{ $planName }}
                            </span>
                        </li>
                        <li class="text-gray-800 text-3xl font-bold text-center pt-2 pb-1">
                            ${{ number_format((float) $planPrice, 2) }} <span class="text-lg font-normal text-gray-500">/yr</span>
                        </li>
                        <li class="flex justify-between text-gray-700 border-t pt-3">
                            <span>Total Billed Today</span>
                            <span class="font-semibold">${{ number_format((float) $planPrice, 2) }}</span>
                        </li>
                    </ul>
                </div>
                <input type="hidden" name="policy_version" value="{{ \App\Support\MembershipPolicies::version() }}">
                <input type="hidden" name="policy_accepted_at" id="policy-accepted-at" value="">
                <label class="ban-checkout-consent" for="policy-consent">
                    <input type="checkbox" id="policy-consent" name="policy_consent" value="1" required onchange="document.getElementById('policy-accepted-at').value = this.checked ? new Date().toISOString() : ''">
                    <span>I have read and agree to the <a href="{{ route('policies.show', 'terms-and-conditions') }}" target="_blank" rel="noopener noreferrer">Terms &amp; Conditions</a>, <a href="{{ route('policies.show', 'privacy-policy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>, <a href="{{ route('policies.show', 'delivery-policy') }}" target="_blank" rel="noopener noreferrer">Delivery Policy</a>, <a href="{{ route('policies.show', 'refund-return-policy') }}" target="_blank" rel="noopener noreferrer">Refund/Return Policy</a>, and <a href="{{ route('policies.show', 'cancellation-policy') }}" target="_blank" rel="noopener noreferrer">Cancellation Policy</a>.</span>
                </label>
                @error('policy_consent')<p class="text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                @error('policy_version')<p class="text-sm text-red-700" role="alert">The policies have changed. Please read and accept the current policies before continuing.</p>@enderror
                <button type="submit" class="mt-4 bg-green-600 text-white w-full py-3 rounded-lg hover:bg-green-700 transition duration-200 flex items-center justify-center font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    Complete Payment
                </button>
                <p class="text-center text-sm text-gray-600 mt-4 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    Secure payment
                </p>
            </div>
        </div>
    </form>

</div>

<style>
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Country code select styling for better emoji display */
    #country_code {
        font-size: 16px;
        padding-right: 30px;
    }
    
    #country_code option {
        font-size: 16px;
        padding: 8px;
    }
    
    /* Fix for emoji rendering on mobile */
    @media (max-width: 768px) {
        #country_code {
            font-size: 15px;
        }
    }
    
    /* Fix for iOS */
    @supports (-webkit-touch-callout: none) {
        #country_code {
            text-indent: 0;
            line-height: normal;
        }
    }
</style>

@endsection
