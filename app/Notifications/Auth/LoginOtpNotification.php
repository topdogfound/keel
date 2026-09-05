<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':app sign-in code', ['app' => config('app.name')]))
            ->markdown('emails.otp-code', [
                'greeting' => __('Your sign-in code'),
                'intro' => __('Use this one-time code to sign in to your :app account:', ['app' => config('app.name')]),
                'code' => $this->code,
                'expiry' => __('This code expires in :minutes minutes. If you did not request it, you can safely ignore this email.', ['minutes' => $this->expiresInMinutes]),
            ]);
    }
}
