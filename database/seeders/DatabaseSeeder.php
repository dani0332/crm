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
            LookupSeeder::class,
            SavingsQuoteDataSeeder::class,
            // ILAGMPermissionSeeder::class,
            // TravelRenewalTeamSeeder::class,
            // QuoteTypeTableSeeder::class,
            DocumentTypesSeeder::class,
            // CommercialCarPlanSeeder::class,
            // RuleNameSeeder::class,
            // BusinessActivitiesSeeder::class,
            // PaymentMethodsAddSeeder::class,
            PermissionSeeder::class,
            AiAdvisorSeeder::class,
            // CarAdditionalDetailsForLivaSeeder::class,
            CarAdditionalDetailsForLivaSeeder::class,
            CarGIGVehicleTransactionDriverDetailsForAMLSeeder::class,
            CPARulesSeeder::class,
            BorDocumentSeeder::class,
            AutomationSeeder::class,
            TravelLeadAllocationDashboardSeeder::class,
            SendUpdateNotesSeeder::class,
        ]);
    }
}
