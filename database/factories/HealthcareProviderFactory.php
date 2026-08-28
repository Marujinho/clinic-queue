<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthcareProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthcareProvider>
 */
class HealthcareProviderFactory extends Factory
{
    protected $model = HealthcareProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Dr. '.fake()->name(),
            'specialty' => fake()->randomElement([
                'Cardiology',
                'Pediatrics',
                'General Practice',
                'Dermatology',
                'Orthopedics',
            ]),
            'license_number' => 'CRM-'.fake()->unique()->numberBetween(100000, 999999),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }
}
