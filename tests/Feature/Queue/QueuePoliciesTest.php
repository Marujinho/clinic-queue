<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Models\User;

it('maps QueuePolicy abilities to the manage-queues gate', function (Role $role, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);
    $queue = Queue::factory()->create();

    expect($user->can('viewAny', Queue::class))->toBe($allowed)
        ->and($user->can('view', $queue))->toBe($allowed)
        ->and($user->can('create', Queue::class))->toBe($allowed)
        ->and($user->can('update', $queue))->toBe($allowed)
        ->and($user->can('delete', $queue))->toBe($allowed);
})->with([
    'admin' => [Role::Admin, true],
    'receptionist' => [Role::Receptionist, false],
    'provider' => [Role::Provider, false],
]);

it('maps QueueTicketPolicy abilities to the ticket gates (BR-07)', function (Role $role, array $expected) {
    $user = User::factory()->create(['role' => $role]);
    $ticket = QueueTicket::factory()->create();

    expect($user->can('create', QueueTicket::class))->toBe($expected['create'])
        ->and($user->can('call', $ticket))->toBe($expected['call'])
        ->and($user->can('start', $ticket))->toBe($expected['start'])
        ->and($user->can('complete', $ticket))->toBe($expected['complete'])
        ->and($user->can('cancel', $ticket))->toBe($expected['cancel']);
})->with([
    'admin' => [Role::Admin, ['create' => true, 'call' => true, 'start' => true, 'complete' => true, 'cancel' => true]],
    'receptionist' => [Role::Receptionist, ['create' => true, 'call' => false, 'start' => false, 'complete' => false, 'cancel' => true]],
    'provider' => [Role::Provider, ['create' => false, 'call' => true, 'start' => true, 'complete' => true, 'cancel' => true]],
]);
