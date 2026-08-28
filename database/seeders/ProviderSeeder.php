<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    /**
     * Seed a representative set of providers across specialties, with a couple
     * of inactive ones to exercise the status filter and toggle.
     */
    public function run(): void
    {
        $specialties = [
            'Cardiology',
            'Pediatrics',
            'General Practice',
            'Dermatology',
            'Orthopedics',
            'Cardiology',
            'Pediatrics',
            'General Practice',
        ];

        foreach ($specialties as $index => $specialty) {
            HealthcareProvider::factory()
                ->when($index >= 6, fn ($factory) => $factory->inactive())
                ->create(['specialty' => $specialty]);
        }
    }
}
