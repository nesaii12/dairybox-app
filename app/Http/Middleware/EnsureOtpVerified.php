<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'customer' && !$user->hasVerifiedEmail()) {
            session([
                'pending_otp_user_id' => $user->id,
                'pending_otp_email' => $user->email,
            ]);

            Auth::logout();

            return redirect()->route('otp.verify')->with('info', 'Please verify your email address to access your account.');
        }

        return $next($request);
    }
}
