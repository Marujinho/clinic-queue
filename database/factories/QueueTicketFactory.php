<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueTicket>
 */
class QueueTicketFactory extends Factory
{
    protected $model = QueueTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_id' => Queue::factory(),
            'patient_id' => Patient::factory(),
            'appointment_id' => null,
            'ticket_number' => 'A'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Waiting,
            'checked_in_at' => now(),
            'called_at' => null,
            'service_started_at' => null,
            'completed_at' => null,
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TicketPriority::High,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TicketPriority::Urgent,
        ]);
    }

    public function called(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Called,
            'called_at' => now(),
        ]);
    }

    public function inService(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::InService,
            'called_at' => now()->subMinutes(5),
            'service_started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Completed,
            'called_at' => now()->subMinutes(20),
            'service_started_at' => now()->subMinutes(15),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Cancelled,
        ]);
    }
}
