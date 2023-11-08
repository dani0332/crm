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
            // RenewalsPermissionSeeder::class,
            // addCarQuoteSearchPermission::class,
            // QuoteStatusTableSeeder::class,
            // UtmLeadsSalesReportSeeder::class,
            // CarLostStorageSeeder::class,
            // AddPersonalLobsProducts::class,
            // addCarQuoteNewStatuses::class,
            // LookupSeeder::class,
            // CommercialKeywordsSeeder::class,
            RuleTypeSeeder::class,
            CommercialMakeModelKeywordsPermissionsSeeder::class,
            FetchRuleDetailsTableRecordsFromRuleLeadSourceSeeder::class,
            // SlabsTableSeeder::class,
            // AddPersonalLobsProducts::class,
            addTeamThresholdViewPermission::class,
            // PetBikeMigrationSeeder::class
            // InsurerQuoteTypeMappingSeeder::class,
            addTeamAllocationThresholdViewPermission::class,
            addQuoteAllocationSwitch::class,
            CreateAndAssignSubTeamsToAdvisors::class,
            AutofollowupSeeder::class,
            addReassignmentTime::class,
            // AddApiLogViewToPermssion::class,
            GenericPermissionSeeder::class,
            UpdateCustomerToHealthAndTravelMemberDetails::class,
            UpdateCodeColumnInCustomerTable::class,
            CustomerMembersTableSeeder::class,
        ]);
    }
}
