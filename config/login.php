<?php

declare(strict_types=1);

use App\Actions\Auth\SendEmailLoginOtp;

return [

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | Where the pending "enter your code" challenge lives in the session
    | between requesting a code and verifying it.
    |
    */

    'session_key' => 'auth.login_challenge',

    /*
    |--------------------------------------------------------------------------
    | One-Time Code Settings
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => (int) env('AUTH_LOGIN_OTP_LENGTH', 6),
        'expires_in_minutes' => (int) env('AUTH_LOGIN_OTP_EXPIRES_IN_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('AUTH_LOGIN_OTP_RESEND_COOLDOWN_SECONDS', 30),

        // Local/CI convenience: the plaintext code is written to the log (and,
        // in local/testing only, cached by destination email) so it can be
        // read without a real mailbox. Never enabled in production.
        //
        // Config files load before the app's "env" container binding exists
        // (see LoadConfiguration), so this checks APP_ENV directly rather
        // than calling app()->environment() here.
        'log_codes' => filter_var(
            env('AUTH_LOGIN_OTP_LOG_ENABLED', env('APP_ENV', 'production') === 'local'),
            FILTER_VALIDATE_BOOL,
        ),
        'log_channel' => env('AUTH_LOGIN_OTP_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Providers
    |--------------------------------------------------------------------------
    |
    | Each is optional and only shown on the login form once its client id is
    | configured (or the app explicitly enables it).
    |
    */

    'providers' => [
        'google' => [
            'enabled' => filter_var(
                env('AUTH_LOGIN_GOOGLE_ENABLED', (bool) env('GOOGLE_CLIENT_ID')),
                FILTER_VALIDATE_BOOL,
            ),
            'label' => 'Google',
        ],
        'github' => [
            'enabled' => filter_var(
                env('AUTH_LOGIN_GITHUB_ENABLED', (bool) env('GITHUB_CLIENT_ID')),
                FILTER_VALIDATE_BOOL,
            ),
            'label' => 'GitHub',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | Email is the only OTP channel today. Kept as a config-driven list (like
    | the sender class below) so a second channel doesn't require touching
    | every action that currently hardcodes "email".
    |
    */

    'channels' => [
        'email' => [
            'field' => 'email',
            'label' => 'Email address',
            'sender' => SendEmailLoginOtp::class,
        ],
    ],

];
