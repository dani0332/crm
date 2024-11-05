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
            ApplicationStorageSeeder::class,
            RolePermissionSeeder::class,
            HealthRevivalQuotesSeeder::class,
            AddSuperLeadStatusChangePermission::class,
            QuoteStatusSeeder::class,
            SendUpdateAdditionalTaxInvoice::class,
            //DocumentTypeSeeder::class,
            InsurerTaxInvoicePermissionsSeeder::class,
            SendUpdatePermission::class,
        ]);
    }
}
