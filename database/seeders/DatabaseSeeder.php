<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Deliberately NOT using WithoutModelEvents: Team::slug is NOT NULL and is
    // populated by a `creating` model event, so suppressing events makes seeding
    // fail on a not-null violation.

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles and permissions are application infrastructure, not demo data,
        // so this call must survive `./keel new`.
        $this->call(RolesAndPermissionsSeeder::class);

        $this->call(DemoSeeder::class);
    }
}
