<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Queue;
use App\Models\QueueTicket;
use Livewire\Livewire;

it('counts waiting, called, in-service and completed-today tickets', function () {
    actingAsRole(Role::Admin);

    QueueTicket::factory()->count(3)->create();
    QueueTicket::factory()->count(2)->called()->create();
    QueueTicket::factory()->inService()->create();
    QueueTicket::factory()->count(2)->completed()->create();
    QueueTicket::factory()->cancelled()->create();

    // Completed yesterday — must not count towards "completed today".
    QueueTicket::factory()->completed()->create(['completed_at' => now()->subDay()]);

    Livewire::test('queue.stats')
        ->assertViewHas('waiting', 3)
        ->assertViewHas('called', 2)
        ->assertViewHas('inService', 1)
        ->assertViewHas('completedToday', 2);
});

it('scopes the stats to a queue when one is given', function () {
    actingAsRole(Role::Admin);

    $queue = Queue::factory()->create();
    $otherQueue = Queue::factory()->create();

    QueueTicket::factory()->for($queue)->count(2)->create();
    QueueTicket::factory()->for($queue)->inService()->create();
    QueueTicket::factory()->for($otherQueue)->count(5)->create();

    Livewire::test('queue.stats', ['queue' => $queue])
        ->assertViewHas('waiting', 2)
        ->assertViewHas('inService', 1);
});

it('averages the wait between check-in and call', function () {
    actingAsRole(Role::Admin);

    QueueTicket::factory()->called()->create([
        'checked_in_at' => now()->subMinutes(30),
        'called_at' => now()->subMinutes(20), // 10 min wait
    ]);
    QueueTicket::factory()->completed()->create([
        'checked_in_at' => now()->subMinutes(60),
        'called_at' => now()->subMinutes(40), // 20 min wait
    ]);
    QueueTicket::factory()->create(['called_at' => null]); // still waiting — excluded

    Livewire::test('queue.stats')
        ->assertViewHas('averageWait', 15)
        ->assertSee('15 min');
});

it('shows a dash when no ticket has been called yet', function () {
    actingAsRole(Role::Admin);

    QueueTicket::factory()->count(2)->create();

    Livewire::test('queue.stats')
        ->assertViewHas('averageWait', null)
        ->assertSee('—');
});
