<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
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
            ['key_name' => ApplicationStorageEnums::ENABLE_UNIVERSAL_SEARCH],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        $this->seedBirdWorkflowUrls();
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
        $this->seedGIGCarPolicyIssuance();
        $this->seedSukoonMedexProductSlug();
        $this->seedLOBCutOffDates();
        $this->seedTravelEnquiryEmail();
        $this->seedOcrSendUpdateLogFlag();
    }

    private function seedBirdWorkflowUrls()
    {
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
            ['key_name' => ApplicationStorageEnums::BIRD_OE_ASSIGNMENT_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/3260c727-5aef-4806-a869-f24f21ac7317/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        $this->seedUnavailableTimeThreshold();
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
            ['key_name' => ApplicationStorageEnums::CPA_AUSTRALIA_HOME_BCC_EMAILS],
            [
                'value' => 'moinuddin.lakdawala@insurancemarket.ae',
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

    private function seedTravelEnquiryEmail()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_ENQUIRIES_EMAIL],
            [
                'value' => 'travel-enquiries@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]
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
}
