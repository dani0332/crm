<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UniversalSearchDataMigration implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    public $tries = 1;

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

        $allowedQuoteTypes = [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Life, QuoteTypeId::Business, QuoteTypeId::Travel];
        // Use chunk to process records in batches to avoid memory issues
        PersonalQuote::select(['id', 'code', 'uuid', 'quote_type_id', 'quote_id', 'insured_id'])
            ->whereIn('quote_type_id', $allowedQuoteTypes)
            ->when($lastProcessedId, function ($q) use ($lastProcessedId) {
                $q->where('id', '>', $lastProcessedId);
            })
            ->whereNull('quote_id')
            ->orderBy('id')
            ->chunkById(1000, function ($personalQuotes) use (&$totalUpdated, &$lastProcessedId, $funName) {
                $chunkStartTime = microtime(true);

                foreach ($personalQuotes as $personalQuote) {

                    $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
                    $quote = $this->getSelectedQuoteObjectBy($quoteType, $personalQuote->uuid, 'uuid');

                    if (! $quote) {
                        $lastProcessedId = $personalQuote->id;

                        continue;
                    }

                    // Update the personal quote with quote_id
                    $isPersonalQuote = $quote->getMorphClass() == PersonalQuote::class;
                    if (! $isPersonalQuote && ! $personalQuote->quote_id) {
                        $personalQuote->updateQuietly(['quote_id' => $quote->id]);
                    }

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
