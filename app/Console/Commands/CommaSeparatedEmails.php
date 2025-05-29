<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Monolog\Logger;

class CommaSeparatedEmails extends Command
{
    public function __construct()
    {
        parent::__construct();
        LoggerService::info('comma-separated-emails command initialized');
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:comma-separated-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process comma-separated email values across all LOB tables. Sets first email as primary email field and remaining emails as additional contact information.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        LoggerService::info('Processing comma-separated emails across all LOB tables');

        // for request_quotes
        /*$quoteTypes = [
            QuoteTypeId::Car,
            QuoteTypeId::Home,
            QuoteTypeId::Health,
            QuoteTypeId::Life,
            QuoteTypeId::Business,
            QuoteTypeId::Bike,
            QuoteTypeId::Yacht,
            QuoteTypeId::Travel,
            QuoteTypeId::Pet,
            QuoteTypeId::Cycle,
            QuoteTypeId::Jetski,
        ];*/

        $personalQuotes = DB::table('personal_quotes')
            ->select('id', 'uuid', 'quote_type_id', 'email', 'customer_id')
            ->where('email', 'like', '%,%')
            ->whereIn('source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY])
            ->orderBy('quote_type_id')
            ->get();

        LoggerService::info('Found '.count($personalQuotes).' personal quotes with comma-separated emails');

        $preparedData = $additionalContactInfo = [];

        if (! empty($personalQuotes)) {
            LoggerService::info('Preparing personal quotes data for comma-separated emails');
            foreach ($personalQuotes as $quote) {
                $emails = explode(',', $quote->email);
                $primaryEmail = $emails[0];
                $secondaryEmail = $emails[1] ?? null;

                $preparedData[$quote->quote_type_id][] = [
                    'id' => $quote->id,
                    'uuid' => $quote->uuid,
                    'quote_type_id' => $quote->quote_type_id,
                    'email' => trim($primaryEmail),
                    'updated_at' => now(),
                ];

                if (! empty($quote->customer_id) && ! empty($secondaryEmail)) {
                    $additionalContactInfo[] = [
                        'customer_id' => $quote->customer_id,
                        'key' => 'email',
                        'value' => trim($secondaryEmail),
                    ];
                }
            }
            LoggerService::info('Data prepared for comma-separated emails');

            if (! empty($preparedData)) {
                $allData = [];
                foreach ($preparedData as $quoteTypeId => $data) {
                    $allData = array_merge($allData, $data);
                }

                LoggerService::info('Running bulk upsert for personal quotes with comma-separated emails');
                DB::table('personal_quotes')->upsert(
                    $allData,
                    ['id'],
                    ['email']
                );
                LoggerService::info('Bulk upsert completed for personal quotes with comma-separated emails');
            }

            if (! empty($additionalContactInfo)) {
                LoggerService::info('Running bulk upsert for additional contact information');
                DB::table('customer_additional_contact')->upsert(
                    $additionalContactInfo,
                    ['customer_id', 'key', 'value'],
                    ['created_at', 'updated_at']
                );
                LoggerService::info('Bulk upsert completed for additional contact information');
            }
        }
    }
}
