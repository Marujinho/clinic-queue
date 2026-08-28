<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\QueueTicket;
use Livewire\Livewire;

it('shows the reception dashboard to admins and receptionists', function (Role $role) {
    actingAsRole($role);

    test()->get('/dashboard')
        ->assertOk()
        ->assertSee("Today's appointments")
        ->assertDontSee('Current patient');
})->with([Role::Admin, Role::Receptionist]);

it('shows the provider dashboard to providers', function () {
    actingAsRole(Role::Provider);

    test()->get('/dashboard')
        ->assertOk()
        ->assertSee('Current patient')
        ->assertDontSee("Today's appointments");
});

it('counts today appointments on the reception dashboard', function () {
    actingAsRole(Role::Receptionist);

    Appointment::factory()->count(3)->create(['scheduled_at' => now()->addHours(2)]);
    Appointment::factory()->create(['scheduled_at' => now()->addDays(3)]);

    Livewire::test('dashboard.reception')
        ->assertSeeInOrder(["Today's appointments", '3']);
});

it('lists active queues with waiting counts on the reception dashboard', function () {
    actingAsRole(Role::Receptionist);

    $queue = Queue::factory()->create(['name' => 'Cardiology']);
    QueueTicket::factory()->for($queue)->count(2)->create();
    Queue::factory()->inactive()->create(['name' => 'Dormant Queue']);

    Livewire::test('dashboard.reception')
        ->assertSee('Cardiology')
        ->assertDontSee('Dormant Queue');
});

it('shows the most recently called ticket as the current patient', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('dashboard.provider')
        ->assertSee($ticket->ticket_number)
        ->assertSee($ticket->patient->full_name);
});

it('lets a provider start then complete service from the dashboard', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('dashboard.provider')
        ->call('start', $ticket->id)
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::InService);

    Livewire::test('dashboard.provider')
        ->call('complete', $ticket->id)
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Completed);
});

it('forbids a receptionist from starting service via the provider dashboard component', function () {
    actingAsRole(Role::Receptionist);

    $ticket = QueueTicket::factory()->called()->create();

    Livewire::test('dashboard.provider')
        ->call('start', $ticket->id)
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Called);
});

it('surfaces an illegal transition as a message instead of an error', function () {
    actingAsRole(Role::Provider);

    $ticket = QueueTicket::factory()->create(); // still Waiting — start is illegal

    Livewire::test('dashboard.provider')
        ->call('start', $ticket->id)
        ->assertSet('errorMessage', fn (?string $message): bool => $message !== null);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
});
