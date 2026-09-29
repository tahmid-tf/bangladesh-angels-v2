@extends($user->isAdmin() ? 'layouts.admin' : 'layouts.investor-panel')

@section('page_title', 'Profile Settings')

@section('page_content')
@php
    $isAdminProfile = $user->isAdmin();
    $roleLabel = $user->role === 'superadmin' ? 'Super Admin' : ($isAdminProfile ? 'Admin' : 'Investor');
    $dashboardRoute = $isAdminProfile ? route('admin.dashboard') : route('investor.dashboard');
@endphp
<div class="investor-profile">
    <header class="investor-profile__heading">
        <div>
            <p class="investor-eyebrow">{{ $isAdminProfile ? 'Administrator account' : 'Account settings' }}</p>
            <h1>Your {{ $isAdminProfile ? 'administrator' : 'investor' }} profile</h1>
            <p>Keep your contact and professional information accurate for the Bangladesh Angels network.</p>
        </div>
        <a href="{{ $dashboardRoute }}" class="investor-profile__back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 18l-6-6 6-6" /></svg>
            Back to dashboard
        </a>
    </header>

    @if (session('status') === 'profile-updated')
        <div class="investor-profile__notice" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            Your profile has been updated successfully.
        </div>
    @endif

    <div class="investor-profile__layout">
        <aside class="investor-profile__summary">
            <div class="investor-profile__portrait">
                <img src="{{ $user->getProfilePhotoUrl() }}" alt="{{ $user->name }}" id="profile-photo-preview">
                <span class="investor-profile__verified" title="Verified {{ strtolower($roleLabel) }} account" aria-label="Verified {{ strtolower($roleLabel) }} account">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </span>
            </div>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->designation ?: $roleLabel }}{{ $user->company_name ? ' · '.$user->company_name : '' }}</p>

            <dl class="investor-profile__facts">
                <div>
                    <dt>{{ $isAdminProfile ? 'Access' : 'Membership' }}</dt>
                    <dd>{{ $isAdminProfile ? $roleLabel : ucfirst($user->account_status ?: 'Free') }}</dd>
                </div>
                <div>
                    <dt>{{ $isAdminProfile ? 'Account' : 'Profile' }}</dt>
                    <dd>{{ $isAdminProfile ? 'Active' : ($user->public_profile === 'true' ? 'Public' : 'Private') }}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd class="{{ $user->hasVerifiedEmail() ? 'is-confirmed' : '' }}">{{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</dd>
                </div>
            </dl>

            <p class="investor-profile__summary-note">{{ $isAdminProfile ? 'Your administrator details identify you across the BAN workspace and internal communications.' : 'Your profile helps the BAN team understand your background and connect you with relevant opportunities.' }}</p>
        </aside>

        <div class="investor-profile__content">
            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="investor-profile__card">
                @csrf
                @method('patch')

                <header class="investor-profile__card-header">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 100-8 4 4 0 000 8zm7 8a7 7 0 00-14 0" /></svg>
                    </span>
                    <div>
                        <h2>Personal information</h2>
                        <p>Manage how your identity and contact details appear.</p>
                    </div>
                </header>

                <div class="investor-profile__photo-field">
                    <img src="{{ $user->getProfilePhotoUrl() }}" alt="" id="profile-photo-form-preview">
                    <div>
                        <label for="profile_picture" class="investor-profile__upload">Choose a new photo</label>
                        <input id="profile_picture" name="profile_picture" type="file" accept="image/png,image/jpeg,image/webp" data-profile-photo-input>
                        <p>PNG, JPG or WebP. Maximum file size 5 MB.</p>
                        @error('profile_picture')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="investor-profile__fields">
                    <div class="investor-profile__field">
                        <label for="name">Full name <span>*</span></label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                        @error('name')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="email">Email address <span>*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                        @error('email')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel" placeholder="e.g. +880 1XXX XXXXXX">
                        @error('phone')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Prefer not to say</option>
                            <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $user->gender) === 'other')>Other</option>
                        </select>
                        @error('gender')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="investor-profile__divider"></div>

                <header class="investor-profile__section-title">
                    <h3>Professional details</h3>
                    <p>Tell us about your current role and location.</p>
                </header>
                <div class="investor-profile__fields">
                    <div class="investor-profile__field">
                        <label for="designation">Job title</label>
                        <input id="designation" name="designation" type="text" value="{{ old('designation', $user->designation) }}" autocomplete="organization-title" placeholder="e.g. Managing Partner">
                        @error('designation')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="company_name">Company or organization</label>
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $user->company_name) }}" autocomplete="organization" placeholder="Your organization">
                        @error('company_name')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="primary_country">Primary country</label>
                        <input id="primary_country" name="primary_country" type="text" value="{{ old('primary_country', $user->primary_country) }}" autocomplete="country-name" placeholder="e.g. Bangladesh">
                        @error('primary_country')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="investor-profile__field">
                        <label for="linkedin">LinkedIn profile</label>
                        <input id="linkedin" name="linkedin" type="text" value="{{ old('linkedin', $user->linkedin) }}" inputmode="url" placeholder="https://linkedin.com/in/your-profile">
                        @error('linkedin')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                @if ($isAdminProfile)
                    <div class="investor-profile__access-note">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 3v5c0 4.6-2.9 8.1-7 10-4.1-1.9-7-5.4-7-10V6l7-3zm-3 9l2 2 4-4" /></svg>
                        </span>
                        <div><strong>{{ $roleLabel }} access</strong><p>Your permission level is managed by the platform and cannot be changed from your profile.</p></div>
                    </div>
                @else
                    <fieldset class="investor-profile__visibility">
                        <legend>Profile visibility</legend>
                        <p>Choose whether other network members can discover your profile.</p>
                        <div>
                            <label>
                                <input type="radio" name="public_profile" value="false" @checked(old('public_profile', $user->public_profile ?? 'false') === 'false')>
                                <span><strong>Private</strong><small>Only the BAN team can view your details.</small></span>
                            </label>
                            <label>
                                <input type="radio" name="public_profile" value="true" @checked(old('public_profile', $user->public_profile) === 'true')>
                                <span><strong>Public</strong><small>Visible in the investor network directory.</small></span>
                            </label>
                        </div>
                        @error('public_profile')<span class="investor-profile__error">{{ $message }}</span>@enderror
                    </fieldset>
                @endif

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="investor-profile__verification">
                        Your email address is not verified. Save any email change first, then use the verification link sent to your inbox.
                    </div>
                @endif

                <footer class="investor-profile__actions">
                    <p>Fields marked with <span>*</span> are required.</p>
                    <button type="submit">Save profile</button>
                </footer>
            </form>

            <form method="post" action="{{ route('password.update') }}" class="investor-profile__card investor-profile__card--security">
                @csrf
                @method('put')

                <header class="investor-profile__card-header">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V8a5 5 0 0110 0v2m-11 0h12v10H6V10z" /></svg>
                    </span>
                    <div>
                        <h2>Password & security</h2>
                        <p>Use a strong, unique password to protect your account.</p>
                    </div>
                </header>

                @if (session('status') === 'password-updated')
                    <div class="investor-profile__inline-success">Your password has been updated.</div>
                @endif

                <div class="investor-profile__fields investor-profile__fields--password">
                    <div class="investor-profile__field">
                        <label for="update_password_current_password">Current password</label>
                        <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" required>
                        @if ($errors->updatePassword->has('current_password'))<span class="investor-profile__error">{{ $errors->updatePassword->first('current_password') }}</span>@endif
                    </div>
                    <div class="investor-profile__field">
                        <label for="update_password_password">New password</label>
                        <input id="update_password_password" name="password" type="password" autocomplete="new-password" required>
                        @if ($errors->updatePassword->has('password'))<span class="investor-profile__error">{{ $errors->updatePassword->first('password') }}</span>@endif
                    </div>
                    <div class="investor-profile__field">
                        <label for="update_password_password_confirmation">Confirm new password</label>
                        <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        @if ($errors->updatePassword->has('password_confirmation'))<span class="investor-profile__error">{{ $errors->updatePassword->first('password_confirmation') }}</span>@endif
                    </div>
                </div>

                <footer class="investor-profile__actions">
                    <p>We recommend at least 12 characters.</p>
                    <button type="submit">Update password</button>
                </footer>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const profilePhotoInput = document.querySelector('[data-profile-photo-input]');
    profilePhotoInput?.addEventListener('change', event => {
        const [file] = event.target.files;
        if (!file) return;

        const previewUrl = URL.createObjectURL(file);
        document.getElementById('profile-photo-preview').src = previewUrl;
        document.getElementById('profile-photo-form-preview').src = previewUrl;
    });
</script>
@endpush
