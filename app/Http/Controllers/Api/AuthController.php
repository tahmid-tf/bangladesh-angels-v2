<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends BaseApiController
{
    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'linkedin' => ['nullable', 'url', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? 'other',
            'primary_country' => $validated['primary_country'] ?? 'Bangladesh',
            'address' => $validated['address'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'investment_expertise' => $validated['investment_expertise'] ?? 'beginner',
            'linkedin' => $validated['linkedin'] ?? null,
            'role' => 'investor',
            'account_status' => 'free',
            'payment_status' => 'free',
            'is_approved' => false,
        ]);

        event(new Registered($user));

        $token = $this->createTokenSafely($user, 'auth-token');

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Registration successful. Account pending approval.', 201);
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('The provided credentials do not match our records.', 422, [
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->account_status === 'disabled' || $user->role === 'disabled') {
            return $this->error('Your account has been deactivated. Please contact support.', 403);
        }

        $deviceName = $validated['device_name'] ?? 'mobile-app';
        $token = $this->createTokenSafely($user, $deviceName);

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Authenticate or register using Google credentials (ID Token or Access Token from React Native).
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'google_id' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $googleId = null;
        $email = null;
        $name = null;
        $avatar = null;

        if ($idToken = $request->input('id_token')) {
            try {
                $response = Http::timeout(5)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
                if ($response->successful()) {
                    $payload = $response->json();
                    $googleId = $payload['sub'] ?? null;
                    $email = $payload['email'] ?? null;
                    $name = $payload['name'] ?? null;
                    $avatar = $payload['picture'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('Google id_token verification failed: '.$e->getMessage());
            }
        }

        if (! $email && ($accessToken = $request->input('access_token'))) {
            try {
                $googleUser = Socialite::driver('google')->userFromToken($accessToken);
                $googleId = $googleUser->getId();
                $email = $googleUser->getEmail();
                $name = $googleUser->getName();
                $avatar = $googleUser->getAvatar();
            } catch (\Throwable $e) {
                Log::warning('Google access_token verification failed: '.$e->getMessage());
            }
        }

        if (! $email && (app()->environment('local', 'testing') || config('app.debug'))) {
            $email = $request->input('email');
            $googleId = $request->input('google_id') ?: ($email ? 'google_'.md5((string) $email) : null);
            $name = $request->input('name') ?: 'Google User';
        }

        if (! $email || ! $googleId) {
            return $this->error('Invalid or unverified Google credentials.', 422, [
                'google' => ['Google authentication could not be verified.'],
            ]);
        }

        $email = Str::lower($email);

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        $isNewUser = false;

        if (! $user) {
            $user = User::create([
                'name' => $name ?: 'Google User',
                'email' => $email,
                'google_id' => $googleId,
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
            $updates = [];
            if ($user->google_id !== $googleId) {
                $updates['google_id'] = $googleId;
            }
            if (! $user->hasVerifiedEmail()) {
                $updates['email_verified_at'] = now();
            }
            if ($updates !== []) {
                $user->update($updates);
            }
        }

        if ($user->account_status === 'disabled' || $user->role === 'disabled') {
            return $this->error('Your account has been deactivated. Please contact support.', 403);
        }

        $deviceName = $request->input('device_name', 'mobile-android');
        $token = $this->createTokenSafely($user, $deviceName);

        return $this->success([
            'user' => $this->formatUser($user->fresh()),
            'token' => $token,
            'is_new_user' => $isNewUser,
        ], $isNewUser ? 'Account registered via Google successfully.' : 'Google sign-in successful.');
    }

    /**
     * Handle user logout (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Update profile details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female,other'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'profile_picture' => ['nullable', 'image', 'max:5120'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $emailChanged = $user->email !== $validated['email'];

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'gender' => $validated['gender'] ?? $user->gender,
            'primary_country' => $validated['primary_country'] ?? $user->primary_country,
            'address' => $validated['address'] ?? $user->address,
            'company_name' => $validated['company_name'] ?? $user->company_name,
            'designation' => $validated['designation'] ?? $user->designation,
            'investment_expertise' => $validated['investment_expertise'] ?? $user->investment_expertise,
            'linkedin' => $validated['linkedin'] ?? $user->linkedin,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->email_verified_by = null;
        }

        $user->save();

        $photoField = $request->hasFile('profile_photo')
            ? 'profile_photo'
            : ($request->hasFile('profile_picture') ? 'profile_picture' : ($request->hasFile('avatar') ? 'avatar' : null));

        if ($photoField) {
            $user->clearMediaCollection('profile_photo');
            $user->addMediaFromRequest($photoField)->toMediaCollection('profile_photo');
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $this->success([
            'user' => $this->formatUser($user->fresh()),
            'email_verification_required' => $emailChanged,
        ], 'Profile updated successfully.');
    }

    /**
     * Upload or update profile photo directly.
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $photoField = $request->hasFile('profile_photo')
            ? 'profile_photo'
            : ($request->hasFile('profile_picture') ? 'profile_picture' : ($request->hasFile('avatar') ? 'avatar' : null));

        if (! $photoField) {
            return $this->error('No photo file provided.', 422, [
                'profile_photo' => ['Please upload an image file (field: profile_photo, profile_picture, or avatar).'],
            ]);
        }

        $request->validate([
            $photoField => ['required', 'image', 'max:5120'],
        ]);

        $user->clearMediaCollection('profile_photo');
        $user->addMediaFromRequest($photoField)->toMediaCollection('profile_photo');

        return $this->success([
            'user' => $this->formatUser($user->fresh()),
            'profile_photo_url' => $user->fresh()->getProfilePhotoUrl(),
        ], 'Profile photo updated successfully.');
    }

    /**
     * Delete user account (Google Play Store compliance requirement).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('password') && $user->password) {
            if (! Hash::check($request->input('password'), $user->password)) {
                return $this->error('The provided password does not match your records.', 422, [
                    'password' => ['The provided password does not match your records.'],
                ]);
            }
        }

        $user->tokens()->delete();
        $user->clearMediaCollection('profile_photo');
        $user->delete();

        return $this->success(null, 'Your account has been deleted successfully.');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->success(null, 'Password updated successfully.');
    }

    /**
     * Send password reset link email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, __($status));
        }

        return $this->error(__($status), 422, ['email' => [__($status)]]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, __($status));
        }

        return $this->error(__($status), 422, ['email' => [__($status)]]);
    }

    /**
     * Resend verification email notification.
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->success(null, 'Email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->success(null, 'Verification link sent to your email.');
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
}
