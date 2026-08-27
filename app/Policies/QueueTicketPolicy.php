<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Maps each ticket lifecycle ability onto the matching spec §6 gate:
 * create→check-in, call→call-patient, start→start-service,
 * complete→complete-service, cancel→cancel-ticket (BR-07).
 */
class QueueTicketPolicy
{
    /**
     * Determine whether the user can check a patient in (create a ticket).
     */
    public function create(User $user): Response
    {
        return $this->gate($user, 'check-in');
    }

    /**
     * Determine whether the user can call the ticket's patient.
     */
    public function call(User $user, QueueTicket $ticket): Response
    {
        return $this->gate($user, 'call-patient');
    }

    /**
     * Determine whether the user can start service for the ticket.
     */
    public function start(User $user, QueueTicket $ticket): Response
    {
        return $this->gate($user, 'start-service');
    }

    /**
     * Determine whether the user can complete service for the ticket.
     */
    public function complete(User $user, QueueTicket $ticket): Response
    {
        return $this->gate($user, 'complete-service');
    }

    /**
     * Determine whether the user can cancel the ticket.
     */
    public function cancel(User $user, QueueTicket $ticket): Response
    {
        return $this->gate($user, 'cancel-ticket');
    }

    private function gate(User $user, string $ability): Response
    {
        return $user->can($ability)
            ? Response::allow()
            : Response::deny();
    }
}
