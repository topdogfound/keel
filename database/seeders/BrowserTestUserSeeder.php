<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The accounts the Playwright suite signs in as (tests/Browser). Test
 * infrastructure rather than demo content, so `./keel new` keeps it and a new
 * project's `./keel e2e` passes from the first run.
 *
 * Only seeded in local/testing, matching the _testing OTP endpoints the suite
 * reads its sign-in codes from.
 */
class BrowserTestUserSeeder extends Seeder
{
    public const string MEMBER = 'member@example.test';

    public const string SUPPORT = 'support@example.test';

    public function run(): void
    {
        $this->user('Alan Turing', self::MEMBER);
        $this->user('Grace Hopper', self::SUPPORT)->assignRole(StaffRole::Support->value);
    }

    /**
     * Create a test user, or reuse it if this seeder already ran.
     */
    private function user(string $name, string $email): User
    {
        $existing = User::where('email', $email)->first();

        if ($existing !== null) {
            return $existing;
        }

        return User::factory()->create(['name' => $name, 'email' => $email]);
    }
}
