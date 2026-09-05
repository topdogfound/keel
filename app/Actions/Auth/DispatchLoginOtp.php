<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DispatchLoginOtp
{
    /**
     * How long the plaintext code stays cached for the Playwright debug
     * endpoint, keyed by destination email. Never happens outside
     * local/testing — see OtpDebugController.
     */
    private const TESTING_CACHE_MINUTES = 5;

    public function __construct(
        private LoginConfiguration $configuration,
        private GenerateOtp $generateOtp,
    ) {}

    /**
     * @return array{
     *     expires_at: string,
     *     code_hash: string,
     *     masked_destination: string,
     *     resend_available_at: string,
     * }
     */
    public function handle(User $user): array
    {
        $otp = $this->generateOtp->handle();
        $destination = (string) $user->{$this->configuration->fieldForChannel('email')};
        $sender = app($this->configuration->senderForChannel('email'));

        $sender($user, $otp['code'], [
            'destination' => $destination,
            'expires_in_minutes' => $this->configuration->expiresInMinutes(),
        ]);

        if (app()->environment(['local', 'testing'])) {
            Cache::put("login-otp-debug:{$destination}", $otp['code'], now()->addMinutes(self::TESTING_CACHE_MINUTES));
        }

        return [
            'expires_at' => $otp['expires_at'],
            'code_hash' => $otp['code_hash'],
            'masked_destination' => $this->maskDestination($destination),
            'resend_available_at' => $otp['resend_available_at'],
        ];
    }

    private function maskDestination(string $destination): string
    {
        [$localPart, $domain] = explode('@', $destination, 2);

        return Str::substr($localPart, 0, 2).str_repeat('*', max(0, strlen($localPart) - 2)).'@'.$domain;
    }
}
