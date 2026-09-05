<?php

use App\Models\User;
use App\Notifications\Auth\LoginOtpNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

function requestLoginCode(string $email): string
{
    Notification::fake();

    test()->post(route('login.store'), ['email' => $email]);

    $code = null;
    Notification::assertSentTo(
        User::where('email', $email)->firstOrFail(),
        LoginOtpNotification::class,
        function (LoginOtpNotification $notification) use (&$code): true {
            $code = $notification->code;

            return true;
        },
    );

    return $code;
}

test('login screen can be rendered', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('requesting a code creates a new user and emails them a code', function (): void {
    $code = requestLoginCode('new-user@example.com');

    expect($code)->toMatch('/^\d{6}$/');
    expect(User::where('email', 'new-user@example.com')->exists())->toBeTrue();
});

test('an existing user can log in by verifying the emailed code', function (): void {
    $user = User::factory()->unverified()->create();

    $code = requestLoginCode($user->email);

    $response = $this->post(route('login.verify'), ['email_code' => $code]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home'));
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('an invalid code is rejected', function (): void {
    $user = User::factory()->create();

    requestLoginCode($user->email);

    $response = $this->post(route('login.verify'), ['email_code' => '000000']);

    $response->assertSessionHasErrors('email_code');
    $this->assertGuest();
});

test('verifying without a pending challenge is rejected', function (): void {
    $response = $this->post(route('login.verify'), ['email_code' => '123456']);

    $response->assertSessionHasErrors('email_code');
    $this->assertGuest();
});

test('an expired code is rejected', function (): void {
    $user = User::factory()->create();
    $code = requestLoginCode($user->email);

    $this->travel(11)->minutes();

    $response = $this->post(route('login.verify'), ['email_code' => $code]);

    $response->assertSessionHasErrors('email_code');
    $this->assertGuest();
});

test('resending is rate limited by the cooldown', function (): void {
    $user = User::factory()->create();
    requestLoginCode($user->email);

    $response = $this->post(route('login.resend'));

    $response->assertSessionHasErrors('resend');
});

test('users can logout', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('users can also logout by navigating to /logout directly', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('logout'));

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('requesting codes is rate limited', function (): void {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.mb_strtolower($user->email).'|127.0.0.1'), amount: 5);

    $response = $this->post(route('login.store'), ['email' => $user->email]);

    $response->assertTooManyRequests();
});
