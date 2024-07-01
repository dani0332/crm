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
            LookupSeeder::class,
            // LostReasonsTableSeeder::class,
            // AddGenericRolePermissionSeeder::class,
            // addDubaiNowEmailGroup::class,
            // DubaiLeadSource::class,
            // ActivitySchedulesSeeder::class,
            GenericPermissionSeeder::class,
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
            MapBusinessTypeOfInsuranceIdsFromBusinessQuotesToPersonalQuotesTableSeeder::class,
            // BusinessTypeInsuranceSeeder::class,
            // MarineSeeder::class,

            AddPaymentPermissions::class,
            AddCreateSendUpdatePermissionToAllRoles::class,
            // AddSendUpdatesCategoriesInLookups::class, Please don't run this seeder on test and stage env.
            InslyRoles::class,
            InslyPermissions::class,
            QuoteStatusMapSeeder::class,
            QuoteStatusSeeder::class,
            ApplicationStorageSeeder::class,
            addInsuranceProvidersConfiguration::class,
            RevokeTempPaymentUpdatePermissionsSeeder::class,
            ImcrmUsersRolesCleaner::class,
        ]);
    }
}
