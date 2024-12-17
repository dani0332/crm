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
            QuoteStatusSeeder::class,
            LookupSeeder::class,
            //DocumentTypeSeeder::class,
            // SendUpdateAdditionalSubType::class,
            InsurerTaxInvoicePermissionsSeeder::class,
            SendUpdatePermission::class,
            GenericPermissionSeeder::class,
            SendUpdateAdditionalSubType::class,
            PermissionsSeeder::class,
            AllianceNationalitySeeder::class,
        ]);
    }
}
