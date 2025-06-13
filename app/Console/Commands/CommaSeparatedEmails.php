<?php

namespace App\Console\Commands;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
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

        LoggerService::info('Found '.count($personalQuotes).' personal quotes with comma-separated emails');

        if (! empty($personalQuotes)) {
            LoggerService::info('Fixing comma-separated emails');
            foreach ($personalQuotes as $quote) {
                $emails = explode(',', $quote->email);
                $primaryEmail = trim($emails[0]);
                $secondaryEmail = trim($emails[1]) ?? null;

                $customer = Customer::firstOrCreate([
                    'email' => $primaryEmail,
                ], [
                    'updated_at' => now(),
                ]);

                $quoteType = QuoteTypes::getName($quote->quote_type_id)->value;
                $tableName = strtolower($quoteType).'_quote_request';

                DB::table($tableName)->where('uuid', $quote->uuid)
                    ->update([
                        'email' => $primaryEmail,
                        'customer_id' => $customer->id,
                        'updated_at' => now(),
                    ]);

                DB::table('personal_quotes')->where('uuid', $quote->uuid)->where('quote_type_id', $quote->quote_type_id)
                    ->update([
                        'email' => $primaryEmail,
                        'customer_id' => $customer->id,
                        'updated_at' => now(),
                    ]);

                LoggerService::info('Email updated on '.$tableName.' and personal_quotes table.', extra: [
                    'uuid' => $quote->uuid,
                    'email' => $primaryEmail,
                ]);

                $additionalContact = CustomerAdditionalContact::firstOrCreate([
                    'customer_id' => $customer->id,
                    'key' => 'email',
                    'value' => $secondaryEmail,
                ]);

                if ($additionalContact->wasRecentlyCreated) {
                    LoggerService::info('Inserted '.$secondaryEmail.' email as an additional contact information', extra: [
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quote->quote_type_id,
                    ]);
                }
            }

            LoggerService::info('comma-separated emails issue fixed');
        }
    }
}
