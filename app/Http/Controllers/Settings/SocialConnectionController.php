<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Auth\LoginConfiguration;
use App\Actions\Auth\StashConnectionIntent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialConnectionController extends Controller
{
    private const PROVIDERS = ['google', 'github'];

    public function __construct(
        protected LoginConfiguration $loginConfiguration,
        protected StashConnectionIntent $stashConnectionIntent,
    ) {}

    /**
     * Send the current user through the provider's OAuth flow to link it to
     * their account. Lands back on the shared auth/{provider}/callback route
     * (see SocialLoginController::callback()), which recognises the stashed
     * intent and links instead of logging in.
     */
    public function redirect(string $provider, Request $request): SymfonyRedirectResponse
    {
        $this->abortUnlessAvailable($provider);

        $this->stashConnectionIntent->stashForUser($request->session(), $request->user()->id);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Unlink a provider from the current user. Always safe: every account
     * can also sign in via emailed one-time code, so this never locks anyone
     * out (see the User model's class docblock).
     */
    public function destroy(string $provider, Request $request): RedirectResponse
    {
        $this->abortUnlessAvailable($provider);

        $request->user()->forceFill(["{$provider}_id" => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':provider disconnected.', ['provider' => ucfirst($provider)])]);

        return to_route('profile.edit');
    }

    private function abortUnlessAvailable(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        abort_unless($this->loginConfiguration->providerEnabled($provider), 404);
    }
}
