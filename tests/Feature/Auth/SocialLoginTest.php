<?php

use App\Actions\Auth\StashConnectionIntent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeSocialiteUser(string $id, string $email, string $name = 'Ada Lovelace'): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $id;
    $user->name = $name;
    $user->email = $email;
    $user->avatar = 'https://example.test/avatar.png';

    return $user;
}

beforeEach(function (): void {
    Config::set('login.providers.google.enabled', true);
    Config::set('login.providers.github.enabled', true);
});

test('an unavailable provider 404s', function (): void {
    Config::set('login.providers.google.enabled', false);

    $this->get(route('login.social.redirect', ['provider' => 'google']))->assertNotFound();
});

test('an unknown provider 404s', function (): void {
    $this->get(route('login.social.redirect', ['provider' => 'twitter']))->assertNotFound();
});

test('signing in with google creates a new user', function (): void {
    Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn(
        fakeSocialiteUser('google-123', 'ada@example.com'),
    );

    $response = $this->get(route('login.social.callback', ['provider' => 'google']));

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home'));
    expect($user->google_id)->toBe('google-123');
    expect($user->email_verified_at)->not->toBeNull();
});

test('signing in with google links to an existing account by email', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn(
        fakeSocialiteUser('google-456', 'ada@example.com'),
    );

    $this->get(route('login.social.callback', ['provider' => 'google']));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-456');
    expect(User::count())->toBe(1);
});

test('a failed google callback redirects back to login', function (): void {
    Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andThrow(new Exception('denied'));

    $response = $this->get(route('login.social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('signing in with github creates a new user', function (): void {
    Socialite::shouldReceive('driver')->once()->with('github')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn(
        fakeSocialiteUser('github-789', 'grace@example.com', 'Grace Hopper'),
    );

    $response = $this->get(route('login.social.callback', ['provider' => 'github']));

    $user = User::where('email', 'grace@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home'));
    expect($user->github_id)->toBe('github-789');
});

test('an authenticated user can connect a provider from settings', function (): void {
    $user = User::factory()->create();

    Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn(
        fakeSocialiteUser('google-999', 'someone-else@example.com', 'Someone Else'),
    );

    $response = $this
        ->actingAs($user)
        ->withSession([StashConnectionIntent::SESSION_KEY => $user->id])
        ->get(route('login.social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('profile.edit'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-999');
});

test('connecting a provider already linked to another account fails without stealing it', function (): void {
    $existingOwner = User::factory()->create(['google_id' => 'google-999']);
    $user = User::factory()->create();

    Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn(
        fakeSocialiteUser('google-999', 'someone-else@example.com'),
    );

    $response = $this
        ->actingAs($user)
        ->withSession([StashConnectionIntent::SESSION_KEY => $user->id])
        ->get(route('login.social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->google_id)->toBeNull();
    expect($existingOwner->fresh()->google_id)->toBe('google-999');
});

test('a user can disconnect a linked provider', function (): void {
    $user = User::factory()->create(['google_id' => 'google-123']);

    $response = $this
        ->actingAs($user)
        ->delete(route('settings.connections.destroy', ['provider' => 'google']));

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->google_id)->toBeNull();
});

test('an unavailable provider cannot be disconnected', function (): void {
    Config::set('login.providers.google.enabled', false);
    $user = User::factory()->create(['google_id' => 'google-123']);

    $this
        ->actingAs($user)
        ->delete(route('settings.connections.destroy', ['provider' => 'google']))
        ->assertNotFound();

    expect($user->fresh()->google_id)->toBe('google-123');
});
