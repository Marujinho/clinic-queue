<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\QueueTicket;
use Livewire\Livewire;

it('is reachable by every authenticated role', function (Role $role) {
    actingAsRole($role);

    test()->get('/queue-board')->assertOk();
})->with([Role::Admin, Role::Receptionist, Role::Provider]);

it('lists waiting tickets in BR-04 order (priority DESC, arrival ASC)', function () {
    actingAsRole(Role::Provider);

    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->create([
        'ticket_number' => 'A001',
        'checked_in_at' => now()->subMinutes(50),
    ]);
    QueueTicket::factory()->for($queue)->urgent()->create([
        'ticket_number' => 'A003',
        'checked_in_at' => now()->subMinutes(10),
    ]);
    QueueTicket::factory()->for($queue)->highPriority()->create([
        'ticket_number' => 'A002',
        'checked_in_at' => now()->subMinutes(30),
    ]);

    Livewire::test('pages::queue.board')
        ->assertSeeInOrder(['A003', 'A002', 'A001']);
});

it('shows the called ticket as now serving', function () {
    actingAsRole(Role::Provider);

    $queue = Queue::factory()->create();
    $patient = Patient::factory()->create(['first_name' => 'Serving', 'last_name' => 'Now']);
    QueueTicket::factory()->for($queue)->for($patient)->called()->create(['ticket_number' => 'A007']);

    Livewire::test('pages::queue.board')
        ->assertSee('Serving Now')
        ->assertSee('A007');
});

it('hides inactive queues', function () {
    actingAsRole(Role::Provider);

    Queue::factory()->inactive()->create(['name' => 'Ghost Queue']);

    Livewire::test('pages::queue.board')
        ->assertDontSee('Ghost Queue');
});

it('lets a provider call the next patient per BR-04', function () {
    actingAsRole(Role::Provider);

    $queue = Queue::factory()->create();
    $normal = QueueTicket::factory()->for($queue)->create(['checked_in_at' => now()->subHour()]);
    $urgent = QueueTicket::factory()->for($queue)->urgent()->create(['checked_in_at' => now()]);

    Livewire::test('pages::queue.board')
        ->call('callNext', $queue->id);

    expect($urgent->fresh()->status)->toBe(TicketStatus::Called)
        ->and($normal->fresh()->status)->toBe(TicketStatus::Waiting);
});

it('denies call-next to a receptionist', function () {
    actingAsRole(Role::Receptionist);

    $queue = Queue::factory()->create();
    $ticket = QueueTicket::factory()->for($queue)->create();

    Livewire::test('pages::queue.board')
        ->call('callNext', $queue->id)
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
});

it('lets a provider start service on a called ticket (BR-07)', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('pages::queue.board')
        ->call('start', $ticket->id);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InService);
});

it('denies start-service to a receptionist (BR-07)', function () {
    actingAsRole(Role::Receptionist);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('pages::queue.board')
        ->call('start', $ticket->id)
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Called);
});

it('lets a provider complete an in-service ticket', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->inService()->create();

    Livewire::test('pages::queue.board')
        ->call('complete', $ticket->id);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Completed)
        ->completed_at->not->toBeNull();
});

it('denies complete-service to a receptionist', function () {
    actingAsRole(Role::Receptionist);

    $ticket = QueueTicket::factory()->inService()->create();

    Livewire::test('pages::queue.board')
        ->call('complete', $ticket->id)
        ->assertForbidden();
});

it('lets a receptionist cancel a waiting ticket', function () {
    actingAsRole(Role::Receptionist);

    $ticket = QueueTicket::factory()->create();

    Livewire::test('pages::queue.board')
        ->call('cancel', $ticket->id);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Cancelled);
});

it('lets a provider cancel a called ticket', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('pages::queue.board')
        ->call('cancel', $ticket->id);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Cancelled);
});

it('surfaces an illegal transition as a message instead of an error', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->completed()->create();

    Livewire::test('pages::queue.board')
        ->call('cancel', $ticket->id)
        ->assertOk()
        ->assertSee('Cannot transition');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Completed);
});
