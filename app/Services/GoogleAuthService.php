<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = (string) config('services.google.client_id', '');
        $this->clientSecret = (string) config('services.google.client_secret', '');
        $this->redirectUri = (string) config('services.google.redirect', url('/auth/google/callback'));
    }

    /**
     * Check if Google credentials are configured in .env.
     */
    public function isConfigured(): bool
    {
        return !empty(trim($this->clientId)) && !empty(trim($this->clientSecret));
    }

    /**
     * Generate the Google OAuth authorization URL.
     */
    public function getAuthUrl(): string
    {
        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Handle the callback authorization code and return the authenticated User model.
     *
     * @param string $code
     * @param string|null $state
     * @return User
     * @throws \Exception
     */
    public function handleCallback(string $code, ?string $state): User
    {
        $savedState = session('google_oauth_state');
        session()->forget('google_oauth_state');

        if (!$savedState || $savedState !== $state) {
            throw new \Exception('Invalid OAuth security state. Please try signing in with Google again.');
        }

        // Exchange authorization code for access token with Google
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if (!$tokenResponse->successful()) {
            $error = $tokenResponse->json('error_description') ?? $tokenResponse->json('error') ?? 'Failed to exchange authorization token with Google.';
            throw new \Exception($error);
        }

        $accessToken = $tokenResponse->json('access_token');
        if (!$accessToken) {
            throw new \Exception('Google did not return a valid access token.');
        }

        // Fetch user profile from Google UserInfo endpoint
        $userResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
        if (!$userResponse->successful()) {
            throw new \Exception('Failed to retrieve account details from Google.');
        }

        $googleData = $userResponse->json();
        $googleId = $googleData['sub'] ?? null;
        $email = $googleData['email'] ?? null;
        $name = $googleData['name'] ?? ($googleData['given_name'] ?? 'Google User');
        $avatar = $googleData['picture'] ?? null;

        if (!$email) {
            throw new \Exception('No email address provided by your Google account.');
        }

        // 1. Locate existing user by google_id or email
        $user = null;
        if (Schema::hasColumn('users', 'google_id') && $googleId) {
            $user = User::where('google_id', $googleId)->first();
        }

        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            // Update existing user with google_id and avatar if missing
            $updates = [];
            if (Schema::hasColumn('users', 'google_id') && empty($user->google_id) && $googleId) {
                $updates['google_id'] = $googleId;
            }
            if (Schema::hasColumn('users', 'avatar') && empty($user->avatar) && $avatar) {
                $updates['avatar'] = $avatar;
            }
            if (!$user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        } else {
            // Register new customer account via Google
            $userData = [
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'status' => 'active',
            ];

            if (Schema::hasColumn('users', 'google_id') && $googleId) {
                $userData['google_id'] = $googleId;
            }
            if (Schema::hasColumn('users', 'avatar') && $avatar) {
                $userData['avatar'] = $avatar;
            }

            $user = User::create($userData);
        }

        return $user;
    }
}
