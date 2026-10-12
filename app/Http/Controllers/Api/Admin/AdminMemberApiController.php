<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\AccountApproved;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AdminMemberApiController extends BaseApiController
{
    /**
     * Paginated list of members with filters and search.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status'); // 'all', 'active', 'inactive', 'pending', 'pending_approval', 'featured'
        $role = $request->query('role');
        $accountStatus = $request->query('account_status');

        $query = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_approved', true)->where(function ($inner) {
                $inner->where('status', 'active')->orWhereNull('status');
            })->where('account_status', '!=', 'disabled'))
            ->when($status === 'inactive', fn ($q) => $q->where(function ($inner) {
                $inner->where('status', 'inactive')->orWhere('account_status', 'disabled');
            }))
            ->when($status === 'pending_approval' || $status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($status === 'featured', fn ($q) => $q->where('featured', true)->where('is_approved', true))
            ->when($status === 'all', fn ($q) => $q->where('is_approved', true))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($accountStatus, fn ($q) => $q->where('account_status', $accountStatus));

        $members = $query->latest()->paginate((int) $request->query('per_page', 25))
            ->through(fn (User $user) => $this->formatUser($user));

        $counts = [
            'all' => User::where('is_approved', true)->count(),
            'active' => User::where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })->where('is_approved', true)->where('account_status', '!=', 'disabled')->count(),
            'inactive' => User::where(function ($q) {
                $q->where('status', 'inactive')->orWhere('account_status', 'disabled');
            })->where('is_approved', true)->count(),
            'pending' => User::where('is_approved', false)->count(),
            'featured' => User::where('featured', true)->where('is_approved', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $members,
            'counts' => $counts,
        ]);
    }

    /**
     * Single member details.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['subscriptions', 'investments.deal', 'commits.deal']);

        $data = $this->formatUser($user);
        $data['subscriptions'] = $user->subscriptions;
        $data['investments'] = $user->investments;
        $data['commits'] = $user->commits;

        return $this->success($data);
    }

    /**
     * Create a new member.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required_without:full_name', 'nullable', 'string', 'max:255'],
            'full_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'role' => ['nullable', 'in:investor,admin,superadmin,disabled'],
            'account_status' => ['nullable', 'in:free,core,advanced,institutional,disabled'],
            'status' => ['nullable', 'in:active,inactive'],
            'is_approved' => ['nullable', 'boolean'],
            'verify_email' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'preference_sector' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $name = trim((string) ($validated['name'] ?? $validated['full_name'] ?? ''));
        $company = $validated['company_name'] ?? $validated['organization'] ?? null;
        $primaryCountry = $validated['primary_country'] ?? $validated['country'] ?? 'Bangladesh';
        $role = $validated['role'] ?? 'investor';
        $accountStatus = $validated['account_status'] ?? 'free';
        $gender = $validated['gender'] ?? 'male';
        $status = $validated['status'] ?? 'active';

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'company_name' => $company,
            'designation' => $validated['designation'] ?? null,
            'gender' => $gender,
            'role' => $role,
            'account_status' => $accountStatus,
            'payment_status' => $accountStatus === 'free' ? 'free' : 'paid',
            'status' => $status,
            'preference_sector' => $validated['preference_sector'] ?? null,
            'is_approved' => $request->boolean('is_approved', true),
            'password' => Hash::make($validated['password']),
            'primary_country' => $primaryCountry,
            'country' => $primaryCountry,
            'linkedin' => $validated['linkedin'] ?? null,
            'investment_expertise' => $validated['investment_expertise'] ?? 'beginner',
            'email_verified_at' => $request->boolean('verify_email', true) ? now() : null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($request->hasFile('profile_photo')) {
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
        }

        return $this->success($this->formatUser($user), 'Member created successfully.', 201);
    }

    /**
     * Update an existing member.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'role' => ['nullable', 'in:investor,admin,superadmin,disabled'],
            'account_status' => ['nullable', 'in:free,core,advanced,institutional,disabled'],
            'status' => ['nullable', 'in:active,inactive'],
            'is_approved' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $company = $validated['company_name'] ?? $validated['organization'] ?? $user->company_name;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
            'company_name' => $company,
            'designation' => $validated['designation'] ?? $user->designation,
            'gender' => $validated['gender'],
            'role' => $validated['role'] ?? $user->role,
            'account_status' => $validated['account_status'] ?? $user->account_status,
            'status' => $validated['status'] ?? $user->status,
            'primary_country' => $validated['primary_country'] ?? $user->primary_country,
            'linkedin' => $validated['linkedin'] ?? $user->linkedin,
            'investment_expertise' => $validated['investment_expertise'] ?? $user->investment_expertise,
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->has('is_approved')) {
            $user->is_approved = $request->boolean('is_approved');
        }

        $user->save();

        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMediaFromRequest('profile_photo')->toMediaCollection('profile_photo');
        }

        return $this->success($this->formatUser($user->fresh()), 'Member updated successfully.');
    }

    /**
     * Approve a pending member.
     */
    public function approve(Request $request, User $user): JsonResponse
    {
        $user->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        try {
            Mail::to($user->email)->send(new AccountApproved($user));
        } catch (\Throwable $e) {
            // Log and continue
        }

        return $this->success($this->formatUser($user->fresh()), 'Member approved successfully.');
    }

    /**
     * Manually verify a member's email address.
     */
    public function verifyEmail(Request $request, User $user): JsonResponse
    {
        $user->update([
            'email_verified_at' => now(),
            'email_verified_by' => $request->user()->id,
        ]);

        return $this->success($this->formatUser($user->fresh()), 'Member email marked as verified.');
    }

    /**
     * Update account status (tier: free, core, advanced, institutional, disabled) and/or member mode (active, inactive).
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'account_status' => ['nullable', 'in:free,core,advanced,institutional,disabled'],
            'status' => ['nullable', 'in:active,inactive'],
            'mode' => ['nullable', 'in:active,inactive'],
        ]);

        $updates = [];
        if (! empty($validated['account_status'])) {
            $updates['account_status'] = $validated['account_status'];
        }

        $mode = $validated['status'] ?? $validated['mode'] ?? null;
        if (! empty($mode)) {
            $updates['status'] = $mode;
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        return $this->success($this->formatUser($user->fresh()), 'Account status updated.');
    }

    /**
     * Update member active/inactive mode.
     */
    public function updateMode(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user->update([
            'status' => $validated['status'],
        ]);

        return $this->success($this->formatUser($user->fresh()), 'Member mode updated successfully.');
    }

    /**
     * Delete a member.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->success(null, 'Member removed successfully.');
    }
}
