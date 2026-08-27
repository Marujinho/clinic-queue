<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Queue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Queue configuration is an admin-only concern: every ability maps to the
 * `manage-queues` gate (admins pass via Gate::before).
 */
class QueuePolicy
{
    /**
     * Determine whether the user can view any queues.
     */
    public function viewAny(User $user): Response
    {
        return $this->manage($user);
    }

    /**
     * Determine whether the user can view the queue.
     */
    public function view(User $user, Queue $queue): Response
    {
        return $this->manage($user);
    }

    /**
     * Determine whether the user can create queues.
     */
    public function create(User $user): Response
    {
        return $this->manage($user);
    }

    /**
     * Determine whether the user can update the queue.
     */
    public function update(User $user, Queue $queue): Response
    {
        return $this->manage($user);
    }

    /**
     * Determine whether the user can delete the queue.
     */
    public function delete(User $user, Queue $queue): Response
    {
        return $this->manage($user);
    }

    private function manage(User $user): Response
    {
        return $user->can('manage-queues')
            ? Response::allow()
            : Response::deny();
    }
}
