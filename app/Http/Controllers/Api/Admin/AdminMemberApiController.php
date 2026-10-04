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
        $status = $request->query('status'); // 'active', 'inactive', 'pending_approval'
        $role = $request->query('role');
        $accountStatus = $request->query('account_status');

        $query = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_approved', true)->where('account_status', '!=', 'disabled'))
            ->when($status === 'inactive', fn ($q) => $q->where('account_status', 'disabled'))
            ->when($status === 'pending_approval', fn ($q) => $q->where('is_approved', false))
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($accountStatus, fn ($q) => $q->where('account_status', $accountStatus));

        $members = $query->latest()->paginate((int) $request->query('per_page', 25))
            ->through(fn (User $user) => $this->formatUser($user));

        return $this->success($members);
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'role' => ['required', 'in:investor,admin,superadmin,disabled'],
            'account_status' => ['required', 'in:free,core,advanced,institutional,disabled'],
            'is_approved' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'gender' => $validated['gender'],
            'role' => $validated['role'],
            'account_status' => $validated['account_status'],
            'payment_status' => $validated['account_status'] === 'free' ? 'free' : 'paid',
            'is_approved' => $request->boolean('is_approved', true),
            'password' => Hash::make($validated['password']),
            'primary_country' => $validated['primary_country'] ?? 'Bangladesh',
            'linkedin' => $validated['linkedin'] ?? null,
            'investment_expertise' => $validated['investment_expertise'] ?? 'beginner',
            'email_verified_at' => now(),
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
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'role' => ['required', 'in:investor,admin,superadmin,disabled'],
            'account_status' => ['required', 'in:free,core,advanced,institutional,disabled'],
            'is_approved' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'primary_country' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'investment_expertise' => ['nullable', 'in:beginner,intermediate,expert'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'address' => $validated['address'] ?? $user->address,
            'company_name' => $validated['company_name'] ?? $user->company_name,
            'designation' => $validated['designation'] ?? $user->designation,
            'gender' => $validated['gender'],
            'role' => $validated['role'],
            'account_status' => $validated['account_status'],
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
     * Update account status (e.g. core, advanced, institutional, free, disabled).
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'account_status' => ['required', 'in:free,core,advanced,institutional,disabled'],
        ]);

        $user->update([
            'account_status' => $validated['account_status'],
        ]);

        return $this->success($this->formatUser($user->fresh()), 'Account status updated.');
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
