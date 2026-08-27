<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthcareProvider;
use App\Models\User;

/**
 * Every provider action is gated by the single 'manage-providers' ability
 * (admin only — see AppServiceProvider). Admins short-circuit via Gate::before.
 */
class HealthcareProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-providers');
    }

    public function view(User $user, HealthcareProvider $provider): bool
    {
        return $user->can('manage-providers');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-providers');
    }

    public function update(User $user, HealthcareProvider $provider): bool
    {
        return $user->can('manage-providers');
    }

    public function delete(User $user, HealthcareProvider $provider): bool
    {
        return $user->can('manage-providers');
    }
}
