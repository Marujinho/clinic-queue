<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Queue>
 */
class QueueFactory extends Factory
{
    protected $model = Queue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'General Consultation',
                'Cardiology',
                'Pediatrics',
                'Emergency',
            ]),
            'description' => fake()->sentence(),
            'priority_enabled' => true,
            'active' => true,
            'department_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }
}
