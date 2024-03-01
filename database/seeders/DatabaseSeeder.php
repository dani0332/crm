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
            QuoteStatusTableSeeder::class,
            // UtmLeadsSalesReportSeeder::class,
            // CarLostStorageSeeder::class,
            // AddPersonalLobsProducts::class,
            // addCarQuoteNewStatuses::class,
            // CommercialKeywordsSeeder::class,
            //            RuleTypeSeeder::class,
            //            CommercialMakeModelKeywordsPermissionsSeeder::class,
            //            FetchRuleDetailsTableRecordsFromRuleLeadSourceSeeder::class,
            //            ApplicationStorageSeeder::class,
            // SlabsTableSeeder::class,
            // AddPersonalLobsProducts::class,
            // addTeamThresholdViewPermission::class,
            // PetBikeMigrationSeeder::class
            // InsurerQuoteTypeMappingSeeder::class,
            //            addTeamAllocationThresholdViewPermission::class,
            //            addQuoteAllocationSwitch::class,
            //            CreateAndAssignSubTeamsToAdvisors::class,
            //            AddApiLogViewToPermssion::class,
            //            AutofollowupSeeder::class,
            //            addReassignmentTime::class,
            //            GenericPermissionSeeder::class,
            //            EmbeddedProductRoleAndPermissionSeeder::class,
            //            addDubaiNowLeadSourceExemptionInAppStorage::class,
            // LookupSeeder::class,
            // addDubaiNowEmailGroup::class,
            // DubaiLeadSource::class,
            LstDocumentsSeeder::class,
            // SendPolicyApplicationStorageSeeder::class,
            // BookPolicyPermissionSeeder::class,
            //            AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
            PaymentMethodsAddSeeder::class,
            AddNewDocumentTypeSeeder::class,
            PaymentStatusAddSeeder::class,
            updateDocTypePayment::class,
            //PaymentsMoveInNewTableStructure::class,
            LookupSeeder::class,
            //            AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
            // AddSendUpdatesCategoriesInLookups::class,
            // AddNewQuoteStatues::class,
            // AddCreateSendUpdatePermissionToAllRoles::class,
            addDubaiNowEmailGroup::class,
            DubaiLeadSource::class,
            AddPolicyIssuanceStatuses::class,
            GenericPermissionSeeder::class,
        ]);
    }
}
