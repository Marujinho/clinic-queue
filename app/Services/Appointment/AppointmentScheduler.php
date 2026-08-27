<?php

declare(strict_types=1);

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonInterface;

/**
 * Encapsulates the scheduling business rules for appointments.
 *
 * BR-03 (provider conflict): a provider cannot have two appointments in the same
 * time interval. Slots are treated as fixed-length windows of SLOT_MINUTES; a new
 * appointment conflicts with an existing (non-cancelled, non-no-show) one for the
 * same provider whose scheduled_at falls strictly within +/- SLOT_MINUTES.
 */
class AppointmentScheduler
{
    public const int SLOT_MINUTES = 30;

    /**
     * Determine whether scheduling an appointment for the given provider at the
     * given time would collide with an existing active appointment.
     *
     * @param  int|null  $ignoreId  Appointment id to exclude (the one being edited).
     */
    public function hasConflict(int $providerId, CarbonInterface $scheduledAt, ?int $ignoreId = null): bool
    {
        $start = $scheduledAt->copy()->subMinutes(self::SLOT_MINUTES);
        $end = $scheduledAt->copy()->addMinutes(self::SLOT_MINUTES);

        return Appointment::query()
            ->where('healthcare_provider_id', $providerId)
            ->whereNotIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::NoShow])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('scheduled_at', '>', $start)
            ->where('scheduled_at', '<', $end)
            ->exists();
    }
}
