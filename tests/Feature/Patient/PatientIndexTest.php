<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Patient;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('searches patients by name', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper', 'medical_record_number' => 'MRN-A']);
    Patient::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing', 'medical_record_number' => 'MRN-B']);

    Livewire::test('pages::patient.index')
        ->set('search', 'Hopper')
        ->assertSee('Grace')
        ->assertDontSee('Alan');
});

it('searches patients by medical record number', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper', 'medical_record_number' => 'MRN-FIND-ME']);
    Patient::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing', 'medical_record_number' => 'MRN-OTHER']);

    Livewire::test('pages::patient.index')
        ->set('search', 'MRN-FIND-ME')
        ->assertSee('Grace')
        ->assertDontSee('Alan');
});

it('filters by active status', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Activo', 'last_name' => 'One', 'active' => true]);
    Patient::factory()->inactive()->create(['first_name' => 'Inactivo', 'last_name' => 'Two']);

    Livewire::test('pages::patient.index')
        ->set('status', 'inactive')
        ->assertSee('Inactivo')
        ->assertDontSee('Activo');
});

it('deactivates a patient', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create(['active' => true]);

    Livewire::test('pages::patient.index')
        ->call('toggleActive', $patient->id);

    expect($patient->fresh()->active)->toBeFalse();
});

it('reactivates a patient', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->inactive()->create();

    Livewire::test('pages::patient.index')
        ->call('toggleActive', $patient->id);

    expect($patient->fresh()->active)->toBeTrue();
});

it('forbids a provider from deactivating a patient', function () {
    actingAsRole(Role::Provider);

    $patient = Patient::factory()->create(['active' => true]);

    Livewire::test('pages::patient.index')
        ->call('toggleActive', $patient->id)
        ->assertForbidden();

    expect($patient->fresh()->active)->toBeTrue();
});

it('lets a provider view the index but hides manage actions', function () {
    actingAsRole(Role::Provider);

    Patient::factory()->create(['first_name' => 'Visible', 'last_name' => 'Patient']);

    Livewire::test('pages::patient.index')
        ->assertOk()
        ->assertSee('Visible')
        ->assertDontSee('New Patient');
});

it('forbids a provider from opening the create form', function () {
    actingAsRole(Role::Provider);

    Livewire::test('pages::patient.index')
        ->call('create')
        ->assertForbidden();
});

it('searches patients by full name across first and last name', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper', 'medical_record_number' => 'MRN-A']);
    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Turing', 'medical_record_number' => 'MRN-B']);

    Livewire::test('pages::patient.index')
        ->set('search', 'Grace Hopper')
        ->assertSee('Hopper')
        ->assertDontSee('Turing');
});

it('treats SQL wildcard characters in the search literally', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper', 'medical_record_number' => 'MRN-A']);

    Livewire::test('pages::patient.index')
        ->set('search', '%')
        ->assertDontSee('Hopper');
});

it('does not let the client change the record being edited', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('pages::patient.index')
        ->set('editingId', 1);
})->throws(CannotUpdateLockedPropertyException::class);
