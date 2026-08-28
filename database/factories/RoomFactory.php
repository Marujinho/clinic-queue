<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => 'Room',
            'number' => (string) fake()->numberBetween(100, 499),
            'active' => true,
        ];
    }
}
