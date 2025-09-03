<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;

use App\Enums\AssignmentTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Jobs\ClaimReassignJob;
use App\Models\ClaimRequest;
use App\Models\ClaimsLeadAllocationConfig;
use App\Models\User;
use App\Pipes\Allocation\Claim\AssignLeadPipe;
use App\Pipes\Allocation\Claim\FetchEligibleAdvisorsPipe;
use App\Pipes\Allocation\Claim\FetchLeadPipe;
use App\Pipes\Allocation\Claim\FinalizeEligibleAdvisorPipe;
use App\Pipes\Allocation\Claim\MakeResponsePipe;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;

class ClaimAllocationService
{
    public function execute(string $claimUuid, int $quoteTypeId)
    {
        LoggerService::startQuoteLogging($claimUuid, LoggerFeatureEnum::CLAIM_ALLOCATION);
        $quoteType = QuoteTypes::getName($quoteTypeId);

        $allocationRequest = new AllocationRequest(
            quoteType: $quoteType,
            claimUUID: $claimUuid,
            assignmentType: AssignmentTypeEnum::SYSTEM_REASSIGNED,
            isReassignmentJob: false,
        );

        try {
            $result = Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                FetchEligibleAdvisorsPipe::class,
                FinalizeEligibleAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

            return $result;
        } catch (Exception $e) {

            return $this->resolveAllocationResponse($allocationRequest, $e);
        }

    }

    public function resolveAllocationResponse(AllocationRequest $request, ?Exception $exception = null): array
    {

        if ($lead = $request->getLead()) {
            $lead->endAllocation();
        }

        if ($request->isAllocated() || $request->isSameAdvisor()) {
            $message = 'Advisor assigned successfully!';

            if ($request->isSameAdvisor()) {
                $message = 'Found same advisor as previous advisor so further allocation is skipped';
            }

            $data = [
                'managerId' => $request->getAdvisor()?->id ?? $lead?->manager_id,
                'message' => $message,
                'status' => Response::HTTP_OK,
            ];

            return $data;
        }

        if ($request->isFailed()) {
            $this->leadAllocationFailedForClaim($request->getClaimUUID(), $request->getQuoteType());
        }

        return [
            'advisorId' => 0,
            'message' => $exception ? $exception->getMessage() : 'Lead allocation failed',
            'status' => $exception ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
    }

    public function leadAllocationFailedForClaim(string $uuid, QuoteTypes $quoteType)
    {
        $quote = ClaimRequest::where('uuid', $uuid)->first();

        if ($quote) {
            $quote->markLeadAllocationFailedForClaim();
        }

        return false;
    }

    public function syncClaimAllocationConfig(int $userId, object $data)
    {

        DB::table('claims_lead_allocation_config')->updateOrInsert(
            [
                'user_id' => $userId,
                'quote_type_id' => $data->quoteTypeId,
            ],
            [
                'max_capacity' => 100,
                'allocation_count' => 0,
                'auto_assignment_count' => 0,
                'manual_assignment_count' => 0,
                'last_allocated' => now()->timestamp,
                'reset_cap' => 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Increment allocation counters for a user's claim allocation config in an atomic and optimized way.
     */
    public function updateClaimAllocationConfig(int $userId, int $quoteTypeId): bool
    {
        // Use atomic increment to avoid race conditions and optimize performance
        $updated = ClaimsLeadAllocationConfig::where('user_id', $userId)
            ->where('quote_type_id', $quoteTypeId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'auto_assignment_count' => DB::raw('auto_assignment_count + 1'),
                'last_allocated' => now()->timestamp,
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    /**
     * Update claim manager availability and related config in a robust, optimized way.
     *
     * @param  \Illuminate\Http\Request|array  $request
     * @param  int  $quoteTypeId
     */
    public function updateAvailability($request): void
    {
        $items = is_array($request) ? $request : $request->all();

        if (empty($items)) {
            return;
        }

        // Eager load all relevant users and configs to minimize queries
        $userIds = collect($items)->pluck('userId')->unique()->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $configs = (object) $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $claimAllocationConfig = $configs->get($configKey);

            if (! $claimAllocationConfig) {
                continue;
            }

            // Handle status change and possible reassignment
            if (array_key_exists('reason', $item)) {
                $reason = $item['reason'];
                if ($reason !== UserStatusEnum::OFFLINE && $reason !== UserStatusEnum::ONLINE) {
                    LoggerService::info('User status is going to change to : '.UserStatusEnum::getUserStatusText($reason));
                    ClaimReassignJob::dispatch($item['userId']);
                }

                /** @var User|null $user */
                $user = $users->get($item['userId']);
                if ($user) {
                    $user->status = $reason;
                    LoggerService::info('user status is going to change on id : '.$user->id.' and status : '.$user->status);
                    $user->save();
                }
            }
            $claimAllocationConfig->save();
        }
    }

    public function updateCaps($request): void
    {
        $items = is_array($request) ? $request : $request->all();
        if (empty($items)) {
            return;
        }

        $configs = (object) $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $claimAllocationConfig = $configs->get($configKey);
            if (! $claimAllocationConfig) {
                continue;
            }
            // Update max capacity
            $claimAllocationConfig->max_capacity = (int) $item['maxCap'];
            $claimAllocationConfig->save();
        }

    }

    public function resetCap($request): void
    {
        $items = is_array($request) ? $request : $request->all();
        $configs = (object) $this->getConfigs($items);

        foreach ($items as $item) {
            $configKey = $item['userId'].'-'.$item['id'];
            $claimAllocationConfig = $configs->get($configKey);
            if (! $claimAllocationConfig) {
                continue;
            }
            // Update max capacity
            $claimAllocationConfig->reset_cap = (int) $item['resetCap'];
            $claimAllocationConfig->save();
        }

    }

    public function getConfigs($items)
    {
        $userIds = collect($items)->pluck('userId')->unique()->toArray();
        $configIds = collect($items)->pluck('id')->unique()->toArray();

        return ClaimsLeadAllocationConfig::whereIn('user_id', $userIds)
            ->whereIn('id', $configIds)
            ->get()
            ->keyBy(function ($item) {
                return $item->user_id.'-'.$item->id;
            });
    }

}
