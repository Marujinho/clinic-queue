<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Models\Patient;
use App\Services\Appointment\AppointmentScheduler;
use Database\Seeders\AppointmentSeeder;

it('seeds around twenty appointments across all statuses', function () {
    $this->seed(AppointmentSeeder::class);

    expect(Appointment::count())->toBe(20);

    foreach (AppointmentStatus::cases() as $status) {
        expect(Appointment::where('status', $status)->count())->toBeGreaterThan(0);
    }
});

it('seeds without violating BR-03', function () {
    $this->seed(AppointmentSeeder::class);

    $active = Appointment::query()
        ->whereNotIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::NoShow])
        ->get()
        ->groupBy('healthcare_provider_id');

    foreach ($active as $appointments) {
        $times = $appointments->pluck('scheduled_at')->sort()->values();

        for ($i = 1; $i < $times->count(); $i++) {
            $gap = abs($times[$i]->diffInMinutes($times[$i - 1]));

            expect($gap)->toBeGreaterThanOrEqual(AppointmentScheduler::SLOT_MINUTES);
        }
    }
});

it('seeds independently by creating patients and providers when none exist', function () {
    expect(Patient::count())->toBe(0)
        ->and(HealthcareProvider::count())->toBe(0);

    $this->seed(AppointmentSeeder::class);

    expect(Patient::count())->toBeGreaterThan(0)
        ->and(HealthcareProvider::count())->toBeGreaterThan(0)
        ->and(Appointment::count())->toBe(20);
});

it('reuses existing active patients and providers', function () {
    $patients = Patient::factory()->count(8)->create();
    $providers = HealthcareProvider::factory()->count(4)->create();

    $this->seed(AppointmentSeeder::class);

    expect(Patient::count())->toBe(8)
        ->and(HealthcareProvider::count())->toBe(4)
        ->and(Appointment::whereNotIn('patient_id', $patients->pluck('id'))->count())->toBe(0)
        ->and(Appointment::whereNotIn('healthcare_provider_id', $providers->pluck('id'))->count())->toBe(0);
});
