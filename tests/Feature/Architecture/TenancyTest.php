<?php

declare(strict_types=1);

use App\Concerns\BelongsToTeam;
use App\Models\Membership;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant isolation only holds if it holds everywhere. These tests fail the build
 * when a new model opts out, rather than leaving the gap to be found in
 * production as a cross-tenant leak.
 */
it('applies BelongsToTeam to every model with a team_id column', function (): void {
    $offenders = [];

    foreach (File::allFiles(app_path('Models')) as $file) {
        $class = 'App\\Models\\'.str_replace(
            ['/', '.php'], ['\\', ''], $file->getRelativePathname()
        );

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract()) {
            continue;
        }

        /** @var Model $model */
        $model = new $class;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'team_id')) {
            continue;
        }

        // Deliberate exceptions, each for a reason that would otherwise break:
        //
        // Membership     - the pivot describing who belongs to a team, rather
        //                  than data owned by one.
        // TeamInvitation - resolved by route-model binding for a recipient who
        //                  is not a member yet, so a team scope would make
        //                  invitations impossible to accept.
        if (in_array($class, [Membership::class, TeamInvitation::class], true)) {
            continue;
        }

        if (! in_array(BelongsToTeam::class, class_uses_recursive($class), true)) {
            $offenders[] = $class;
        }
    }

    expect($offenders)->toBe([], 'These models have a team_id but no BelongsToTeam trait, so their queries are not tenant-scoped.');
});

it('confines the cross-tenant escape hatch to the staff panel', function (): void {
    $offenders = [];

    foreach (File::allFiles(app_path()) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $path = $file->getRelativePathname();

        // The staff panel is deliberately cross-tenant; the trait defines it.
        if (str_starts_with($path, 'Filament/') || str_contains($path, 'BelongsToTeam.php')) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (str_contains($contents, 'acrossAllTeams') || str_contains($contents, 'withoutGlobalScope(TeamScope')) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([], 'Only app/Filament may query across tenants.');
});
