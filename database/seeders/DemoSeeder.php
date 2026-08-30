<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Team;
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
        $owner = $this->user('Ada Lovelace', 'owner@keel.test');
        $admin = $this->user('Grace Hopper', 'admin@keel.test');
        $member = $this->user('Alan Turing', 'member@keel.test');

        $acme = resolve(CreateTeam::class)->handle($owner, 'Acme Corp');
        $this->join($acme, $admin, TeamRole::Admin);
        $this->join($acme, $member, TeamRole::Member);

        // A second team proves tenant isolation is actually doing something:
        // nothing owned by Acme should ever be visible from here.
        $rival = resolve(CreateTeam::class)->handle(
            $this->user('Katherine Johnson', 'rival@keel.test'),
            'Rival Industries',
        );

        $this->command->newLine();
        $this->command->info('Demo accounts (password: '.self::PASSWORD.')');
        $this->command->table(
            ['Email', 'Team', 'Role'],
            [
                ['owner@keel.test', $acme->name, 'Owner'],
                ['admin@keel.test', $acme->name, 'Admin'],
                ['member@keel.test', $acme->name, 'Member'],
                ['rival@keel.test', $rival->name, 'Owner'],
            ],
        );
    }

    private function user(string $name, string $email): User
    {
        /** @var array<string, mixed> $attributes */
        $attributes = User::factory()->raw(['name' => $name, 'email' => $email]);

        return User::firstOrCreate(['email' => $email], $attributes);
    }

    private function join(Team $team, User $user, TeamRole $role): void
    {
        $team->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            ['role' => $role],
        );

        $user->switchTeam($team);
    }
}
