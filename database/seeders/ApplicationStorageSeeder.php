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
        //Payment Expire Notification and Email Enable Disable
        $paymentNotificationEnable = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION)->first();
        if (! $paymentNotificationEnable) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION,
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $paymentAuthorizedDays = ApplicationStorage::where('key_name', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        if (! $paymentAuthorizedDays) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS,
                'value' => '8',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $paymentNotificationEmailTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::PAYMENT_NOTIFICATION_EMAIL_TEMPLATE)->first();
        if (! $paymentNotificationEmailTemplate) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::PAYMENT_NOTIFICATION_EMAIL_TEMPLATE,
                'value' => '681',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $enableCammyFollowUps = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_CAMMY_FOLLOWUP)->first();
        if (! $enableCammyFollowUps) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ENABLE_CAMMY_FOLLOWUP,
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $advisorNotificationTemplateId = ApplicationStorage::where('key_name', 'ADVISOR_NOTIFICATION_TEMPLATE')->count();
        if ($advisorNotificationTemplateId == 0) {
            $advisorNotificationTemplateId = ApplicationStorage::create([
                'key_name' => 'ADVISOR_NOTIFICATION_TEMPLATE',
                'value' => '603',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advisorNotificationCarAdvisor = ApplicationStorage::where('key_name', 'ADVISOR_NOTIFICATION_CAR_ADVISOR')->count();
        if ($advisorNotificationCarAdvisor == 0) {
            $advisorNotificationCarAdvisor = ApplicationStorage::create([
                'key_name' => 'ADVISOR_NOTIFICATION_CAR_ADVISOR',
                'value' => 'Veeral Joshi,veeral.joshi@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advisorNotificationHealthAdvisor = ApplicationStorage::where('key_name', 'ADVISOR_NOTIFICATION_HEALTH_ADVISOR')->count();
        if ($advisorNotificationHealthAdvisor == 0) {
            $advisorNotificationHealthAdvisor = ApplicationStorage::create([
                'key_name' => 'ADVISOR_NOTIFICATION_HEALTH_ADVISOR',
                'value' => 'Agatha Alicdan,agatha.alicdan@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advisorNotificationBccEmails = ApplicationStorage::where('key_name', 'ADVISOR_NOTIFICATION_BCC_EMAILS')->count();
        if ($advisorNotificationBccEmails == 0) {
            $advisorNotificationBccEmails = ApplicationStorage::create([
                'key_name' => 'ADVISOR_NOTIFICATION_BCC_EMAILS',
                'value' => 'IM HR,hr@insurancemarket.ae,Hitesh Motwani,hitesh.motwani@insurancemarket.ae,Fayaz Kariyambath,fayaz.k@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advisorNotificationEnable = ApplicationStorage::where('key_name', ApplicationStorageEnums::ADVISOR_ONLINE_NOTIFICATION_EMAILS_ENABLE)->count();
        if ($advisorNotificationEnable == 0) {
            $advisorNotificationEnable = ApplicationStorage::create([
                'key_name' => ApplicationStorageEnums::ADVISOR_ONLINE_NOTIFICATION_EMAILS_ENABLE,
                'value' => '0',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $epMdxV2From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V2_FROM)->first();
        if (! $epMdxV2From) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::EP_MDX_V2_FROM,
                'value' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $epMdxV3From = ApplicationStorage::where('key_name', ApplicationStorageEnums::EP_MDX_V3_FROM)->first();
        if (! $epMdxV3From) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::EP_MDX_V3_FROM,
                'value' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::EMAIL_CAMPAIGN)->exists()) {
            ApplicationStorage::create([
                'key_name' => ApplicationStorageEnums::EMAIL_CAMPAIGN,
                'value' => 'WIN-FOR-SURE',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::INVITATION_EMAIL_TEMPLATE_FOR_CAMPAIGN)->exists()) {
            ApplicationStorage::create([
                'key_name' => ApplicationStorageEnums::INVITATION_EMAIL_TEMPLATE_FOR_CAMPAIGN,
                'value' => '651',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_ENABLED)->exists()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_ENABLED,
                'value' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::BRIDGER_PASSWORD)->exists()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::BRIDGER_PASSWORD,
                'value' => '@bridger@0005',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_DAYS)->exists()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::QUOTE_SYNC_CLEANUP_DAYS,
                'value' => 30,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_FOLLOWUP_TEMPLATE)->exists()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ALFRED_FOLLOWUP_TEMPLATE,
                'value' => 117,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_OCB_EMAIL_TEMPLATE)->exists()) {
            ApplicationStorage::create([
                'key_name' => ApplicationStorageEnums::HEALTH_OCB_EMAIL_TEMPLATE,
                'value' => '677', ]);
        }
        $applicationStorageSeeder = [
            [
                'key_name' => ApplicationStorageEnums::CAR_BOOK_POLICY_TEMPLATE,
                'value' => '591',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::TRAVEL_BOOK_POLICY_TEMPLATE,
                'value' => '612',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::HEALTH_BOOK_POLICY_TEMPLATE,
                'value' => '593',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::LIFE_BOOK_POLICY_TEMPLATE,
                'value' => '617',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::HOME_BOOK_POLICY_TEMPLATE,
                'value' => '616',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::PET_BOOK_POLICY_TEMPLATE,
                'value' => '615',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::BIKE_BOOK_POLICY_TEMPLATE,
                'value' => '592',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::CYCLE_BOOK_POLICY_TEMPLATE,
                'value' => '618',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::YACHT_BOOK_POLICY_TEMPLATE,
                'value' => '622',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::GROUP_MEDICAL_BOOK_POLICY_TEMPLATE,
                'value' => '613',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::CORPLINE_BOOK_POLICY_TEMPLATE,
                'value' => '614',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::DIS_INBOX_EMAIL_BCC,
                'value' => 'sendpolicyupdate@insurancemarket.ae',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::BUP_HEALTH_DOC,
                'value' => 'https://membersworld.bupaglobal.com',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::OIC_HEALTH_DOC,
                'value' => 'policy-wordings/health/My-Sukoon-App-Manual.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::AXA_HEALTH_DOC,
                'value' => 'policy-wordings/health/MyAXA-Mobile-App.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::DIC_HEALTH_DOC,
                'value' => 'policy-wordings/health/DUBAICARE-MOBILE-APP.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::CIG_HEALTH_DOC,
                'value' => 'https://my.cigna.com/web/public/guest',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::ALLIANZ_HEALTH_DOC,
                'value' => 'policy-wordings/health/Allianz-MyHealth-Digital-Services-EN-2021.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::MEDNET_HEALTH_DOC,
                'value' => 'policy-wordings/health/HealthPass-by-Mednet---User-Guide.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::NEXTCARE_HEALTH_DOC,
                'value' => 'policy-wordings/health/LUMI-APP-BY-NEXTCARE.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::NAS_HEALTH_DOC,
                'value' => 'policy-wordings/health/myNAS-App---New-User-Guide.pdf',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::E_CARE_HEALTH_DOC,
                'value' => 'policy-wordings/health/Ecare-member-login-vcard.pdf',
                'is_active' => 1,
            ],
        ];

        foreach ($applicationStorageSeeder as $applicationStorage) {
            $conditions = [
                'key_name' => $applicationStorage['key_name'],
            ];
            ApplicationStorage::firstOrCreate($conditions, $applicationStorage);
        }

        // SEND UPDATE TEMPLATE START

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CAR_SEND_POLICY_TEMPLATE],
            ['value' => '664',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HEALTH_SEND_POLICY_TEMPLATE],
            ['value' => '665',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_SEND_POLICY_TEMPLATE],
            ['value' => '666',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::LIFE_SEND_POLICY_TEMPLATE],
            ['value' => '667',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_SEND_POLICY_TEMPLATE],
            ['value' => '668',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PET_SEND_POLICY_TEMPLATE],
            ['value' => '669',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CYCLE_SEND_POLICY_TEMPLATE],
            ['value' => '670',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIKE_SEND_POLICY_TEMPLATE],
            ['value' => '671',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::YACHT_SEND_POLICY_TEMPLATE],
            ['value' => '672',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CORPLINE_CAR_SEND_POLICY_TEMPLATE],
            ['value' => '673',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::CORPLINE_TRADE_SEND_POLICY_TEMPLATE],
            ['value' => '674',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::GROUP_MEDICAL_SEND_POLICY_TEMPLATE],
            ['value' => '675',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SEND_UPDATE_EMAIL],
            ['value' => 'updates@notify.insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::IM_EB_SERVICE_TEAM_EMAIL],
            ['value' => 'ebserviceteam@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1, ]
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SEND_POLICY_UPDATE_EMAIL],
            ['value' => 'sendpolicyupdate@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1],
        );

        // SEND UPDATE TEMPLATE END
        $sicFollowupEmailTempID = ApplicationStorage::where('key_name', ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID)->first();
        if (empty($sicFollowupEmailTempID)) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID,
                'value' => 678,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::EMAIL_CAMPAIGN_ENABLED)->exists()) {
            ApplicationStorage::create([
                'key_name' => ApplicationStorageEnums::EMAIL_CAMPAIGN_ENABLED,
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sukoonConstants = [
            [
                'key_name' => ApplicationStorageEnums::SUKOON_PAYMENT_GATEWAY,
                'value' => 'invoice',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_PRODUCT_SLUG,
                'value' => 'accident_health_afia',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_POLICY_CERTIFICATE,
                'value' => (config('constants.APP_ENV') != EnvEnum::PRODUCTION) ? '9024065094027325487' : '9139600398162627641',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT,
                'value' => (config('constants.APP_ENV') != EnvEnum::PRODUCTION) ? '9099784511062815329' : '9139600409696963644',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER,
                'value' => (config('constants.APP_ENV') != EnvEnum::PRODUCTION) ? '9099784511515800162' : '9139600411592789053',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE,
                'value' => (config('constants.APP_ENV') != EnvEnum::PRODUCTION) ? '9068692883229388975' : '9139600402977688634',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER,
                'value' => (config('constants.APP_ENV') != EnvEnum::PRODUCTION) ? '9083336468679635183' : '9139600407281044539',
                'is_active' => 1,
            ],
        ];
        foreach ($sukoonConstants as $sukoon) {
            $conditions = [
                'key_name' => $sukoon['key_name'],
            ];
            ApplicationStorage::firstOrCreate($conditions, $sukoon);
        }

        $ecomSourceValue = 'insurancemarket.ae';
        if (config('constants.APP_ENV') == EnvEnum::STAGING) {
            $ecomSourceValue = 'staging.alfred.ae';
        } elseif (config('constants.APP_ENV') == EnvEnum::UAT) {
            $ecomSourceValue = 'ecom.alfred.ae';
        } elseif (config('constants.APP_ENV') == EnvEnum::DEVELOPMENT) {
            $ecomSourceValue = 'dev.alfred.ae';
        } elseif (config('constants.APP_ENV') == EnvEnum::TEST) {
            $ecomSourceValue = 'testing.alfred.ae';
        }
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE],
            [
                'value' => $ecomSourceValue,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::INSLY_M2_RELEASE_DATE],
            [
                'value' => '2024-08-10',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $this->seedTravelSICStorage();
        $this->seedBirdWorkflowUrls();

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW],
            [
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/79b9011e-ff3b-4a5c-a63f-fed3d0743a7f',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADVISOR_CONVERSION_QUOTE_STATUS_DATE],
            [
                'value' => '2024-12-01',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }

    private function seedTravelSICStorage()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SIC_TRAVEL_WORKFLOW_ENABLE],
            [
                'value' => 'travel_sic_workflow_enable',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SIC_TRAVEL_WORKFLOW_DISABLE],
            [
                'value' => 'travel_sic_workflow_disable',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_EMAIL_TEMPLATE],
            [
                'value' => 697,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SIC_TRAVEL_FOLLOWUP_TEMPLATE_ID],
            [
                'value' => 698,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::SIC_TRAVEL_EMAIL_CC],
            [
                'value' => 'travel.enquiries@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::TRAVEL_EMAIL_REPLY_TO],
            [
                'value' => 'travel@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_WORKFLOW],
            [
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/85a0a2b6-51b9-4ac5-9319-a6ada510025b',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_SIC_MOTOR_RENEWAL_TAG],
            [
                'value' => 'sic-3-0-renewal-process',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }
    private function seedBirdWorkflowUrls()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL],
            [
                'value' => 'https://capture.eu-west-1.nest.messagebird.com/webhooks/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/7f5bd31b-6387-4e3e-826f-0f464badb2cd',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
    }
}
