<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Foundation data (users, clinic, departments, rooms) is always seeded first.
     * Each domain ships its own seeder; they are called here only if present, so
     * this file never needs editing when a domain is merged in.
     */
    public function run(): void
    {
        $this->call(FoundationSeeder::class);

        $domainSeeders = [
            PatientSeeder::class,
            ProviderSeeder::class,
            AppointmentSeeder::class,
            QueueSeeder::class,
        ];

        foreach ($domainSeeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
