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
            PaymentMethodsSeeder::class,
            DocumentTypeSeeder::class,
            AddRoleForBetaUserSeeder::class,
            CustomerAdditionalContactSeeder::class,
            addRenewalAppSettings::class,
            SendPolicyApplicationStorageSeeder::class,
            AddRoleForMarketingSeeder::class,
            addPermissionForManualLeadTesting::class,
            QuoteTypeTableSeeder::class,
            AddRoleForRenewalsManager::class,
            addPermissionForCarAllocation::class,
            addLeadSourcesForAllocation::class,
            addCarLeadAllocationFetchStartDate::class,
            addAdvisorConvertionReportBatchStartDate::class,
            addCarAllocationMasterSwitch::class,
            addPermissionsForDashboard::class,
            //CarQuoteRequestSeeder::class,
            QuoteStatusTableSeeder::class,
            QuoteStatusMapCarTransactionApproved::class,
            UpdateApplicationStorage::class,
            addReportsMaxDays::class,
            addCostPerLeadForExistingLeads::class,
            addLMSIntroEmailTemplateId::class,
        ]);
    }
}
