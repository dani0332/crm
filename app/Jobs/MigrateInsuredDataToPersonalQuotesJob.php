<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class MigrateInsuredDataToPersonalQuotesJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    public $tries = 1;

    /**
     * Indicates if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Whether to force process all entries, including previously processed ones
     *
     * @var bool
     */
    protected $forceProcess;

    /**
     * Cache key for tracking processed IDs
     *
     * @var string
     */
    protected $cacheKey = 'last_processed_personal_quote_id';

    private $lockPostfix;

    /**
     * Class name for logging
     *
     * @var string
     */
    const CLASS_NAME = 'migrateInsuredDataToPersonalQuotesJob';

    /**
     * Create a new job instance.
     *
     * @param  bool  $forceProcess  Whether to force processing of all entries
     * @return void
     */
    public function __construct(bool $forceProcess, $lockKey)
    {
        $this->forceProcess = $forceProcess;
        $this->lockPostfix = $lockKey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $funName = __FUNCTION__;
        LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' PersonalQuotes - Update Insured and Quote ID - Starting to update personal quotes with insured_id and quote_id...');

        $totalUpdated = 0;

        // Get already processed IDs from cache or initialize empty array
        $lastProcessedId = Cache::get($this->cacheKey, null);

        // Clear the cache if force option is used
        if ($this->forceProcess) {
            $lastProcessedId = null;
            Cache::forget($this->cacheKey);
            LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Force option used. Clearing processed records cache.');
        } else {
            LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' previously processed record id :  '.$lastProcessedId.' in cache.');
        }

        $allowedQuoteTypes = [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Life, QuoteTypeId::Business, QuoteTypeId::Travel, QuoteTypeId::Home, QuoteTypeId::Bike, QuoteTypeId::Yacht];
        // Use chunk to process records in batches to avoid memory issues
        PersonalQuote::select(['id', 'code', 'uuid', 'quote_type_id', 'quote_id', 'insured_id'])
            ->whereIn('quote_type_id', $allowedQuoteTypes)
            ->when($lastProcessedId, function ($q) use ($lastProcessedId) {
                $q->where('id', '>', $lastProcessedId);
            })
            ->where(function ($q) {
                $q->whereNull('quote_id')
                    ->orWhereNull('insured_id');
            })
            ->orderBy('id')
            ->chunkById(1000, function ($personalQuotes) use (&$totalUpdated, &$lastProcessedId, $funName) {
                $chunkStartTime = microtime(true);
                foreach ($personalQuotes as $personalQuote) {
                    $personalQuoteUpdateData = [];
                    $iterationStartTime = microtime(true);

                    /*LoggerService::startQuoteLogging($personalQuote);
                    LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' - Start : ', extra: ['insured_id' => $personalQuote->insured_id, 'quote_id' => $personalQuote->quote_id]);*/

                    $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
                    $quote = $this->getSelectedQuoteObjectBy($quoteType, $personalQuote->uuid, 'uuid');

                    if (! $quote) {
                        /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' - Quote not found.'); */
                        $lastProcessedId = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with quote_id
                    $isPersonalQuote = $quote->getMorphClass() == PersonalQuote::class;
                    if (! $isPersonalQuote && ! $personalQuote->quote_id) {
                        $personalQuoteUpdateData['quote_id'] = $quote->id;
                        /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' updated.', ['quote_id' => $quote->id]); */
                    } else {
                        /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' - Quote is of Personal Quote Table.'); */
                    }

                    // Find the entity mapping for this quote
                    $entityMapping = QuoteRequestEntityMapping::where('quote_request_id', $quote->id)
                        ->where('quote_type_id', $personalQuote->quote_type_id)
                        ->first();

                    if ($entityMapping) {
                        // Get insured record using entity_id
                        $insured = Insured::where('entity_id', $entityMapping->entity_id)->first();

                        if ($insured && ! $personalQuote->insured_id) {
                            $personalQuoteUpdateData['insured_id'] = $insured->id;
                        } else {
                            /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$quote->code.' - Entity Mapping ID: '.$entityMapping->id.' - Entity ID: '.$entityMapping->entity_id.' - Insured not found.'); */
                            $lastProcessedId = $personalQuote->id;
                        }

                    } else {
                        /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$quote->code.' - Entity mapping not found.'); */
                        $lastProcessedId = $personalQuote->id;
                    }

                    // Update the personal quote
                    if (! empty($personalQuoteUpdateData)) {
                        $personalQuote->updateQuietly($personalQuoteUpdateData);

                        /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Updated Personal Quote ID: '.$personalQuote->id.' - Quote Code: '.$personalQuote->code.' updated.', extra: ['data' => $personalQuoteUpdateData]); */
                    }
                    $iterationEndTime = microtime(true);
                    $iterationExecutionTime = $iterationEndTime - $iterationStartTime;
                    /* LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Quote Code: '.$personalQuote->code.' iteration execution time(seconds) : '.$iterationExecutionTime, extra: ['Data' => $personalQuoteUpdateData]); */

                    $lastProcessedId = $personalQuote->id;
                    $totalUpdated++;
                }

                // Update the cache after each chunk to avoid losing progress
                Cache::put($this->cacheKey, $lastProcessedId, now()->addDays(30));

                $chunkEndTime = microtime(true);
                $chunkExecutionTime = $chunkEndTime - $chunkStartTime;
                // Report progress
                LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' Chunk execution time(seconds) : '.$chunkExecutionTime.' Processed a chunk. Last processed ID in cache: '.$lastProcessedId);
            });

        // Get final id from cache for reporting
        $finalProcessedId = Cache::get($this->cacheKey, null);

        LoggerService::info(self::CLASS_NAME.' fn:'.$funName.' PersonalQuotes - Update Insured and Quote ID - Job completed. Last cached IDs: '.$finalProcessedId);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        LoggerService::error(self::CLASS_NAME.' Job failed with exception: '.$exception->getMessage(), extra: [
            'trace' => $exception->getTraceAsString(),
            'last_processed_id' => Cache::get($this->cacheKey, null),
        ]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->cacheKey.'-'.$this->lockPostfix))->dontRelease()];
    }
}
