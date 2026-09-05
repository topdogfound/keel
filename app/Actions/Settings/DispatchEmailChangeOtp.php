<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Auth\GenerateOtp;
use App\Actions\Auth\LoginConfiguration;
use App\Notifications\Settings\EmailChangeOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class DispatchEmailChangeOtp
{
    private const TESTING_CACHE_MINUTES = 5;

    public function __construct(
        private GenerateOtp $generateOtp,
        private LoginConfiguration $configuration,
    ) {}

    /**
     * Send a code to the *new*, not-yet-owned email address — there's no
     * User record at that address to call ->notify() on, so it's routed
     * ad-hoc.
     *
     * @return array{expires_at: string, code_hash: string, resend_available_at: string}
     */
    public function handle(string $newEmail): array
    {
        $otp = $this->generateOtp->handle();

        Notification::route('mail', $newEmail)->notify(
            new EmailChangeOtpNotification($otp['code'], $this->configuration->expiresInMinutes()),
        );

        if (app()->environment(['local', 'testing'])) {
            Cache::put("email-change-otp-debug:{$newEmail}", $otp['code'], now()->addMinutes(self::TESTING_CACHE_MINUTES));
        }

        return [
            'expires_at' => $otp['expires_at'],
            'code_hash' => $otp['code_hash'],
            'resend_available_at' => $otp['resend_available_at'],
        ];
    }
}
