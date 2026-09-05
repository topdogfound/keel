<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Carbon;

class LoginChallengeView
{
    /**
     * @param  mixed  $challenge  The raw session-stored challenge value, if any.
     * @return array{maskedDestination: string, resendAvailableAt: string}|null
     */
    public function handle(mixed $challenge): ?array
    {
        if (! is_array($challenge) || ! isset($challenge['expires_at'], $challenge['masked_destination'])) {
            return null;
        }

        if (Carbon::parse($challenge['expires_at'])->isPast()) {
            return null;
        }

        return [
            'maskedDestination' => (string) $challenge['masked_destination'],
            'resendAvailableAt' => (string) ($challenge['resend_available_at'] ?? now()->toIso8601String()),
        ];
    }
}
