<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ApplicationStorageSeeder::class,
            RolePermissionSeeder::class,
            // HealthRevivalQuotesSeeder::class,
            // QuoteStatusSeeder::class,
            LookupSeeder::class,
            TravelRenewalTeamSeeder::class,
            PermissionsSeeder::class,
        ]);
    }
}
