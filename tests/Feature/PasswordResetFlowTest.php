<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $migrationFiles = glob(database_path('migrations/*.php'));
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $basename = basename($file);
            if ($basename === '2026_11_11_000001_create_rider_remittances_and_cash_ledger_tables.php') {
                if (!Schema::hasColumn('orders', 'remittance_id')) {
                    Schema::table('orders', function (Blueprint $table) {
                        $table->unsignedBigInteger('remittance_id')->nullable()->after('rider_id');
                    });
                }
                continue;
            }

            Artisan::call('migrate', ['--path' => 'database/migrations/' . $basename, '--force' => true]);
        }
    }

    public function test_forgot_password_page_renders_cleanly(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertSee('Forgot Password?');
        $response->assertSee('Send Reset Link');
    }

    public function test_forgot_password_sends_notification_on_valid_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'customer.test@dairybox.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'customer.test@dairybox.com',
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_catches_transport_exception_without_crashing_500(): void
    {
        // Mock notification failure simulating Gmail Bad Credentials 535 TransportException
        Notification::shouldReceive('sendNow')
            ->andThrow(new TransportException('535 5.7.8 Username and Password not accepted'));

        $user = User::factory()->create([
            'email' => 'toldsian.test@dairybox.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'toldsian.test@dairybox.com',
        ]);

        // Must not crash with 500 error!
        $response->assertStatus(302);
        $response->assertSessionHas('dev_reset_url');
        $response->assertSessionHas('status');
    }

    public function test_forgot_password_rejects_unregistered_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'resetuser@dairybox.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $token = PasswordBroker::broker()->createToken($user);

        // View reset form
        $viewResponse = $this->get(route('password.reset', [
            'token' => $token,
            'email' => 'resetuser@dairybox.com',
        ]));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Set New Password');

        // Submit new password
        $resetResponse = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'resetuser@dairybox.com',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }
}
