<?php

namespace App\Jobs;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
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
        $this->onQueue('shared');
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
            ->get()
            ->map(function ($lead) {
                // Determine quote_type first
                $quoteType = null;
                if (isset($lead->business_type_of_insurance_id)) {
                    $quoteType = ($lead->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) ? QuoteTypes::GROUP_MEDICAL : null;
                } elseif (isset($lead->quote_type_id)) {
                    $quoteType = $this->quoteType->getName($lead->quote_type_id);
                }

                return collect([
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'code' => $lead->code ?? $lead->id,
                    'created_at' => Carbon::parse($lead->created_at)->format('d M Y H:i'),
                    'lead_url' => $this->quoteType->url($lead->uuid),
                    'quote_type' => $quoteType,
                ]);
            });
    }

    /**
     * Send assignment email to support user
     */
    private function sendAssignmentEmail($assignerUser,$supportUser, $leads): void
    {
        $emailService = app(SendEmailCustomerService::class);

        $quoteTypeName = '';

        // Get all quote types from leads, handling both enum and string values
        $quoteTypes = $leads->map(function ($lead) {
            $quoteType = $lead['quote_type'];
            if ($quoteType instanceof QuoteTypes) {
                return $quoteType->value;
            }
            return $quoteType; // string or null
        })->filter()->unique();

        if ($quoteTypes->count() == 1) {
            $quoteTypeName = $quoteTypes->first();
        } else {
            $quoteTypeName = $this->quoteType->value;
        }

        $emailData = collect([
            'to' => [
                'email' => $supportUser->email,
                'name' => $supportUser->name,
            ],
            'params' => [
                'quoteTypeName' => $quoteTypeName,
                'supportUserName' => $supportUser->name,
                'assignerName' => $assignerUser->name,
                'assignerEmail' => $assignerUser->email,
                'leads' => $leads,
            ],
            'tags' => [
                'support-user-assignment',
            ],
        ]);


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
