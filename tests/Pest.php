<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Both suites extend the application TestCase. Feature tests always reset the
| database and skip Vite; Unit tests opt into RefreshDatabase per file.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| Shared test helpers. `actingAsRole()` logs in a freshly-created user with
| the given role — handy for authorization tests across every domain.
|
*/

function actingAsRole(Role $role): User
{
    $user = User::factory()->create(['role' => $role]);

    test()->actingAs($user);

    return $user;
}
