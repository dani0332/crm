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
            // LookupSeeder::class,
            // LostReasonsTableSeeder::class,
            // AddGenericRolePermissionSeeder::class,
            // addDubaiNowEmailGroup::class,
            // DubaiLeadSource::class,
            // ActivitySchedulesSeeder::class,
            // DocumentTypeSeeder::class,
            // GenericPermissionSeeder::class,
            // ApplicationStorageSeeder::class,
            // AddPaymentPermissions::class,
            // AddCreateSendUpdatePermissionToAllRoles::class,
            // SendUpdateDocumentTypesSeeder::class,
            // AddSendUpdatesCategoriesInLookups::class,
            // InslyRoles::class,
            // InslyPermissions::class,
            DocumentTypeSeeder::class,
        ]);
    }
}
