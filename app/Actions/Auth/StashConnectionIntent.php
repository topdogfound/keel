<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Contracts\Session\Session;

/**
 * Distinguishes a settings-initiated "connect this provider to my account"
 * OAuth round trip from an ordinary login, since both share the same
 * `auth/{provider}/callback` route (see SocialLoginController::callback()).
 */
class StashConnectionIntent
{
    public const SESSION_KEY = 'auth.connection_intent';

    public function stashForUser(Session $session, int $userId): void
    {
        $session->put(self::SESSION_KEY, $userId);
    }

    public function pull(Session $session): ?int
    {
        $userId = $session->pull(self::SESSION_KEY);

        return is_int($userId) ? $userId : null;
    }
}
