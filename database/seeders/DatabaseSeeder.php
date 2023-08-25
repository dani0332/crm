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
            RenewalsPermissionSeeder::class,
            addCarQuoteSearchPermission::class,
            QuoteStatusTableSeeder::class,
            UtmLeadsSalesReportSeeder::class,
            SlabsTableSeeder::class,
            AddPersonalLobsProducts::class,
            // PetBikeMigrationSeeder::class
            InsurerQuoteTypeMappingSeeder::class,
            addTeamAllocationThresholdViewPermission::class,
        ]);
    }
}
