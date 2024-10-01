<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Services\CRUDService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FixQuoteStatusDate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $quoteType;
    private $statuses;
    private $chunkSize;
    public $tries = 3;
    public $timeout = 2000;
    public $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(QuoteTypes $quoteType, $statuses, $chunkSize = 200)
    {
        $this->quoteType = $quoteType;
        $this->statuses = $statuses;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $leadType = $this->quoteType->value;
        info(self::class." - Fixing Quote Status Date for {$leadType} Leads...");
        $totalRecordsFixed = [];
        $this->quoteType?->model()::whereIn('quote_status_id', $this->statuses)
            ->chunk($this->chunkSize, function ($leads) use (&$totalRecordsFixed, $leadType) {
                foreach ($leads as $lead) {
                    $history = app(CRUDService::class)->getLeadAuditHistory(strtolower($leadType), $lead->id);

                    $lastHistoryWithLeadStatus = $history->filter(fn ($record) => ! empty($record->NewStatus))->first();

                    if ($lastHistoryWithLeadStatus && $lastHistoryWithLeadStatus->ModifiedAt && Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt)->toDateTimeString() !== Carbon::parse($lead->quote_status_date)->toDateTimeString()) {
                        info(self::class." - Updating {$leadType} Lead Quote Status Date from {$lead->quote_status_date} to {$lastHistoryWithLeadStatus->ModifiedAt} for uuid: {$lead->uuid}");
                        $this->quoteType?->model()::withoutEvents(function () use ($lead, $lastHistoryWithLeadStatus) {
                            $lead->update([
                                'quote_status_date' => Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt),
                            ]);
                        });
                        PersonalQuote::withoutEvents(function () use ($lead, $lastHistoryWithLeadStatus) {
                            PersonalQuote::where('uuid', $lead->uuid)->update([
                                'quote_status_date' => Carbon::parse($lastHistoryWithLeadStatus->ModifiedAt),
                            ]);
                        });
                        $totalRecordsFixed[] = $lead->uuid;
                    }
                }
                info(self::class." - Going to Sleep for 2 seconds before Retrying next iteration for next {$this->chunkSize} records");
                sleep(2);
                info(self::class.' - Waking up after 2 seconds');
            });

        $fixedCount = count($totalRecordsFixed);
        info(self::class." - Total Fixed Records: {$fixedCount} for {$leadType} Leads");
    }
}
