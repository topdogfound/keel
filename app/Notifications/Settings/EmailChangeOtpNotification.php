<?php

declare(strict_types=1);

namespace App\Notifications\Settings;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeOtpNotification extends Notification
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
            ->subject(__('Confirm your new email address'))
            ->markdown('emails.otp-code', [
                'greeting' => __('Confirm this email address'),
                'intro' => __('Use this one-time code to confirm this is your new :app email address:', ['app' => config('app.name')]),
                'code' => $this->code,
                'expiry' => __('This code expires in :minutes minutes. If you did not request this change, you can safely ignore this email.', ['minutes' => $this->expiresInMinutes]),
            ]);
    }
}
