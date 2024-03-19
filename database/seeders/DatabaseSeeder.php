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
            LookupSeeder::class,
            //            AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
            DttOCBNewBusinessSeeder::class,
            GenericPermissionSeeder::class,
            HealthPlanTableElibilityIdSeeder::class,
        ]);
    }
}
