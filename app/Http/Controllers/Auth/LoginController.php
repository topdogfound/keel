<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\DispatchLoginOtp;
use App\Actions\Auth\LoginChallengeView;
use App\Actions\Auth\LoginConfiguration;
use App\Actions\Auth\StashLoginIntent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StartLoginOtpRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        protected LoginConfiguration $loginConfiguration,
        protected DispatchLoginOtp $dispatchLoginOtp,
        protected LoginChallengeView $loginChallengeView,
        protected StashLoginIntent $stashLoginIntent,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'challenge' => $this->loginChallengeView->handle(
                $request->session()->get($this->loginConfiguration->sessionKey()),
            ),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StartLoginOtpRequest $request): RedirectResponse
    {
        $email = $request->email();
        // email isn't mass-assignable (settings must never accept it that
        // way), so a first-time sign-in creates the row unguarded instead.
        $user = User::query()->where('email', $email)->first()
            ?? User::forceCreate(['email' => $email, 'name' => $this->nameFromEmail($email)]);

        $returnUrl = $request->returnUrl();
        $request->session()->put($this->loginConfiguration->sessionKey(), [
            'user_id' => $user->id,
            'remember' => $request->boolean('remember'),
            'return_url' => $returnUrl,
            ...$this->dispatchLoginOtp->handle($user),
        ]);

        if ($request->boolean('modal')) {
            $this->stashLoginIntent->seedIntendedUrl($request->session(), $returnUrl);

            return redirect($returnUrl)->with(['status' => __('Verification code sent.'), 'openLoginModal' => true]);
        }

        return to_route('login')->with('status', __('Verification code sent.'));
    }

    public function verify(VerifyLoginOtpRequest $request): RedirectResponse
    {
        $challenge = $this->currentChallenge($request);

        if (! $challenge) {
            throw ValidationException::withMessages([
                'email_code' => __('Request a new verification code to continue.'),
            ]);
        }

        if ($this->challengeHasExpired($challenge)) {
            $this->forgetChallenge($request);

            throw ValidationException::withMessages([
                'email_code' => __('Your verification code expired. Request a new one to continue.'),
            ]);
        }

        $user = $this->userForChallenge($challenge);

        if (! $user) {
            $this->forgetChallenge($request);

            throw ValidationException::withMessages([
                'email_code' => __('Request a new verification code to continue.'),
            ]);
        }

        if (! Hash::check($request->code(), (string) ($challenge['code_hash'] ?? ''))) {
            throw ValidationException::withMessages([
                'email_code' => __('The verification code is invalid.'),
            ]);
        }

        $this->markEmailAsVerified($user);

        Auth::login($user, (bool) ($challenge['remember'] ?? false));

        $this->forgetChallenge($request);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $challenge = $this->currentChallenge($request);

        if (! $challenge) {
            return to_route('login');
        }

        $user = $this->userForChallenge($challenge);

        if (! $user || blank($user->email)) {
            $this->forgetChallenge($request);

            return to_route('login');
        }

        $resendAvailableAt = isset($challenge['resend_available_at'])
            ? Carbon::parse($challenge['resend_available_at'])
            : null;

        if ($resendAvailableAt?->isFuture()) {
            $secondsRemaining = (int) ceil(now()->diffInSeconds($resendAvailableAt, absolute: true));

            throw ValidationException::withMessages([
                'resend' => __('Please wait :seconds seconds before requesting a new code.', ['seconds' => $secondsRemaining]),
            ]);
        }

        $request->session()->put($this->loginConfiguration->sessionKey(), [
            'user_id' => $user->id,
            'remember' => (bool) ($challenge['remember'] ?? false),
            'return_url' => $challenge['return_url'] ?? route('home', absolute: false),
            ...$this->dispatchLoginOtp->handle($user),
        ]);

        if ($request->boolean('modal')) {
            return redirect($challenge['return_url'] ?? route('home', absolute: false))
                ->with(['status' => __('Verification code sent again.'), 'openLoginModal' => true]);
        }

        return to_route('login')->with('status', __('Verification code sent again.'));
    }

    public function destroyChallenge(Request $request): RedirectResponse
    {
        $challenge = $this->currentChallenge($request);
        $this->forgetChallenge($request);

        if ($request->boolean('modal')) {
            return redirect($challenge['return_url'] ?? route('home', absolute: false))
                ->with('openLoginModal', true);
        }

        return to_route('login');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentChallenge(Request $request): ?array
    {
        $challenge = $request->session()->get($this->loginConfiguration->sessionKey());

        return is_array($challenge) ? $challenge : null;
    }

    /** @param array<string, mixed> $challenge */
    private function userForChallenge(array $challenge): ?User
    {
        $userId = $challenge['user_id'] ?? null;

        if (! is_int($userId) && ! (is_string($userId) && ctype_digit($userId))) {
            return null;
        }

        return User::query()->find((int) $userId);
    }

    /** @param array<string, mixed> $challenge */
    private function challengeHasExpired(array $challenge): bool
    {
        return Carbon::parse($challenge['expires_at'])->isPast();
    }

    private function forgetChallenge(Request $request): void
    {
        $request->session()->forget($this->loginConfiguration->sessionKey());
    }

    private function nameFromEmail(string $email): string
    {
        $name = (string) Str::of(Str::before($email, '@'))
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title();

        return $name !== '' ? $name : 'New User';
    }

    private function markEmailAsVerified(User $user): void
    {
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
