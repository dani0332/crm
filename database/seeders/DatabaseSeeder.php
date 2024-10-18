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
            AddSuperLeadStatusChangePermission::class,
            QuoteStatusSeeder::class,
            AddReApprovePaymentPermission::class,
            SendUpdateAdditionalTaxInvoice::class,
            DocumentTypeSeeder::class,
            PermissionsSeeder::class,
            GenericPermissionSeeder::class,
        ]);
    }
}
