<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display the customer account settings page.
     */
    public function index(): View
    {
        $user = auth()->user();
        $ordersCount = Order::where('customer_id', $user->id)->count();
        $latestOrder = Order::where('customer_id', $user->id)->latest()->first();

        return view('customer.settings.index', compact('user', 'ordersCount', 'latestOrder'));
    }

    /**
     * Update customer profile details.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'phone.unique' => 'This phone number is already registered. Please use a different phone number.',
        ]);

        $user->update($validated);

        return back()->with('success', 'Your profile details have been successfully updated.');
    }

    /**
     * Update customer account password.
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

        return back()->with('success', 'Your account password has been updated successfully.');
    }
}
