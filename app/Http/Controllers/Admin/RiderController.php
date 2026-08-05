<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RiderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', session('per_page_riders', 10));
        session(['per_page_riders' => $perPage]);

        $query = User::where('role', 'rider');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $riders = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.riders.index', compact('riders'));
    }

    public function create()
    {
        return view('admin.riders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
        ]);

        $rider = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'rider',
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'RIDER_CREATED',
            'details' => "Created rider account {$rider->name} (#{$rider->id})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.riders.index')
            ->with('success', "Rider account for '{$rider->name}' has been created successfully.");
    }

    public function update(Request $request, User $rider)
    {
        if ($rider->role !== 'rider') {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($rider->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'status' => 'required|in:active,restricted',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $rider->update($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'RIDER_UPDATED',
            'details' => "Updated rider account {$rider->name} (#{$rider->id})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.riders.index')
            ->with('success', "Rider account for '{$rider->name}' has been updated successfully.");
    }

    public function destroy(User $rider)
    {
        if ($rider->role !== 'rider') {
            abort(404);
        }

        $riderName = $rider->name;
        $riderId = $rider->id;
        $rider->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'RIDER_DELETED',
            'details' => "Deleted rider account {$riderName} (#{$riderId})",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.riders.index')
            ->with('success', "Rider account '{$riderName}' has been deleted successfully.");
    }
}
