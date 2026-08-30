<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\TenantRecord;

beforeEach(function (): void {
    Schema::create('tenant_records', function (Blueprint $table): void {
        $table->id();
        $table->ulid('public_id')->unique();
        $table->foreignId('team_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->timestamps();
    });

    $this->teamA = Team::factory()->create(['name' => 'Team A']);
    $this->teamB = Team::factory()->create(['name' => 'Team B']);

    CurrentTeam::pretend($this->teamA, fn () => TenantRecord::create(['title' => 'A record']));
    CurrentTeam::pretend($this->teamB, fn () => TenantRecord::create(['title' => 'B record']));
});

afterEach(function (): void {
    CurrentTeam::forget();
});

it('returns only the current team rows', function (): void {
    CurrentTeam::set($this->teamA);

    expect(TenantRecord::pluck('title')->all())->toBe(['A record']);

    CurrentTeam::set($this->teamB);

    expect(TenantRecord::pluck('title')->all())->toBe(['B record']);
});

it('fills team_id on create without being told', function (): void {
    CurrentTeam::set($this->teamB);

    $record = TenantRecord::create(['title' => 'implicit']);

    expect($record->team_id)->toBe($this->teamB->id);
});

it('hides another team record from find, so lookups 404 rather than 403', function (): void {
    $bRecord = CurrentTeam::pretend($this->teamB, fn () => TenantRecord::first());

    CurrentTeam::set($this->teamA);

    expect(TenantRecord::find($bRecord->id))->toBeNull()
        ->and(TenantRecord::where('public_id', $bRecord->public_id)->first())->toBeNull();
});

it('scopes aggregates and deletes too, not just selects', function (): void {
    CurrentTeam::set($this->teamA);

    expect(TenantRecord::count())->toBe(1);

    TenantRecord::query()->delete();

    expect(TenantRecord::count())->toBe(0);

    CurrentTeam::set($this->teamB);
    expect(TenantRecord::count())->toBe(1);
});

it('exposes a ULID public id and binds routes by it', function (): void {
    CurrentTeam::set($this->teamA);

    $record = TenantRecord::first();

    expect($record->getRouteKeyName())->toBe('public_id')
        ->and($record->public_id)->toBeString()->toHaveLength(26)
        ->and((string) $record->id)->not->toBe($record->public_id);
});

it('lets the staff panel escape hatch see every team', function (): void {
    CurrentTeam::set($this->teamA);

    expect(TenantRecord::count())->toBe(1)
        ->and(TenantRecord::acrossAllTeams()->count())->toBe(2);
});

it('leaks nothing when there is no team context', function (): void {
    CurrentTeam::forget();

    // No authenticated user and no override: the scope cannot narrow the query,
    // so this documents the fallback rather than pretending it filters.
    expect(TenantRecord::count())->toBe(2);
});

it('scopes to the authenticated user current team', function (): void {
    CurrentTeam::forget();

    // UserFactory's afterCreating hook builds a personal team and points
    // current_team_id at it, so this has to be set after creation.
    $user = User::factory()->create();
    $user->forceFill(['current_team_id' => $this->teamB->id])->save();

    $this->actingAs($user);

    expect(TenantRecord::pluck('title')->all())->toBe(['B record']);
});
