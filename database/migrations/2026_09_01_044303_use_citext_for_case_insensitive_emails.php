<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema as PostgresSchema;

/**
 * Make email addresses case-insensitive at the database level.
 *
 * Without this, `Casey@Example.com` and `casey@example.com` are two different
 * rows and pass the unique index, so one person quietly ends up with two
 * accounts -- and whichever one they land in depends on how they typed it.
 * Application-level lowercasing does not fix it either, because it only covers
 * the paths that remember to call it.
 *
 * CITEXT pushes the guarantee into Postgres, where every path gets it.
 */
return new class extends Migration
{
    public function up(): void
    {
        PostgresSchema::createExtensionIfNotExists('citext');

        // Collapse any duplicates that already differ only by case, keeping the
        // earliest account, or the type change will fail on the unique index.
        DB::statement(<<<'SQL'
            DELETE FROM users a
            USING users b
            WHERE lower(a.email) = lower(b.email)
              AND a.id > b.id
        SQL);

        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE varchar(255)');
    }
};
