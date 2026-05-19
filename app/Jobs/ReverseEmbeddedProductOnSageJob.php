<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Exceptions\EpSageReversalRetryableException;
use App\Models\EmbeddedTransaction;
use App\Models\EpLog;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use App\Services\SageApiEmbeddedProductService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SendsEpFailureEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;
use Throwable;

class ReverseEmbeddedProductOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    use GenericQueriesAllLobs;
    use SendsEpFailureEmail;

    public int $tries;
    public int $timeout = 120;
    private mixed $sageRequest;
    private EmbeddedTransaction $epTransaction;
    private mixed $request;
    private SageProcess $sageProcess;
    private string $lockPostfix;
    private string $logFor = '';

    public function __construct($sageRequest, EmbeddedTransaction $epTransaction, $request, SageProcess $sageProcess)
    {
        $this->tries = max(1, (int) config('constants.EP_SAGE_REVERSAL_MAX_TRIES', 3));
        $this->sageRequest = $sageRequest;
        $this->epTransaction = $epTransaction;
        $this->request = $request;
        $this->sageProcess = $sageProcess;
        $this->lockPostfix = Carbon::now()->format('YmdHi');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $seconds = max(60, (int) config('constants.EP_SAGE_REVERSAL_BACKOFF_SECONDS', 300));

        return [$seconds, $seconds * 2];
    }

    public function handle(): void
    {
        $this->logFor = 'ReverseEmbeddedProductOnSageJob : '.$this->epTransaction->code;
        LoggerService::startQuoteLogging($this->epTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING_REVERSAL);
        LoggerService::info('--------------------------------Sage Embedded Product Reversal Job execution started--------------------------------');

        $this->sageProcess = SageProcess::query()->findOrFail($this->sageProcess->id);
        $quote = $this->getQuoteObjectBy($this->request->modelType, $this->request->quoteId);
        $this->epTransaction = EmbeddedTransaction::query()->findOrFail($this->epTransaction->id);

        EpLog::create([
            'embedded_transaction_id' => $this->epTransaction->id,
            'event' => 'sage_reversal_attempt',
            'values' => json_encode([
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'sage_process_id' => $this->sageProcess->id,
            ]),
            'loggable_id' => $this->epTransaction->id,
            'loggable_type' => $this->epTransaction->getMorphClass(),
        ]);

        if ($this->sageProcess->status !== SageEnum::SAGE_PROCESS_PENDING_STATUS) {
            LoggerService::info('Reverse embedded product on Sage skipped — process not pending', extra: [
                'SageProcessID' => $this->sageProcess->id,
                'Status' => $this->sageProcess->status,
            ]);
            app(SageApiService::class)->scheduleSageProcesses($this->sageRequest->insurerID);

            return;
        }

        app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PROCESSING_STATUS, null, $this->logFor);

        $isLastAttempt = $this->attempts() >= $this->tries;

        $response = app(SageApiEmbeddedProductService::class)->bookReversalOfEmbeddedProductOnSageAfterImcrmRefund(
            [$quote, $this->epTransaction, $this->sageRequest, $this->epTransaction],
            $this->sageRequest->epShortCode,
            $isLastAttempt
        );

        if (! $response['status']) {
            $message = (string) ($response['message'] ?? 'Sage EP reversal failed');
            if ($message === SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE) {
                LoggerService::info('Sage conflict during EP reversal — resetting process to pending', extra: [
                    'EPCode' => $this->epTransaction->code,
                ]);
                app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, $this->logFor);
                app(SageApiService::class)->scheduleSageProcesses($this->sageRequest->insurerID);

                return;
            }

            if ($isLastAttempt) {
                LoggerService::warning('EP Sage reversal failed on final attempt', extra: [
                    'EPCode' => $this->epTransaction->code,
                    'message' => $message,
                ]);
                app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message, $this->logFor);
                EpLog::create([
                    'embedded_transaction_id' => $this->epTransaction->id,
                    'event' => 'sage_reversal_failed',
                    'values' => json_encode([
                        'message' => $message,
                        'response' => $response,
                    ]),
                    'loggable_id' => $this->epTransaction->id,
                    'loggable_type' => $this->epTransaction->getMorphClass(),
                ]);
                $this->sendEpReversalFailureEmail(
                    (int) $this->request->quoteId,
                    (int) $this->request->quoteTypeId,
                    (int) $this->epTransaction->id,
                    $this->logFor,
                    $message
                );
                app(SageApiService::class)->scheduleSageProcesses($this->sageRequest->insurerID);

                return;
            }

            LoggerService::info('EP Sage reversal attempt failed — will retry', extra: [
                'EPCode' => $this->epTransaction->code,
                'message' => $message,
                'attempt' => $this->attempts(),
            ]);
            app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_PENDING_STATUS, $message, $this->logFor);
            throw new EpSageReversalRetryableException($message);
        }

        app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_COMPLETED_STATUS, null, $this->logFor);

        EpLog::create([
            'embedded_transaction_id' => $this->epTransaction->id,
            'event' => 'sage_reversal_success',
            'values' => json_encode([
                'message' => $response['message'] ?? null,
            ]),
            'loggable_id' => $this->epTransaction->id,
            'loggable_type' => $this->epTransaction->getMorphClass(),
        ]);

        app(SageApiService::class)->scheduleSageProcesses($this->sageRequest->insurerID);
        LoggerService::info('--------------------------------Sage Embedded Product Reversal Job execution completed--------------------------------');
    }

    public function failed(Throwable $exception): void
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if (str_contains($message, SageEnum::SAGE_TIMEOUT_REQUEST_MESSAGE)) {
            app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_TIMEOUT_STATUS, $message);
        } else {
            app(SageApiService::class)->updateSageProcessStatus($this->sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, $message);
        }

        LoggerService::error('ReverseEmbeddedProductOnSageJob exhausted or timed out', extra: [
            'EPCode' => $this->epTransaction->code,
            'ErrorCode' => $code,
            'ErrorMessage' => $message,
            'trace' => $exception->getTraceAsString(),
        ]);

        app(SageApiEmbeddedProductService::class)->updateAndLogEPBookingStatus(
            $this->epTransaction,
            SageEmbeddedProductEnum::BOOKING_REVERSAL_FAILED->id(),
            $this->logFor
        );

        EpLog::create([
            'embedded_transaction_id' => $this->epTransaction->id,
            'event' => 'sage_reversal_job_failed',
            'values' => json_encode([
                'message' => $message,
                'code' => $code,
            ]),
            'loggable_id' => $this->epTransaction->id,
            'loggable_type' => $this->epTransaction->getMorphClass(),
        ]);

        $this->sendEpReversalFailureEmail(
            (int) $this->request->quoteId,
            (int) $this->request->quoteTypeId,
            (int) $this->epTransaction->id,
            $this->logFor,
            $message
        );

        app(SageApiService::class)->scheduleSageProcesses($this->sageRequest->insurerID);
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->epTransaction->code.'-reverse-'.$this->lockPostfix))->dontRelease()];
    }
}
