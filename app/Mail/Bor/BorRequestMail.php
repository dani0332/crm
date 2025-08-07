<?php

namespace App\Mail\Bor;

use App\Models\BorLog;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $borLog;
    protected $customerData;
    protected $advisorData;
    protected $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, ?string $portalUrl = null, array $advisorData)
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
     * Send BOR request email via Bird service
     */
    public function sendViaBird()
    {
        try {
            $birdData = $this->buildBirdEmailData();
            $workflowUrl = $this->getBirdWorkflowUrl();
            
            if (!$workflowUrl) {
                LoggerService::error('BOR Request Email: Bird workflow URL not configured', [
                    'bor_log_id' => $this->borLog->id,
                    'lead_id' => $this->borLog->lead_id
                ]);
                return false;
            }

            $birdService = app(BirdService::class);
            LoggerService::info('BOR Request Email: Bird data', $birdData);
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Request Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'customer_email' => $this->customerData['email'] ?? '',
                'advisor_email' => $this->advisorData['email'] ?? '',
                'response_status' => $response->status_code
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;

        } catch (\Exception $e) {
            LoggerService::error('BOR Request Email failed', [
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
        $quoteType = strtolower(QuoteTypes::getName($personalQuote->quote_type_id)->value) .'-insurance';
        $quoteUuid = $personalQuote->uuid;
        $quoteLink = $this->portalUrl .'/'. $quoteType .'/quote/'. $quoteUuid .'/bor';
        return [
            'uuid' => $personalQuote->uuid ?? '',
            'ref_id' => $personalQuote->code ?? '',
            'quote_type' => $quoteType ?? '',
            'workflow_type' => WorkflowTypeEnum::BOR_REQUEST ?? '',
            'quote_link' => $quoteLink ?? '',
            'customer_name' => $this->getCustomerName() ?? '',
            'subject_line' => $this->getSubjectLine($personalQuote, $quoteType) ?? '',
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
                'customer_type' => $this->borLog->customer_type == "Entity" ? "company" : "individual",
                'bor_ref_id' => $this->borLog->bor_reference ?? '',
                'document_id' => $this->borLog->document_id ?? '',
                'date_created' => $this->borLog->date_created ?? '',
            ],
            'advisor' => $this->advisorData,
        ];
    }

    private function getSubjectLine($personalQuote, $quoteType)
    {
        $provider = \App\Models\InsuranceProvider::find($this->borLog->insurance_provider_id);
        $name = $this->borLog->customer_type == 'Entity' ? $this->customerData['company_name'] : $this->customerData['first_name'];
        if($personalQuote->quote_type_id === QuoteTypeId::Car && $provider && (strtolower($provider->code) === 'oic' || stripos($provider->text, 'sukoon') !== false)) {
            $subjectLine = 'BOR '. $this->borLog->chassis_number . ' - ' . $name;
            return $subjectLine;
        }
        $subjectLine = $name."'s " . $quoteType .' Insurance with Alfred '. $personalQuote->code;
        return $subjectLine;
    }

    /**
     * Get customer display name based on customer type
     */
    private function getCustomerName()
    {
        if ($this->borLog->customer_type === 'Entity') {
            return $this->customerData['company_name'] ?? 'Valued Company';
        }
        
        $firstName = $this->customerData['first_name'] ?? '';
        $lastName = $this->customerData['last_name'] ?? '';
        
        return trim($firstName . ' ' . $lastName) ?: 'Valued Customer';
    }

    /**
     * Get Bird workflow URL for BOR request emails
     */
    private function getBirdWorkflowUrl()
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL)->first();
        return $workflowConfig ? $workflowConfig->value : null;
    }
} 