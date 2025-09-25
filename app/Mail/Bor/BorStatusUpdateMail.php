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

class BorStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $borLog;
    protected $customerData;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(BorLog $borLog, array $customerData, string $oldStatus, string $newStatus)
    {
        $this->borLog = $borLog;
        $this->customerData = $customerData;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
     * Send BOR status update email via Bird service
     */
    public function sendViaBird()
    {
        try {
            $birdData = $this->buildBirdEmailData();
            $workflowUrl = $this->getBirdWorkflowUrl();
            
            if (!$workflowUrl) {
                LoggerService::error('BOR Status Update Email: Bird workflow URL not configured', [
                    'bor_log_id' => $this->borLog->id,
                    'personal_quote_id' => $this->borLog->personal_quote_id,
                    'old_status' => $this->oldStatus,
                    'new_status' => $this->newStatus
                ]);
                return false;
            }

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info('BOR Status Update Email sent via Bird', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'customer_email' => $this->customerData['email'],
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'response_status' => $response->status_code
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;

        } catch (\Exception $e) {
            LoggerService::error('BOR Status Update Email failed', [
                'bor_log_id' => $this->borLog->id,
                'personal_quote_id' => $this->borLog->personal_quote_id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
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
        return [
            'uuid' => $this->borLog->personal_quote_id,
            'workflow_type' => 'bor_status_update',
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
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'status_change_date' => now()->format('Y-m-d H:i:s'),
                'document_id' => $this->borLog->document_id,
            ],
            'template_variables' => [
                'customer_name' => $this->getCustomerName(),
                'policy_number' => $this->borLog->policy_number,
                'insurer_name' => $this->borLog->insurer_name,
                'old_status_display' => $this->getStatusDisplay($this->oldStatus),
                'new_status_display' => $this->getStatusDisplay($this->newStatus),
                'status_change_date' => now()->format('F j, Y'),
                'company_name' => config('app.name', 'InsuranceMarket.ae'),
                'status_message' => $this->getStatusMessage(),
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
     * Get human-readable status display
     */
    private function getStatusDisplay(string $status)
    {
        $statusMap = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'policy_issued' => 'Policy Issued',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
        ];

        return $statusMap[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get contextual status message
     */
    private function getStatusMessage()
    {
        switch ($this->newStatus) {
            case 'completed':
                return 'Your BOR request has been completed successfully. Thank you for your cooperation.';
            case 'policy_issued':
                return 'Great news! Your policy has been issued and the BOR process is now complete.';
            case 'in_progress':
                return 'Your BOR request is currently being processed. We will update you once completed.';
            case 'cancelled':
                return 'Your BOR request has been cancelled. Please contact us if you have any questions.';
            case 'rejected':
                return 'Your BOR request could not be processed. Please contact us for more information.';
            default:
                return 'The status of your BOR request has been updated.';
        }
    }

    /**
     * Get Bird workflow URL for BOR status update emails
     */
    private function getBirdWorkflowUrl()
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL)->first();
        return $workflowConfig ? $workflowConfig->value : null;
    }
} 