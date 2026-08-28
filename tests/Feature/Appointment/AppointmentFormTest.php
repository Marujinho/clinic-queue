<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use Livewire\Livewire;

it('creates an appointment with valid data', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $provider = HealthcareProvider::factory()->create();

    Livewire::test('appointment.form')
        ->set('patient_id', $patient->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('reason', 'Annual check-up')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('appointment-saved');

    $appointment = Appointment::first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->patient_id)->toBe($patient->id)
        ->and($appointment->healthcare_provider_id)->toBe($provider->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

it('rejects an inactive patient', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->inactive()->create();
    $provider = HealthcareProvider::factory()->create();

    Livewire::test('appointment.form')
        ->set('patient_id', $patient->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['patient_id']);

    expect(Appointment::count())->toBe(0);
});

it('rejects an inactive provider', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $provider = HealthcareProvider::factory()->inactive()->create();

    Livewire::test('appointment.form')
        ->set('patient_id', $patient->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['healthcare_provider_id']);

    expect(Appointment::count())->toBe(0);
});

it('rejects a scheduled time in the past', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $provider = HealthcareProvider::factory()->create();

    Livewire::test('appointment.form')
        ->set('patient_id', $patient->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['scheduled_at']);
});

it('rejects an overlapping appointment for the same provider (BR-03)', function () {
    actingAsRole(Role::Receptionist);

    $provider = HealthcareProvider::factory()->create();
    $slot = now()->addDay()->setTime(10, 0);

    Appointment::factory()->create([
        'healthcare_provider_id' => $provider->id,
        'scheduled_at' => $slot,
    ]);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', $slot->copy()->addMinutes(15)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['scheduled_at']);

    expect(Appointment::count())->toBe(1);
});

it('accepts a non-overlapping appointment for the same provider', function () {
    actingAsRole(Role::Receptionist);

    $provider = HealthcareProvider::factory()->create();
    $slot = now()->addDay()->setTime(10, 0);

    Appointment::factory()->create([
        'healthcare_provider_id' => $provider->id,
        'scheduled_at' => $slot,
    ]);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', $slot->copy()->addHour()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::count())->toBe(2);
});

it('accepts an overlapping time for a different provider', function () {
    actingAsRole(Role::Receptionist);

    $slot = now()->addDay()->setTime(10, 0);

    Appointment::factory()->create(['scheduled_at' => $slot]);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', HealthcareProvider::factory()->create()->id)
        ->set('scheduled_at', $slot->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::count())->toBe(2);
});

it('does not treat a cancelled appointment as a conflict', function () {
    actingAsRole(Role::Receptionist);

    $provider = HealthcareProvider::factory()->create();
    $slot = now()->addDay()->setTime(10, 0);

    Appointment::factory()->cancelled()->create([
        'healthcare_provider_id' => $provider->id,
        'scheduled_at' => $slot,
    ]);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', $provider->id)
        ->set('scheduled_at', $slot->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::count())->toBe(2);
});

it('lets an appointment keep its own slot when edited', function () {
    actingAsRole(Role::Receptionist);

    $appointment = Appointment::factory()->create([
        'scheduled_at' => now()->addDay()->setTime(10, 0),
        'reason' => 'Original reason',
    ]);

    Livewire::test('appointment.form', ['appointmentId' => $appointment->id])
        ->set('reason', 'Updated reason')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('appointment-saved');

    expect($appointment->fresh()->reason)->toBe('Updated reason');
});

it('allows an admin to create an appointment', function () {
    actingAsRole(Role::Admin);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', HealthcareProvider::factory()->create()->id)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::count())->toBe(1);
});

it('forbids a provider role from creating an appointment', function () {
    actingAsRole(Role::Provider);

    Livewire::test('appointment.form')
        ->set('patient_id', Patient::factory()->create()->id)
        ->set('healthcare_provider_id', HealthcareProvider::factory()->create()->id)
        ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertForbidden();

    expect(Appointment::count())->toBe(0);
});
