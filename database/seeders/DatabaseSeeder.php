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
            BuyLeadsRevivalPermissionSeeder::class,
            RolePermissionSeeder::class,
            UpdateRuleNameSeederCompany::class,
            // HealthRevivalQuotesSeeder::class,
            QuoteStatusSeeder::class,
            LookupSeeder::class,
            ClaimAllocationConfigManagersSeeder::class,
            ClaimStatusesSeeder::class, // ClaimStatusesSeeder is dependent on LookupSeeder
            SavingsQuoteDataSeeder::class,
            GenericDocumentTypesSeeder::class,
            CyberQuoteDataSeeder::class,
            CyberLeadAllocationSeeder::class,
            // ILAGMPermissionSeeder::class,
            // TravelRenewalTeamSeeder::class,
            // QuoteTypeTableSeeder::class,
            DocumentTypesSeeder::class,
            // CommercialCarPlanSeeder::class,
            // RuleNameSeeder::class,
            // BusinessActivitiesSeeder::class,
            // PaymentMethodsAddSeeder::class,
            PermissionSeeder::class,
            // AiAdvisorSeeder::class,
            // CarAdditionalDetailsForLivaSeeder::class,
            // CarAdditionalDetailsForLivaSeeder::class,
            CarGIGVehicleTransactionDriverDetailsForAMLSeeder::class,
            CPARulesSeeder::class,
            BorDocumentSeeder::class,
            AutomationSeeder::class,
            TravelLeadAllocationDashboardSeeder::class,
            ManagerLeadAllocationRoleSeeder::class,
            VehicleColorSeeder::class,
            SendUpdateNotesSeeder::class,
            NonApiInsurerVehicleColorSeeder::class,
            NonApiInsurerMortgageBySeeder::class,
            InsurancePlateCodeSeeder::class,
            UpdateTooltipCarDocuments::class,
            CarAdditionalDetailsForSukoonSeeder::class,
            QICTokioLookupSeeder::class,
            DeviceQuoteSeeder::class,
            DocRequiredForPolicySendSeeder::class,
            SendUpdateSeederForCyber::class,
            BranchSeeder::class,
            BranchOverrideConfigSeeder::class,
            SetPcpTeamAllocationThresholdDisabledSeeder::class,
            /*HealthTeamSeeder::class,
            NationalityPoolConfigSeeder::class,
            HealthNationalityGroupSeeder::class,
            CanonicalNationalitySeeder::class,
            HealthGroupNationalitySeeder::class,*/
            ClaimFormsGenericDocumentSeeder::class,
            AwnicNationalitySeeder::class,
            InsuranceProviderTransitionsSeeder::class,
            BackfillBusinessQuoteEmirateFromInsuredSeeder::class,
            ConverILAGroupMedicalConfigurationBranchWise::class,
            HealthCoverForSeeder::class,
            MemberCategorySeeder::class,
            VisaCategorySeeder::class,
            SalaryBandSeeder::class,
            MaritalStatusSeeder::class,
            EAModelRolePermissionSeeder::class
        ]);
    }
}
