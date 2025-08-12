<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use App\Models\QuoteType;
use Carbon\Carbon;
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
                'code' => 'PaymentInitiated',
                'text' => 'Payment Initiated',
                'text_ar' => 'Payment Initiated',
                'is_active' => 1,
                'sort_order' => 20,
                'is_deleted' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
                'uuid' => 'ea826923-11bb-11ee-a8a6-2a23318a2517',
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            //     [
            //         'code' => 'PaymentLinkRequestedByCustomer',
            //         'text' => 'Payment Link Requested By Customer',
            //         'text_ar' => 'Payment Link Requested By Customer',
            //         'is_active' => 1,
            //         'sort_order' => 23,
            //         'is_deleted' => 0,
            //         'created_at' => Carbon::now(),
            //         'updated_at' => Carbon::now(),
            //         'deleted_at' => null,
            //         'uuid' => '0a9232db-11aa-11ec-b285-8f7ab6218021',
            //         'created_by' => 'muhammad.waris@myalfred.com',
            //         'updated_by' => 'muhammad.waris@myalfred.com',
            //     ],
            //     [
            //         'code' => 'PaymentLinkInProgress',
            //         'text' => 'Payment Link In Progress',
            //         'text_ar' => 'Payment Link In Progress',
            //         'is_active' => 1,
            //         'sort_order' => 20,
            //         'is_deleted' => 0,
            //         'created_at' => Carbon::now(),
            //         'updated_at' => Carbon::now(),
            //         'deleted_at' => null,
            //         'uuid' => 'eaa01b0f-11cc-11ee-a8a6-2a23318a2517',
            //         'created_by' => 'muhammad.waris@myalfred.com',
            //         'updated_by' => 'muhammad.waris@myalfred.com',
            //     ],
            //     [
            //         'code' => 'PaymentLinkSentToCustomer',
            //         'text' => 'Payment Link Sent To Customer',
            //         'text_ar' => 'Payment Link Sent ToCustomer',
            //         'is_active' => 1,
            //         'sort_order' => 19,
            //         'is_deleted' => 0,
            //         'created_at' => Carbon::now(),
            //         'updated_at' => Carbon::now(),
            //         'deleted_at' => null,
            //         'uuid' => 'ea826923-11bb-11ee-a8a6-2a23318a2517',
            //         'created_by' => 'muhammad.waris@myalfred.com',
            //         'updated_by' => 'muhammad.waris@myalfred.com',
            //     ],
                [   
                    'code' => 'PendingBorRequest',
                    'text' => 'Pending Bor Request',
                    'text_ar' => 'Pending Bor Request',
                    'is_active' => 1,
                    'sort_order' => 22,
                    'is_deleted' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'deleted_at' => null,
                    'uuid' => 'ea826923-11bb-11ee-a8a6-2a23318a2520',
                    'created_by' => 'muhammad.waris@myalfred.com',
                    'updated_by' => 'muhammad.waris@myalfred.com',
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
            $commonData = [
                'quote_type_id' => $quoteType->id,
                'sort_order' => 22,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // QuoteStatusMap::firstOrCreate([
            //     'quote_status_id' => QuoteStatusEnum::PaymentLinkRequestedByCustomer,
            // ], $commonData);

            // QuoteStatusMap::firstOrCreate([
            //     'quote_status_id' => QuoteStatusEnum::PaymentLinkInprogress,
            // ], $commonData);

            // QuoteStatusMap::firstOrCreate([
            //     'quote_status_id' => QuoteStatusEnum::PaymentLinkSentToCustomer,
            // ], $commonData);

            // This have been run for prod/uat/test
            // QuoteStatusMap::firstOrCreate([
            //     'quote_type_id' => $quoteType->id,
            //     'quote_status_id' => QuoteStatusEnum::PaymentInitiated,
            // ], $commonData);

            // This have been run for prod/uat/test 
            // QuoteStatusMap::firstOrCreate([
            //     'quote_type_id' => $quoteType->id,
            //     'quote_status_id' => QuoteStatusEnum::PaymentLinkSentToCustomer,
            // ], $commonData);

            QuoteStatusMap::firstOrCreate([
                'quote_type_id' => $quoteType->id,
                'quote_status_id' => QuoteStatusEnum::PendingBorRequest,
            ], $commonData);
        }
    }
}
