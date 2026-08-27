<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use InvalidArgumentException;

/**
 * Enforces the appointment status lifecycle. Only the transitions declared in
 * {@see self::TRANSITIONS} are permitted; anything else is rejected.
 *
 * Allowed:
 *   scheduled   -> confirmed | cancelled | no_show
 *   confirmed   -> in_progress | cancelled | no_show
 *   in_progress -> completed
 */
class AppointmentTransitioner
{
    /**
     * @var array<string, list<AppointmentStatus>>
     */
    private const array TRANSITIONS = [
        AppointmentStatus::Scheduled->value => [
            AppointmentStatus::Confirmed,
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
        ],
        AppointmentStatus::Confirmed->value => [
            AppointmentStatus::InProgress,
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
        ],
        AppointmentStatus::InProgress->value => [
            AppointmentStatus::Completed,
        ],
    ];

    public function canTransition(AppointmentStatus $from, AppointmentStatus $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /**
     * @return list<AppointmentStatus>
     */
    public function allowedTransitions(AppointmentStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    /**
     * Apply and persist a status transition.
     *
     * @throws InvalidArgumentException when the transition is not allowed.
     */
    public function transition(Appointment $appointment, AppointmentStatus $to): Appointment
    {
        if (! $this->canTransition($appointment->status, $to)) {
            throw new InvalidArgumentException(
                "Cannot transition appointment from {$appointment->status->value} to {$to->value}."
            );
        }

        $appointment->status = $to;
        $appointment->save();

        return $appointment;
    }
}
