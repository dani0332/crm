<?php

namespace App\Mail\Bor;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\BorLog;
use App\Models\InsuranceProvider;
use App\Services\BirdService;
use App\Services\Bor\BorPdfService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorInsurerNotificationMail extends Mailable
{
    use BorMailTrait, Queueable, SerializesModels;

    protected $borLog;
    protected $insurerContact;
    protected $advisorData;
    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, $insurerContact, $advisorData)
    {
        $this->borLog = $borLog;
        $this->advisorData = $advisorData;
        $this->insurerContact = $insurerContact;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // This method is required by Laravel Mailable but we'll use Bird service instead
        return $this;
    }

    /**
     * Send BOR insurer notification email via Bird service
     */
    public function sendViaBird()
    {
        try {
            $birdData = $this->buildBirdEmailData();
            $workflowUrl = $this->getBirdWorkflowUrl();

            if (! $workflowUrl) {
                LoggerService::error('BOR Insurer Notification Email: Bird workflow URL not configured', [
                    'bor_log_id' => $this->borLog->id,
                    'personal_quote_id' => $this->borLog->personal_quote_id,
                    'insurer_email' => $this->insurerContact->emails,
                ]);

                return false;
            }

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Insurer Notification Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'insurer_email' => $this->insurerContact->emails,
                'response_status' => $response->status_code,
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;

        } catch (\Exception $e) {
            LoggerService::error('BOR Insurer Notification Email failed', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'insurer_email' => $this->insurerContact->emails,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * Build Bird email data payload
     */
    private function buildBirdEmailData()
    {
        $this->borLog->load('personalQuote', 'insuranceProvider');
        $personalQuote = $this->borLog->personalQuote;
        $insurerEmails = explode(';', $this->insurerContact->emails);
        $quoteType = strtolower(QuoteTypes::getName($personalQuote->quote_type_id)->value).'-insurance';

        // First email is the recipient, rest are CC emails
        $recipientEmail = $insurerEmails[0] ?? '';
        $ccEmails = array_slice($insurerEmails, 1);

        return [
            'uuid' => $personalQuote->uuid ?? '',
            'ref_id' => $personalQuote->code ?? '',
            'workflow_type' => WorkflowTypeEnum::BOR_INSURER_NOTIFICATION ?? 'bor_insurer_notification',
            'customer_name' => $this->getCustomerName(false, true) ?? '',
            'subject_line' => $this->getSubjectLine($personalQuote, $quoteType) ?? '',
            'insurance' => [
                'insurance_name' => $this->borLog->insuranceProvide?->text ?? '',
                'insurance_representative' => $recipientEmail,
                'cc_emails' => count($ccEmails) > 0 ? $ccEmails : [],
            ],
            'bor_data' => [
                'policy_number' => $this->borLog->policy_number ?? '',
                'policy_expiry_date' => $this->borLog->policy_expiry ?? '',
                'insurer_name' => $this->borLog->insurer_name ?? '',
                'customer_type' => $this->borLog->customer_type == CustomerTypeEnum::Entity ? 'company' : 'individual',
                'bor_ref_id' => $this->borLog->bor_reference ?? '',
                'document_id' => $this->borLog->document_id ?? '',
                'date_created' => $this->borLog->date_created ?? '',
            ],
            'advisor' => $this->advisorData,
            'attachPdf' => app(BorPdfService::class)->generateTemporaryBorPdf($this->borLog),
        ];
    }
}
