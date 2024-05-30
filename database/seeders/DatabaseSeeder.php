<?php

namespace Database\Seeders;

use Illuminate\Console\Application;
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
            LostReasonsTableSeeder::class,
            AddGenericRolePermissionSeeder::class,
            addDubaiNowEmailGroup::class,
            DubaiLeadSource::class,
            ActivitySchedulesSeeder::class,
            GenericPermissionSeeder::class,
            ApplicationStorageSeeder::class,
            AddPaymentPermissions::class,
            AddCreateSendUpdatePermissionToAllRoles::class,
            AddSendUpdatesCategoriesInLookups::class,
            InslyRoles::class,
            InslyPermissions::class,
            QuoteStatusMapSeeder::class,
            QuoteStatusSeeder::class,
            ApplicationStorageSeeder::class,
            addInsuranceProvidersConfiguration::class,
        ]);
    }
}
