<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['patient_id', 'healthcare_provider_id', 'scheduled_at', 'status', 'reason', 'notes'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<HealthcareProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(HealthcareProvider::class, 'healthcare_provider_id');
    }

    /**
     * @return HasOne<QueueTicket, $this>
     */
    public function queueTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class);
    }
}
