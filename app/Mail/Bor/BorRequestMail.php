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

class BorRequestMail extends Mailable
{
    use BorMailTrait, Queueable, SerializesModels;

    protected $borLog;
    protected $customerData;
    protected $advisorData;
    protected $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, ?string $portalUrl, array $advisorData)
    {
        $this->borLog = $borLog;
        $this->customerData = $customerData;
        $this->advisorData = $advisorData;
        $this->portalUrl = $portalUrl ?? config('constants.AFIA_WEBSITE_DOMAIN');
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
     * Send BOR request email via WebEngage
     */
    public function sendViaBird()
    {
        try {
            $payload = $this->buildBirdEmailData();

            app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::BOR_REQUEST, $payload);

            LoggerService::info('BOR Request Email sent via WebEngage', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'customer_email' => $this->customerData['email'] ?? '',
            ]);

            return true;

        } catch (\Exception $e) {
            LoggerService::error('BOR Request Email failed', [
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
        $quoteUuid = $personalQuote->uuid;
        $quoteLink = $this->portalUrl.'/'.$quoteType.'/quote/'.$quoteUuid.'/bor/'.$this->borLog->bor_reference;

        return [
            'customerId' => $personalQuote->customer_id ?? $this->customerData['email'] ?? '',
            'firstName' => $this->customerData['first_name'] ?? '',
            'lastName' => $this->customerData['last_name'] ?? '',
            'customerEmail' => $this->customerData['email'] ?? '',
            'customerMobile' => ! empty($this->customerData['mobile']) ? '+'.formatMobileNoWithoutPlus($this->customerData['mobile']) : '',
            'quoteUID' => $quoteUuid ?? '',
            'uuid' => $personalQuote->uuid ?? '',
            'ref_id' => $personalQuote->code ?? '',
            'quote_type' => $quoteType ?? '',
            'workflow_type' => WorkflowTypeEnum::BOR_REQUEST ?? '',
            'quote_link' => $quoteLink ?? '',
            'customer_name' => $this->getCustomerName(true) ?? '',
            'subject_line' => $this->getSubjectLine($personalQuote) ?? '',
            'insurance' => [
                'insurance_name' => $this->borLog->insuranceProvide?->text ?? '',
                'insurance_representative' => 'insurance_representative@email.com',
            ],
            'customer' => [
                'email' => $this->customerData['email'] ?? '',
                'first_name' => $this->customerData['first_name'] ?? '',
                'last_name' => $this->customerData['last_name'] ?? '',
                'company_name' => $this->customerData['company_name'] ?? '',
                'mobile' => $this->customerData['mobile'] ?? '',
            ],
            'bor_data' => [
                'policy_number' => $this->borLog->policy_number ?? '',
                'insurer_name' => $this->borLog->insurer_name ?? '',
                'customer_type' => $this->borLog->customer_type == 'Entity' ? 'company' : 'individual',
                'bor_ref_id' => $this->borLog->bor_reference ?? '',
                'document_id' => $this->borLog->document_id ?? '',
                'date_created' => $this->borLog->date_created ?? '',
            ],
            'attachPdf' => $this->borLog->customer_type == 'Entity' ? app(BorPdfService::class)->generateTemporaryBorPdf($this->borLog) : null,
            'advisor' => $this->advisorData,
        ];
    }
}
