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
            HealthRevivalQuotesSeeder::class,
            QuoteStatusSeeder::class,
            LookupSeeder::class,
            PermissionsSeeder::class,
            //DocumentTypeSeeder::class,
            // SendUpdateAdditionalSubType::class,
            // SendUpdateAdditionalSubType::class,
            // PermissionsSeeder::class,
            // AllianceNationalitySeeder::class,
            // SUAdditionalCRNSubTypesSeeder::class,
            RuleNameSeeder::class,
        ]);
    }
}
