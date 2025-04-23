<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
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
        // TODO : not every quote have entity so in case of failure need to discuss

        info(self::className.' fn:'.__FUNCTION__.' PersonalQuotes - Update Insured and Quote ID - Starting to update personal quotes with insured_id and quote_id...');

        $totalUpdated = 0;
        $forceProcess = $this->option('force');

        // Get already processed IDs from cache or initialize empty array
        $processedIds = Cache::get($this->cacheKey, []);

        // Clear the cache if force option is used
        if ($forceProcess) {
            $processedIds = [];
            Cache::forget($this->cacheKey);
            info(self::className.' fn:'.__FUNCTION__.' Force option used. Clearing processed records cache.');
        } else {
            info(self::className.' fn:'.__FUNCTION__.' Found '.count($processedIds).' previously processed records in cache.');
        }

        // Use chunk to process records in batches to avoid memory issues
        PersonalQuote::whereNull('quote_id')
            ->orWhereNull('insured_id')
            ->whereNotIn('id', $processedIds)
            ->chunkById(1000, function ($personalQuotes) use (&$totalUpdated, &$processedIds) {
                foreach ($personalQuotes as $personalQuote) {
                    $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
                    $quote = $this->getQuoteObjectBy($quoteType, $personalQuote->uuid, 'uuid');

                    if (! $quote) {
                        info(self::className.' fn:'.__FUNCTION__.' Quote Code: '.$personalQuote->code.' - Quote not found.');
                        $processedIds[] = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with quote_id
                    if ($quote->getMorphClass() != PersonalQuote::class) {
                        $personalQuote->update(['quote_id' => $quote->id]);
                    } else {
                        info(self::className.' fn:'.__FUNCTION__.' Quote Code: '.$personalQuote->code.' - Quote is of Personal QuoteTable.');
                    }

                    // Find the entity mapping for this quote
                    $entityMapping = QuoteRequestEntityMapping::where('quote_request_id', $quote->id)
                        ->where('quote_type_id', $personalQuote->quote_type_id)
                        ->first();

                    if (! $entityMapping) {
                        info(self::className.' fn:'.__FUNCTION__.' Quote Code: '.$quote->code.' - Entity mapping not found.');
                        $processedIds[] = $personalQuote->id;

                        continue;
                    }

                    // Get insured record using entity_id
                    $insured = Insured::where('entity_id', $entityMapping->entity_id)->first();

                    if (! $insured) {
                        info(self::className.' fn:'.__FUNCTION__.' Quote Code: '.$quote->code.' - Entity Mapping ID: '.$entityMapping->id.' - Entity ID: '.$entityMapping->entity_id.' - Insured not found.');
                        $processedIds[] = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with insured_id
                    $personalQuote->update(['insured_id' => $insured->id]);

                    $processedIds[] = $personalQuote->id;
                    $totalUpdated++;
                    info(self::className.' fn:'.__FUNCTION__.' Updated Personal Quote ID: '.$personalQuote->id.' with Insured ID: '.$insured->id.' and Quote ID: '.$quote->id);
                }

                // Update the cache after each chunk to avoid losing progress
                Cache::put($this->cacheKey, $processedIds, now()->addDays(30));

                // Report progress
                info(self::className.' fn:'.__FUNCTION__.' Processed a chunk. Current total processed IDs in cache: '.count($processedIds));
            });

        // Get final count from cache for reporting
        $finalProcessedIds = Cache::get($this->cacheKey, []);

        info(self::className.' fn:'.__FUNCTION__.' PersonalQuotes - Update Insured and Quote ID - Command completed. Total cached IDs: '.count($finalProcessedIds));

        return 0;
    }
}
