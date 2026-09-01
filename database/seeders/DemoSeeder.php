<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo content so a fresh `./keel setup` lands on a working, populated app
 * rather than an empty login screen.
 *
 * `./keel new` removes this seeder — it is template scaffolding, not app code.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $this->user('Ada Lovelace', 'super_admin@keel.test')->assignRole(StaffRole::SuperAdmin->value);
        $this->user('Grace Hopper', 'support@keel.test')->assignRole(StaffRole::Support->value);
        $this->user('Alan Turing', 'member@keel.test');

        $this->command->newLine();
        $this->command->info('Demo accounts (password: '.self::PASSWORD.')');
        $this->command->table(
            ['Email', 'Role'],
            [
                ['super_admin@keel.test', 'Super Admin (staff panel)'],
                ['support@keel.test', 'Support (staff panel)'],
                ['member@keel.test', '— (ordinary user)'],
            ],
        );
    }

    /**
     * Create a demo user, or reuse it if this seeder already ran.
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
