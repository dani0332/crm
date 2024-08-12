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
            LookupSeeder::class,
            // LostReasonsTableSeeder::class,
            // AddGenericRolePermissionSeeder::class,
            // addDubaiNowEmailGroup::class,
            // DubaiLeadSource::class,
            // ActivitySchedulesSeeder::class,
            DocumentTypeSeeder::class,
            //            AddNewDocumentTypesSeeder::class,
            // UpdateCustomerToHealthAndTravelMemberDetails::class,
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
            // MapBusinessTypeOfInsuranceIdsFromBusinessQuotesToPersonalQuotesTableSeeder::class,
            // BusinessTypeInsuranceSeeder::class,
            // MarineSeeder::class,
            // ImcrmUsersRolesCleaner::class,
            // AddeTicketDocumentTypeSeeder::class,
            // DocumentVerifyPermissionSeeder::class,
            AddLOBsClaimHistoryOptionsSeeder::class,
            AddRenewalTemplateStorageSeeder::class,
            CarMakeSeeder::class,
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
            addPermissionsForInsurerNowPayment::class,
            AddeTicketDocumentTypeSeeder::class,
            DocumentVerifyPermissionSeeder::class,
            DepartmentPermissionSeeder::class,
            DocumentTypeAuditRecordSU::class, // Add new Document type for send update - Audit Records
        ]);
    }
}
