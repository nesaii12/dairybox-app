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

    /**
     * Step 1 of Customer Registration:
     * Validate registration details, store hashed 6-digit OTP + pending registration payload
     * in encrypted cache (5-minute TTL, max 5 attempts), and email the verification code.
     */
    public function sendRegistrationOtp(Request $request, OtpService $otpService, SmsOtpService $smsOtpService)
    {
        $normalizedEmail = $otpService->normalizeEmail((string) $request->input('email', ''));
        $isResendOnly = $request->boolean('resend') && !$request->filled('password');

        if ($isResendOnly) {
            if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid email address.',
                ], 422);
            }

            if (User::where('email', $normalizedEmail)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email address is already registered. Please log in instead.',
                ], 422);
            }

            $existing = $otpService->getPendingRegistrationRecord($normalizedEmail);
            if (!$existing || empty($existing['pending_user'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your registration session expired. Please submit the registration form again.',
                    'expired_session' => true,
                ], 422);
            }

            $pendingData = $existing['pending_user'];
        } else {
            $normalizedPhone = $smsOtpService->normalizePhone((string) $request->input('phone', ''));
            $request->merge([
                'email' => $normalizedEmail,
                'phone' => $normalizedPhone,
            ]);

            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users,email',
                'phone'    => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
                'password' => ['required', 'string', 'confirmed', Password::defaults()],
            ], [
                'email.unique' => 'This email address is already registered. Please log in or use a different email.',
                'phone.regex'  => 'Please enter a valid 11-digit Philippine mobile number starting with 09.',
                'phone.unique' => 'This phone number is already registered. Please use a different phone number.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pendingData = [
                'name' => trim((string) $request->input('name')),
                'email' => $normalizedEmail,
                'phone' => $normalizedPhone,
                'password_hash' => Hash::make((string) $request->input('password')),
            ];
        }

        $cooldown = $otpService->getPendingRegistrationCooldownSeconds($normalizedEmail);
        if ($cooldown > 0) {
            return response()->json([
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another verification code.",
                'cooldown' => $cooldown,
                'masked_email' => $otpService->maskEmail($normalizedEmail),
                'masked_phone' => $smsOtpService->maskPhone($pendingData['phone']),
                'email' => $normalizedEmail,
                'phone' => $pendingData['phone'],
            ], 429);
        }

        try {
            $result = $otpService->storeAndSendPendingRegistrationOtp($pendingData);

            $message = "We sent a 6-digit SMS verification code to {$result['masked_phone']}.";
            $fallbackNotice = null;
            if ($result['delivery_channel'] === 'sms_fallback_email') {
                $fallbackNotice = "SMS gateway API key is not configured in .env yet — a copy was also sent to {$result['masked_email']}.";
            } elseif ($result['delivery_channel'] === 'sms_log') {
                $fallbackNotice = 'SMS gateway API key is not configured in .env yet — check storage/logs/laravel.log for your verification code.';
            }

            return response()->json([
                'success' => true,
                'email' => $result['email'],
                'masked_email' => $result['masked_email'],
                'phone' => $result['phone'],
                'masked_phone' => $result['masked_phone'],
                'delivery_channel' => $result['delivery_channel'],
                'sms_provider' => $result['sms_provider'],
                'fallback_notice' => $fallbackNotice,
                'cooldown' => $result['cooldown'],
                'expires_in' => $result['expires_in'],
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Registration OTP SMS dispatch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to send SMS verification code. Please check your phone number and try again.',
            ], 500);
        }
    }

    /**
     * Step 2 of Customer Registration:
     * Verify submitted 6-digit OTP against encrypted hashed store, finalize User creation
     * with email_verified_at set, delete used OTP, log customer in, and redirect to Customer Home.
     */
    public function verifyRegistrationOtp(Request $request, OtpService $otpService)
    {
        $normalizedEmail = $otpService->normalizeEmail((string) $request->input('email', ''));
        $submittedOtp = (string) ($request->input('otp') ?? $request->input('code') ?? '');

        $validator = Validator::make([
            'email' => $normalizedEmail,
            'otp' => preg_replace('/\D+/', '', trim($submittedOtp)),
        ], [
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ], [
            'otp.digits' => 'Please enter the 6-digit numeric verification code.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = $otpService->verifyPendingRegistrationOtp($normalizedEmail, $submittedOtp);

        if (!$verification['valid']) {
            return response()->json([
                'success' => false,
                'reason' => $verification['reason'] ?? 'invalid',
                'attempts_remaining' => $verification['attempts_remaining'] ?? null,
                'message' => $verification['message'] ?? 'Invalid or expired OTP code. Please try again.',
                'errors' => [
                    'otp' => [$verification['message'] ?? 'Invalid or expired OTP code. Please try again.'],
                ],
            ], 422);
        }

        $pending = $verification['pending_user'];

        if (User::where('email', $pending['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This email address is already registered. Please log in instead.',
            ], 422);
        }

        if (User::where('phone', $pending['phone'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered. Please use a different phone number.',
            ], 422);
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password_hash'],
            'phone' => $pending['phone'],
            'role' => 'customer',
            'status' => 'active',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        session()->flash('success', 'Email verified and account created! Welcome to DairyBox.');

        return response()->json([
            'success' => true,
            'message' => 'Email verified and account created! Welcome to DairyBox.',
            'redirect' => route('home'),
        ]);
    }

    public function sendEmailOtp(Request $request, OtpService $otpService)
    {
        $rawEmail = (string) $request->input('email', '');
        $normalizedEmail = $otpService->normalizeEmail($rawEmail);

        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address (e.g., yourname@gmail.com).',
            ], 422);
        }

        if (User::where('email', $normalizedEmail)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This email address is already registered. Please log in or use a different email.',
            ], 422);
        }

        $cooldown = $otpService->getRegistrationEmailCooldownSeconds($normalizedEmail);
        if ($cooldown > 0) {
            return response()->json([
                'success' => false,
                'message' => "Please wait {$cooldown} seconds before requesting another verification code.",
                'cooldown' => $cooldown,
            ], 429);
        }

        try {
            $result = $otpService->generateAndSendForRegistrationEmail($normalizedEmail);

            return response()->json([
                'success' => true,
                'email' => $normalizedEmail,
                'cooldown' => $result['cooldown'],
                'message' => "A 6-digit verification code has been sent to {$normalizedEmail}. Please check your Gmail inbox or spam folder.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please check your email address and try again.',
            ], 500);
        }
    }

    public function verifyEmailOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $normalizedEmail = $otpService->normalizeEmail($request->email);

        if ($otpService->verifyRegistrationEmail($normalizedEmail, $request->code)) {
            return response()->json([
                'success' => true,
                'email' => $normalizedEmail,
                'message' => 'Email address verified successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired email verification code. Please check the code and try again.',
        ], 422);
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
            $fallbackEmail = $request->input('email');
            $result = $smsOtpService->generateAndSend($normalizedPhone, $fallbackEmail);

            $message = "A 6-digit SMS verification code has been sent to {$normalizedPhone}.";
            if ($result['sent_via'] === 'email_fallback') {
                $message = "Verification code sent to {$normalizedPhone} (and forwarded to {$fallbackEmail} while SMS gateway key is not configured in .env).";
            } elseif ($result['sent_via'] === 'log') {
                $message = "SMS gateway API key is not set in .env yet — check storage/logs/laravel.log for code or enter your Email Address above to receive the code.";
            }

            return response()->json([
                'success' => true,
                'phone' => $normalizedPhone,
                'cooldown' => $result['cooldown'],
                'sent_via' => $result['sent_via'],
                'message' => $message,
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
        $normalizedEmail = $otpService->normalizeEmail((string) $request->input('email', ''));
        $request->merge([
            'phone' => $normalizedPhone,
            'email' => $normalizedEmail,
        ]);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone'    => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
        ], [
            'phone.regex'  => 'Please enter a valid 11-digit Philippine mobile number starting with 09.',
            'phone.unique' => 'This phone number is already registered. Please use a different phone number.',
        ]);

        $isEmailVerified = $otpService->isRegistrationEmailVerified($normalizedEmail);

        if (!$isEmailVerified && $request->filled('email_otp')) {
            $isEmailVerified = $otpService->verifyRegistrationEmail($normalizedEmail, (string) $request->input('email_otp'));
        }

        $validator->after(function ($validator) use ($isEmailVerified) {
            if (!$isEmailVerified) {
                $validator->errors()->add('email', 'Please verify your email address with the 6-digit code sent to your Gmail before creating your account.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $normalizedEmail,
            'password' => Hash::make($request->password),
            'phone' => $normalizedPhone,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $otpService->clearRegistrationEmailVerified($normalizedEmail);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.catalog')->with('success', 'Email verified and account created successfully! Welcome to DairyBox.');
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
