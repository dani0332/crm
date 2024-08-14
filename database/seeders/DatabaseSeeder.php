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
            // SICFollowupEmailTemplateIDSeeder::class,
            // QuoteStatusTableSeeder::class,
            // RenewalsPermissionSeeder::class,
            // addCarQuoteSearchPermission::class,
            // QuoteStatusTableSeeder::class,
            // UtmLeadsSalesReportSeeder::class,
            // CarLostStorageSeeder::class,
            // AddPersonalLobsProducts::class,
            // addCarQuoteNewStatuses::class,
            // CommercialKeywordsSeeder::class,
            // RuleTypeSeeder::class,
            // CommercialMakeModelKeywordsPermissionsSeeder::class,
            // FetchRuleDetailsTableRecordsFromRuleLeadSourceSeeder::class,
            // ApplicationStorageSeeder::class,
            // SlabsTableSeeder::class,
            // AddPersonalLobsProducts::class,
            // addTeamThresholdViewPermission::class,
            // PetBikeMigrationSeeder::class
            // InsurerQuoteTypeMappingSeeder::class,
            // addTeamAllocationThresholdViewPermission::class,
            // addQuoteAllocationSwitch::class,
            // CreateAndAssignSubTeamsToAdvisors::class,
            // AddApiLogViewToPermssion::class,
            // AutofollowupSeeder::class,
            // addReassignmentTime::class,
            // GenericPermissionSeeder::class,
            // addDubaiNowLeadSourceExemptionInAppStorage::class,
            // LookupSeeder::class,
            // LostReasonsTableSeeder::class,
            // AddGenericRolePermissionSeeder::class,
            // addDubaiNowEmailGroup::class,
            // DubaiLeadSource::class,
            // ActivitySchedulesSeeder::class,
            DocumentTypeSeeder::class,
            //            AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
            GenericPermissionSeeder::class,
            // UpdateOldTeamNamesSeeder::class,
            // AddCapAdvisorPermissionSeeder::class,
            // UpdateLeadAllocationByQuoteId::class,
            // AddLegacyPaymentsPermssion::class,
            ApplicationStorageSeeder::class,
            /*addSICWorkflow::class,
            UpdateRenewalTemplateStorageSeeder::class,
            addDubaiNowEmailGroup::class,
            DubaiLeadSource::class,*/
            // AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
            /* PaymentMethodsAddSeeder::class,
            AddNewDocumentTypeSeeder::class,
            PaymentStatusAddSeeder::class,
            updateDocTypePayment::class,
            AddSageFlagApplicationStorage::class,
            AddTempUpdateTotalPricePermission::class,
            PaymentLookupSeeder::class,
            InsuranceQuoteTypeSeeder::class,
            AddPaymentPermissionsSeeder::class,
            TotalPremiumReportPermissionSeeder::class,*/

            // dtt seeder
            // AddDttFlagApplicationStorage::class,
            // DttOCBNewBusinessSeeder::class,
            // RevivalConversionReportPermissionSeeder::class,
            // end
            // AddCrossLOBSeeder::class,
            // MapBusinessTypeOfInsuranceIdsFromBusinessQuotesToPersonalQuotesTableSeeder::class,
            // BusinessTypeInsuranceSeeder::class,
            // MarineSeeder::class,
            // ImcrmUsersRolesCleaner::class,
            // AddeTicketDocumentTypeSeeder::class,
            // DocumentVerifyPermissionSeeder::class,
            AddLOBsClaimHistoryOptionsSeeder::class,
            AddRenewalTemplateStorageSeeder::class,
            CarMakeSeeder::class,
        ]);
    }
}
