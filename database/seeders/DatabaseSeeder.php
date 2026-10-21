<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Foundation data (users, clinic, departments, rooms) goes first; the domain
     * seeders depend on it.
     */
    public function run(): void
    {
        $this->call([
            FoundationSeeder::class,
            PatientSeeder::class,
            ProviderSeeder::class,
            AppointmentSeeder::class,
            QueueSeeder::class,
        ]);
    }
}
