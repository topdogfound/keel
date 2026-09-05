<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see the home page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('home')
            ->where('auth.user', null)
        );
});

test('authenticated users see the home page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('home')
            ->has('auth.user')
        );
});
