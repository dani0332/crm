<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Collection;

class EmbeddedTransactionService extends BaseService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected EmbeddedTransactionRepository $embeddedTransactionRepo
    ) {
        parent::__construct();
    }

    public function retargetEpReminder($quote, int $quoteTypeId): void
    {
        $epTransactions = $this->embeddedTransactionRepo->getDraftEpTransactions($quote->id, $quoteTypeId, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::ECB]);
        foreach ($epTransactions as $epTransaction) {
            $this->triggerBirdWorkflowForRetargetingEpReminder($quote, $quoteTypeId, $epTransaction);
        }
    }
    
    /**
     * This function use to trigger bird workflow
     */
    private function triggerBirdWorkflowForRetargetingEpReminder($quote, int $quoteTypeId, EmbeddedTransaction $epTransaction)
    {
        $birdWorkflowUrl = getAppStorageValueByKey(
            ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL, 
            'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/14dadaed-1800-4d7a-b483-050159c1d6f8/invoke-sync'
        );
        $retargetingEpReminderBirdFlowUrl = getAppStorageValueByKey(
            ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_FLOW_URL, 
            'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/08c1d960-bcf7-4f5f-bc9a-eb3e0a9450dd/invoke-sync'
        );

        if (empty($birdWorkflowUrl) || empty($retargetingEpReminderBirdFlowUrl)) {
            LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: Configuration URLs not found");
            return;
        }

        $getStatusRetargetingEpReminderUrl = route('get.status.retargeting-ep-reminder', ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId, 'embeddedTransactionCode' => $epTransaction->code]);

        $birdEmailData = [
            "quoteId" => $quote->id,
            "quoteTypeId" => $quoteTypeId,
            "refID" => $quote->code,
            "embeddedTransactionCode" => $epTransaction->code,
            "workflowType" => WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER,
            "getStatusRetargetingEpReminderUrl" => $getStatusRetargetingEpReminderUrl,
            "retargetingEpReminderFlowUrl" => $retargetingEpReminderBirdFlowUrl
        ];

        LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: ", extra: ['data' => $birdEmailData]);
        app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }
}
