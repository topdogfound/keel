<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->user = User::factory()->create();
    $this->team = Team::factory()->create(['name' => 'Acme Corp']);
    $this->team->memberships()->create(['user_id' => $this->user->id, 'role' => TeamRole::Owner]);
    $this->user->forceFill(['current_team_id' => $this->team->id])->save();

    $this->otherTeam = Team::factory()->create(['name' => 'Rival Industries']);
});

it('requires a token', function (): void {
    $this->getJson('/api/v1/teams')->assertUnauthorized();
});

it('returns one consistent error envelope', function (): void {
    $this->getJson('/api/v1/teams')
        ->assertUnauthorized()
        ->assertJsonStructure(['error' => ['status', 'message', 'errors', 'request_id']]);
});

it('lists only the teams the token holder belongs to', function (): void {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/v1/teams')->assertOk();
    $names = $response->json('data.*.name');

    // UserFactory's afterCreating hook gives every user a personal team, so the
    // caller legitimately belongs to two.
    expect($names)->toContain('Acme Corp')
        ->and($names)->not->toContain('Rival Industries')
        ->and($response->json('meta.total'))->toBe(2);
});

it('identifies teams by slug, never by primary key', function (): void {
    Sanctum::actingAs($this->user);

    $id = $this->getJson('/api/v1/teams')->json('data.0.id');

    expect($id)->toBe($this->team->slug)
        ->and($id)->not->toBe($this->team->id);
});

it('refuses a team the caller does not belong to', function (): void {
    Sanctum::actingAs($this->user);

    $this->getJson("/api/v1/teams/{$this->otherTeam->slug}")
        ->assertForbidden()
        ->assertJsonPath('error.status', 403);
});

it('supports the shared filter and sort vocabulary', function (): void {
    $second = Team::factory()->create(['name' => 'Zeta Division']);
    $second->memberships()->create(['user_id' => $this->user->id, 'role' => TeamRole::Member]);

    Sanctum::actingAs($this->user);

    // Sorting descending puts Zeta first regardless of the personal team.
    expect($this->getJson('/api/v1/teams?sort=-name')->json('data.0.name'))
        ->toBe('Zeta Division');

    // Filtering narrows to exactly one, which is the point of the shared vocabulary.
    expect($this->getJson('/api/v1/teams?filter[name]=Zeta')->json('data.*.name'))
        ->toBe(['Zeta Division']);

    expect($this->getJson('/api/v1/teams?filter[is_personal]=0')->json('data.*.name'))
        ->toEqualCanonicalizing(['Acme Corp', 'Zeta Division']);
});

it('caps page size so a client cannot ask for everything', function (): void {
    Sanctum::actingAs($this->user);

    expect($this->getJson('/api/v1/teams?per_page=5000')->json('meta.per_page'))->toBe(100);
});
