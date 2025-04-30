<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MigrateInsuredDataToPersonalQuotes extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'personal-quotes:update-insured-and-quote-id {--force : Process all entries including previously checked ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update personal quotes with insured_id from related entity mapping';

    protected $cacheKey = 'processed_personal_quote_ids';

    const className = 'migrateInsuredDataToPersonalQuotes';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $funName = __FUNCTION__;
        LoggerService::info(self::className.' fn:'.$funName.' PersonalQuotes - Update Insured and Quote ID - Starting to update personal quotes with insured_id and quote_id...');

        $totalUpdated = 0;
        $forceProcess = $this->option('force');

        // Get already processed IDs from cache or initialize empty array
        $lastProcessedId = Cache::get($this->cacheKey, null);

        // Clear the cache if force option is used
        if ($forceProcess) {
            $lastProcessedId = null;
            Cache::forget($this->cacheKey);
            LoggerService::info(self::className.' fn:'.$funName.' Force option used. Clearing processed records cache.');
        } else {
            LoggerService::info(self::className.' fn:'.$funName.' previously processed record id :  '.$lastProcessedId.' in cache.');
        }

        // Use chunk to process records in batches to avoid memory issues
        PersonalQuote::when($lastProcessedId, function ($q) use ($lastProcessedId) {
            $q->where('id', '>', $lastProcessedId);
        })
            ->where(function ($q) {
                $q->whereNull('quote_id')
                    ->orWhereNull('insured_id');
            })
            ->orderBy('id')
            ->chunkById(1000, function ($personalQuotes) use (&$totalUpdated, &$lastProcessedId, $funName) {
                foreach ($personalQuotes as $personalQuote) {

                    LoggerService::startQuoteLogging($personalQuote);

                    $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
                    $quote = $this->getQuoteObjectBy($quoteType, $personalQuote->uuid, 'uuid');

                    if (! $quote) {
                        LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' - Quote not found.');
                        $lastProcessedId = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with quote_id
                    if ($quote->getMorphClass() != PersonalQuote::class) {
                        $personalQuote->update(['quote_id' => $quote->id]);
                        LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' updated.', ['quote_id' => $quote->id]);
                    } else {
                        LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' - Quote is of Personal QuoteTable.');
                    }

                    // Find the entity mapping for this quote
                    $entityMapping = QuoteRequestEntityMapping::where('quote_request_id', $quote->id)
                        ->where('quote_type_id', $personalQuote->quote_type_id)
                        ->first();

                    if (! $entityMapping) {
                        LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$quote->code.' - Entity mapping not found.');
                        $lastProcessedId = $personalQuote->id;

                        continue;
                    }

                    if ($entityMapping->entity_type_code) {
                        $personalQuote->update(['insured_type_code' => $entityMapping->entity_type_code]);
                    }

                    // Get insured record using entity_id
                    $insured = Insured::where('entity_id', $entityMapping->entity_id)->first();

                    if (! $insured) {
                        LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$quote->code.' - Entity Mapping ID: '.$entityMapping->id.' - Entity ID: '.$entityMapping->entity_id.' - Insured not found.');
                        $lastProcessedId = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with insured_id
                    $personalQuote->update(['insured_id' => $insured->id]);
                    LoggerService::info(self::className.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' updated.', ['insured_id' => $insured->id]);

                    $lastProcessedId = $personalQuote->id;
                    $totalUpdated++;
                    LoggerService::info(self::className.' fn:'.$funName.' Updated Personal Quote ID: '.$personalQuote->id.' with Insured ID: '.$insured->id.' and Quote ID: '.$quote->id);
                }

                // Update the cache after each chunk to avoid losing progress
                Cache::put($this->cacheKey, $lastProcessedId, now()->addDays(30));

                // Report progress
                LoggerService::info(self::className.' fn:'.$funName.' Processed a chunk. Last processed ID in cache: '.$lastProcessedId);
            });

        // Get final count from cache for reporting
        $finalProcessedId = Cache::get($this->cacheKey, null);

        LoggerService::info(self::className.' fn:'.$funName.' PersonalQuotes - Update Insured and Quote ID - Command completed. Last cached IDs: '.$finalProcessedId);

        return 0;
    }
}
