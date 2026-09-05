<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginConfiguration;
use App\Actions\Auth\StashConnectionIntent;
use App\Actions\Auth\StashLoginIntent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRedirectRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'github'];

    public function __construct(
        protected LoginConfiguration $loginConfiguration,
        protected StashLoginIntent $stashLoginIntent,
        protected StashConnectionIntent $stashConnectionIntent,
    ) {}

    public function redirect(string $provider, SocialLoginRedirectRequest $request): SymfonyRedirectResponse
    {
        $this->abortUnlessAvailable($provider);

        $this->stashLoginIntent->stashForRedirect($request->session(), $request->returnUrl());

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request): RedirectResponse
    {
        $this->abortUnlessAvailable($provider);

        $connectingUserId = $this->stashConnectionIntent->pull($request->session());

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            if ($connectingUserId !== null) {
                Inertia::flash('toast', ['type' => 'error', 'message' => __('Connecting :provider was cancelled or failed.', ['provider' => ucfirst($provider)])]);

                return to_route('profile.edit');
            }

            $this->stashLoginIntent->pullReturnUrl($request->session());

            return to_route('login')->with('status', __('Sign-in was cancelled or failed. Please try again.'));
        }

        if ($connectingUserId !== null) {
            return $this->linkProvider($provider, $socialiteUser, $connectingUserId);
        }

        $user = $this->findOrCreateUser($provider, $socialiteUser);

        Auth::login($user, remember: true);

        $this->stashLoginIntent->pullReturnUrl($request->session());
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Attach a provider identity to an already-authenticated user, reached
     * via SocialConnectionController::redirect() from the settings page.
     */
    private function linkProvider(string $provider, SocialiteUser $socialiteUser, int $userId): RedirectResponse
    {
        $column = "{$provider}_id";
        $providerId = (string) $socialiteUser->getId();

        $alreadyLinkedElsewhere = User::query()
            ->where($column, $providerId)
            ->where('id', '!=', $userId)
            ->exists();

        if ($alreadyLinkedElsewhere) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This :provider account is already linked to a different user.', ['provider' => ucfirst($provider)])]);

            return to_route('profile.edit');
        }

        $user = User::findOrFail($userId);
        $user->forceFill([$column => $providerId])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':provider connected.', ['provider' => ucfirst($provider)])]);

        return to_route('profile.edit');
    }

    private function abortUnlessAvailable(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        abort_unless($this->loginConfiguration->providerEnabled($provider), 404);
    }

    private function findOrCreateUser(string $provider, SocialiteUser $socialiteUser): User
    {
        $column = "{$provider}_id";
        $providerId = (string) $socialiteUser->getId();
        $email = Str::lower(trim((string) $socialiteUser->getEmail()));

        $user = User::query()->where($column, $providerId)->first()
            ?? User::query()->where('email', $email)->first();

        if (! $user) {
            // email isn't mass-assignable (settings must never accept it
            // that way), so a first-time sign-in creates the row unguarded.
            $user = User::forceCreate([
                'name' => $this->displayName($socialiteUser, $email),
                'email' => $email,
            ]);
        }

        $user->forceFill([
            $column => $providerId,
            'avatar' => $socialiteUser->getAvatar() ?: $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function displayName(SocialiteUser $socialiteUser, string $email): string
    {
        $name = trim((string) $socialiteUser->getName());

        if ($name !== '') {
            return $name;
        }

        $fallback = (string) Str::of(Str::before($email, '@'))
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title();

        return $fallback !== '' ? $fallback : 'New User';
    }
}
