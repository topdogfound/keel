<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\DispatchEmailChangeOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StartEmailChangeRequest;
use App\Http\Requests\Settings\VerifyEmailChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmailController extends Controller
{
    private const SESSION_KEY = 'settings.email_change_challenge';

    public function __construct(private DispatchEmailChangeOtp $dispatchEmailChangeOtp) {}

    /**
     * Send a verification code to the new email address.
     */
    public function store(StartEmailChangeRequest $request): RedirectResponse
    {
        $newEmail = $request->newEmail();

        $request->session()->put(self::SESSION_KEY, [
            'new_email' => $newEmail,
            ...$this->dispatchEmailChangeOtp->handle($newEmail),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification code sent to :email.', ['email' => $newEmail])]);

        return to_route('profile.edit');
    }

    /**
     * Verify the code and apply the pending email change.
     */
    public function update(VerifyEmailChangeRequest $request): RedirectResponse
    {
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (! is_array($challenge) || ! isset($challenge['new_email'], $challenge['code_hash'], $challenge['expires_at'])) {
            throw ValidationException::withMessages([
                'email_code' => __('Request a new verification code to continue.'),
            ]);
        }

        if (Carbon::parse($challenge['expires_at'])->isPast()) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'email_code' => __('Your verification code expired. Request a new one to continue.'),
            ]);
        }

        if (! Hash::check($request->code(), (string) $challenge['code_hash'])) {
            throw ValidationException::withMessages([
                'email_code' => __('The verification code is invalid.'),
            ]);
        }

        $request->user()->forceFill([
            'email' => $challenge['new_email'],
            'email_verified_at' => now(),
        ])->save();

        $request->session()->forget(self::SESSION_KEY);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email address updated.')]);

        return to_route('profile.edit');
    }
}
