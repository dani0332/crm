<?php

namespace App\Mail\Bor;

use App\Models\BorLog;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Services\BirdService;
use App\Services\Bor\BorPdfService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

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
     * Send BOR completion email via Bird service
     */
    public function sendViaBird()
    {
        try {
            $birdData = $this->buildBirdEmailData();
            $workflowUrl = $this->getBirdWorkflowUrl();
            
            if (!$workflowUrl) {
                LoggerService::error('BOR Completion Email: Bird workflow URL not configured', [
                    'bor_log_id' => $this->borLog->id,
                    'lead_id' => $this->borLog->lead_id
                ]);
                return false;
            }

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Completion Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'customer_email' => $this->customerData['email'],
                'response_status' => $response->status_code
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;

        } catch (\Exception $e) {
            LoggerService::error('BOR Completion Email failed', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
        $quoteType = strtolower(QuoteTypes::getName($personalQuote->quote_type_id)->value) . '-insurance';
        return [
            'uuid' => $personalQuote->uuid ?? '',
            'ref_id' => $personalQuote->code ?? '',
            'quote_type' => $quoteType ?? '',
            'workflow_type' => WorkflowTypeEnum::BOR_UPLOAD ?? '',
            'quote_link' => $quoteLink ?? '',
            'customer_name' => $this->getCustomerName(true) ?? '',
            'subject_line' => $this->getSubjectLine($personalQuote, $quoteType) ?? '',
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
                'customer_type' => $this->borLog->customer_type == "Entity" ? "company" : "individual",
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

    private function getSubjectLine($personalQuote, $quoteType)
    {
        $provider = \App\Models\InsuranceProvider::find($this->borLog->insurance_provider_id);
        $name = $this->getCustomerName();
        if ($personalQuote->quote_type_id === QuoteTypeId::Car && $provider && (strtolower($provider->code) === 'oic' || stripos($provider->text, 'sukoon') !== false)) {
            $subjectLine = 'BOR ' . $this->borLog->chassis_number . ' - ' . $name;
            return $subjectLine;
        }
        $subjectLine = $name . ' For signature - Broker Appointment Letter ' . $personalQuote->code;
        return $subjectLine;
    }

    /**
     * Get customer display name based on customer type
     */
    private function getCustomerName($firstName = false)
    {
        if ($this->borLog->customer_type === 'Entity') {
            return $this->borLog->company_name ?? $this->customerData['company_name'] ?? 'Valued Company';
        }

        $firstName = $this->customerData['first_name'] ?? '';
        $lastName = $this->customerData['last_name'] ?? '';
        $name = trim($firstName . ' ' . $lastName);

        if ($firstName) {
            return $firstName;
        }

        return $this->borLog->insurer_name ?? $name ?: 'Valued Customer';
    }

    /**
     * Get Bird workflow URL for BOR completion emails
     */
    private function getBirdWorkflowUrl()
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL)->first();
        return $workflowConfig ? $workflowConfig->value : null;
    }
} 