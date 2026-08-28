<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('lets a user log in with valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass')]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('validates required fields', function () {
    Livewire::test('pages::auth.login')
        ->call('login')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});

it('renders the authenticated dashboard', function () {
    actingAsRole(Role::Receptionist);

    $this->get('/dashboard')->assertOk()->assertSee('Hello');
});
