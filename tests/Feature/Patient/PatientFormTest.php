<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Patient;
use Livewire\Livewire;

it('creates a patient with valid data', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('patient.form')
        ->set('first_name', 'Ada')
        ->set('last_name', 'Lovelace')
        ->set('date_of_birth', '1990-05-10')
        ->set('email', 'ada@example.com')
        ->set('phone', '+1 555 0100')
        ->set('medical_record_number', 'MRN-0001')
        ->set('active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('patient-saved');

    expect(Patient::where('medical_record_number', 'MRN-0001')->exists())->toBeTrue();
});

it('rejects a duplicate medical record number (BR-01)', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['medical_record_number' => 'MRN-DUP']);

    Livewire::test('patient.form')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('date_of_birth', '1985-01-01')
        ->set('phone', '555')
        ->set('medical_record_number', 'MRN-DUP')
        ->call('save')
        ->assertHasErrors(['medical_record_number' => 'unique']);
});

it('rejects a future date of birth', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('patient.form')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('date_of_birth', now()->addDay()->format('Y-m-d'))
        ->set('phone', '555')
        ->set('medical_record_number', 'MRN-FUT')
        ->call('save')
        ->assertHasErrors(['date_of_birth']);
});

it('rejects names that are too short or too long', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('patient.form')
        ->set('first_name', 'A')
        ->set('last_name', str_repeat('x', 101))
        ->set('date_of_birth', '1990-01-01')
        ->set('phone', '555')
        ->set('medical_record_number', 'MRN-NAME')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name']);
});

it('requires phone', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('patient.form')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Roe')
        ->set('date_of_birth', '1990-01-01')
        ->set('phone', '')
        ->set('medical_record_number', 'MRN-PH')
        ->call('save')
        ->assertHasErrors(['phone']);
});

it('lets a patient keep its own MRN when editing', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create(['medical_record_number' => 'MRN-SELF']);

    Livewire::test('patient.form', ['patientId' => $patient->id])
        ->set('first_name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($patient->fresh()->first_name)->toBe('Renamed');
});

it('allows an admin to save a patient', function () {
    actingAsRole(Role::Admin);

    Livewire::test('patient.form')
        ->set('first_name', 'Admin')
        ->set('last_name', 'Created')
        ->set('date_of_birth', '1990-01-01')
        ->set('phone', '555')
        ->set('medical_record_number', 'MRN-ADM')
        ->call('save')
        ->assertHasNoErrors();

    expect(Patient::where('medical_record_number', 'MRN-ADM')->exists())->toBeTrue();
});

it('forbids a provider from saving a patient', function () {
    actingAsRole(Role::Provider);

    Livewire::test('patient.form')
        ->set('first_name', 'No')
        ->set('last_name', 'Access')
        ->set('date_of_birth', '1990-01-01')
        ->set('phone', '555')
        ->set('medical_record_number', 'MRN-DENY')
        ->call('save')
        ->assertForbidden();

    expect(Patient::where('medical_record_number', 'MRN-DENY')->exists())->toBeFalse();
});
