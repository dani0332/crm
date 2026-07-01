<?php

namespace App\Mail\Bor;

use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\BorLog;
use App\Services\Bor\BorPdfService;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorCompletionMail extends Mailable
{
    use BorMailTrait, Queueable, SerializesModels;

    protected $borLog;
    protected $customerData;
    protected $advisorData;
    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, array $advisorData)
    {
        $this->borLog = $borLog;
        $this->customerData = $customerData;
        $this->advisorData = $advisorData;
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
     * Send BOR completion email via WebEngage
     */
    public function sendViaBird()
    {
        try {
            $payload = $this->buildBirdEmailData();

            app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::BOR_UPLOAD, $payload);

            LoggerService::info('BOR Completion Email sent via WebEngage', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'customer_email' => $this->customerData['email'],
            ]);

            return true;

        } catch (\Exception $e) {
            LoggerService::error('BOR Completion Email failed', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
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
        $quoteType = strtolower(QuoteTypes::getName($personalQuote->quote_type_id)->value).'-insurance';

        return [
            'customerId' => $personalQuote->customer_id ?? $this->customerData['email'] ?? '',
            'firstName' => $this->customerData['first_name'] ?? '',
            'lastName' => $this->customerData['last_name'] ?? '',
            'customerEmail' => $this->customerData['email'] ?? '',
            'customerMobile' => ! empty($this->customerData['mobile']) ? '+'.formatMobileNoWithoutPlus($this->customerData['mobile']) : '',
            'quoteUID' => $personalQuote->uuid ?? '',
            'uuid' => $personalQuote->uuid ?? '',
            'ref_id' => $personalQuote->code ?? '',
            'quote_type' => $quoteType ?? '',
            'workflow_type' => WorkflowTypeEnum::BOR_UPLOAD ?? '',
            'customer_name' => $this->getCustomerName(true) ?? '',
            'subject_line' => $this->getSubjectLine($personalQuote) ?? '',
            'customer' => [
                'email' => $this->customerData['email'],
                'first_name' => $this->customerData['first_name'] ?? '',
                'last_name' => $this->customerData['last_name'] ?? '',
                'company_name' => $this->customerData['company_name'] ?? '',
                'mobile' => $this->customerData['mobile'] ?? '',
            ],
            'advisor' => $this->advisorData,
            'bor_data' => [
                'policy_number' => $this->borLog->policy_number ?? ' ',
                'insurer_name' => $this->borLog->insurer_name ?? ' ',
                'customer_type' => $this->borLog->customer_type == 'Entity' ? 'company' : 'individual',
                'bor_ref_id' => $this->borLog->bor_reference ?? ' ',
                'document_id' => $this->borLog->document_id ?? ' ',
                'date_created' => $this->borLog->date_created ?? ' ',
            ],
            'insurance' => [
                'insurance_name' => $this->borLog->insuranceProvide?->text ?? '',
                'insurance_representative' => 'insurance_representative@email.com',
            ],
            'attachPdf' => app(BorPdfService::class)->generateTemporaryBorPdf($this->borLog),
        ];
    }
}
