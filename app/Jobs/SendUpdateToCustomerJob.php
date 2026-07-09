<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Jobs\EP\SendEPJob;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use App\Services\SendEmailCustomerService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SendUpdateToCustomerJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 100;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    private $sendUpdate;

    private $payload;
    public function __construct($sendUpdateLog, $payload)
    {
        $this->sendUpdate = $sendUpdateLog;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, SendUpdateLogService $sendUpdateLogServices)
    {
        $sendUpdateLog = SendUpdateLog::find($this->sendUpdate->id);
        LoggerService::startQuoteLogging($sendUpdateLog->code);
        LoggerService::info('job:SendUpdateToCustomerJob - Job started');

        $this->processEmailSending($sendUpdateLog, $sendEmailCustomerService, $sendUpdateLogServices);
        $this->processSageCallIfNeeded($sendUpdateLog, $sendUpdateLogServices);

        LoggerService::info('job:SendUpdateToCustomerJob - Job completed - SendUpdateCode: '.$sendUpdateLog->code);
    }

    private function processEmailSending($sendUpdateLog, SendEmailCustomerService $sendEmailCustomerService, SendUpdateLogService $sendUpdateLogServices)
    {
        if ($sendUpdateLog->is_email_sent) {
            LoggerService::info('job:SendUpdateToCustomerJob - Email process skipped - Email already sent');

            return;
        }

        $quote = $this->getQuoteForEmail($sendUpdateLog);
        [$templateId, $emailData, $tag, $quoteTypeId] = $sendUpdateLogServices->sendUpdateToCustomerEmailData($this->sendUpdate, $quote);

        if (empty($templateId) && (int) $this->sendUpdate?->quote_type_id !== QuoteTypeId::Device) {
            LoggerService::warning('job:SendUpdateToCustomerJob - Template ID not found - skipping sendUpdateToCustomerEmailData email', extra: ['emailData' => json_encode($emailData), 'quoteTypeId' => $quoteTypeId]);

            return;
        }

        LoggerService::info('job:SendUpdateToCustomerJob - Job Email Data prepared', extra: ['emailData' => json_encode($emailData), 'quoteTypeId' => $quoteTypeId]);

        $response = $this->sendEmailBasedOnQuoteType($quote, $quoteTypeId, $templateId, $emailData, $tag, $sendEmailCustomerService);
        $this->handleEmailResponse($response, $sendUpdateLog, $quoteTypeId);
    }

    private function getQuoteForEmail($sendUpdateLog)
    {
        $quoteTypeId = $sendUpdateLog->quote_type_id;
        $quoteType = QuoteTypeId::getOptions()[$quoteTypeId];
        $quoteModel = $this->getModelObject($quoteType);

        return $quoteModel::with('latestInsured')->where('uuid', $sendUpdateLog->quote_uuid)->first();
    }

    private function sendEmailBasedOnQuoteType($quote, $quoteTypeId, $templateId, $emailData, $tag, SendEmailCustomerService $sendEmailCustomerService)
    {
        if ($quoteTypeId == QuoteTypeId::Device) {
            return $this->sendDeviceEmail($quote, $quoteTypeId, $emailData);
        }

        return $this->sendBrevoEmail($quote, $quoteTypeId, $templateId, $emailData, $tag, $sendEmailCustomerService);
    }

    private function sendDeviceEmail($quote, $quoteTypeId, $emailData)
    {
        LoggerService::info('job:SendUpdateToCustomerJob - Routing Device quote to Bird', extra: ['uuid' => $quote->uuid]);

        $birdEmailData = app(CentralService::class)->prepareDeviceUpdateBirdData($quote, $quoteTypeId, $emailData);

        if (empty($birdEmailData)) {
            LoggerService::error('job:SendUpdateToCustomerJob - Failed to prepare Bird email data', extra: ['uuid' => $quote->uuid]);

            return null;
        }

        $response = app(CentralService::class)->sendUpdateToCustomerEmail($quote, $birdEmailData, $quoteTypeId, 'Device Update');
        LoggerService::info('job:SendUpdateToCustomerJob - Bird response received', extra: ['response' => json_encode($response), 'uuid' => $quote->uuid]);

        return $response;
    }

    private function sendBrevoEmail($quote, $quoteTypeId, $templateId, $emailData, $tag, SendEmailCustomerService $sendEmailCustomerService)
    {
        LoggerService::info('job:SendUpdateToCustomerJob - Routing to Brevo', extra: ['quoteType' => $quoteTypeId, 'uuid' => $quote->uuid]);

        $response = $sendEmailCustomerService->sendUpdateToCustomerEmail($templateId, $emailData, $tag, $quoteTypeId);
        LoggerService::info('job:SendUpdateToCustomerJob - Brevo response received', extra: ['response' => json_encode($response), 'uuid' => $quote->uuid]);

        return $response;
    }

    private function handleEmailResponse($response, $sendUpdateLog, $quoteTypeId)
    {
        if ($this->isEmailResponseSuccessful($quoteTypeId, $response)) {
            $this->updateEmailStatusToSent($sendUpdateLog, $response);
            $this->dispatchEPJobIfNeeded($sendUpdateLog);
        } else {
            LoggerService::info('job:SendUpdateToCustomerJob - Job failed');
        }
    }

    private function isEmailResponseSuccessful($quoteTypeId, $response)
    {
        if ($quoteTypeId === QuoteTypeId::Device) {
            return intval($response) === Response::HTTP_OK || intval($response) === Response::HTTP_CREATED;
        }

        return intval($response) === Response::HTTP_CREATED;
    }

    private function updateEmailStatusToSent($sendUpdateLog, $response)
    {
        LoggerService::info('job:SendUpdateToCustomerJob - Updating status', extra: [
            'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            'status_code' => $response,
        ]);

        $sendUpdateLog->update([
            'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            'is_email_sent' => true,
        ]);
        $sendUpdateLog->refresh();
    }

    private function dispatchEPJobIfNeeded($sendUpdateLog)
    {
        if ($sendUpdateLog->category->code !== SendUpdateLogStatusEnum::EN) {
            return;
        }

        $quoteType = QuoteTypeId::getOptions()[$sendUpdateLog->quote_type_id];
        $quote = $this->getQuoteObject($quoteType, $sendUpdateLog->quote_uuid);
        SendEPJob::dispatch($quote->id, $quoteType, null, true);
    }

    private function processSageCallIfNeeded($sendUpdateLog, SendUpdateLogService $sendUpdateLogServices)
    {
        if (! $this->shouldProcessSageCall()) {
            return;
        }

        LoggerService::info('job:SendUpdateToCustomerJob - Calling updateSageProcessForDispatching function through sendUpdateToCustomer - SendUpdateCode: '.$sendUpdateLog->code);

        $sageRequestPayload = $this->payload['sageRequestPayload'];
        unset($this->payload['sageRequestPayload']);
        unset($this->payload['ccPaymentProcess']);

        $sendUpdateLogServices->updateSageProcessForDispatching($this->payload, $sendUpdateLog, $sageRequestPayload);

        (new SageApiService)->scheduleSageProcesses($sageRequestPayload->insurerID);
        LoggerService::info('job:SendUpdateToCustomerJob - fn:scheduleSageProcesses triggered for Insurer - '.$sageRequestPayload->insurerID.' - SendUpdateCode: '.$sendUpdateLog->code);
    }

    private function shouldProcessSageCall()
    {
        return $this->payload['action'] == SendUpdateLogStatusEnum::ACTION_SNBU &&
               isset($this->payload['dispatchSageCall']) &&
               ! $this->payload['ccPaymentProcess'];
    }

    public function failed(Throwable $exception)
    {
        LoggerService::info('job:SendUpdateToCustomerJob - SendUpdateCode: '.$this->sendUpdate->code.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->sendUpdate->id))->dontRelease()];
    }
}
