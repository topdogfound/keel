<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Hash;

/**
 * The random-code-generation core shared by the login OTP flow
 * (DispatchLoginOtp) and the settings email-change flow
 * (App\Actions\Settings\DispatchEmailChangeOtp). Sending the code is the part
 * that differs between them — a login code goes to the user's own,
 * already-verified email via Notifiable; an email-change code goes to the new,
 * not-yet-owned address via an ad-hoc notification route — so only the
 * generation and hashing live here.
 */
class GenerateOtp
{
    public function __construct(private LoginConfiguration $configuration) {}

    /** @return array{code: string, code_hash: string, expires_at: string, resend_available_at: string} */
    public function handle(): array
    {
        $code = $this->generateCode();

        return [
            'code' => $code,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($this->configuration->expiresInMinutes())->toIso8601String(),
            'resend_available_at' => now()->addSeconds($this->configuration->resendCooldownSeconds())->toIso8601String(),
        ];
    }

    private function generateCode(): string
    {
        $code = '';

        for ($i = 0; $i < $this->configuration->otpLength(); $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }
}
