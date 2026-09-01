<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function staffUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(StaffRole::SuperAdmin->value);

    return $user;
}

/**
 * Horizon, Pulse and Telescope all ship gates that only permit the local
 * environment, so they 403 everywhere else unless wired up. These assert the
 * wiring, in both directions.
 */
it('opens the dashboards to staff', function (string $gate): void {
    expect(Gate::forUser(staffUser())->allows($gate))->toBeTrue();
})->with(['viewHorizon', 'viewPulse', 'viewTelescope']);

it('closes the dashboards to non-staff', function (string $gate): void {
    expect(Gate::forUser(User::factory()->create())->allows($gate))->toBeFalse();
})->with(['viewHorizon', 'viewPulse', 'viewTelescope']);

it('closes the dashboards to guests', function (): void {
    expect(Gate::allows('viewPulse'))->toBeFalse()
        ->and(Gate::allows('viewHorizon'))->toBeFalse();
});
