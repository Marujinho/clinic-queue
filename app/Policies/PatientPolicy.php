<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    /**
     * Determine whether the user can view any patients.
     */
    public function viewAny(User $user): Response
    {
        return $user->can('view-patients')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Determine whether the user can view the patient.
     */
    public function view(User $user, Patient $patient): Response
    {
        return $user->can('view-patients')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Determine whether the user can create patients.
     */
    public function create(User $user): Response
    {
        return $user->can('manage-patients')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Determine whether the user can update the patient.
     */
    public function update(User $user, Patient $patient): Response
    {
        return $user->can('manage-patients')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Determine whether the user can delete the patient.
     */
    public function delete(User $user, Patient $patient): Response
    {
        return $user->can('manage-patients')
            ? Response::allow()
            : Response::deny();
    }
}
