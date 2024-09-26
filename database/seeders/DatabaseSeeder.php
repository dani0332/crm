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
            addInsuranceProvidersConfiguration::class,
            HealthRevivalQuotesSeeder::class,
            RevokeTempPaymentUpdatePermissionsSeeder::class,
            ImcrmUsersRolesCleaner::class,
            RolePermissionSeeder::class,
            AddSuperLeadStatusChangePermission::class,
            AddReApprovePaymentPermission::class,
        ]);
    }
}
