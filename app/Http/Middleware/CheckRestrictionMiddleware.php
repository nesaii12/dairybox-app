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

            return redirect()->route('customer.cart.index')->with('error',
                "Your account is temporarily restricted from ordering until {$until} due to a cancelled order policy violation."
            );
        }

        return $next($request);
    }
}
