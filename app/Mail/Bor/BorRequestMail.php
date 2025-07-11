<?php

namespace App\Mail\Bor;

use App\Models\BorLog;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
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
    protected $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, string $portalUrl = null)
    {
        $this->borLog = $borLog;
        $this->customerData = $customerData;
        $this->portalUrl = $portalUrl ?? config('app.customer_portal_url');
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
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Request Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'customer_email' => $this->customerData['email'],
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
        $portalLink = $this->portalUrl . '/bor/' . $this->borLog->document_id;
        
        return [
            'uuid' => $this->borLog->lead_id,
            'workflow_type' => 'bor_request',
            'customer' => [
                'email' => $this->customerData['email'],
                'first_name' => $this->customerData['first_name'] ?? '',
                'last_name' => $this->customerData['last_name'] ?? '',
                'company_name' => $this->customerData['company_name'] ?? '',
                'mobile' => $this->customerData['mobile'] ?? '',
            ],
            'bor_data' => [
                'policy_number' => $this->borLog->policy_number,
                'insurer_name' => $this->borLog->insurer_name,
                'customer_type' => $this->borLog->customer_type,
                'portal_link' => $portalLink,
                'document_id' => $this->borLog->document_id,
                'date_created' => $this->borLog->date_created,
            ],
            'template_variables' => [
                'customer_name' => $this->getCustomerName(),
                'policy_number' => $this->borLog->policy_number,
                'insurer_name' => $this->borLog->insurer_name,
                'portal_url' => $portalLink,
                'company_name' => config('app.name', 'InsuranceMarket.ae'),
            ]
        ];
    }

    /**
     * Get customer display name based on customer type
     */
    private function getCustomerName()
    {
        if ($this->borLog->customer_type === 'Entity') {
            return $this->customerData['company_name'] ?? 'Valued Customer';
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
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_REQUEST_WORKFLOW_URL)->first();
        return $workflowConfig ? $workflowConfig->value : null;
    }
} 