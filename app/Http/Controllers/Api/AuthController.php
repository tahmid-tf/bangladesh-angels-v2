<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

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

        $token = $user->createToken('auth-token')->plainTextToken;

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
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Login successful.');
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

        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
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
}
