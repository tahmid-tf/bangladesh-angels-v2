<?php

namespace App\Http\Controllers\Api;

use App\Models\AngelAcademyNetworkApplication;
use App\Models\FounderPitchSubmission;
use App\Models\SubscriptionTier;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubmissionApiController extends BaseApiController
{
    /**
     * Submit a startup pitch deck (PDF required).
     */
    public function submitPitch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_email' => ['required', 'email', 'max:255'],
            'one_line' => ['required', 'string', 'max:280'],
            'pitch_deck' => ['required', 'file', 'mimes:pdf', 'max:12288'],
        ]);

        $submission = DB::transaction(function () use ($request, $validated) {
            $record = FounderPitchSubmission::create([
                'user_id' => $request->user()?->id,
                'contact_email' => $validated['contact_email'],
                'one_line' => $validated['one_line'],
            ]);

            $record->addMediaFromRequest('pitch_deck')
                ->toMediaCollection(FounderPitchSubmission::MEDIA_PITCH_DECK);

            return $record;
        });

        return $this->success([
            'id' => $submission->id,
            'contact_email' => $submission->contact_email,
            'one_line' => $submission->one_line,
        ], 'Pitch deck submitted successfully. Our investment committee will review your submission.', 201);
    }

    /**
     * Get options/choices for Angel Academy application form.
     */
    public function angelAcademyOptions(): JsonResponse
    {
        return $this->success([
            'invested_before' => AngelAcademyNetworkApplication::INVESTED_BEFORE,
            'primary_motivation' => AngelAcademyNetworkApplication::PRIMARY_MOTIVATION,
            'startup_stages' => AngelAcademyNetworkApplication::STARTUP_STAGES,
            'sectors' => AngelAcademyNetworkApplication::SECTORS,
            'cheque_size' => AngelAcademyNetworkApplication::CHEQUE_SIZE,
        ]);
    }

    /**
     * Submit an application for Angel Academy.
     */
    public function applyAngelAcademy(Request $request): JsonResponse
    {
        $investedKeys = array_keys(AngelAcademyNetworkApplication::INVESTED_BEFORE);
        $motivationKeys = array_keys(AngelAcademyNetworkApplication::PRIMARY_MOTIVATION);
        $stageKeys = array_keys(AngelAcademyNetworkApplication::STARTUP_STAGES);
        $sectorKeys = array_keys(AngelAcademyNetworkApplication::SECTORS);
        $chequeKeys = array_keys(AngelAcademyNetworkApplication::CHEQUE_SIZE);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'string', 'max:64'],
            'linkedin_url' => ['required', 'string', 'max:512'],
            'invested_before' => ['required', 'string', Rule::in($investedKeys)],
            'primary_motivation' => ['required', 'string', Rule::in($motivationKeys)],
            'startup_stages' => ['required', 'array', 'min:1'],
            'startup_stages.*' => ['string', Rule::in($stageKeys)],
            'sectors' => ['required', 'array', 'min:1'],
            'sectors.*' => ['string', Rule::in($sectorKeys)],
            'sectors_other' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn () => in_array('other', (array) $request->input('sectors', []), true)),
            ],
            'cheque_size' => ['required', 'string', Rule::in($chequeKeys)],
        ]);

        $application = AngelAcademyNetworkApplication::create([
            'user_id' => $request->user()?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'],
            'linkedin_url' => $validated['linkedin_url'],
            'invested_before' => $validated['invested_before'],
            'primary_motivation' => $validated['primary_motivation'],
            'startup_stages' => array_values(array_unique($validated['startup_stages'])),
            'sectors' => array_values(array_unique($validated['sectors'])),
            'sectors_other' => in_array('other', $validated['sectors'], true)
                ? ($validated['sectors_other'] ?? null)
                : null,
            'cheque_size' => $validated['cheque_size'],
        ]);

        return $this->success([
            'id' => $application->id,
            'name' => $application->name,
            'email' => $application->email,
        ], 'Angel Academy application submitted successfully.', 201);
    }

    /**
     * Submit an Investor membership application.
     */
    public function applyMember(Request $request): JsonResponse
    {
        if ($request->user()?->hasPaidMembership()) {
            return $this->error('Your membership plan is already active.', 400);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'investment_expertise' => ['nullable', 'string'],
            'gender' => ['required', 'in:male,female,other'],
            'organization' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'primary_country' => ['required', 'string', 'max:100'],
            'country_code' => ['required', 'string', 'max:100'],
            'preference_sector' => ['nullable', 'string', 'max:255'],
            'strategic_analyst' => ['nullable', 'in:TL,FS,TB'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'selected_plan' => ['required', 'string', 'max:64'],
            'selected_plan_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $selectedPlan = $validated['selected_plan'];
        $paidTier = null;

        if ($selectedPlan !== 'free') {
            $paidTier = SubscriptionTier::active()->where('slug', $selectedPlan)->first();

            if (! $paidTier) {
                return $this->error('The selected tier is no longer available.', 422, [
                    'selected_plan' => ['The selected tier is no longer available.'],
                ]);
            }
        }

        $user = User::create([
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['country_code'].$validated['phone'],
            'address' => $validated['address'],
            'gender' => $validated['gender'],
            'company_name' => $validated['organization'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'investment_expertise' => $validated['investment_expertise'] ?? 'beginner',
            'linkedin' => $validated['linkedin'] ?? null,
            'primary_country' => $validated['primary_country'],
            'preference_sector' => $validated['preference_sector'] ?? null,
            'account_status' => 'free',
            'payment_status' => 'free',
            'role' => 'investor',
            'is_approved' => false,
        ]);

        if ($request->hasFile('profile_photo')) {
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
        }

        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'requires_payment' => (bool) $paidTier,
            'selected_plan' => $paidTier ? [
                'slug' => $paidTier->slug,
                'name' => $paidTier->name,
                'price' => (float) $paidTier->price_yearly,
            ] : null,
        ], 'Membership application submitted successfully.', 201);
    }
}
