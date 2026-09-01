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

        $this->call(DemoSeeder::class);
    }
}
