<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $personalQuotes = DB::table('personal_quotes')
            ->select('id', 'uuid', 'quote_type_id', 'email', 'customer_id')
            ->where('email', 'like', '%,%')
            ->whereIn('source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY])
            ->orderBy('quote_type_id')
            ->get();

        $quoteTypeIds = $personalQuotes->pluck('quote_type_id')->unique()->toArray();

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
                    $additionalContactInfo[$quote->quote_type_id][] = [
                        'customer_id' => $quote->customer_id,
                        'key' => 'email',
                        'value' => trim($secondaryEmail),
                        'uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ];
                }
            }
            LoggerService::info('Data prepared for comma-separated emails');

            if (! empty($preparedData)) {
                foreach ($quoteTypeIds as $quoteTypeId) {
                    $quoteType = QuoteTypes::getName($quoteTypeId)->value;
                    $tableName = strtolower($quoteType).'_quote_request';

                    LoggerService::info('Fixing comma-separated emails on '.$tableName.' table', extra: [
                        'tableName' => $tableName,
                        'records' => count($preparedData[$quoteTypeId]),
                    ]);

                    foreach ($preparedData[$quoteTypeId] as $record) {
                        DB::table($tableName)->where('uuid', $record['uuid'])
                            ->update([
                                'email' => $record['email'],
                                'updated_at' => $record['updated_at'],
                            ]);

                        DB::table('personal_quotes')->where('uuid', $record['uuid'])->where('quote_type_id', $quoteTypeId)
                            ->update([
                                'email' => $record['email'],
                                'updated_at' => $record['updated_at'],
                            ]);

                        LoggerService::info('Email updated on '.$tableName.' and personal_quotes table.', extra: [
                            'uuid' => $record['uuid'],
                            'email' => $record['email'],
                        ]);
                    }

                    if (! empty($additionalContactInfo[$quoteTypeId])) {
                        LoggerService::info('Inserting additional contact information for '.$quoteTypeId, extra: [
                            'records' => count($additionalContactInfo[$quoteTypeId]),
                        ]);
                        foreach ($additionalContactInfo[$quoteTypeId] as $contact) {
                            DB::table('customer_additional_contact')->insertOrIgnore([
                                'customer_id' => $contact['customer_id'],
                                'key' => $contact['key'],
                                'value' => $contact['value'],
                            ]);

                            LoggerService::info('Inserted '.$contact['value'].' email as an additional contact information', extra: [
                                'quote_uuid' => $contact['uuid'],
                                'quote_type_id' => $contact['quote_type_id'],
                            ]);
                        }
                    }
                }
            }

        }
    }
}
