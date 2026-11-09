<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use App\Services\SmsOtpService;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request, OtpService $otpService)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->status === 'restricted' && $user->isRestricted()) {
                Auth::logout();
                $until = $user->restricted_until ? $user->restricted_until->format('M d, Y h:i A') : 'further notice';
                return back()->withErrors([
                    'email' => "Your account is temporarily restricted until {$until} due to policy violations.",
                ])->onlyInput('email');
            }

            // Check if customer email is not verified yet
            if ($user->role === 'customer' && !$user->hasVerifiedEmail()) {
                Auth::logout();
                session([
                    'pending_otp_user_id' => $user->id,
                    'pending_otp_email' => $user->email,
                ]);

                if ($otpService->canResend($user)) {
                    $otpService->generateAndSend($user);
                }

                return redirect()->route('otp.verify')->with('info', 'Please enter your 6-digit verification code to activate your account.');
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.register');
    }

    public function sendPhoneOtp(Request $request, SmsOtpService $smsOtpService)
    {
        $rawPhone = (string) $request->input('phone', '');
        $normalizedPhone = $smsOtpService->normalizePhone($rawPhone);

        if (!$smsOtpService->isValidPhilippineMobile($normalizedPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 11-digit Philippine mobile number (e.g., 09171234567).',
            ], 422);
        }

        if (User::where('phone', $normalizedPhone)->orWhere('phone', $rawPhone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered. Please use a different phone number.',
            ], 422);
        }

        $cooldown = $smsOtpService->getRemainingCooldownSeconds($normalizedPhone);
        if ($cooldown > 0) {
            return response()->json([
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another SMS code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        try {
            $result = $smsOtpService->generateAndSend($normalizedPhone);

            return response()->json([
                'success' => true,
                'phone' => $normalizedPhone,
                'cooldown' => $result['cooldown'],
                'sent_via' => $result['sent_via'],
                'message' => "A 6-digit SMS verification code has been sent to {$normalizedPhone}.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to send SMS verification code. Please try again.',
            ], 500);
        }
    }

    public function verifyPhoneOtp(Request $request, SmsOtpService $smsOtpService)
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string|size:6',
        ]);

        $normalizedPhone = $smsOtpService->normalizePhone($request->phone);

        if ($smsOtpService->verify($normalizedPhone, $request->code, $request->session()->getId())) {
            return response()->json([
                'success' => true,
                'phone' => $normalizedPhone,
                'message' => 'Phone number verified successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired SMS verification code. Please check the code and try again.',
        ], 422);
    }

    public function register(Request $request, OtpService $otpService, SmsOtpService $smsOtpService)
    {
        $normalizedPhone = $smsOtpService->normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $normalizedPhone]);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone'    => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
        ], [
            'phone.regex'  => 'Please enter a valid 11-digit Philippine mobile number starting with 09.',
            'phone.unique' => 'This phone number is already registered. Please use a different phone number.',
        ]);

        $sessionId = $request->session()->getId();
        $isPhoneVerified = $smsOtpService->isVerifiedForSession($normalizedPhone, $sessionId);

        if (!$isPhoneVerified && $request->filled('phone_otp')) {
            $isPhoneVerified = $smsOtpService->verify($normalizedPhone, (string) $request->input('phone_otp'), $sessionId);
        }

        $validator->after(function ($validator) use ($isPhoneVerified) {
            if (!$isPhoneVerified) {
                $validator->errors()->add('phone', 'Please verify your phone number via SMS code before creating your account.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $normalizedPhone,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $smsOtpService->clearVerifiedForSession($normalizedPhone, $sessionId);

        // Generate and send 6-digit OTP code to the newly created user's email
        $otpService->generateAndSend($user);

        // Store pending user in session for OTP verification
        session([
            'pending_otp_user_id' => $user->id,
            'pending_otp_email' => $user->email,
        ]);

        return redirect()->route('otp.verify')->with('success', 'Verification code sent to ' . $user->email . '. Please enter the 6-digit code to activate your account.');
    }

    /**
     * Show the OTP verification form.
     */
    public function showOtpVerification(OtpService $otpService)
    {
        $userId = session('pending_otp_user_id') ?? Auth::id();
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in or register to verify your account.');
        }

        if ($user->hasVerifiedEmail()) {
            if (!Auth::check()) {
                Auth::login($user);
            }
            return $this->redirectBasedOnRole($user);
        }

        $remainingCooldown = $otpService->getRemainingCooldownSeconds($user);

        return view('auth.verify-otp', [
            'user' => $user,
            'email' => $user->email,
            'remainingCooldown' => $remainingCooldown,
        ]);
    }

    /**
     * Verify the entered 6-digit OTP code.
     */
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $userId = session('pending_otp_user_id') ?? Auth::id();
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        if ($otpService->verify($user, $request->otp)) {
            session()->forget(['pending_otp_user_id', 'pending_otp_email']);
            Auth::login($user);

            return redirect()->route('customer.catalog')->with('success', 'Email verified successfully! Welcome to DairyBox.');
        }

        return back()->withErrors([
            'otp' => 'The verification code you entered is invalid or has expired. Please try again.',
        ]);
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp(Request $request, OtpService $otpService)
    {
        $userId = session('pending_otp_user_id') ?? Auth::id();
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return redirect()->route('login')->with('error', 'Session expired. Please register or log in again.');
        }

        if (!$otpService->canResend($user)) {
            $seconds = $otpService->getRemainingCooldownSeconds($user);
            return back()->withErrors([
                'otp' => "Please wait {$seconds} seconds before requesting a new code.",
            ]);
        }

        $otpService->generateAndSend($user);

        return back()->with('success', 'A new 6-digit verification code has been sent to ' . $user->email . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    /**
     * Redirect user to Google OAuth endpoint.
     */
    public function redirectToGoogle(GoogleAuthService $googleAuth)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        if (!$googleAuth->isConfigured()) {
            return redirect()->route('login')->with('error', 'Google Sign-In is not configured yet. Please configure GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file.');
        }

        return redirect()->away($googleAuth->getAuthUrl());
    }

    /**
     * Handle the callback returned from Google OAuth.
     */
    public function handleGoogleCallback(Request $request, GoogleAuthService $googleAuth)
    {
        if ($request->has('error')) {
            $errorMsg = $request->input('error_description', 'Google sign-in was cancelled or encountered an error.');
            return redirect()->route('login')->with('error', $errorMsg);
        }

        $code = $request->input('code');
        $state = $request->input('state');

        if (!$code) {
            return redirect()->route('login')->with('error', 'Google authorization code was missing.');
        }

        try {
            $user = $googleAuth->handleCallback($code, $state);
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        // Check account restriction
        if ($user->status === 'restricted' && $user->isRestricted()) {
            $until = $user->restricted_until ? $user->restricted_until->format('M d, Y h:i A') : 'further notice';
            return redirect()->route('login')->withErrors([
                'email' => "Your account is temporarily restricted until {$until} due to policy violations.",
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.forgot-password');
    }

    /**
     * Send password reset link to user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => "We couldn't find an account registered with that email address.",
            ])->onlyInput('email');
        }

        $token = PasswordBroker::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        if (config('mail.default') === 'log' && app()->environment('local')) {
            session()->flash('dev_reset_url', route('password.reset', ['token' => $token, 'email' => $user->email]));
        }

        return back()->with('status', 'We have emailed your password reset link!');
    }

    /**
     * Show the password reset form with 60 seconds countdown.
     */
    public function showResetPassword(Request $request, $token = null)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        $email = $request->email;
        $remainingSeconds = 0;
        $isExpired = false;

        if ($email && $token) {
            $record = DB::table('password_reset_tokens')->where('email', $email)->first();
            if ($record && Hash::check($token, $record->token)) {
                $createdAt = Carbon::parse($record->created_at);
                $expiresAt = $createdAt->copy()->addSeconds(60);
                $diff = (int) now()->diffInSeconds($expiresAt, false);
                if ($diff > 0) {
                    $remainingSeconds = min(60, $diff);
                } else {
                    $isExpired = true;
                }
            } else {
                $isExpired = true;
            }
        } else {
            $isExpired = true;
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'remainingSeconds' => $remainingSeconds,
            'isExpired' => $isExpired,
        ]);
    }

    /**
     * Reset the user's password with strict 60 seconds validation.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        // Enforce strict 60 seconds expiration
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || !Hash::check($request->token, $record->token) || Carbon::parse($record->created_at)->addSeconds(60)->isPast()) {
            return back()->withErrors([
                'email' => 'This password reset link has expired (60-second limit). Please request a new link.'
            ])->onlyInput('email');
        }

        $status = PasswordBroker::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been successfully reset! You can now log in with your new password.');
        }

        return back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    protected function redirectBasedOnRole($user)
    {
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'rider') {
            return redirect()->route('rider.dashboard');
        } else {
            return redirect()->route('customer.catalog');
        }
    }
}
