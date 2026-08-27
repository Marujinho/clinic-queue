<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\Appointment\AppointmentTransitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->transitioner = new AppointmentTransitioner;
});

it('allows every valid transition', function (AppointmentStatus $from, AppointmentStatus $to) {
    expect($this->transitioner->canTransition($from, $to))->toBeTrue();
})->with([
    'scheduled → confirmed' => [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed],
    'confirmed → in_progress' => [AppointmentStatus::Confirmed, AppointmentStatus::InProgress],
    'in_progress → completed' => [AppointmentStatus::InProgress, AppointmentStatus::Completed],
    'scheduled → cancelled' => [AppointmentStatus::Scheduled, AppointmentStatus::Cancelled],
    'confirmed → cancelled' => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
    'scheduled → no_show' => [AppointmentStatus::Scheduled, AppointmentStatus::NoShow],
    'confirmed → no_show' => [AppointmentStatus::Confirmed, AppointmentStatus::NoShow],
]);

it('rejects invalid transitions', function (AppointmentStatus $from, AppointmentStatus $to) {
    expect($this->transitioner->canTransition($from, $to))->toBeFalse();
})->with([
    'scheduled → completed' => [AppointmentStatus::Scheduled, AppointmentStatus::Completed],
    'scheduled → in_progress' => [AppointmentStatus::Scheduled, AppointmentStatus::InProgress],
    'in_progress → cancelled' => [AppointmentStatus::InProgress, AppointmentStatus::Cancelled],
    'in_progress → no_show' => [AppointmentStatus::InProgress, AppointmentStatus::NoShow],
    'completed → cancelled' => [AppointmentStatus::Completed, AppointmentStatus::Cancelled],
    'completed → scheduled' => [AppointmentStatus::Completed, AppointmentStatus::Scheduled],
    'cancelled → confirmed' => [AppointmentStatus::Cancelled, AppointmentStatus::Confirmed],
    'no_show → completed' => [AppointmentStatus::NoShow, AppointmentStatus::Completed],
]);

it('persists a valid transition', function () {
    $appointment = Appointment::factory()->create();

    $this->transitioner->transition($appointment, AppointmentStatus::Confirmed);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});

it('throws and does not persist an invalid transition', function () {
    $appointment = Appointment::factory()->completed()->create();

    expect(fn () => $this->transitioner->transition($appointment, AppointmentStatus::Cancelled))
        ->toThrow(InvalidArgumentException::class);

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);
});
