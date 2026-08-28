<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\HealthcareProvider;
use Livewire\Livewire;

it('creates a provider with valid data', function () {
    actingAsRole(Role::Admin);

    Livewire::test('provider.form')
        ->set('name', 'Dr. Alice Smith')
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-555001')
        ->set('active', true)
        ->call('save')
        ->assertDispatched('provider-saved')
        ->assertHasNoErrors();

    expect(HealthcareProvider::where('license_number', 'CRM-555001')->exists())->toBeTrue();
});

it('rejects a duplicate license number', function () {
    actingAsRole(Role::Admin);

    HealthcareProvider::factory()->create(['license_number' => 'CRM-DUP-1']);

    Livewire::test('provider.form')
        ->set('name', 'Dr. Bob Jones')
        ->set('specialty', 'Pediatrics')
        ->set('license_number', 'CRM-DUP-1')
        ->call('save')
        ->assertHasErrors(['license_number' => 'unique']);
});

it('allows keeping the same license number when editing (ignore self)', function () {
    actingAsRole(Role::Admin);

    $provider = HealthcareProvider::factory()->create([
        'name' => 'Dr. Carol White',
        'specialty' => 'Dermatology',
        'license_number' => 'CRM-SELF-1',
    ]);

    Livewire::test('provider.form', ['providerId' => $provider->id])
        ->set('name', 'Dr. Carol White-Updated')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('provider-saved');

    expect($provider->fresh()->name)->toBe('Dr. Carol White-Updated')
        ->and($provider->fresh()->license_number)->toBe('CRM-SELF-1');
});

it('requires a name', function () {
    actingAsRole(Role::Admin);

    Livewire::test('provider.form')
        ->set('name', '')
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-NAME-1')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('rejects a name shorter than the minimum', function () {
    actingAsRole(Role::Admin);

    Livewire::test('provider.form')
        ->set('name', 'A')
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-NAME-2')
        ->call('save')
        ->assertHasErrors(['name' => 'min']);
});

it('rejects a name longer than the maximum', function () {
    actingAsRole(Role::Admin);

    Livewire::test('provider.form')
        ->set('name', str_repeat('a', 101))
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-NAME-3')
        ->call('save')
        ->assertHasErrors(['name' => 'max']);
});

it('toggles active state via the index page', function () {
    actingAsRole(Role::Admin);

    $provider = HealthcareProvider::factory()->create(['active' => true]);

    Livewire::test('pages::provider.index')
        ->call('toggleActive', $provider->id);

    expect($provider->fresh()->active)->toBeFalse();

    Livewire::test('pages::provider.index')
        ->call('toggleActive', $provider->id);

    expect($provider->fresh()->active)->toBeTrue();
});

it('searches providers by name', function () {
    actingAsRole(Role::Admin);

    HealthcareProvider::factory()->create(['name' => 'Dr. Zebra Longname', 'specialty' => 'Cardiology']);
    HealthcareProvider::factory()->create(['name' => 'Dr. Other Person', 'specialty' => 'Pediatrics']);

    Livewire::test('pages::provider.index')
        ->set('search', 'Zebra')
        ->assertSee('Dr. Zebra Longname')
        ->assertDontSee('Dr. Other Person');
});

it('searches providers by specialty', function () {
    actingAsRole(Role::Admin);

    HealthcareProvider::factory()->create(['name' => 'Dr. Heart Doc', 'specialty' => 'Cardiology']);
    HealthcareProvider::factory()->create(['name' => 'Dr. Kid Doc', 'specialty' => 'Pediatrics']);

    Livewire::test('pages::provider.index')
        ->set('search', 'Cardiology')
        ->assertSee('Dr. Heart Doc')
        ->assertDontSee('Dr. Kid Doc');
});
