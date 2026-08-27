<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\HealthcareProvider;
use Livewire\Livewire;

it('lets an admin save a provider and view the index route', function () {
    actingAsRole(Role::Admin);

    Livewire::test('provider.form')
        ->set('name', 'Dr. Admin Made')
        ->set('specialty', 'Orthopedics')
        ->set('license_number', 'CRM-ADMIN-1')
        ->call('save')
        ->assertHasNoErrors();

    expect(HealthcareProvider::where('license_number', 'CRM-ADMIN-1')->exists())->toBeTrue();

    test()->get('/providers')->assertOk();
});

it('forbids a receptionist from saving and from the index route', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('provider.form')
        ->set('name', 'Dr. Should Fail')
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-RECEP-1')
        ->call('save')
        ->assertForbidden();

    expect(HealthcareProvider::where('license_number', 'CRM-RECEP-1')->exists())->toBeFalse();

    test()->get('/providers')->assertForbidden();
});

it('forbids a provider from saving and from the index route', function () {
    actingAsRole(Role::Provider);

    test()->get('/providers')->assertForbidden();

    Livewire::test('provider.form')
        ->set('name', 'Dr. Should Fail Too')
        ->set('specialty', 'Cardiology')
        ->set('license_number', 'CRM-PROV-1')
        ->call('save')
        ->assertForbidden();

    expect(HealthcareProvider::where('license_number', 'CRM-PROV-1')->exists())->toBeFalse();
});
