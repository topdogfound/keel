<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Emails are CITEXT, so case can never split one person into two accounts.
 * This is enforced in Postgres rather than in application code, because
 * application-level lowercasing only protects the paths that remember it.
 */
it('refuses a second account differing only by case', function (): void {
    User::factory()->create(['email' => 'Casey@Example.com']);

    expect(fn () => User::factory()->create(['email' => 'casey@example.com']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('finds a user regardless of the case used to look them up', function (): void {
    $user = User::factory()->create(['email' => 'Casey@Example.com']);

    expect(User::where('email', 'CASEY@EXAMPLE.COM')->first()?->id)->toBe($user->id)
        ->and(User::where('email', 'casey@example.com')->first()?->id)->toBe($user->id);
});

it('preserves the address as the user typed it', function (): void {
    $user = User::factory()->create(['email' => 'Casey@Example.com']);

    // Case-insensitive for comparison, but not rewritten on the way in.
    expect($user->fresh()->email)->toBe('Casey@Example.com');
});
