<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Defines the role-based permission matrix from the spec (§6). Admins may do
     * everything (see the Gate::before short-circuit); the individual gates below
     * describe what Receptionists and Providers may do. Domain Policies delegate
     * to these gates so authorization stays in one place.
     */
    public function boot(): void
    {
        // Admins can perform every gated action.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        /** @var array<string, list<Role>> $gates */
        $gates = [
            // Patients: admin + receptionist manage; providers have read-only (view).
            'manage-patients' => [Role::Receptionist],
            'view-patients' => [Role::Receptionist, Role::Provider],

            // Providers, queues and users: admin only (covered by Gate::before).
            'manage-providers' => [],
            'manage-queues' => [],
            'manage-users' => [],

            // Queue ticket operations.
            'check-in' => [Role::Receptionist],
            'call-patient' => [Role::Provider],
            'start-service' => [Role::Provider],
            'complete-service' => [Role::Provider],
            'cancel-ticket' => [Role::Receptionist, Role::Provider],

            // Appointments are managed by reception (and admin); providers may view.
            'manage-appointments' => [Role::Receptionist],
            'view-appointments' => [Role::Receptionist, Role::Provider],
        ];

        foreach ($gates as $ability => $roles) {
            Gate::define($ability, fn (User $user) => in_array($user->role, $roles, true));
        }
    }
}
