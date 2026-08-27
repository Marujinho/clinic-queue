<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use Livewire\Livewire;

it('renders the appointments page for every role', function (Role $role) {
    actingAsRole($role);

    $this->get('/appointments')->assertOk();
})->with([
    'admin' => Role::Admin,
    'receptionist' => Role::Receptionist,
    'provider' => Role::Provider,
]);

it('lists appointments with patient and provider', function () {
    actingAsRole(Role::Receptionist);

    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']),
        'healthcare_provider_id' => HealthcareProvider::factory()->create(['name' => 'Dr. House']),
    ]);

    Livewire::test('pages::appointment.index')
        ->assertSee('Grace Hopper')
        ->assertSee('Dr. House');
});

it('filters appointments by status', function () {
    actingAsRole(Role::Receptionist);

    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Sched', 'last_name' => 'Uled']),
    ]);
    Appointment::factory()->completed()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Comp', 'last_name' => 'Leted']),
    ]);

    Livewire::test('pages::appointment.index')
        ->set('status', AppointmentStatus::Completed->value)
        ->assertSee('Comp Leted')
        ->assertDontSee('Sched Uled');
});

it('filters appointments by provider', function () {
    actingAsRole(Role::Receptionist);

    $providerA = HealthcareProvider::factory()->create(['name' => 'Dr. Alpha']);
    $providerB = HealthcareProvider::factory()->create(['name' => 'Dr. Beta']);

    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Patient']),
        'healthcare_provider_id' => $providerA->id,
    ]);
    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Beta', 'last_name' => 'Patient']),
        'healthcare_provider_id' => $providerB->id,
    ]);

    Livewire::test('pages::appointment.index')
        ->set('provider', (string) $providerA->id)
        ->assertSee('Alpha Patient')
        ->assertDontSee('Beta Patient');
});

it('filters appointments by date', function () {
    actingAsRole(Role::Receptionist);

    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Tomorrow', 'last_name' => 'Person']),
        'scheduled_at' => now()->addDay()->setTime(9, 0),
    ]);
    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'NextWeek', 'last_name' => 'Person']),
        'scheduled_at' => now()->addWeek()->setTime(9, 0),
    ]);

    Livewire::test('pages::appointment.index')
        ->set('date', now()->addDay()->format('Y-m-d'))
        ->assertSee('Tomorrow Person')
        ->assertDontSee('NextWeek Person');
});

it('performs each valid status transition', function (Appointment $appointment, string $to) {
    actingAsRole(Role::Receptionist);

    Livewire::test('pages::appointment.index')
        ->call('changeStatus', $appointment->id, $to);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::from($to));
})->with([
    'scheduled → confirmed' => [fn () => Appointment::factory()->create(), 'confirmed'],
    'confirmed → in_progress' => [fn () => Appointment::factory()->confirmed()->create(), 'in_progress'],
    'in_progress → completed' => [fn () => Appointment::factory()->inProgress()->create(), 'completed'],
    'scheduled → cancelled' => [fn () => Appointment::factory()->create(), 'cancelled'],
    'confirmed → cancelled' => [fn () => Appointment::factory()->confirmed()->create(), 'cancelled'],
    'scheduled → no_show' => [fn () => Appointment::factory()->create(), 'no_show'],
    'confirmed → no_show' => [fn () => Appointment::factory()->confirmed()->create(), 'no_show'],
]);

it('rejects an invalid status transition', function () {
    actingAsRole(Role::Receptionist);

    $appointment = Appointment::factory()->completed()->create();

    Livewire::test('pages::appointment.index')
        ->call('changeStatus', $appointment->id, AppointmentStatus::Cancelled->value);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});

it('rejects skipping straight from scheduled to completed', function () {
    actingAsRole(Role::Receptionist);

    $appointment = Appointment::factory()->create();

    Livewire::test('pages::appointment.index')
        ->call('changeStatus', $appointment->id, AppointmentStatus::Completed->value);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled);
});

it('lets a provider view the index but hides manage actions', function () {
    actingAsRole(Role::Provider);

    Appointment::factory()->create([
        'patient_id' => Patient::factory()->create(['first_name' => 'Visible', 'last_name' => 'Appointment']),
    ]);

    Livewire::test('pages::appointment.index')
        ->assertOk()
        ->assertSee('Visible Appointment')
        ->assertDontSee('New Appointment');
});

it('forbids a provider from opening the create form', function () {
    actingAsRole(Role::Provider);

    Livewire::test('pages::appointment.index')
        ->call('create')
        ->assertForbidden();
});

it('forbids a provider from transitioning an appointment', function () {
    actingAsRole(Role::Provider);

    $appointment = Appointment::factory()->create();

    Livewire::test('pages::appointment.index')
        ->call('changeStatus', $appointment->id, AppointmentStatus::Confirmed->value)
        ->assertForbidden();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled);
});

it('allows an admin to transition an appointment', function () {
    actingAsRole(Role::Admin);

    $appointment = Appointment::factory()->create();

    Livewire::test('pages::appointment.index')
        ->call('changeStatus', $appointment->id, AppointmentStatus::Confirmed->value);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});
