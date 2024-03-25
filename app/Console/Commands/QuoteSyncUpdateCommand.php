<?php

namespace App\Console\Commands;

use App\Models\ApplicationStorage;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\QuoteSync;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QuoteSyncUpdateCommand extends Command
{
    use PersonalQuoteSyncTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteSyncUpdate:cron';

    protected $description = 'Sync Quotes Data from QuoteSync table to respective quote tables';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info('----------- QuoteSyncJob Started -----------');
        $isQuoteSyncEnabled = ApplicationStorage::where('key_name', 'quote_sync_enabled')->first();
        $startDate = Carbon::parse('2021-01-05 00:00:00')->toDateTimeString();

        if (! $isQuoteSyncEnabled || $isQuoteSyncEnabled->value == 0) {
            info('----------- QuoteSync is disabled -----------');

            return;
        }

        $entries = QuoteSync::where('is_synced', false)->take(100)->get();

        if ($entries->isEmpty()) {
            info('----------- No entries found to be processed in quote sync table -----------');

            return;
        }

        foreach ($entries as $entry) {

            info('Syncing entry: '.$entry->quote_uuid);

            $quote = PersonalQuote::where('uuid', $entry->quote_uuid)->where('quote_type_id', $entry->quote_type_id)->first();

            if ($quote) {
                // Existing quote
                $this->processExistingQuote($quote, $entry);
            } else {
                // Quote not found
                $this->processQuoteNotFound($entry);
            }
        }
    }

    private function processExistingQuote($quote, $entry)
    {
        if ($entry->quote_type_id) {
            info('Entry for quote: '.$entry->quote_uuid.' found in personal quotes table');
            try {
                $newValues = json_decode($entry->updated_fields, true);
                $this->syncTable($quote, $newValues, 'personal_quotes');
                $quote->quote_type_id = $entry->quote_type_id;
                $quote->save();
                $entry->update(['is_synced' => true, 'synced_at' => now()]);
                info('Entry for quote: '.$entry->quote_uuid.' updated in quote sync table');
            } catch (Exception $e) {
                Log::error('QuoteSyncJob Error: '.$e->getMessage());
            }
        } else {
            info('Entry for quote: '.$entry->quote_uuid.' found in personal quotes table but missing required fields');
            $sourceQuote = $this->getQuoteRecord($entry->quote_type_id, $entry->quote_uuid);
            if ($sourceQuote) {
                $this->syncTable($quote, $sourceQuote->getAttributes(), 'personal_quotes');
                $quote->quote_type_id = $entry->quote_type_id;
                $quote->save();
                $this->syncTable($quote, json_decode($entry->updated_fields, true), 'personal_quotes');
                $quote->quote_type_id = $entry->quote_type_id;
                $quote->save();
            }
        }
    }

    private function processQuoteNotFound($entry)
    {
        info('Entry for quote: '.$entry->quote_uuid.' not found in personal quotes table');
        $sourceQuote = $this->getQuoteRecord($entry->quote_type_id, $entry->quote_uuid);

        if ($sourceQuote) {
            try {
                $newValues = json_decode($entry->updated_fields, true);
                $personalQuote = $this->createPersonalQuoteFromSource($sourceQuote, $entry);
                $this->syncTable($personalQuote, $newValues, 'personal_quotes');
                $this->createPersonalQuoteDetail($personalQuote, $newValues);
                $entry->update(['is_synced' => true, 'synced_at' => now()]);
                info('Entry for quote: '.$personalQuote->id.' saved in personal quotes table');
            } catch (Exception $e) {
                Log::error('QuoteSyncJob Error: '.$e->getMessage().$e->getTraceAsString());
            }
        }
    }

    private function getQuoteRecord($quote_type_id, $quote_uuid)
    {
        $modelClassName = $this->getQuoteType($quote_type_id);
        $sourceQuote = $modelClassName::where('uuid', $quote_uuid)->first();

        return $sourceQuote;
    }

    private function createPersonalQuoteFromSource($sourceQuote, $entry)
    {
        $personalQuote = new PersonalQuote();
        $sourceAttributes = $sourceQuote->getAttributes();
        $this->syncTable($personalQuote, $sourceAttributes, 'personal_quotes');
        $personalQuote->quote_type_id = $entry->quote_type_id;
        $existingQuote = PersonalQuote::where('uuid', $entry->quote_uuid)->where('quote_type_id', $entry->quote_type_id)->first();
        if ($existingQuote) {
            $this->syncTable($existingQuote, $sourceAttributes, 'personal_quotes');
            $existingQuote->quote_type_id = $entry->quote_type_id;
            $existingQuote->save();

            return $existingQuote;
        } else {
            $personalQuote->save();

            return $personalQuote;
        }
    }

    public function getQuoteType($quoteTypeId)
    {
        $quoteTypeModels = [
            1 => CarQuote::class,
            2 => HomeQuote::class,
            3 => HealthQuote::class,
            4 => LifeQuote::class,
            5 => BusinessQuote::class,
            6 => BikeQuote::class,
            7 => YachtQuote::class,
            8 => TravelQuote::class,
            9 => PetQuote::class,
            10 => CycleQuote::class,
            11 => JetskiQuote::class,
        ];

        // Retrieve the source quote based on quote type and UUID
        $modelClassName = $quoteTypeModels[$quoteTypeId];

        return $modelClassName;
    }

    private function createPersonalQuoteDetail($personalQuote, $newValues)
    {
        $personalQuoteDetail = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();

        if (! $personalQuoteDetail) {
            $personalQuoteDetail = new PersonalQuoteDetail();
        }

        $this->syncTable($personalQuoteDetail, $newValues, 'personal_quote_details');

        $personalQuoteDetail->personal_quote_id = $personalQuote->id;
        $personalQuoteDetail->save();

        return $personalQuoteDetail;
    }
}
