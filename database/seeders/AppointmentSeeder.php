<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Seed ~20 appointments across every status without violating BR-03: each
     * provider's appointments are spaced a full hour apart (tracked per provider),
     * so no two appointments for the same provider ever fall within ±30 minutes.
     *
     * Works after PatientSeeder/ProviderSeeder, but also stands alone — missing
     * patients or providers are created via factories.
     */
    public function run(): void
    {
        $patients = Patient::query()->active()->get();

        if ($patients->count() < 5) {
            $patients = $patients->concat(Patient::factory()->count(10)->create());
        }

        $providers = HealthcareProvider::query()->active()->get();

        if ($providers->count() < 3) {
            $providers = $providers->concat(
                HealthcareProvider::factory()->count(3 - $providers->count())->create()
            );
        }

        // Status mix: past statuses land on past days, upcoming ones on future days.
        $plan = [
            [AppointmentStatus::Completed, -2],
            [AppointmentStatus::Completed, -2],
            [AppointmentStatus::Completed, -1],
            [AppointmentStatus::Completed, -1],
            [AppointmentStatus::NoShow, -2],
            [AppointmentStatus::NoShow, -1],
            [AppointmentStatus::Cancelled, -1],
            [AppointmentStatus::Cancelled, 1],
            [AppointmentStatus::InProgress, 0],
            [AppointmentStatus::InProgress, 0],
            [AppointmentStatus::Confirmed, 0],
            [AppointmentStatus::Confirmed, 0],
            [AppointmentStatus::Confirmed, 1],
            [AppointmentStatus::Confirmed, 1],
            [AppointmentStatus::Scheduled, 1],
            [AppointmentStatus::Scheduled, 2],
            [AppointmentStatus::Scheduled, 2],
            [AppointmentStatus::Scheduled, 3],
            [AppointmentStatus::Scheduled, 3],
            [AppointmentStatus::Scheduled, 4],
        ];

        /** @var array<int, int> $slotByProvider */
        $slotByProvider = [];

        foreach ($plan as $index => [$status, $dayOffset]) {
            $provider = $providers[$index % $providers->count()];

            // Hourly slots per provider guarantee > 30 minutes between any two
            // of a provider's appointments, regardless of day or status (BR-03).
            $slot = $slotByProvider[$provider->id] ?? 0;
            $slotByProvider[$provider->id] = $slot + 1;

            Appointment::factory()->create([
                'patient_id' => $patients->random()->id,
                'healthcare_provider_id' => $provider->id,
                'scheduled_at' => Carbon::today()->addDays($dayOffset)->setTime(8, 0)->addHours($slot),
                'status' => $status,
            ]);
        }
    }
}
