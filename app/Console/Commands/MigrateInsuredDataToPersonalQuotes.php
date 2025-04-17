<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class MigrateInsuredDataToPersonalQuotes extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:update-insured-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update personal quotes with insured_id from related entity mapping';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('PersonalQuotes - Update Insured and Quote ID - Starting to update personal quotes with insured_id and quote_id...');

        $totalUpdated = 0;

        // Use chunk to process records in batches to avoid memory issues
        PersonalQuote::whereNull('quote_id')->whereNull('insured_id')
            ->chunk(100, function ($personalQuotes) use (&$totalUpdated) {
                foreach ($personalQuotes as $personalQuote) {
                    $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
                    $quote = $this->getQuoteObject($quoteType, $personalQuote->uuid);

                    if (! $quote) {
                        info("Quote Code: {$personalQuote->code} - Quote not found.");

                        continue;
                    }

                    // Find the entity mapping for this quote
                    $entityMapping = QuoteRequestEntityMapping::where('quote_request_id', $quote->id)
                        ->where('quote_type_id', $personalQuote->quote_type_id)
                        ->first();

                    if (! $entityMapping) {
                        info("Quote Code: {$quote->code} - Entity mapping not found.");

                        continue;
                    }

                    // Get insured record using entity_id
                    $insured = Insured::where('entity_id', $entityMapping->entity_id)->first();

                    if (! $insured) {
                        info("Quote Code: {$quote->code} - Entity Mapping ID: {$entityMapping->id} - Entity ID: {$entityMapping->entity_id} - Insured not found.");

                        continue;
                    }

                    // Update the personal quote with insured_id
                    $personalQuote->update([
                        'insured_id' => $insured->id,
                        'quote_id' => $quote->id,
                    ]);

                    $totalUpdated++;
                    info("Updated Personal Quote ID: {$personalQuote->id} with Insured ID: {$insured->id} and Quote ID: {$quote->id}");
                }
            });

        info("PersonalQuotes - Update Insured and Quote ID - Command completed. Total updated quotes: {$totalUpdated}");

        return 0;
    }
}
