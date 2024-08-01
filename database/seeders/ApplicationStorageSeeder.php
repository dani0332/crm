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
                'value' => '677',
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
                'value' => '9024065094027325487',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT,
                'value' => '9099784511062815329',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER,
                'value' => '9099784511515800162',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE,
                'value' => '9068692883229388975',
                'is_active' => 1,
            ],
            [
                'key_name' => ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER,
                'value' => '9083336468679635183',
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
    }
}
