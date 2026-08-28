<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\QueueTicket;
use Livewire\Livewire;

it('creates a waiting ticket with checked_in_at and a sequential number', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasNoErrors();

    $ticket = QueueTicket::query()->sole();

    expect($ticket)
        ->patient_id->toBe($patient->id)
        ->queue_id->toBe($queue->id)
        ->status->toBe(TicketStatus::Waiting)
        ->ticket_number->toBe('A001')
        ->checked_in_at->not->toBeNull()
        ->called_at->toBeNull();
});

it('numbers consecutive check-ins sequentially per queue', function () {
    actingAsRole(Role::Receptionist);

    $queue = Queue::factory()->create();
    [$first, $second] = Patient::factory()->count(2)->create();

    $component = Livewire::test('pages::queue.check-in');

    $component->call('selectPatient', $first->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasNoErrors();

    $component->call('selectPatient', $second->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($queue->queueTickets()->orderBy('id')->pluck('ticket_number')->all())
        ->toBe(['A001', 'A002']);
});

it('rejects an inactive patient (BR-02)', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->inactive()->create();
    $queue = Queue::factory()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasErrors('patient_id');

    expect(QueueTicket::count())->toBe(0);
});

it('rejects a patient who already holds an active ticket (BR-05)', function (string $state) {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->create();

    $factory = QueueTicket::factory()->for($patient);
    if ($state !== 'waiting') {
        $factory = $factory->{$state}();
    }
    $factory->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasErrors('patient_id');

    expect($patient->queueTickets()->count())->toBe(1);
})->with(['waiting', 'called', 'inService']);

it('allows a new check-in when previous tickets are completed or cancelled', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($patient)->completed()->create();
    QueueTicket::factory()->for($patient)->cancelled()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($patient->queueTickets()->where('status', TicketStatus::Waiting)->count())->toBe(1);
});

it('stores the chosen priority on a priority-enabled queue', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->create(['priority_enabled' => true]);

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->set('priority', TicketPriority::Urgent->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(QueueTicket::query()->sole()->priority)->toBe(TicketPriority::Urgent);
});

it('forces Normal priority when the queue has priority disabled', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->create(['priority_enabled' => false]);

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->set('priority', TicketPriority::Urgent->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(QueueTicket::query()->sole()->priority)->toBe(TicketPriority::Normal);
});

it('rejects an inactive queue', function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $queue = Queue::factory()->inactive()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->call('save')
        ->assertHasErrors('queue_id');
});

it("rejects an appointment that doesn't belong to the patient", function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $otherPatientAppointment = Appointment::factory()->create();
    $queue = Queue::factory()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->set('appointment_id', $otherPatientAppointment->id)
        ->call('save')
        ->assertHasErrors('appointment_id');
});

it("links the patient's open appointment to the ticket", function () {
    actingAsRole(Role::Receptionist);

    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->confirmed()->for($patient)->create();
    $queue = Queue::factory()->create();

    Livewire::test('pages::queue.check-in')
        ->call('selectPatient', $patient->id)
        ->set('queue_id', $queue->id)
        ->set('appointment_id', $appointment->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(QueueTicket::query()->sole()->appointment_id)->toBe($appointment->id);
});

it('searches active patients by name', function () {
    actingAsRole(Role::Receptionist);

    Patient::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
    Patient::factory()->inactive()->create(['first_name' => 'Graceila', 'last_name' => 'Inactive']);
    Patient::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    Livewire::test('pages::queue.check-in')
        ->set('patientSearch', 'Grace')
        ->assertSee('Grace Hopper')
        ->assertDontSee('Graceila')
        ->assertDontSee('Alan');
});

it('allows admins and receptionists but forbids providers (authz)', function () {
    actingAsRole(Role::Admin);
    test()->get('/check-in')->assertOk();

    actingAsRole(Role::Receptionist);
    test()->get('/check-in')->assertOk();

    actingAsRole(Role::Provider);
    test()->get('/check-in')->assertForbidden();
});
