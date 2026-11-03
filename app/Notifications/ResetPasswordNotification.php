<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Your DairyBox Password')
            ->greeting('Hello ' . ($notifiable->name ?? 'Valued Customer') . '!')
            ->line('You are receiving this email because we received a password reset request for your DairyBox account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 seconds.')
            ->line('If you did not request a password reset, no further action is required and your account remains safe.')
            ->salutation('Best regards,' . "\n" . 'The DairyBox Team');
    }
}
