<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Department;
use App\Models\Queue;
use App\Models\QueueTicket;
use Livewire\Livewire;

it('allows only admins on /queues', function () {
    actingAsRole(Role::Admin);
    test()->get('/queues')->assertOk();

    actingAsRole(Role::Receptionist);
    test()->get('/queues')->assertForbidden();

    actingAsRole(Role::Provider);
    test()->get('/queues')->assertForbidden();
});

it('lists queues with their waiting counts', function () {
    actingAsRole(Role::Admin);

    $queue = Queue::factory()->create(['name' => 'Cardiology Fast Track']);
    QueueTicket::factory()->for($queue)->count(2)->create();
    QueueTicket::factory()->for($queue)->completed()->create();

    Livewire::test('pages::queue.index')
        ->assertSee('Cardiology Fast Track')
        ->assertSee('2');
});

it('lets an admin create a queue', function () {
    actingAsRole(Role::Admin);

    $department = Department::factory()->create();

    Livewire::test('queue.form')
        ->set('name', 'Emergency')
        ->set('description', 'Urgent triage')
        ->set('priority_enabled', true)
        ->set('active', true)
        ->set('department_id', $department->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('queue-saved');

    expect(Queue::where('name', 'Emergency')->sole())
        ->priority_enabled->toBeTrue()
        ->department_id->toBe($department->id);
});

it('lets an admin edit a queue', function () {
    actingAsRole(Role::Admin);

    $queue = Queue::factory()->create(['name' => 'Old Name', 'priority_enabled' => false]);

    Livewire::test('queue.form', ['queueId' => $queue->id])
        ->set('name', 'New Name')
        ->set('priority_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($queue->fresh())
        ->name->toBe('New Name')
        ->priority_enabled->toBeTrue();
});

it('validates the queue name', function () {
    actingAsRole(Role::Admin);

    Livewire::test('queue.form')
        ->set('name', 'X')
        ->call('save')
        ->assertHasErrors(['name' => 'min']);
});

it('forbids a receptionist from saving a queue', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test('queue.form')
        ->set('name', 'Sneaky Queue')
        ->call('save')
        ->assertForbidden();

    expect(Queue::count())->toBe(0);
});

it('toggles a queue active state', function () {
    actingAsRole(Role::Admin);

    $queue = Queue::factory()->create(['active' => true]);

    Livewire::test('pages::queue.index')
        ->call('toggleActive', $queue->id);

    expect($queue->fresh()->active)->toBeFalse();

    Livewire::test('pages::queue.index')
        ->call('toggleActive', $queue->id);

    expect($queue->fresh()->active)->toBeTrue();
});
