<?php

declare(strict_types=1);

use App\Models\Queue;
use App\Models\QueueTicket;
use App\Services\Queue\TicketNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts each queue at A001', function () {
    $queue = Queue::factory()->create();

    expect((new TicketNumberGenerator)->next($queue))->toBe('A001');
});

it('increments sequentially within the same queue and day', function () {
    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->create(['ticket_number' => 'A001', 'checked_in_at' => now()]);

    expect((new TicketNumberGenerator)->next($queue))->toBe('A002');

    QueueTicket::factory()->for($queue)->create(['ticket_number' => 'A002', 'checked_in_at' => now()]);

    expect((new TicketNumberGenerator)->next($queue))->toBe('A003');
});

it('keeps sequences independent per queue', function () {
    $queueA = Queue::factory()->create(['name' => 'General Consultation']);
    $queueB = Queue::factory()->create(['name' => 'Cardiology']);

    QueueTicket::factory()->for($queueA)->count(3)->create(['checked_in_at' => now()]);

    expect((new TicketNumberGenerator)->next($queueA))->toBe('A004')
        ->and((new TicketNumberGenerator)->next($queueB))->toBe('A001');
});

it("resets daily: yesterday's tickets don't bump today's sequence", function () {
    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->count(5)->create(['checked_in_at' => now()->subDay()]);

    expect((new TicketNumberGenerator)->next($queue))->toBe('A001');
});

it('zero-pads the sequence to three digits', function () {
    $queue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->count(9)->create(['checked_in_at' => now()]);

    expect((new TicketNumberGenerator)->next($queue))->toBe('A010');
});
