<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Scaffolds a tenant-owned model with the isolation conventions already wired.
 *
 * The tenancy guarantee is only as strong as the least careful model, so the
 * generator makes the correct thing the easy thing: BelongsToTeam, a ULID public
 * id, and a migration carrying both columns.
 */
class MakeTenantModel extends Command
{
    protected $signature = 'make:tenant-model {name : The model name, e.g. Invoice}
        {--force : Overwrite an existing model}';

    protected $description = 'Create a team-scoped model, migration, factory and policy';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        $this->call('make:model', [
            'name' => $name,
            '--migration' => true,
            '--factory' => true,
            '--policy' => true,
            '--force' => (bool) $this->option('force'),
        ]);

        $this->wireModel($name);
        $this->wireMigration($name);

        // Rewriting files by hand leaves import order and spacing off; let Pint
        // settle it so generated code matches everything else in the repo.
        // Pint is a binary, not an artisan command, so it has to be invoked.
        $this->formatWithPint(array_filter([
            app_path("Models/{$name}.php"),
            $this->migrationPath($name),
        ]));

        $this->newLine();
        $this->components->info("{$name} is team-scoped: queries filter to the current team and team_id fills itself on create.");
        $this->components->warn('Review the generated migration before running it.');

        return self::SUCCESS;
    }

    private function wireModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");

        if (! file_exists($path)) {
            return;
        }

        $contents = (string) file_get_contents($path);

        $contents = str_replace(
            'use Illuminate\\Database\\Eloquent\\Model;',
            "use App\\Concerns\\BelongsToTeam;\nuse App\\Concerns\\HasPublicId;\nuse Illuminate\\Database\\Eloquent\\Model;",
            $contents,
        );

        $contents = preg_replace(
            '/(class '.preg_quote($name, '/').' extends Model\s*\{\n)/',
            "$1    use BelongsToTeam, HasPublicId;\n",
            $contents,
            1,
        );

        file_put_contents($path, (string) $contents);
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function formatWithPint(array $paths): void
    {
        $binary = base_path('vendor/bin/pint');

        if (! is_executable($binary) || $paths === []) {
            return;
        }

        Process::run(array_merge([$binary, '--quiet'], $paths));
    }

    private function migrationPath(string $name): ?string
    {
        $table = Str::snake(Str::pluralStudly($name));
        $matches = glob(database_path("migrations/*_create_{$table}_table.php")) ?: [];

        return $matches === [] ? null : (string) end($matches);
    }

    private function wireMigration(string $name): void
    {
        $path = $this->migrationPath($name);

        if ($path === null) {
            return;
        }
        $contents = (string) file_get_contents($path);

        $contents = str_replace(
            '$table->id();',
            "\$table->id();\n            \$table->ulid('public_id')->unique();\n            \$table->foreignId('team_id')->constrained()->cascadeOnDelete();",
            $contents,
        );

        file_put_contents($path, $contents);
    }
}
