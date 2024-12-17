<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use App\Models\QuoteType;
use Illuminate\Database\Seeder;

class QuoteStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteStatusSeeder = [
            [
                'code' => 'Policy Issued',
                'text' => 'Policy Issued',
                'text_ar' => null,
                'is_active' => 1,
                'sort_order' => 23,
                'is_deleted' => 0,
                'created_at' => '2021-12-26 11:39:42',
                'updated_at' => '2021-12-26 11:39:42',
                'deleted_at' => null,
                'uuid' => '0a9232db-6612-11ec-b285-8f7ab6218021',
                'created_by' => 'saroosh.hameed@afia.ae',
                'updated_by' => 'saroosh.hameed@afia.ae',
            ],
            [
                'code' => 'PolicySentToCustomer',
                'text' => 'Policy Sent to Customer',
                'text_ar' => 'Policy Sent to Customer',
                'is_active' => 1,
                'sort_order' => 19,
                'is_deleted' => 0,
                'created_at' => '2024-01-30 16:48:52',
                'updated_at' => '2024-01-30 16:48:52',
                'deleted_at' => null,
                'uuid' => 'ea826923-bf6d-11ee-a8a6-2a23318a2517',
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ],
            [
                'code' => 'PolicyBooked',
                'text' => 'Policy Booked',
                'text_ar' => 'Policy Booked',
                'is_active' => 1,
                'sort_order' => 20,
                'is_deleted' => 0,
                'created_at' => '2024-01-30 16:48:52',
                'updated_at' => '2024-01-30 16:48:52',
                'deleted_at' => null,
                'uuid' => 'eaa01b0f-bf6d-11ee-a8a6-2a23318a2517',
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ],
            [
                'code' => 'PolicyPending',
                'text' => 'Policy Pending',
                'text_ar' => 'Policy Pending',
                'is_active' => 1,
                'sort_order' => 20,
                'is_deleted' => 0,
                'created_at' => '2024-03-01 15:08:39',
                'updated_at' => '2024-03-01 15:08:39',
                'deleted_at' => null,
                'uuid' => '0af23ff3-d7bc-11ee-8669-36bd12237afb',
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ],
            [
                'code' => 'EarlyRenewal',
                'text' => 'Early Renewal',
                'text_ar' => 'Early Renewal',
                'is_active' => 1,
                'sort_order' => 84,
                'is_deleted' => 0,
                'created_at' => '2024-03-14 13:18:37',
                'updated_at' => '2024-03-14 13:18:37',
                'deleted_at' => null,
                'uuid' => 'eb4f09ba-bf6d-11ee-a8a6-2a23318a2050',
                'created_by' => 'bilal.ahmad@myalfred.com',
                'updated_by' => 'bilal.ahmad@myalfred.com',
            ],
            [
                'code' => 'PolicyCancelledReissued',
                'text' => 'Policy Cancelled & Reissued',
                'text_ar' => 'Policy Cancelled & Reissued',
                'is_active' => 1,
                'sort_order' => 85,
                'is_deleted' => 0,
                'created_at' => '2024-06-11 16:48:52',
                'updated_at' => '2024-06-11 16:48:52',
                'deleted_at' => null,
                'created_by' => 'bilal.saeed@insurancemarket.ae',
                'updated_by' => 'bilal.saeed@insurancemarket.ae',
            ],
            [
                'code' => 'PolicyBookingQueued',
                'text' => 'Policy Booking Queued',
                'text_ar' => 'Policy Booking Queued',
                'is_active' => 1,
                'sort_order' => 86,
                'is_deleted' => 0,
                'created_at' => '2024-09-09 16:48:52',
                'updated_at' => '2024-09-09 16:48:52',
                'deleted_at' => null,
                'created_by' => 'muhammad.ali@insurancemarket.ae',
                'updated_by' => 'muhammad.ali@insurancemarket.ae',
            ],
            [
                'code' => 'PolicyBookingFailed',
                'text' => 'Policy Booking Failed',
                'text_ar' => 'Policy Booking Failed',
                'is_active' => 1,
                'sort_order' => 87,
                'is_deleted' => 0,
                'created_at' => '2024-09-09 16:48:52',
                'updated_at' => '2024-09-09 16:48:52',
                'deleted_at' => null,
                'created_by' => 'muhammad.ali@insurancemarket.ae',
                'updated_by' => 'muhammad.ali@insurancemarket.ae',
            ],
        ];

        foreach ($quoteStatusSeeder as $quoteStatus) {
            $conditions = [
                'code' => $quoteStatus['code'],
            ];
            QuoteStatus::firstOrCreate($conditions, $quoteStatus);
        }
        $quoteTypes = QuoteType::all();
        foreach ($quoteTypes as $quoteType) {
            QuoteStatusMap::firstOrCreate([
                'quote_type_id' => $quoteType->id,
                'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED,
            ], [
                'quote_type_id' => $quoteType->id,
                'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED,
                'sort_order' => 22,
                'created_by' => 'muhammad.ali@insurancemarket.ae',
                'updated_by' => 'muhammad.ali@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            QuoteStatusMap::firstOrCreate([
                'quote_type_id' => $quoteType->id,
                'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED,
            ], [
                'quote_type_id' => $quoteType->id,
                'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED,
                'sort_order' => 22,
                'created_by' => 'muhammad.ali@insurancemarket.ae',
                'updated_by' => 'muhammad.ali@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
