<?php

namespace App\Jobs\OCB;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\PersonalQuote;
use App\Services\EmailServices\CyberEmailService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendCyberOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * The UUID of the PersonalQuote.
     */
    private string $quoteUuid;

    /**
     * (Optional) Previous advisor for the quote, if any.
     *
     * @var mixed
     */
    private $previousAdvisor;

    /**
     * Whether to trigger the SIC workflow.
     */
    private bool $triggerSICWorkflow;

    /**
     * Whether to handle zero plans specifically.
     */
    private bool $handleZeroPlans;

    /**
     * Whether to force the SIC workflow regardless of other checks.
     */
    private bool $forceSicWorkflow;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $quoteUuid,
        $previousAdvisor = null,
        bool $triggerSICWorkflow = false,
        bool $handleZeroPlans = false,
        bool $forceSicWorkflow = false
    ) {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
        $this->handleZeroPlans = $handleZeroPlans;
        $this->forceSicWorkflow = $forceSicWorkflow;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(CyberEmailService $cyberEmailService): void
    {
        $lead = PersonalQuote::where('uuid', $this->quoteUuid)->first();
        if (! $lead) {
            LoggerService::info(static::class." - Lead not found for uuid: {$this->quoteUuid}");

            return;
        }
        if (in_array($lead->quote_status_id, [QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost, QuoteStatusEnum::Fake])) {
            LoggerService::info(static::class." - Lead not eligible for OCB email uuid: {$this->quoteUuid}");

            return;
        }
        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::CYBER_OCB_INTRO_EMAIL);

        try {
            $cyberEmailService->sendCyberOCBIntroEmail(
                $lead,
                $this->previousAdvisor,
                $this->triggerSICWorkflow,
                $this->handleZeroPlans,
                $this->forceSicWorkflow
            );
            LoggerService::info(static::class." - OCB Intro Email sent for uuid: {$this->quoteUuid}");
        } catch (Exception $e) {
            LoggerService::error(static::class." - Error sending Cyber OCB Intro Email: {$e->getMessage()} for uuid: {$this->quoteUuid}", [
                'exception' => $e,
                'uuid' => $this->quoteUuid,
            ]);
        } finally {
            LoggerService::endLogging();
        }
    }

    /**
     * Prevent jobs with the same quoteUuid from overlapping.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->quoteUuid))->dontRelease()];
    }
}
