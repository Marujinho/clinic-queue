<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Services\Queue\InvalidTicketTransition;
use App\Services\Queue\TicketStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->machine = new TicketStateMachine;
});

it('calls a waiting ticket and stamps called_at', function () {
    $ticket = QueueTicket::factory()->create();

    $this->machine->call($ticket);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Called)
        ->called_at->not->toBeNull();
});

it('starts a called ticket and stamps service_started_at', function () {
    $ticket = QueueTicket::factory()->called()->create();

    $this->machine->start($ticket);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::InService)
        ->service_started_at->not->toBeNull();
});

it('completes an in-service ticket and stamps completed_at', function () {
    $ticket = QueueTicket::factory()->inService()->create();

    $this->machine->complete($ticket);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Completed)
        ->completed_at->not->toBeNull();
});

it('runs the full happy path call → start → complete', function () {
    $ticket = QueueTicket::factory()->create();

    $this->machine->call($ticket);
    $this->machine->start($ticket);
    $this->machine->complete($ticket);

    expect($ticket->fresh())
        ->status->toBe(TicketStatus::Completed)
        ->called_at->not->toBeNull()
        ->service_started_at->not->toBeNull()
        ->completed_at->not->toBeNull();
});

it('cancels a waiting ticket', function () {
    $ticket = QueueTicket::factory()->create();

    $this->machine->cancel($ticket);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Cancelled);
});

it('cancels a called ticket', function () {
    $ticket = QueueTicket::factory()->called()->create();

    $this->machine->cancel($ticket);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Cancelled);
});

it('rejects illegal transitions (BR-06)', function (string $factoryState, string $method) {
    $factory = QueueTicket::factory();

    if ($factoryState !== 'waiting') {
        $factory = $factory->{$factoryState}();
    }

    $ticket = $factory->create();

    expect(fn () => $this->machine->{$method}($ticket))
        ->toThrow(InvalidTicketTransition::class);
})->with([
    'call a called ticket' => ['called', 'call'],
    'call an in-service ticket' => ['inService', 'call'],
    'call a completed ticket' => ['completed', 'call'],
    'call a cancelled ticket' => ['cancelled', 'call'],
    'start a waiting ticket' => ['waiting', 'start'],
    'start a completed ticket' => ['completed', 'start'],
    'complete a waiting ticket' => ['waiting', 'complete'],
    'complete a called ticket' => ['called', 'complete'],
    'cancel an in-service ticket' => ['inService', 'cancel'],
    'cancel a completed ticket' => ['completed', 'cancel'],
    'cancel a cancelled ticket' => ['cancelled', 'cancel'],
]);

it('leaves the ticket untouched when a transition is rejected', function () {
    $ticket = QueueTicket::factory()->completed()->create();

    expect(fn () => $this->machine->cancel($ticket))->toThrow(InvalidTicketTransition::class)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Completed);
});

it('calls the next ticket by priority DESC then arrival ASC (BR-04)', function () {
    $queue = Queue::factory()->create();

    $normalEarly = QueueTicket::factory()->for($queue)->create(['checked_in_at' => now()->subMinutes(60)]);
    $urgentLate = QueueTicket::factory()->for($queue)->urgent()->create(['checked_in_at' => now()->subMinutes(5)]);
    $highMid = QueueTicket::factory()->for($queue)->highPriority()->create(['checked_in_at' => now()->subMinutes(30)]);

    expect($this->machine->callNext($queue)->id)->toBe($urgentLate->id)
        ->and($this->machine->callNext($queue)->id)->toBe($highMid->id)
        ->and($this->machine->callNext($queue)->id)->toBe($normalEarly->id);
});

it('breaks priority ties by earliest check-in (BR-04)', function () {
    $queue = Queue::factory()->create();

    $later = QueueTicket::factory()->for($queue)->create(['checked_in_at' => now()->subMinutes(10)]);
    $earlier = QueueTicket::factory()->for($queue)->create(['checked_in_at' => now()->subMinutes(45)]);

    expect($this->machine->callNext($queue)->id)->toBe($earlier->id);
});

it('only considers waiting tickets from the given queue for call-next', function () {
    $queue = Queue::factory()->create();
    $otherQueue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->called()->create();
    QueueTicket::factory()->for($queue)->inService()->create();
    QueueTicket::factory()->for($otherQueue)->urgent()->create(['checked_in_at' => now()->subHour()]);
    $waiting = QueueTicket::factory()->for($queue)->create(['checked_in_at' => now()]);

    expect($this->machine->callNext($queue)->id)->toBe($waiting->id);
});

it('returns null from call-next when nothing is waiting', function () {
    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->completed()->create();

    expect($this->machine->callNext($queue))->toBeNull();
});
