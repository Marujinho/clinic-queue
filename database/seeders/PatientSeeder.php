<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Seed a realistic set of patients: mostly active with a handful inactive.
     */
    public function run(): void
    {
        Patient::factory()->count(14)->create();

        Patient::factory()->count(4)->inactive()->create();
    }
}
