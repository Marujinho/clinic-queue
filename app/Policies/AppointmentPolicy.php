<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Authorization for the Appointment domain. Admins pass everything via the
 * Gate::before short-circuit; the abilities here map to the domain gates:
 *   - viewing   -> 'view-appointments'   (admin + receptionist + provider)
 *   - managing  -> 'manage-appointments' (admin + receptionist)
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-appointments');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('view-appointments');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-appointments');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('manage-appointments');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('manage-appointments');
    }

    public function transition(User $user, Appointment $appointment): bool
    {
        return $user->can('manage-appointments');
    }
}
