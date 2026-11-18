<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates that the authenticated user has one of the allowed roles.
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,rider')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        $user = auth()->user();

        if (!in_array($user->role, $roles)) {
            if ($request->expectsJson()) {
                $targetRoute = match ($user->role) {
                    'admin' => route('admin.dashboard'),
                    'rider' => route('rider.dashboard'),
                    default => route('customer.catalog'),
                };
                return response()->json([
                    'message' => 'Unauthorized role.',
                    'redirect' => $targetRoute,
                ], 403);
            }

            // Redirect authenticated user to their role's respective dashboard
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'rider') {
                return redirect()->route('rider.dashboard');
            } else {
                return redirect()->route('customer.catalog');
            }
        }

        return $next($request);
    }
}
