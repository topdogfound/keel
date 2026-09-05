<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Contracts\Session\Session;

class StashLoginIntent
{
    /**
     * Where a social-login redirect stashes the return URL so it survives the
     * off-site OAuth round trip.
     */
    public const SESSION_KEY = 'auth.login_intent';

    public function seedIntendedUrl(Session $session, string $returnUrl): void
    {
        $session->put('url.intended', $returnUrl);
    }

    public function stashForRedirect(Session $session, string $returnUrl): void
    {
        $session->put(self::SESSION_KEY, $returnUrl);
        $this->seedIntendedUrl($session, $returnUrl);
    }

    public function pullReturnUrl(Session $session): ?string
    {
        $returnUrl = $session->pull(self::SESSION_KEY);

        return is_string($returnUrl) ? $returnUrl : null;
    }
}
