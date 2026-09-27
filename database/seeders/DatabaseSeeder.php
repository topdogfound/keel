<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles and permissions are application infrastructure, not demo data,
        // so this call must survive `./keel new`.
        $this->call(RolesAndPermissionsSeeder::class);

        // The browser suite's accounts. Also kept by `./keel new`, and never
        // seeded outside local/testing.
        if (app()->environment(['local', 'testing'])) {
            $this->call(BrowserTestUserSeeder::class);
        }

        $this->call(DemoSeeder::class);
    }
}
