<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display the settings dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $settings = SettingService::all();
        $cancellationPolicy = SettingService::getReservationCancellationPolicy();
        $xenditConfigured = app(\App\Services\XenditService::class)->isConfigured();

        return view('admin.settings.index', compact('user', 'settings', 'cancellationPolicy', 'xenditConfigured'));
    }

    /**
     * Update the authenticated admin's profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Admin profile updated successfully.');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Update store and operational threshold preferences.
     */
    public function updateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:100'],
            'store_email' => ['required', 'email', 'max:100'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:1000'],
            'near_expiry_days' => ['required', 'integer', 'min:1', 'max:90'],
            'order_auto_cancel_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        SettingService::set($validated);

        return back()->with('success', 'Store and operational settings saved successfully.');
    }

    /**
     * Update database-persisted Reservation Cancellation Policy settings.
     */
    public function updateReservationPolicy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_fee_enabled' => ['nullable', 'boolean'],
            'cancellation_fee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'cancellation_policy_description' => ['nullable', 'string', 'max:1000'],
        ], [
            'cancellation_fee_percentage.required' => 'Please enter a cancellation fee percentage between 0 and 100.',
            'cancellation_fee_percentage.min' => 'Cancellation fee percentage cannot be less than 0%.',
            'cancellation_fee_percentage.max' => 'Cancellation fee percentage cannot exceed 100%.',
        ]);

        $enabled = $request->boolean('cancellation_fee_enabled');
        $feePercentage = (float) $validated['cancellation_fee_percentage'];
        $description = $validated['cancellation_policy_description'] ?? '';

        SettingService::updateReservationCancellationPolicy(
            $enabled,
            $feePercentage,
            $description,
            auth()->id(),
            $request->ip()
        );

        return back()->with('success', 'Reservation cancellation policy saved successfully.');
    }
}
