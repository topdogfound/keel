<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use App\Support\PermissionScope;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Health\Facades\Health;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

/**
 * The framework's /up only proves PHP is executing. These checks prove the
 * things that actually break -- and the scheduler and queue ones only pass
 * while the corresponding Compose services are running, which is the point.
 */
it('registers checks for the parts that actually fail', function (): void {
    $names = collect(Health::registeredChecks())->map(fn ($c) => $c->getName())->all();

    expect($names)->toContain('Database', 'Cache', 'Schedule', 'Queue', 'UsedDiskSpace');
});

it('keeps health output away from anonymous visitors', function (): void {
    // Check output describes the infrastructure, so it is not public.
    $this->get('/health')->assertRedirect();
    $this->get('/health.json')->assertRedirect();
});

it('refuses health output to a non-staff user', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/health')
        ->assertForbidden();
});

it('shows health output to staff', function (): void {
    $staff = User::factory()->create();
    PermissionScope::global();
    $staff->assignRole(StaffRole::SuperAdmin->value);

    $this->actingAs($staff)->get('/health')->assertSuccessful();
});

it('leaves the framework up endpoint public for load balancers', function (): void {
    $this->get('/up')->assertSuccessful();
});
