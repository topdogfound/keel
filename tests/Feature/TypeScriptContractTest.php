<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

/**
 * The PHP -> TypeScript contract is only useful while it is actually being
 * generated. A silent break here would not fail the typecheck -- tsc would
 * simply check a stale file, or none -- so assert the generation step itself.
 */
it('generates TypeScript types for the DTOs and enums the frontend consumes', function (): void {
    $output = resource_path('js/types/generated.d.ts');

    if (file_exists($output)) {
        unlink($output);
    }

    Artisan::call('typescript:transform');

    expect($output)->toBeFile();

    $contents = (string) file_get_contents($output);

    expect($contents)
        ->toContain('namespace App')
        // DTOs sent to Inertia pages
        ->toContain('UserTeam')
        ->toContain('TeamPermissions')
        // enums the UI branches on
        ->toContain('TeamRole')
        ->toContain('TeamPermission')
        // property names must survive, not just type names
        ->toContain('isPersonal')
        ->toContain('canCreateInvitation');
});
