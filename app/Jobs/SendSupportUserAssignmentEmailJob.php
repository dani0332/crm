<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\ThirdPartyTagEnum;
use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendSupportUserAssignmentEmailJob
 *
 * Flexible job to send email notifications to support users when leads are assigned to them.
 * Works with all LOBs (Business, Car, Health, Travel, Life, Bike, etc.)
 *
 * Usage:
 * 1. Individual assignment (from Observer):
 *    SendSupportUserAssignmentEmailJob::dispatch($supportUserId, [$leadId], $quoteType);
 *
 * 2. Bulk assignment (from CRUDController):
 *    SendSupportUserAssignmentEmailJob::dispatch($supportUserId, $leadIds, $quoteType);
 *
 * Features:
 * - Supports multiple leads in a single email
 * - Generates proper lead URLs for each LOB
 * - Comprehensive logging for debugging
 * - Flexible to work with any QuoteTypes enum value
 * - Graceful error handling and fallbacks
 */
class SendSupportUserAssignmentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;

    private $assignerUserId;
    private $supportUserId;
    private $leadIds;
    private $quoteType;

    /**
     * Create a new job instance.
     *
     * @param int $supportUserId
     * @param array|string $leadIds - Array of lead IDs or comma-separated string
     * @param QuoteTypes $quoteType
     */
    public function __construct(int $userId, int $supportUserId, $leadIds, QuoteTypes $quoteType)
    {
        $this->assignerUserId = $userId;
        $this->supportUserId = $supportUserId;
        $this->leadIds = is_array($leadIds) ? $leadIds : explode(',', $leadIds);
        $this->quoteType = $quoteType;

        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            LoggerService::startFeatureLogging(LoggerFeatureEnum::SUPPORT_USER_ASSIGNMENT);
            LoggerService::info('Sending support user assignment email', [
                'support_user_id' => $this->supportUserId,
                'lead_ids' => $this->leadIds,
                'quote_type' => $this->quoteType->value,
            ]);

            // Get support user
            $supportUser = User::find($this->supportUserId);
            $assignerUser = User::find($this->assignerUserId);

            if (!$supportUser) {
                LoggerService::error('Support user not found', ['support_user_id' => $this->supportUserId]);
                return;
            }

            if (!$assignerUser) {
                LoggerService::error('Assigner user not found', ['support_user_id' => $this->assignerUserId]);
                return;
            }

            // Get leads with details
            $leads = $this->getLeadsWithDetails();
            if ($leads->isEmpty()) {
                LoggerService::warning('No leads found for assignment email');
                return;
            }

            // Send email
            $this->sendAssignmentEmail($assignerUser,$supportUser, $leads);

            LoggerService::info('Support user assignment email sent successfully');

        } catch (Exception $e) {
            LoggerService::error('Error sending support user assignment email', [
                'support_user_id' => $this->supportUserId,
                'lead_ids' => $this->leadIds,
                'quote_type' => $this->quoteType->value,
                'error' => $e->getMessage(),
            ], $e);

            Log::error('SendSupportUserAssignmentEmailJob failed', [
                'support_user_id' => $this->supportUserId,
                'lead_ids' => $this->leadIds,
                'quote_type' => $this->quoteType->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get leads with their details
     */
    private function getLeadsWithDetails()
    {
        $model = $this->quoteType->model();

        return $model::whereIn('id', $this->leadIds)
            ->with(['customer', 'supportUser'])
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'code' => $lead->code ?? $lead->id,
                    'customer_name' => $lead->customer->name ?? 'N/A',
                    'customer_email' => $lead->customer->email ?? 'N/A',
                    'created_at' => Carbon::parse($lead->created_at)->format('d M Y H:i'),
                    'lead_url' => $this->quoteType->url($lead->uuid),
                ];
            });
    }

    /**
     * Send assignment email to support user
     */
    private function sendAssignmentEmail($assignerUser,$supportUser, $leads): void
    {
        // For now, we'll use a simple email template
        // In production, you should create a proper email template ID
        $emailTemplateId = config('mail.templates.support_user_assignment', 123); // Replace with actual template ID

        $emailService = app(SendEmailCustomerService::class);

        $emailData = [
            'to' => [[
                'email' => $supportUser->email,
                'name' => $supportUser->name,
            ]],
            'templateId' => (int) $emailTemplateId,
            'params' => [
                'supportUserName' => $supportUser->name,
                'quoteTypeName' => $this->quoteType->value,
//                'totalLeads' => $emailData->totalLeads,
                'leads' => $leads,
//                'assignedAt' => $emailData->assignedAt,
            ],
            'tags' => [
                'support-user-assignment',
                $this->quoteType->value . '-assignment',
            ],
        ];

        logger()->debug("sendAssignmentEmail: ".print_r([
                '$emailData' => $emailData,
            ],1));

        // Send the email (you may need to adjust this based on your email service implementation)
        LoggerService::info('Sending email with data', $emailData);

        $emailService->sendSupportUserAssignmentEmail($emailData);
    }


    /**
     * Prepare email data for the template
     */
    private function buildEmailData($supportUser, $leads): object
    {
        return (object) [
            'supportUserName' => $supportUser->name,
            'supportUserEmail' => $supportUser->email,
            'quoteTypeName' => $this->quoteType->value,
            'totalLeads' => $leads->count(),
            'leads' => $leads->toArray(),
            'assignedAt' => now()->format('d M Y H:i'),
        ];
    }

}
