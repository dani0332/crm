<?php

namespace App\Jobs;

use App\DTO\AutomationFailedEmailDataRequest;
use App\Enums\QuoteTypes;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\AutomationFailedService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class AutomationFailedJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 100;
    public int $tries = 3;
    private string $insurerName = '';
    private $recipientEmail;
    private $recipientName;

    public function __construct(
        private $quoteId,
        private $quoteTypeId,
        private $actionRequired,
        private $statusAPIFailed,
        private $processInvolved,
        private $workflowType,
        private $userToSendEmail = null
    ) {
        LoggerService::info('job:AutomationFailedJob - Initializing job', extra: [
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationFailedService $service)
    {
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $quote = $this->getQuoteObject($quoteType, $this->quoteId);

        if (! $quote) {
            LoggerService::error('job:AutomationFailedJob - Quote not found', extra: [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => $this->quoteTypeId,
                'quoteType' => $quoteType,
            ]);

            return;
        }

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('job:AutomationFailedJob - Job started', extra: [
            'userToSendEmail' => $this->userToSendEmail,
            'quoteCode' => $quote->code ?? 'unknown',
        ]);

        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);

        // Set up the service with insurance provider
        $service->setInsuranceProvider($insuranceProvider);
        $this->insurerName = $service->getInsurerName();

        // Check if this is a Device/NGI quote for FRD-specific logic
        $isDeviceNgi = $service->isDeviceNgiQuote($this->quoteTypeId, $quoteType);

        // Determine recipient based on LOB/Provider
        $recipient = $service->determineRecipient($quote, $isDeviceNgi, $this->processInvolved, $this->userToSendEmail);
        if (! $recipient) {
            return;
        }
        $recipientEmail = $recipient['email'];
        $recipientName = $recipient['name'];

        // Build CC emails with LOB/Provider-specific logic
        $cc = $service->buildCcEmails($quote, $isDeviceNgi, $this->processInvolved);

        if (! $recipientEmail || ! $recipientName) {
            LoggerService::info('job:AutomationFailedJob - Recipient details missing, stopping job - Insurer: '.$this->insurerName);

            return;
        }

        // Build email data request DTO
        $emailDataRequest = new AutomationFailedEmailDataRequest(
            cc: $cc,
            ccEmails: $this->extractCcEmails($cc),
            isDeviceNgi: $isDeviceNgi,
            actionRequired: $this->actionRequired,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            statusAPIFailed: $this->statusAPIFailed,
            processInvolved: $this->processInvolved,
            workflowType: $this->workflowType
        );

        // Build email data with LOB-specific fields
        $emailData = $service->buildEmailData($quote, $emailDataRequest);

        $response = app(CentralService::class)->sendAutomationEmail($quote, $emailData, $this->quoteTypeId, $this->workflowType);
        LoggerService::info('job:AutomationFailedJob - Job Response ', extra: ['emailData' => json_encode($response)]);

        if ($response == 200) {
            LoggerService::info('job:AutomationFailedJob - email sent successfully - Insurer: '.$this->insurerName);
        } else {
            LoggerService::error('job:AutomationFailedJob - Job failed - Insurer: '.$this->insurerName, extra: [
                'response' => json_encode($response),
            ]);
        }

        LoggerService::info('job:AutomationFailedJob - Job completed - Quote Code: '.$quote->code);
    }

    public function failed(Exception $ex)
    {
        LoggerService::error('job:AutomationFailedJob - Insurer: '.($this->insurerName).' Failed', exception: $ex);
    }

    public function middleware()
    {
        LoggerService::info('job:AutomationFailedJob - Middleware setup', extra: ['quoteId' => $this->quoteId]);

        return [(new WithoutOverlapping($this->quoteId.'-automation'))->dontRelease()];
    }

    private function extractCcEmails(mixed $cc): array
    {
        if (! is_array($cc)) {
            return [];
        }

        $ccEmailSources = [
            $cc['ccEmails'] ?? null,
            $cc['cc'] ?? null,
            $cc['notificationContext']['cc'] ?? null,
        ];

        $normalizedEmails = [];

        foreach ($ccEmailSources as $source) {
            if (is_array($source)) {
                $normalizedEmails = $source;
                break;
            }

            if (is_string($source) && trim($source) !== '') {
                $normalizedEmails = array_map('trim', explode(',', $source));
                break;
            }
        }

        return $normalizedEmails;
    }
}
