<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\QueueTicket;
use Database\Seeders\FoundationSeeder;
use Database\Seeders\QueueSeeder;

it('seeds the four clinic queues standalone', function () {
    $this->seed(QueueSeeder::class);

    expect(Queue::count())->toBe(4)
        ->and(Queue::where('name', 'General Consultation')->sole()->priority_enabled)->toBeTrue()
        ->and(Queue::where('name', 'Emergency')->sole()->priority_enabled)->toBeTrue()
        ->and(Queue::where('name', 'Cardiology')->sole()->priority_enabled)->toBeFalse()
        ->and(Queue::where('name', 'Pediatrics')->sole()->priority_enabled)->toBeFalse()
        ->and(Queue::where('active', true)->count())->toBe(4);
});

it('seeds tickets in varied states with varied priorities', function () {
    $this->seed(QueueSeeder::class);

    expect(QueueTicket::where('status', TicketStatus::Waiting)->count())->toBeGreaterThanOrEqual(3)
        ->and(QueueTicket::where('status', TicketStatus::Called)->count())->toBe(1)
        ->and(QueueTicket::where('status', TicketStatus::InService)->count())->toBe(1)
        ->and(QueueTicket::query()->get()->pluck('priority')->unique()->count())->toBeGreaterThanOrEqual(2);
});

it('respects BR-05: no patient holds more than one active ticket', function () {
    $this->seed(QueueSeeder::class);

    $activePerPatient = QueueTicket::query()
        ->whereIn('status', [TicketStatus::Waiting, TicketStatus::Called, TicketStatus::InService])
        ->get()
        ->groupBy('patient_id')
        ->map->count();

    expect($activePerPatient->max())->toBe(1);
});

it('numbers seeded tickets sequentially per queue', function () {
    $this->seed(QueueSeeder::class);

    $general = Queue::where('name', 'General Consultation')->sole();

    expect($general->queueTickets()->orderBy('id')->pluck('ticket_number')->all())
        ->toBe(['A001', 'A002', 'A003', 'A004']);
});

it('links queues to departments when run after the foundation seeder', function () {
    $this->seed(FoundationSeeder::class);
    $this->seed(QueueSeeder::class);

    expect(Queue::where('name', 'Cardiology')->sole()->department?->name)->toBe('Cardiology')
        ->and(Queue::where('name', 'Emergency')->sole()->department?->name)->toBe('Emergency');
});

it('reuses existing active patients instead of always creating new ones', function () {
    Patient::factory()->count(12)->create();

    $before = Patient::count();

    $this->seed(QueueSeeder::class);

    expect(Patient::count())->toBe($before)
        ->and(QueueTicket::count())->toBe(9);
});

it('can run twice without violating BR-05', function () {
    $this->seed(QueueSeeder::class);
    $this->seed(QueueSeeder::class);

    $activePerPatient = QueueTicket::query()
        ->whereIn('status', [TicketStatus::Waiting, TicketStatus::Called, TicketStatus::InService])
        ->get()
        ->groupBy('patient_id')
        ->map->count();

    expect(Queue::count())->toBe(4)
        ->and($activePerPatient->max())->toBe(1);
});
