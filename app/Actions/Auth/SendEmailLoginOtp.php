<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Notifications\Auth\LoginOtpNotification;
use Illuminate\Support\Facades\Log;

class SendEmailLoginOtp
{
    /**
     * @param  array{destination: string, expires_in_minutes: int}  $context
     */
    public function __invoke(User $user, string $code, array $context): void
    {
        $user->notify(new LoginOtpNotification(
            code: $code,
            expiresInMinutes: $context['expires_in_minutes'],
        ));

        if (! config('login.otp.log_codes', false)) {
            return;
        }

        Log::channel((string) config('login.otp.log_channel', config('logging.default')))
            ->debug('Email login OTP generated.', [
                'user_id' => $user->id,
                'email' => $context['destination'],
                'otp' => $code,
                'expires_in_minutes' => $context['expires_in_minutes'],
            ]);
    }
}
