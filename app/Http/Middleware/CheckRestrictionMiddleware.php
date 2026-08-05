<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Blocks restricted customers from placing orders for the duration
     * of their restriction period (e.g. 3-day abuse ban).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isRestricted()) {
            $until = $user->restricted_until->format('M d, Y h:i A');

            return redirect()->back()->with('error',
                "Your account is temporarily restricted until {$until} due to a policy violation. Please contact support if you believe this is an error."
            );
        }

        return $next($request);
    }
}
