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
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class QuoteSyncUpdateCommand extends Command
{
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

        $isQuoteSyncEnabled = ApplicationStorage::where('key_name', 'quote_sync_enabled')->first();
        // add date in this format yyyy-mm-dd hh:mm:ss
        $startDate = Carbon::parse('2021-01-05 00:00:00')->toDateTimeString();
        if (! $isQuoteSyncEnabled || $isQuoteSyncEnabled->value == 0) {
            info('----------- QuoteSync is disabled -----------');

            return;
        }

        $entries = QuoteSync::where('is_synced', false)->whereBetween('created_at', [$startDate, now()->endOfDay()])->take(30)->get();

        foreach ($entries as $entry) {
            info('Syncing entry: '.$entry->quote_uuid);
            $quote = PersonalQuote::where('uuid', $entry->quote_uuid)->where('quote_type_id', $entry->quote_type_id)->first();
            if ($quote) {
                $quoteDetail = PersonalQuoteDetail::where('personal_quote_id', $quote->id)->first();
                DB::beginTransaction();
                try {
                    $newValues = json_decode($entry->updated_fields, true);
                    foreach ($newValues as $column => $value) {
                        if ($column === 'id') {
                            continue;
                        }

                        if (Schema::hasColumn('personal_quotes', $column)) {
                            $columnType = DB::getSchemaBuilder()->getColumnType('personal_quotes', $column);
                            // Surround the value with quotes if it's a string, date, or datetime
                            if (in_array($columnType, ['string', 'date', 'datetime'])) {
                                $value = "'$value'";
                            }
                            if ($column == 'currently_insured_with') {
                                $quote->currently_insured_with_id = $value;
                            } else {
                                $quote->$column = $value;
                            }
                        }

                        if (! $quoteDetail) {
                            info('Entry for quote : '.$entry->quote_uuid.' not found in personal quotes details table');
                        }
                        $quoteDetail = $this->createOrUpdatePersonalQuoteDetail($quote, $newValues);

                    }

                    $quote->save();
                    $entry->update(['is_synced' => true, 'synced_at' => now()]);
                    info('Entry for quote : '.$entry->quote_uuid.' updated in quote sync table');
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('QuoteSyncJob Error: '.$e->getMessage());
                }
            } else {
                info('Entry for quote : '.$entry->quote_uuid.' not found in personal quotes table');
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

                $modelClassName = $quoteTypeModels[$entry->quote_type_id];
                $sourceQuote = $modelClassName::where('uuid', $entry->quote_uuid)->first();
                if ($sourceQuote) {
                    DB::beginTransaction();
                    try {
                        $newValues = json_decode($entry->updated_fields, true);
                        $personalQuote = $this->createOrUpdatePersonalQuote($sourceQuote, $newValues, $entry);
                        $this->createOrUpdatePersonalQuoteDetail($personalQuote, $newValues, $entry);
                        $entry->update(['is_synced' => true, 'synced_at' => now()]);
                        info('Entry for quote : '.$personalQuote->id.' saved in personal quotes table');
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error(' QuoteSyncJob Error: '.$e->getMessage());
                    }
                }
            }
        }
    }

    private function createOrUpdatePersonalQuote($sourceQuote, $newValues, $entry)
    {
        $personalQuote = PersonalQuote::where('uuid', $sourceQuote->uuid)->first();
        if ($personalQuote) {
            foreach ($newValues as $column => $value) {
                if ($column === 'id') {
                    continue;
                }

                if (Schema::hasColumn('personal_quotes', $column)) {
                    $columnType = DB::getSchemaBuilder()->getColumnType('personal_quotes', $column);
                    // Surround the value with quotes if it's a string, date, or datetime
                    if (in_array($columnType, ['string', 'date', 'datetime'])) {
                        $value = "'$value'";
                    }
                    if ($column == 'currently_insured_with') {
                        $personalQuote->currently_insured_with_id = $value;
                    } else {
                        $personalQuote->$column = $value;
                    }
                }
            }
            $personalQuote->uuid = $entry->quote_uuid;
            $personalQuote->quote_type_id = $entry->quote_type_id;
            $personalQuote->save();
        } else {
            $personalQuote = new PersonalQuote();
            foreach ($newValues as $column => $value) {
                if ($column === 'id') {
                    continue;
                }

                if (Schema::hasColumn('personal_quotes', $column)) {
                    $personalQuote->$column = $value;
                }
            }
            $personalQuote->uuid = $entry->quote_uuid;
            $personalQuote->quote_type_id = $entry->quote_type_id;
            $personalQuote->save();
        }

        return $personalQuote;
    }

    private function createOrUpdatePersonalQuoteDetail($personalQuote, $newValues)
    {

        $personalQuoteDetail = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();
        if ($personalQuoteDetail) {
            foreach ($newValues as $column => $value) {
                if ($column === 'id') {
                    continue;
                }

                if (Schema::hasColumn('personal_quote_details', $column)) {
                    $columnType = DB::getSchemaBuilder()->getColumnType('personal_quote_details', $column);
                    // Surround the value with quotes if it's a string, date, or datetime
                    if (in_array($columnType, ['string', 'date', 'datetime'])) {
                        $value = "'$value'";
                    }
                    $personalQuoteDetail->$column = $value;
                }
            }
            $personalQuoteDetail->save();
        } else {
            $personalQuoteDetail = new PersonalQuoteDetail();
            foreach ($newValues as $column => $value) {
                if ($column === 'id') {
                    continue;
                }

                if (Schema::hasColumn('personal_quote_details', $column)) {
                    $columnType = DB::getSchemaBuilder()->getColumnType('personal_quote_details', $column);
                    // Surround the value with quotes if it's a string, date, or datetime
                    if (in_array($columnType, ['string', 'date', 'datetime'])) {
                        $value = "'$value'";
                    }
                    $personalQuoteDetail->$column = $value;
                }
            }
            $personalQuoteDetail->personal_quote_id = $personalQuote->id;
            $personalQuoteDetail->save();
        }

        return $personalQuoteDetail;
    }
}
