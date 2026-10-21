<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

it('rejects a deactivated user even with valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret-pass'), 'active' => false]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('signs out a user who is deactivated mid-session', function () {
    $user = actingAsRole(Role::Receptionist);

    $this->get('/dashboard')->assertOk();

    $user->update(['active' => false]);

    $this->get('/dashboard')->assertRedirect('/login');

    expect(auth()->check())->toBeFalse();
});

it('throttles repeated failed login attempts', function () {
    RateLimiter::clear(Str::transliterate('throttled@clinic.test|127.0.0.1'));

    $user = User::factory()->create(['email' => 'throttled@clinic.test', 'password' => bcrypt('secret-pass')]);

    foreach (range(1, 5) as $attempt) {
        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('email');
    }

    // Sixth attempt is locked out even with the correct password.
    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertHasErrors('email')
        ->assertSee('Too many login attempts');

    expect(auth()->check())->toBeFalse();
});

it('resets the login throttle after a successful attempt', function () {
    RateLimiter::clear(Str::transliterate('reset@clinic.test|127.0.0.1'));

    $user = User::factory()->create(['email' => 'reset@clinic.test', 'password' => bcrypt('secret-pass')]);

    foreach (range(1, 4) as $attempt) {
        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong')
            ->call('login');
    }

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertHasNoErrors();

    expect(RateLimiter::attempts(Str::transliterate('reset@clinic.test|127.0.0.1')))->toBe(0);
});
