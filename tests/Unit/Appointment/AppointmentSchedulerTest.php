<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\HealthcareProvider;
use App\Services\Appointment\AppointmentScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->scheduler = new AppointmentScheduler;
    $this->provider = HealthcareProvider::factory()->create();
    $this->base = Carbon::parse('2026-09-01 10:00:00');
});

it('detects a conflict for the same provider within 30 minutes (BR-03)', function () {
    Appointment::factory()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base,
    ]);

    expect($this->scheduler->hasConflict($this->provider->id, $this->base->copy()))->toBeTrue()
        ->and($this->scheduler->hasConflict($this->provider->id, $this->base->copy()->addMinutes(15)))->toBeTrue()
        ->and($this->scheduler->hasConflict($this->provider->id, $this->base->copy()->subMinutes(29)))->toBeTrue();
});

it('allows appointments 30 or more minutes apart', function () {
    Appointment::factory()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base,
    ]);

    expect($this->scheduler->hasConflict($this->provider->id, $this->base->copy()->addMinutes(30)))->toBeFalse()
        ->and($this->scheduler->hasConflict($this->provider->id, $this->base->copy()->subMinutes(30)))->toBeFalse()
        ->and($this->scheduler->hasConflict($this->provider->id, $this->base->copy()->addHours(2)))->toBeFalse();
});

it('allows overlapping times for a different provider', function () {
    Appointment::factory()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base,
    ]);

    $other = HealthcareProvider::factory()->create();

    expect($this->scheduler->hasConflict($other->id, $this->base->copy()))->toBeFalse();
});

it('ignores cancelled and no-show appointments', function () {
    Appointment::factory()->cancelled()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base,
    ]);

    Appointment::factory()->noShow()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base->copy()->addMinutes(10),
    ]);

    expect($this->scheduler->hasConflict($this->provider->id, $this->base->copy()))->toBeFalse();
});

it('excludes the appointment being edited via ignoreId', function () {
    $appointment = Appointment::factory()->create([
        'healthcare_provider_id' => $this->provider->id,
        'scheduled_at' => $this->base,
    ]);

    expect($this->scheduler->hasConflict($this->provider->id, $this->base->copy(), $appointment->id))->toBeFalse()
        ->and($this->scheduler->hasConflict($this->provider->id, $this->base->copy()))->toBeTrue();
});
