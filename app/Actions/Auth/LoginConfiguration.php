<?php

declare(strict_types=1);

namespace App\Actions\Auth;

class LoginConfiguration
{
    /**
     * Providers the login form may show a button for, in display order.
     *
     * @return array<int, array{key: string, label: string, enabled: bool}>
     */
    public function socialProviders(): array
    {
        return [
            [
                'key' => 'google',
                'label' => (string) config('login.providers.google.label', 'Google'),
                'enabled' => $this->providerEnabled('google'),
            ],
            [
                'key' => 'github',
                'label' => (string) config('login.providers.github.label', 'GitHub'),
                'enabled' => $this->providerEnabled('github'),
            ],
        ];
    }

    public function providerEnabled(string $provider): bool
    {
        return (bool) config("login.providers.{$provider}.enabled", false);
    }

    public function fieldForChannel(string $channel): string
    {
        return (string) config("login.channels.{$channel}.field");
    }

    public function labelForChannel(string $channel): string
    {
        return (string) config("login.channels.{$channel}.label");
    }

    public function senderForChannel(string $channel): string
    {
        return (string) config("login.channels.{$channel}.sender");
    }

    public function otpLength(): int
    {
        return max(4, (int) config('login.otp.length', 6));
    }

    public function expiresInMinutes(): int
    {
        return max(1, (int) config('login.otp.expires_in_minutes', 10));
    }

    public function resendCooldownSeconds(): int
    {
        return max(0, (int) config('login.otp.resend_cooldown_seconds', 30));
    }

    public function sessionKey(): string
    {
        return (string) config('login.session_key', 'auth.login_challenge');
    }

    /**
     * Props shared with the frontend on every request.
     *
     * @return array{
     *     otpLength: int,
     *     socialProviders: array<int, array{key: string, label: string, enabled: bool}>,
     * }
     */
    public function inertia(): array
    {
        return [
            'otpLength' => $this->otpLength(),
            'socialProviders' => $this->socialProviders(),
        ];
    }
}
