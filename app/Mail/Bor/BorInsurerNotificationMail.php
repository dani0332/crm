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

class BorInsurerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $borLog;
    protected $customerData;
    protected $insurerEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, string $insurerEmail)
    {
        $this->borLog = $borLog;
        $this->customerData = $customerData;
        $this->insurerEmail = $insurerEmail;
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
            
            if (!$workflowUrl) {
                LoggerService::error('BOR Insurer Notification Email: Bird workflow URL not configured', [
                    'bor_log_id' => $this->borLog->id,
                    'lead_id' => $this->borLog->lead_id,
                    'insurer_email' => $this->insurerEmail
                ]);
                return false;
            }

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Insurer Notification Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'insurer_email' => $this->insurerEmail,
                'response_status' => $response->status_code
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;

        } catch (\Exception $e) {
            LoggerService::error('BOR Insurer Notification Email failed', [
                'bor_log_id' => $this->borLog->id,
                'lead_id' => $this->borLog->lead_id,
                'insurer_email' => $this->insurerEmail,
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
        $documentUrl = $this->borLog->document_path ? 
            asset('storage/' . $this->borLog->document_path) : null;

        return [
            'uuid' => $this->borLog->lead_id,
            'workflow_type' => 'bor_insurer_notification',
            'insurer' => [
                'email' => $this->insurerEmail,
                'company_name' => $this->borLog->insurer_name,
            ],
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
                'completion_date' => now()->format('Y-m-d H:i:s'),
                'document_url' => $documentUrl,
                'document_id' => $this->borLog->document_id,
            ],
            'template_variables' => [
                'customer_name' => $this->getCustomerName(),
                'policy_number' => $this->borLog->policy_number,
                'insurer_name' => $this->borLog->insurer_name,
                'completion_date' => now()->format('F j, Y'),
                'document_download_url' => $documentUrl,
                'broker_company' => config('app.name', 'InsuranceMarket.ae'),
            ]
        ];
    }

    /**
     * Get customer display name based on customer type
     */
    private function getCustomerName()
    {
        if ($this->borLog->customer_type === 'Entity') {
            return $this->customerData['company_name'] ?? 'Customer';
        }
        
        $firstName = $this->customerData['first_name'] ?? '';
        $lastName = $this->customerData['last_name'] ?? '';
        
        return trim($firstName . ' ' . $lastName) ?: 'Customer';
    }

    /**
     * Get Bird workflow URL for BOR insurer notification emails
     */
    private function getBirdWorkflowUrl()
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL)->first();
        return $workflowConfig ? $workflowConfig->value : null;
    }
} 