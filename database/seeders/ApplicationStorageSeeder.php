<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class ApplicationStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SEND_POLICY_ISSUED_WHATSAPP_MESSAGE_TO_CUSTOMER_EVENT_URL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/aca43f6c-5b22-48f3-ba68-5642a074b853/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BULK_POLICY_DOCUMENT_SEND_CODES],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_UNIVERSAL_SEARCH],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $isProd = config('app.env') === 'production';
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER],
            [
                'value' => $isProd ? 0 : 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => $isProd ? 0 : 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CAR_CAT_A_REVIVAL_ALLOCATION_LOOKBACK_DAYS],
            [
                'value' => 15,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $this->seedBirdWorkflowUrls();
        $this->claimGoogleReviewEmail();
        $this->seedClaimSubStatusCustomerEmailBcc();
        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::SAGE_TIMEOUT_RETRY_ENABLED],
        //     [
        //         'value' => 0,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //         'is_active' => 1,
        //     ],
        // );

        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::ADVISOR_CONVERSION_QUOTE_STATUS_DATE],
        //     [
        //         'value' => '2024-12-01',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //         'is_active' => 1,
        //     ],
        // );

        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL],
        //     [
        //         'value' => 0,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //         'is_active' => 1,
        //     ],
        // );
        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::BIRD_ACCESS_KEY],
        //     [
        //         'value' => 'PFW43eLvGkOFh521QmolXW1fTLpT5C3Z3hiA',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //         'is_active' => 1,
        //     ],
        // );
        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::ENABLE_ALLIANCE_TRAVEL_POLICY_ISSUANCE],
        //     [
        //         'value' => 0,
        //         'is_active' => 1,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // );
        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ALLIANCE_TRAVEL_POLICY_ISSUANCE],
        //     [
        //         'value' => 0,
        //         'is_active' => 1,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // );
        // ApplicationStorage::firstOrCreate(
        //     ['key_name' => ApplicationStorageEnums::LMS_INTRO_BIKE_EMAIL_BCC],
        //     [
        //         'value' => 'newleadpool@insurancemarket.ae',
        //         'is_active' => 1,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // );
        // $this->seedBenchmarking();
        // $this->seedStopDeduplicateScript();
        // $this->seedAmlAutomation();

        // $this->seedYachtAndPetAdvisors();
        $this->seedCycleAdvisors();
        $this->seedCorplineAdvisors();
        $this->seedLifeOCAEmail();
        $this->savingsLOB();

        $this->seedOcrEnabled();
        $this->livaCarAutomationSeed();
        $this->seedGIGCarPolicyIssuance();
        $this->seedSukoonMedexProductSlug();
        $this->seedLOBCutOffDates();
        $this->seedBorWorkflowUrl();
        $this->seedTravelEnquiryEmail();
        $this->seedSendUpdateEmailTemplate();
        $this->seedOcrSendUpdateLogFlag();
        $this->seedProductionApprovalEmails();
        $this->seedCustomerVerificationEnabled();
        $this->seedAutoCaptureEPPayments();
        $this->seedEnableVoiceAIIntegration();
        $this->seedSla();
        // $this->seedTravelAutomatedFollowUps();
        $this->rtaPortalLink();
        $this->seedOCRCustomerJourneyFlag();
        $this->seedEpEcbConfigurations();
        $this->seedUnavailableTimeThreshold();
        $this->sendUpdateEmailBirdFlow();
        $this->seedEnableMetLife();
        $this->seedTempDisableSageBooking();
        $this->seedMrIncludeFailedBookings();
        $this->seedDeviceSmartphonePolicyIssuanceSettings();
        $this->seedPolicyIssuanceChiefDeputyOfficerContactDetails();
        $this->seedDeviceFailureEmailSettings();
        $this->seedLegacyPolicyKeys();
        $this->seedCyberConfigurations();
        $this->seedBranchData();
        $this->seedOcrUtilEnabled();
        $this->seedAdnicHealthAutomation();
        $this->seedCarOcbEmailTemplatesUpdate();
        $this->seedHealthTeamRoutingEnabled();
        $this->seedOCRCustomerJourneyHealthEnabled();
        $this->seedAdvisorPaymentNotificationWorkflowUrl();
        $this->seedDisableClaimsModule();
        $this->seedMotorRevivalWorkflow();
        $this->seedAmlAutomationOutcomeWorkflowUrl();
        $this->seedDttLifeEnabled();
        $this->seedOcrPlanValidation();
    }

    private function livaCarAutomationSeed()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_LIVA_CAR_POLICY_ISSUANCE],
            [
                'value' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_LIVA_CAR_POLICY_ISSUANCE],
            [
                'value' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::LIVA_AUTOMATION_API_TIMEOUT],
            [
                'value' => 90,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedAmlAutomationOutcomeWorkflowUrl()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_AML_AUTOMATION_OUTCOME_WORKFLOW_URL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/dc950ff5-df11-4347-b486-2c2a43d2b81d/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedAdnicHealthAutomation()
    {

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADNIC_HEALTH_AUTOMATION_API_TIMEOUT],
            [
                'value' => 90,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_ADNIC_HEALTH_POLICY_ISSUANCE],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ADNIC_HEALTH_POLICY_ISSUANCE],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADNIC_POLICY_ISSUANCE_TIMEOUT_RETRY_COOLDOWN_MINUTES],
            [
                'value' => 5,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADNIC_NUMBER_OF_ALLOWED_RETRY_FOR_TIMEOUT],
            [
                'value' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedBirdWorkflowUrls()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AUTOMATED_HOME_RENEWAL_FOLLOWUP_SWITCH],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_RENEWAL_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/cf06f7d2-1c3b-4051-80f5-d0ed503b996a/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AUTOMATED_TRAVEL_RENEWAL_FOLLOWUP_SWITCH],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/6ac637e8-4bf6-418b-8f65-7485ce47687f/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::NB_MOTOR_FOLLOWUP_DELAY_DURATION],
            [
                'value' => '24',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_TRAVEL_RENEWALS_OCB],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/968e6273-9965-473b-a258-2a069c8fb7da/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PROCESS_CC_PAYMENTS_ENABLED],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $this->seedHomeAdvisors();
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_RENEWALS_SWITCH],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_ALLIANCE_FAILED_ALLOCATION_EMAIL_EVENT_URL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/968e6273-9965-473b-a258-2a069c8fb7da/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_WHATSAPP_NO_PLANS_ASSIGNMENT_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/5fd51eb0-a17a-43d4-b9a8-11910469e7ac/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PUBLIC_HOLIDAY_START_DATE],
            [
                'value' => '2024-12-02 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PUBLIC_HOLIDAY_END_DATE],
            [
                'value' => '2024-12-03 23:59:59',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_RENEWAL_OCB],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/114f64e5-5a67-4110-bb66-7038f3f34c04/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_AI_ADVISOR_OCB],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/c66ef6c5-966c-43d4-a6d2-8402d53f79bf/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_OE_ASSIGNMENT_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/3260c727-5aef-4806-a869-f24f21ac7317/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        $isProd = config('app.env') === 'production';
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL],
            [
                'value' => $isProd ? 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/dc85dec3-4bc3-4030-9214-7a8909ecbc7b/invoke-sync' : 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/07d270c5-5121-441c-b048-9e4b1e2478f1/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/736a4efe-5b5b-49c0-a658-0563a0dbb0e2/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/736a4efe-5b5b-49c0-a658-0563a0dbb0e2/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_INSTANT_ALFRED_EXPORT_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/310dbe00-d35a-49b2-8e8e-9dc0e7053016/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT_PARAMS],
            [
                'value' => '', /* It saves a configuration via JSON see sample at app/Services/Reports/ConversionOptimizationScheduledExportService.php:230 */
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedHomeAdvisors()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_VALUE_ADVISORS],
            [
                'value' => 'marialuisa.deguzman@insurancemarket.ae,virgilio.ocon@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_VOLUME_ADVISORS],
            [
                'value' => 'ghana.naeem@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/114f64e5-5a67-4110-bb66-7038f3f34c04/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS_SWITCH],
            [
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_RENEWALS_DAYS_THRESHOLD],
            [
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_TAP_INTEGRATION],
            [
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        // TODO:: Need to confirm email addresses with Shahrukh
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TAP_AUTHORIZED_EMAILS],
            [
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/5fd51eb0-a17a-43d4-b9a8-11910469e7ac/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CAR_CQF_RENEWALS_DAYS_THRESHOLD],
            [
                'value' => '120',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CPA_AUSTRALIA_HOME_BCC_EMAILS],
            [
                'value' => 'moinuddin.lakdawala@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CAR_CQF_RENEWALS_SWITCH],
            [
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CPA_AUSTRALIA_SAVINGS_BCC_EMAILS],
            [
                'value' => 'moinuddin.lakdawala@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_LEAD_POOL_BCC],
            [
                'value' => 'newleadpool@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SAVINGS_LEAD_POOL_BCC],
            [
                'value' => 'newleadpool@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedUnavailableTimeThreshold()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::USER_UNAVAILABLE_TIME_THRESHOLD],
            [
                'value' => 120,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function sendUpdateEmailBirdFlow(): void
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_INSLY_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/b93647e0-13a8-4abc-b3fa-82aa9eca8536/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    public function seedBenchmarking()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BENCHMARKING_ENABLED],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BENCHMARKING_QUERY_TIMEOUT_THRESHOLD_IN_MS],
            [
                'value' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::GROUP_MEDICAL_MICRO_ADVISORS],
            [
                'value' => 'loren.fronda@insurancemarket.ae,amandeep.bindra@insurancemarket.ae,ashfaq.mohammed@insurancemarket.ae,sumit.kumar@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::GROUP_MEDICAL_NON_MICRO_ADVISORS],
            [
                'value' => 'ali.riaz@insurancemarket.ae,tarique.mahar@insurancemarket.ae,vipin.das@insurancemarket.ae,umar.khan@insurancemarket.ae,sudhir.veedu@insurancemarket.ae,quinn.menezes@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    public function seedStopDeduplicateScript()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::STOP_DE_DUPLICATION_JOB],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedAmlAutomation()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AML_AUTOMATION_ENABLED],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedYachtAndPetAdvisors()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::YACHT_ADVISORS],
            [
                'value' => 'vignesh.prasad@insurancemarket.ae,jayaraj.anthonyswamy@insurancemarket.ae,zaid.sheikh@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PET_ADVISORS],
            [
                'value' => 'smitha.chandran@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_ADVISORS_FOR_PET],
            [
                'value' => 'ghana.naeem@insurancemarket.ae,marialuisa.deguzman@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedCycleAdvisors()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYCLE_ADVISORS],
            [
                'value' => 'marialuisa.deguzman@insurancemarket.ae,virgilio.ocon@insurancemarket.ae,ghana.naeem@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedCorplineAdvisors()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CORPLINE_ADVISORS],
            [
                'value' => 'vignesh.prasad@insurancemarket.ae,jayaraj.anthonyswamy@insurancemarket.ae,zaid.sheikh@insurancemarket.ae,arun.shankar@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedLifeOCAEmail()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::LIFE_OCA_EMAIL_FLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/bb88d699-342a-47d5-b618-6997ab2fe7f1/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::FIC_LIFE_EMAIL_SWITCH],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function savingsLOB()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::NEW_LEAD_POOL_BCC],
            [
                'value' => 'newleadpool@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SAVINGS_BOOK_POLICY_TEMPLATE],
            [
                'value' => 734,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SAVINGS_SEND_POLICY_TEMPLATE],
            [
                'value' => 737,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SAVINGS_OCA_EMAIL_FLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/b53c653f-be84-4a66-97d4-696cf2b64f01/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedOcrEnabled()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::OCR_ENABLED],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedOCRCustomerJourneyHealthEnabled()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::OCR_CUSTOMER_JOURNEY_HEALTH_ENABLED],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedGIGCarPolicyIssuance()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_GIG_CAR_POLICY_ISSUANCE],
            [
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_GIG_CAR_POLICY_ISSUANCE],
            [
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedSukoonMedexProductSlug()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SUKOON_MEDEX_PRODUCT_SLUG],
            [
                'value' => 'afia_driver_medex',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedLOBCutOffDates()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_CUT_OFF_DATE],
            [
                'value' => '2025-04-10 21:30:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::LIFE_CUT_OFF_DATE],
            [
                'value' => '2025-07-25 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedBorWorkflowUrl()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/e6b4f8c2-74d7-4cc2-a1dd-d9e18d8b4655/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function claimGoogleReviewEmail(): void
    {
        $isProd = app()->environment() === EnvEnum::PRODUCTION;
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_EMAILS_WORKFLOW_URL],
            [
                'value' => $isProd ? 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/82c9e316-bd96-4cbd-9b78-0962478a2473/invoke-sync' : 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/f440f3b1-7c43-445c-a229-2b694e71179c/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC],
            [
                'value' => $isProd ? 'claims@insurancemarket.ae,ashmy.arackal@insurancemarket.ae' : '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL_BCC],
            [
                'value' => $isProd ? 'claims@insurancemarket.ae,surabhi.singh@insurancemarket.ae,healthclaims@insurancemarket.ae' : '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

    }

    private function seedClaimSubStatusCustomerEmailBcc(): void
    {
        $isProd = app()->environment() === EnvEnum::PRODUCTION;
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_MOTOR_AND_GENERAL],
            [
                'value' => $isProd ? 'claims@insurancemarket.ae,ashmy.arackal@insurancemarket.ae' : '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_HEALTH],
            [
                'value' => $isProd ? 'claims@insurancemarket.ae,surabhi.singh@insurancemarket.ae,healthclaims@insurancemarket.ae' : '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_LIFE],
            [
                'value' => $isProd ? 'claims@insurancemarket.ae,santhosh.ganesan@insurancemarket.ae,life.admin@insurancemarket.ae' : '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedTravelEnquiryEmail()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_ENQUIRIES_EMAIL],
            [
                'value' => 'travel-enquiries@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedSendUpdateEmailTemplate()
    {
        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::CAR_SEND_POLICY_TEMPLATE],
            [
                'value' => 756,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::BIKE_SEND_POLICY_TEMPLATE],
            [
                'value' => 757,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::CYCLE_SEND_POLICY_TEMPLATE],
            [
                'value' => 758,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::YACHT_SEND_POLICY_TEMPLATE],
            [
                'value' => 759,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_SEND_POLICY_TEMPLATE],
            [
                'value' => 760,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::LIFE_SEND_POLICY_TEMPLATE],
            [
                'value' => 761,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_SEND_POLICY_TEMPLATE],
            [
                'value' => 762,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::BUSINESS_SEND_POLICY_TEMPLATE],
            [
                'value' => 763,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::HEALTH_SEND_POLICY_TEMPLATE],
            [
                'value' => 764,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::GROUP_MEDICAL_SEND_POLICY_TEMPLATE],
            [
                'value' => 765,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::PET_SEND_POLICY_TEMPLATE],
            [
                'value' => 766,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::CORPLINE_CAR_SEND_POLICY_TEMPLATE],
            [
                'value' => 767,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::CORPLINE_TRADE_SEND_POLICY_TEMPLATE],
            [
                'value' => 768,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::PROFESSIONAL_SEND_POLICY_TEMPLATE],
            [
                'value' => 769,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::COMMERCIAL_CAR_SEND_POLICY_TEMPLATE],
            [
                'value' => 770,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function seedOcrSendUpdateLogFlag()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_SENDUPDATE_OCR],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedProductionApprovalEmails()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL],
            [
                'value' => 'production.approval@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::APPROVAL_PRODUCTION_EMAIL],
            [
                'value' => 'approval.production@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedSla()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SLA_CALLBACK_HOURS],
            [
                'value' => 2,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SLA_REMINDER_MINUTES],
            [
                'value' => 15,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedAutoCaptureEPPayments()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_AUTO_CAPTURE_EP_PAYMENTS],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AUTO_CAPTURE_EP_PAYMENTS_BOOKING_DAYS],
            [
                'value' => 7,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedTravelAutomatedFollowUps()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/d2390476-791f-493e-a68e-a3625839261c/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AUTOMATED_TRAVEL_FOLLOWUP_SWITCH],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedEnableVoiceAIIntegration()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_VOICE_AI_INTEGRATION],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedCustomerVerificationEnabled()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CUSTOMER_VERIFICATION_ENABLED],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function rtaPortalLink()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::RTA_PORTAL_LINK],
            [
                'value' => 'https://vls.rta.ae/renewal/identityVerification',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedOCRCustomerJourneyFlag()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::OCR_CUSTOMER_JOURNEY_ENABLED],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedEpEcbConfigurations()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM],
            [
                'value' => 'alfred@testnotify.alfred.ae,InsuranceMarket-Test',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_FAILURE_EMAIL_TO],
            [
                'value' => 'rucha.keluskar@myalfred.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO],
            [
                'value' => 'test.emails@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_FAILURE_EMAIL_CC],
            [
                'value' => 'diya.lekhwani@myalfred.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_CC],
            [
                'value' => 'diya.lekhwani@myalfred.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_ECB_POLICY_CLAIM_LIMIT],
            [
                'value' => 'One claim per policy term.',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_ECB_POLICY_COVERAGE],
            [
                'value' => 'If you have an accident, you pay part of the repair bill (this is called "excess"), usually between AED 350 to AED 1,400. This benefit gives you back up to AED 1,200.',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::EP_ECB_POLICY_DURATION],
            [
                'value' => 'Your coverage lasts for 13 months or until the expiry of your motor insurance policy, whichever comes first.',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CYBER_POLICY_ISSUANCE],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::AWNI_CYBER_AUTOMATION_API_TIMEOUT],
            [
                'value' => 90,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedEnableMetLife()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_METLIFE],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedTempDisableSageBooking()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TEMP_DISABLE_SAGE_BOOKING],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedMrIncludeFailedBookings()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::MR_INCLUDE_FAILED_BOOKINGS],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::MR_FAILED_BOOKING_DATE_FROM],
            [
                'value' => '2025-11-27 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedDeviceSmartphonePolicyIssuanceSettings()
    {

        // Smartphone - Book Policy Template ID (same as New Policy for consistency)
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_BOOK_POLICY_TEMPLATE],
            [
                'value' => 'db6caa9d-d274-41b8-8950-a6fb4b56ae41', // Smartphone - New Policy template
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        // Smartphone - Update Policy Template ID (Send Update Email)
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_UPDATE_POLICY_TEMPLATE],
            [
                'value' => 'cd3ef8ad-f6ad-4e73-89ca-2af81d0eb385', // Smartphone - Update Policy template
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        // Smartphone - Payment Authorized Template Alias (FTC Email)
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_PAYMENT_AUTHORIZED_TEMPLATE],
            [
                'value' => 'smartphone-payment-authorized', // Smartphone - Payment Authorized template alias
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_NGI_SMARTPHONE_POLICY_ISSUANCE],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::NGI_SMARTPHONE_AUTOMATION_API_TIMEOUT],
            [
                'value' => 90,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedPolicyIssuanceChiefDeputyOfficerContactDetails(): void
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_MOBILE_NO],
            [
                'value' => '9710502732524',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_EMAIL_ID],
            [
                'value' => 'hitesh.motwani@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );
    }

    private function seedDeviceFailureEmailSettings()
    {

        $deviceFailureEmailsTo = config('app.env') === 'production' ? 'production.approval.team@insurancemarket.ae' : 'production.approval.team@yopmail.com,device-smartphone-ngi-failure-email@yopmail.com';

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO],
            [
                'key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO,
                'value' => $deviceFailureEmailsTo,
                'is_active' => 1,
            ],
        );

        $deviceFailureEmailsCc = config('app.env') === 'production' ? 'dt.system.notifications@insurancemarket.ae,sandeep.sharma@insurancemarket.ae,rucha.keluskar@myalfred.com,digital.transformation.support@myalfred.com' : 'production.approval.team@yopmail.com,device-smartphone-ngi-failure-email@yopmail.com';

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC],
            [
                'key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC,
                'value' => $deviceFailureEmailsCc,
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK],
            [
                'key_name' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK,
                'value' => 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T',
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::IMCRM_POLICY_ISSUANCE_FAILURE_EMAIL],
            [
                'key_name' => ApplicationStorageEnums::IMCRM_POLICY_ISSUANCE_FAILURE_EMAIL,
                'value' => 'imcrm-policy-issue-fake@yopmail.com,ngi-policy-issue-fake@yopmail.com',
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::IMCRM_DOC_DOWNLOAD_FAILURE_EMAIL],
            [
                'key_name' => ApplicationStorageEnums::IMCRM_DOC_DOWNLOAD_FAILURE_EMAIL,
                'value' => 'imcrm-doc-download-fake@yopmail.com,ngi-doc-download-fake@yopmail.com',
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::IMCRM_DOC_UPLOAD_FAILURE_EMAIL],
            [
                'key_name' => ApplicationStorageEnums::IMCRM_DOC_UPLOAD_FAILURE_EMAIL,
                'value' => 'imcrm-doc-upload-fake@yopmail.com,ngi-doc-upload-fake@yopmail.com',
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::IMCRM_BOOK_POLICY_FAILURE_EMAIL],
            [
                'key_name' => ApplicationStorageEnums::IMCRM_BOOK_POLICY_FAILURE_EMAIL,
                'value' => 'imcrm-book-policy-fake@yopmail.com,ngi-book-policy-fake@yopmail.com',
                'is_active' => 1,
            ],
        );

    }

    private function seedLegacyPolicyKeys()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::INSLY_TEMP_SALES_PERSON_ID],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::INSLY_TEMP_POLICY_OID],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::INSLY_TEMP_CUSTOMER_EMAIL],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::INSLY_TEMP_CUSTOMER_POLICY_OID],
            [
                'value' => '2025-11-27 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedCyberConfigurations()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_ADVISORS],
            [
                'value' => 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_SEND_POLICY_TEMPLATE],
            [
                'value' => 772,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $ccEmails = config('app.env') === 'production' ? 'dt.system.notifications@insurancemarket.ae, cyber.enquiries@insurancemarket.ae, sandeep.sharma@insurancemarket.ae, diya.lekhwani@myalfred.com, digital.transformation.support@myalfred.com' : 'diya.lekhwani@myalfred.com, productionapproval@yopmail.com, sheza.moeen@myalfred.com, tasawar.hussain@myalfred.com';

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_FAILURE_EMAIL],
            [
                'value' => $ccEmails,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );

        $cyberCaptureFailureEmail = config('app.env') === 'production' ? 'production.approval.team@insurancemarket.ae' : 'productionapproval@yopmail.com';

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_CAPTURE_FAILURE_EMAIL],
            [
                'value' => $cyberCaptureFailureEmail,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $escalationLink = config('app.env') === 'production' ? 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T' : 'https://imcrmuat.alfred.ae/';

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_ESCALATION_LINK],
            [
                'value' => $escalationLink,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYBER_HAPPINESS_SUPPORT_USER_EMAIL],
            [
                'value' => 'hapexuser@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_MOBILE_NO],
            [
                'value' => '971502732524',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedBranchData()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_MISREPORT_JOB],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        $birdWorkflowUrl = 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/5fd51eb0-a17a-43d4-b9a8-11910469e7ac/invoke-sync';
        if (env('APP_ENV') === 'production') {
            $birdWorkflowUrl = 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/7e474d25-cfad-4f11-a13e-daa26f08133a/invoke-sync';
        }

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_MISREPORT_JOB_WORKFLOW],
            [
                'value' => $birdWorkflowUrl,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedOcrUtilEnabled()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::OCR_UTIL_ENABLED],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedCarOcbEmailTemplatesUpdate()
    {
        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::SIB_CAR_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE],
            [
                'value' => 778,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::SIB_CAR_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE],
            [
                'value' => 778,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::updateOrCreate(
            ['key_name' => ApplicationStorageEnums::SIB_CAR_QUOTE_ONE_CLICK_BUY_ZERO_PLAN_TEMPLATE],
            [
                'value' => 778,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedDisableClaimsModule(): void
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DISABLE_CLAIMS_MODULE],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    // region Advisor Payment Notification

    private function seedAdvisorPaymentNotificationWorkflowUrl()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL_TO_ADVISOR],
            [
                'value' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => true,
            ],
        );

        $birdWorkflowUrl = 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/4f168567-e5fa-4617-9f74-43293e0f6c6c/invoke-sync';
        if (config('constants.APP_ENV') == EnvEnum::PRODUCTION) {
            $birdWorkflowUrl = 'https://api.bird.com/workspaces/7e7ef00d-88c7-446a-81bf-c3b6cd522318/flows/bdfeeeee-4101-4d9d-97b2-22f51b82ba26/invoke-sync';
        }

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_ADVISOR_PAYMENT_NOTIFICATION_WORKFLOW_URL],
            [
                'value' => $birdWorkflowUrl,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => true,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADVISOR_AUTHORISED_PAYMENT_NOTIFICATION_DAYS],
            [
                'value' => 30,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => true,
            ],
        );
        // endregion
    }

    private function seedHealthTeamRoutingEnabled()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED],
            [
                'value' => ApplicationStorageEnums::ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedMotorRevivalWorkflow()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::MOTOR_REVIVAL_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/c01d3d9c-e111-45ae-a40e-ff51ac6a7294/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedDttLifeEnabled(): void
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::DTT_LIFE_ENABLED],
            [
                'value' => ApplicationStorageEnums::ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => ApplicationStorageEnums::ACTIVE,
            ],
        );
    }

    private function seedOcrPlanValidation()
    {
        /** For sukoon purple API */
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::OCR_SAVINGS_PASSPORT_ELIGIBLE_PLAN_CODES],
            [
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }
}
