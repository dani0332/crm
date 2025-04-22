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
            QuoteStatusSeeder::class,
            SUAdditionalCRNSubTypesSeeder::class,
            //DocumentTypeSeeder::class,
            // SendUpdateAdditionalSubType::class,
            LifeInsuranceTenureSeeder::class,
            // SendUpdateAdditionalSubType::class,
            // TravelRenewalTeamSeeder::class,
            ILAGMPermissionSeeder::class,
            // TravelRenewalTeamSeeder::class,
            LookupSeeder::class,
            RolePermissionSeeder::class,
            DocumentTypesSeeder::class,
        ]);
    }
}
