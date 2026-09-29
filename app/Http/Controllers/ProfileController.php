<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $view = ($request->user()->isInvestor() || $request->user()->isAdmin())
            ? 'profile.investor-edit'
            : 'profile.edit';

        return view($view, [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Fill the user data with validated input
        $user->fill($request->validated());

        // If email is updated, reset email verification
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $user->email_verified_by = null;
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Remove old media in the 'profile_update' collection if it exists
            $user->clearMediaCollection('profile_photo');

            // Add the new profile picture to the media library
            $user->addMedia($request->file('profile_picture'))
                ->toMediaCollection('profile_photo');
        }

        // Save the user data
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
