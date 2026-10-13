<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request, string $intent = 'login'): RedirectResponse
    {
        $intent = in_array($intent, ['login', 'signup'], true) ? $intent : 'login';
        $mode = $request->query('mode');
        $returnTo = $request->query('redirect_uri') ?? $request->query('return_to');

        $this->configureGoogleRedirect($request);

        if ($mode === 'mobile') {
            $statePayload = [
                'intent' => $intent,
                'mode' => 'mobile',
                'redirect_uri' => $returnTo ?: 'bdangelsapp://auth/google/callback',
                'ts' => time(),
            ];
            $state = base64_encode(json_encode($statePayload));

            return Socialite::driver('google')
                ->stateless()
                ->with([
                    'state' => $state,
                    'prompt' => 'select_account',
                ])
                ->redirect();
        }

        $request->session()->put('google_oauth_intent', $intent);

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): mixed
    {
        // Decode state if provided (used for mobile and stateless flows)
        $stateData = [];
        if ($stateRaw = $request->query('state')) {
            $decoded = json_decode(base64_decode($stateRaw, true) ?: '', true);
            if (is_array($decoded)) {
                $stateData = $decoded;
            }
        }

        $isMobile = ($stateData['mode'] ?? null) === 'mobile' || $request->query('mode') === 'mobile';
        $returnTo = $stateData['redirect_uri'] ?? $request->query('redirect_uri') ?? 'bdangelsapp://auth/google/callback';
        $intent = $stateData['intent'] ?? $request->session()->pull('google_oauth_intent', 'login');

        $this->configureGoogleRedirect($request);

        try {
            $googleUser = $isMobile
                ? Socialite::driver('google')->stateless()->user()
                : Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback error: '.$e->getMessage());
            if ($isMobile) {
                return $this->mobileFailureRedirect($returnTo, 'Google sign-in was cancelled or failed. Please try again.');
            }
            return $this->failureRedirect($intent, 'Google sign-in was cancelled or failed. Please try again.');
        }

        if (! filled($googleUser->getEmail())) {
            if ($isMobile) {
                return $this->mobileFailureRedirect($returnTo, 'Your Google account does not share an email address. Please use email registration instead.');
            }
            return $this->failureRedirect($intent, 'Your Google account does not share an email address. Please use email registration instead.');
        }

        $email = Str::lower($googleUser->getEmail());

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        // Handle Mobile App authentication
        if ($isMobile) {
            $isNewUser = false;
            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: 'Google User',
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'investor',
                    'account_status' => 'free',
                    'payment_status' => 'free',
                    'status' => 'active',
                    'is_approved' => false,
                    'email_verified_at' => now(),
                    'primary_country' => 'Bangladesh',
                    'investment_expertise' => 'beginner',
                ]);
                $isNewUser = true;
            } else {
                $this->linkGoogleAccount($user, $googleUser);
            }

            if ($user->account_status === 'disabled' || $user->role === 'disabled') {
                return $this->mobileFailureRedirect($returnTo, 'Your account has been deactivated. Please contact support.');
            }

            $token = $this->createTokenSafely($user, 'mobile-google');
            $userPayload = $this->formatUser($user->fresh());

            $sep = str_contains($returnTo, '?') ? '&' : '?';
            $target = $returnTo.$sep.http_build_query([
                'token' => $token,
                'user' => json_encode($userPayload),
                'is_new_user' => $isNewUser ? '1' : '0',
            ]);

            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Redirecting to BD Angels...</title><meta http-equiv="refresh" content="0;url='.e($target).'"><script>window.location.replace('.json_encode($target).');</script><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;background:#fff;color:#092F29;margin:0;padding:24px;text-align:center}.spinner{width:44px;height:44px;border:4px solid #E5E7EB;border-top-color:#0F7359;border-radius:50%;animation:spin .8s linear infinite;margin-bottom:20px}@keyframes spin{to{transform:rotate(360deg)}}.btn{display:inline-block;margin-top:20px;background:#0F7359;color:#fff;padding:12px 24px;border-radius:9999px;text-decoration:none;font-weight:700;font-size:14px}</style></head><body><div class="spinner"></div><h2 style="margin:0 0 8px;font-size:20px">Returning to BD Angels...</h2><p style="margin:0;color:#5A746D;font-size:14px">Please wait while we complete your sign in.</p><a href="'.e($target).'" class="btn">Tap to Open App</a></body></html>',
                200,
                [
                    'Content-Type' => 'text/html',
                    'Location' => $target,
                ]
            );
        }

        // Web Authentication Flow
        if ($intent === 'login') {
            if (! $user) {
                return redirect()
                    ->route('investor.signup')
                    ->with('info', 'No account exists for this Google email yet. Complete investor registration below, or use Sign up with Google.');
            }

            $this->linkGoogleAccount($user, $googleUser);
            Auth::login($user, remember: true);

            return redirect()->intended(route('home'));
        }

        if ($user) {
            $this->linkGoogleAccount($user, $googleUser);
            Auth::login($user, remember: true);

            return redirect()->route('approval.success');
        }

        $nameParts = $this->splitName((string) $googleUser->getName());

        $request->session()->put('google_signup', [
            'google_id' => $googleUser->getId(),
            'email' => $email,
            'first_name' => $nameParts['first'],
            'last_name' => $nameParts['last'],
        ]);

        return redirect()
            ->route('investor.signup')
            ->with('info', 'Google account connected. Complete the investor application below.');
    }

    private function linkGoogleAccount(User $user, \Laravel\Socialite\Contracts\User $googleUser): void
    {
        $updates = [];

        if ($user->google_id !== $googleUser->getId()) {
            $updates['google_id'] = $googleUser->getId();
        }

        if (! $user->hasVerifiedEmail()) {
            $updates['email_verified_at'] = now();
            $updates['email_verified_by'] = null;
        }

        if ($updates !== []) {
            $user->update($updates);
        }
    }

    /**
     * @return array{first: string, last: string}
     */
    private function splitName(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['first' => '', 'last' => ''];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        $first = array_shift($parts) ?? '';
        $last = trim(implode(' ', $parts));

        return [
            'first' => $first,
            'last' => $last !== '' ? $last : $first,
        ];
    }

    private function failureRedirect(string $intent, string $message): RedirectResponse
    {
        if ($intent === 'signup') {
            return redirect()->route('investor.signup')->withErrors(['google' => $message]);
        }

        return redirect()->route('login')->withErrors(['google' => $message]);
    }

    private function mobileFailureRedirect(string $returnTo, string $message): \Illuminate\Http\Response
    {
        $sep = str_contains($returnTo, '?') ? '&' : '?';
        $target = $returnTo.$sep.http_build_query([
            'error' => $message,
        ]);

        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Redirecting...</title><meta http-equiv="refresh" content="0;url='.e($target).'"><script>window.location.replace('.json_encode($target).');</script></head><body><p>Redirecting to app...</p><a href="'.e($target).'">Return to App</a></body></html>',
            200,
            [
                'Content-Type' => 'text/html',
                'Location' => $target,
            ]
        );
    }

    /** Use the current site URL so Google redirect_uri matches how the user opened the app. */
    private function configureGoogleRedirect(Request $request): void
    {
        config([
            'services.google.redirect' => $request->getSchemeAndHttpHost().'/auth/google/callback',
        ]);
    }

    /**
     * Safely issue an API token, auto-creating the personal_access_tokens table if not yet migrated.
     */
    protected function createTokenSafely(User $user, string $deviceName): string
    {
        $this->ensurePersonalAccessTokensTableExists();

        try {
            return $user->createToken($deviceName)->plainTextToken;
        } catch (\Throwable $e) {
            Log::warning('Sanctum createToken initial attempt failed: '.$e->getMessage());

            $this->ensurePersonalAccessTokensTableExists(true);
            try {
                return $user->createToken($deviceName)->plainTextToken;
            } catch (\Throwable $e2) {
                Log::error('Sanctum createToken retry failed: '.$e2->getMessage());
                return 'tok_'.bin2hex(random_bytes(24));
            }
        }
    }

    /**
     * Auto-create personal_access_tokens table if it does not exist in database.
     */
    protected function ensurePersonalAccessTokensTableExists(bool $force = false): void
    {
        try {
            if ($force || ! Schema::hasTable('personal_access_tokens')) {
                Schema::create('personal_access_tokens', function (Blueprint $table) {
                    $table->id();
                    $table->morphs('tokenable');
                    $table->text('name');
                    $table->string('token', 64)->unique();
                    $table->text('abilities')->nullable();
                    $table->timestamp('last_used_at')->nullable();
                    $table->timestamp('expires_at')->nullable()->index();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('ensurePersonalAccessTokensTableExists notice: '.$e->getMessage());
        }
    }

    /**
     * Standardized representation of a User model.
     */
    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'primary_country' => $user->primary_country,
            'country' => $user->country,
            'address' => $user->address,
            'company_name' => $user->company_name,
            'designation' => $user->designation,
            'organization' => $user->organization,
            'role' => $user->role,
            'is_admin' => rescue(fn () => $user->isAdmin(), $user->role === 'admin' || $user->role === 'superadmin'),
            'is_investor' => rescue(fn () => $user->isInvestor(), $user->role === 'investor'),
            'is_approved' => (bool) $user->is_approved,
            'is_email_verified' => rescue(fn () => $user->hasVerifiedEmail(), false),
            'account_status' => $user->account_status,
            'status' => $user->status ?? 'active',
            'is_active' => rescue(fn () => $user->isActive(), true),
            'tier_name' => rescue(fn () => $user->tierName(), 'Free Tier'),
            'payment_status' => $user->payment_status,
            'investment_expertise' => $user->investment_expertise,
            'expertise_level' => $user->expertise_level,
            'preference_sector' => $user->preference_sector,
            'secondary_contact' => $user->secondary_contact,
            'linkedin' => $user->linkedin,
            'profile_photo_url' => rescue(fn () => $user->getProfilePhotoUrl(), null),
            'can_view_paywalled_deals' => rescue(fn () => $user->canViewPaywalledDeals(), false),
            'has_paid_membership' => rescue(fn () => $user->hasPaidMembership(), false),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
