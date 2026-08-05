<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', session('per_page_customers', 10));
        session(['per_page_customers' => $perPage]);

        $query = User::where('role', 'customer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('orders')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        if ($customer->role !== 'customer') {
            abort(404);
        }

        // Get customer's orders history
        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get customer's ratings & reviews
        $reviews = $customer->ratings()->with('product')->get();

        return view('admin.customers.show', compact('customer', 'orders', 'reviews'));
    }

    public function restrict(Request $request, User $customer)
    {
        if ($customer->role !== 'customer') {
            abort(404);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $banDurationDays = 3;
        $until = now()->addDays($banDurationDays);

        $customer->update([
            'status' => 'restricted',
            'restricted_until' => $until,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'CUSTOMER_RESTRICTED',
            'details' => "Restricted customer {$customer->name} (#{$customer->id}) for {$banDurationDays} days. Reason: {$request->reason}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.customers.show', $customer->id)
            ->with('success', "Customer account has been suspended for {$banDurationDays} days (until " . $until->format('M d, Y h:i A') . ").");
    }
}
