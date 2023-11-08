<?php

namespace App\Console\Commands;

use App\Models\PersonalQuote;
use App\Models\QuoteSync;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetLeadAllocationCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteSync:cron';

    protected $description = 'Sync Quotes Data from QuoteSync table to respective quote tables';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $entries = QuoteSync::where('is_synced', false)->get();

        foreach ($entries as $entry) {
            info('Syncing QuoteSync entry: '.$entry->id);
            $quote = PersonalQuote::where('uuid', $entry->quote_uuid)->first();
            info('Syncing QuoteSync entry: '.$entry->id.' quote Id : '.$quote->uuid);
            if ($quote) {
                DB::beginTransaction();
                try {
                    $newValues = json_decode($entry->updated_fields, true);

                    foreach ($newValues as $column => $value) {
                        if (Schema::hasColumn('personal_quotes', $column)) {
                            $quote->$column = $value;
                        }
                    }

                    $quote->save();

                    $entry->update(['is_synced' => true, 'synced_at' => now()]);

                    DB::commit();

                } catch (Exception $e) {
                    DB::rollBack();
                    info(' QuoteSyncJob Error: '.$e->getMessage());
                }
            } else {
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
                        $personalQuote = new PersonalQuote();
                        $personalQuote->uuid = $entry->quote_uuid;
                        foreach ($newValues as $column => $value) {
                            if (Schema::hasColumn('personal_quotes', $column)) {
                                $personalQuote->$column = $value;
                            }
                        }
                        info('Syncing QuoteSync entry: '.$entry->id.' personalQuote: '.$personalQuote);
                        $personalQuote->save();
                        $entry->update(['processed' => true]);
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        info(' QuoteSyncJob Error: '.$e->getMessage());
                    }
                }
            }
        }
    }

}
