<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Encodes the spec §6 permission matrix. Each row asserts which roles are granted
 * an ability. Admin is granted everything via the Gate::before short-circuit.
 */
dataset('abilities', [
    // ability            => [admin, receptionist, provider]
    'manage-patients' => ['manage-patients', true, true, false],
    'view-patients' => ['view-patients', true, true, true],
    'manage-providers' => ['manage-providers', true, false, false],
    'manage-queues' => ['manage-queues', true, false, false],
    'manage-users' => ['manage-users', true, false, false],
    'check-in' => ['check-in', true, true, false],
    'call-patient' => ['call-patient', true, false, true],
    'start-service' => ['start-service', true, false, true],
    'complete-service' => ['complete-service', true, false, true],
    'cancel-ticket' => ['cancel-ticket', true, true, true],
    'manage-appointments' => ['manage-appointments', true, true, false],
]);

it('enforces the permission matrix', function (string $ability, bool $admin, bool $receptionist, bool $provider) {
    $users = [
        Role::Admin->value => User::factory()->admin()->create(),
        Role::Receptionist->value => User::factory()->receptionist()->create(),
        Role::Provider->value => User::factory()->provider()->create(),
    ];

    expect(Gate::forUser($users[Role::Admin->value])->allows($ability))->toBe($admin)
        ->and(Gate::forUser($users[Role::Receptionist->value])->allows($ability))->toBe($receptionist)
        ->and(Gate::forUser($users[Role::Provider->value])->allows($ability))->toBe($provider);
})->with('abilities');
