<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public string $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("DairyBox Verification Code: {$this->otp}")
            ->greeting('Hello ' . ($notifiable->name ?? 'Valued Customer') . '!')
            ->line('Thank you for signing up with DairyBox! Please enter the 6-digit one-time password (OTP) below to verify your email and activate your account:')
            ->line("# {$this->otp}")
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this verification code, please disregard this email.')
            ->salutation('Best regards,' . "\n" . 'The DairyBox Team');
    }
}
