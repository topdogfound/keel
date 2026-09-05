<?php

use App\Models\User;
use App\Notifications\Settings\EmailChangeOtpNotification;
use Illuminate\Support\Facades\Notification;

function requestEmailChangeCode(User $user, string $newEmail): string
{
    Notification::fake();

    test()->actingAs($user)->post(route('profile.email.store'), ['email' => $newEmail]);

    $code = null;
    Notification::assertSentOnDemand(
        EmailChangeOtpNotification::class,
        function (EmailChangeOtpNotification $notification, array $channels, object $notifiable) use (&$code, $newEmail): true {
            $code = $notification->code;
            expect($notifiable->routes['mail'])->toBe($newEmail);

            return true;
        },
    );

    return $code;
}

test('a verification code is sent to the new email address', function (): void {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $code = requestEmailChangeCode($user, 'new@example.com');

    expect($code)->toMatch('/^\d{6}$/');
    expect($user->fresh()->email)->toBe('old@example.com');
});

test('the new email address cannot already be taken', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'old@example.com']);

    $response = $this->actingAs($user)->post(route('profile.email.store'), ['email' => 'taken@example.com']);

    $response->assertSessionHasErrors('email');
});

test('the new email address cannot be the current one', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);

    $response = $this->actingAs($user)->post(route('profile.email.store'), ['email' => 'same@example.com']);

    $response->assertSessionHasErrors('email');
});

test('verifying the code applies the new email address', function (): void {
    $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => null]);

    $code = requestEmailChangeCode($user, 'new@example.com');

    $response = $this->actingAs($user)->put(route('profile.email.update'), ['email_code' => $code]);

    $response->assertSessionHasNoErrors();
    $user->refresh();
    expect($user->email)->toBe('new@example.com');
    expect($user->email_verified_at)->not->toBeNull();
});

test('an invalid code does not change the email address', function (): void {
    $user = User::factory()->create(['email' => 'old@example.com']);

    requestEmailChangeCode($user, 'new@example.com');

    $response = $this->actingAs($user)->put(route('profile.email.update'), ['email_code' => '000000']);

    $response->assertSessionHasErrors('email_code');
    expect($user->fresh()->email)->toBe('old@example.com');
});
