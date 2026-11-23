<?php

namespace Tests\Feature;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationOtpFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_25_000001_add_google_id_to_users_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_25_000002_add_verification_otp_to_users_table.php']);
    }

    public function test_register_page_renders_with_otp_modal(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('Create Account')
            ->assertSee('Verify &amp; Create Account', false)
            ->assertSee('/register/send-otp')
            ->assertSee('/register/verify-otp');
    }

    private function validRegistrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'password' => 'StrongPass!1',
            'password_confirmation' => 'StrongPass!1',
        ], $overrides);
    }

    public function test_send_otp_validates_fields_and_rejects_duplicate_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '09170000001',
            'role' => 'customer',
        ]);

        $response = $this->postJson(route('register.sendOtp'), $this->validRegistrationPayload([
            'email' => 'existing@example.com',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        Mail::assertNothingSent();
    }

    public function test_full_registration_otp_flow_sends_hashed_encrypted_otp_and_creates_verified_customer(): void
    {
        Mail::fake();

        $payload = $this->validRegistrationPayload();
        $sendResponse = $this->postJson(route('register.sendOtp'), $payload);

        $sendResponse->assertOk()
            ->assertJson([
                'success' => true,
                'email' => 'juan@example.com',
                'masked_email' => 'j***@example.com',
                'phone' => '09171234567',
                'masked_phone' => '0917***4567',
                'delivery_channel' => 'sms_fallback_email',
                'cooldown' => 60,
                'expires_in' => 300,
            ]);

        // User must NOT exist yet before OTP verification
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);

        $capturedOtp = null;
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;
            return $mail->hasTo('juan@example.com')
                && preg_match('/^\d{6}$/', $mail->otp) === 1
                && $mail->expiresMinutes === 5;
        });

        $this->assertNotNull($capturedOtp);

        // Verify stored OTP in encrypted cache is hashed (not plaintext)
        $otpService = app(OtpService::class);
        $record = $otpService->getPendingRegistrationRecord('juan@example.com');
        $this->assertNotNull($record);
        $this->assertNotSame($capturedOtp, $record['otp_code']);
        $this->assertTrue(Hash::check($capturedOtp, $record['otp_code']));

        // Verify OTP & finalize account creation
        $verifyResponse = $this->postJson(route('register.verifyOtp'), [
            'email' => 'juan@example.com',
            'otp' => $capturedOtp,
        ]);

        $verifyResponse->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => route('home'),
            ]);

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        // Used OTP record must be deleted
        $this->assertNull($otpService->getPendingRegistrationRecord('juan@example.com'));

        // Verify password was not double-hashed and normal login works
        $this->post(route('logout'));
        $this->assertGuest();

        $loginResponse = $this->post(route('login'), [
            'email' => 'juan@example.com',
            'password' => 'StrongPass!1',
        ]);
        $loginResponse->assertRedirect(route('customer.catalog'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_otp_increments_attempts_and_invalidates_after_five_tries(): void
    {
        Mail::fake();

        $this->postJson(route('register.sendOtp'), $this->validRegistrationPayload())->assertOk();

        $capturedOtp = null;
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;
            return true;
        });

        $wrongOtp = $capturedOtp === '000000' ? '111111' : '000000';

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $res = $this->postJson(route('register.verifyOtp'), [
                'email' => 'juan@example.com',
                'otp' => $wrongOtp,
            ]);
            $res->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired OTP code. Please try again.',
                    'attempts_remaining' => 5 - $attempt,
                ]);
        }

        // 5th failed attempt invalidates the OTP
        $fifthRes = $this->postJson(route('register.verifyOtp'), [
            'email' => 'juan@example.com',
            'otp' => $wrongOtp,
        ]);
        $fifthRes->assertStatus(422)
            ->assertJson([
                'success' => false,
                'reason' => 'max_attempts',
                'attempts_remaining' => 0,
            ]);

        // Even the originally valid OTP is now rejected
        $afterMaxRes = $this->postJson(route('register.verifyOtp'), [
            'email' => 'juan@example.com',
            'otp' => $capturedOtp,
        ]);
        $afterMaxRes->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_expired_otp_is_rejected_after_five_minutes(): void
    {
        Mail::fake();

        $this->postJson(route('register.sendOtp'), $this->validRegistrationPayload())->assertOk();

        $capturedOtp = null;
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$capturedOtp) {
            $capturedOtp = $mail->otp;
            return true;
        });

        $this->travel(6)->minutes();

        $res = $this->postJson(route('register.verifyOtp'), [
            'email' => 'juan@example.com',
            'otp' => $capturedOtp,
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP code. Please try again.',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_resend_enforces_60_second_cooldown_and_works_after_cooldown(): void
    {
        Mail::fake();

        $payload = $this->validRegistrationPayload();
        $this->postJson(route('register.sendOtp'), $payload)->assertOk();

        // Immediate resend blocked by 60s cooldown
        $blocked = $this->postJson(route('register.sendOtp'), array_merge($payload, ['resend' => true]));
        $blocked->assertStatus(429)
            ->assertJson(['success' => false]);

        $this->travel(61)->seconds();

        $resent = $this->postJson(route('register.sendOtp'), array_merge($payload, ['resend' => true]));
        $resent->assertOk()
            ->assertJson([
                'success' => true,
                'cooldown' => 60,
            ]);

        Mail::assertSentCount(2);
    }

    public function test_existing_admin_and_rider_logins_remain_intact(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('AdminPass!1'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $rider = User::factory()->create([
            'email' => 'rider@test.com',
            'password' => Hash::make('RiderPass!1'),
            'role' => 'rider',
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@test.com',
            'password' => 'AdminPass!1',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => 'rider@test.com',
            'password' => 'RiderPass!1',
        ])->assertRedirect(route('rider.dashboard'));
        $this->assertAuthenticatedAs($rider);
    }

    public function test_live_sms_gateway_dispatches_otp_to_phone_number_without_email_fallback(): void
    {
        Mail::fake();
        config(['services.sms.semaphore_api_key' => 'test-semaphore-key']);

        $capturedSmsCode = null;
        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => function ($request) use (&$capturedSmsCode) {
                $body = $request->data();
                if (isset($body['message']) && preg_match('/\b(\d{6})\b/', $body['message'], $m)) {
                    $capturedSmsCode = $m[1];
                }
                return Http::response([['message_id' => 12345]], 200);
            },
        ]);

        $sendResponse = $this->postJson(route('register.sendOtp'), $this->validRegistrationPayload([
            'email' => 'smsuser@example.com',
            'phone' => '09181234567',
        ]));

        $sendResponse->assertOk()
            ->assertJson([
                'success' => true,
                'phone' => '09181234567',
                'masked_phone' => '0918***4567',
                'delivery_channel' => 'sms',
                'sms_provider' => 'semaphore',
            ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.semaphore.co/api/v4/messages'
                && ($request->data()['number'] ?? null) === '09181234567';
        });
        Mail::assertNothingSent();
        $this->assertNotNull($capturedSmsCode);

        $verifyResponse = $this->postJson(route('register.verifyOtp'), [
            'email' => 'smsuser@example.com',
            'otp' => $capturedSmsCode,
        ]);

        $verifyResponse->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => route('home'),
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'smsuser@example.com',
            'phone' => '09181234567',
            'role' => 'customer',
        ]);
    }
}
