<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Department;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the cross-cutting reference data every domain relies on: one clinic with
 * its departments and rooms, plus a user for each role (all password: "password").
 */
class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedClinic();
    }

    private function seedUsers(): void
    {
        $users = [
            ['name' => 'Alice Admin', 'email' => 'admin@clinic.test', 'role' => Role::Admin],
            ['name' => 'Rita Reception', 'email' => 'receptionist@clinic.test', 'role' => Role::Receptionist],
            ['name' => 'Dr. Paul Provider', 'email' => 'provider@clinic.test', 'role' => Role::Provider],
        ];

        foreach ($users as $attributes) {
            User::query()->updateOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'role' => $attributes['role'],
                    'active' => true,
                    'password' => Hash::make('password'),
                ],
            );
        }
    }

    private function seedClinic(): void
    {
        $clinic = Clinic::query()->firstOrCreate(
            ['name' => 'São Paulo Medical Center'],
            ['address' => '123 Avenida Paulista, São Paulo'],
        );

        $departments = ['Cardiology', 'Pediatrics', 'General Practice', 'Emergency'];

        foreach ($departments as $index => $name) {
            $department = Department::query()->firstOrCreate([
                'clinic_id' => $clinic->id,
                'name' => $name,
            ]);

            // Two rooms per department (e.g. Room 101 / 102 for the first dept).
            $base = 101 + ($index * 100);
            foreach ([$base, $base + 1] as $number) {
                Room::query()->firstOrCreate([
                    'department_id' => $department->id,
                    'number' => (string) $number,
                ], [
                    'name' => 'Room',
                    'active' => true,
                ]);
            }
        }
    }
}
