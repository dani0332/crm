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
use App\Pipes\Allocation\Claim\FetchEligibleManagersPipe;
use App\Pipes\Allocation\Claim\FetchLeadPipe;
use App\Pipes\Allocation\Claim\FinalizeEligibleManagerPipe;
use App\Pipes\Allocation\Claim\MakeResponsePipe;
use App\Pipes\Allocation\Claim\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Claim\VerifyLeadPreChecksPipe;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;

class ClaimAllocationService
{
    public function execute(string $claimUuid, int $quoteTypeId, string $quoteTypeLabel, bool $isReassignmentJob = false)
    {
        LoggerService::startQuoteLogging($claimUuid, LoggerFeatureEnum::CLAIM_ALLOCATION);

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::getName($quoteTypeId),
            quoteTypeLabel: QuoteTypes::getName(QuoteTypes::getIdFromValue($quoteTypeLabel)) ?? QuoteTypes::getName($quoteTypeId),
            claimUUID: $claimUuid,
            assignmentType: $isReassignmentJob ? AssignmentTypeEnum::SYSTEM_REASSIGNED : AssignmentTypeEnum::SYSTEM_ASSIGNED,
            isReassignmentJob: $isReassignmentJob,
        );

        try {
            $result = Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                FetchEligibleManagersPipe::class,
                FinalizeEligibleManagerPipe::class,
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
        $lead = $request->getLead();
        // Only clear allocation-in-progress on failure path; success path already called endAllocation() in assignToManager()
        if ($exception !== null && $lead) {
            $lead->endAllocation();
        }

        if ($request->isAllocated() || $request->isSameManager() || $request->isAlreadyAssigned()) {
            $message = 'Manager assigned successfully!';

            if ($request->isAlreadyAssigned()) {
                $message = 'Manager is already assigned';
            } elseif ($request->isSameManager()) {
                $message = 'Found same manager as previous manager so further allocation is skipped';
            }

            $data = [
                'managerId' => $request->getManager()?->id ?? $lead?->manager_id,
                'message' => $message,
                'status' => Response::HTTP_OK,
            ];

            return $data;
        }

        if ($request->isFailed()) {
            $this->leadAllocationFailedForClaim($request->getClaimUUID(), $request->getQuoteType());
        }

        return [
            'managerId' => 0,
            'message' => $exception ? $exception->getMessage() : 'Claim allocation failed',
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
        if (isset($data->quoteTypeId) && ! empty($data->quoteTypeId)) {
            $isExists = ClaimsLeadAllocationConfig::where('user_id', $userId)->where('quote_type_id', $data->quoteTypeId)->first();
            if ($isExists) {
                LoggerService::warning('Claim allocation config already exists for user: '.$userId.' and quote type: '.$data->quoteTypeId);

                return true;
            }
            ClaimsLeadAllocationConfig::create([
                'user_id' => $userId,
                'quote_type_id' => $data->quoteTypeId,
                'max_capacity' => 100,
                'allocation_count' => 0,
                'auto_assignment_count' => 0,
                'manual_assignment_count' => 0,
                'last_allocated' => null,
                'reset_cap' => 0,
            ]);

            return true;
        }
        LoggerService::warning('Quote type id is not set for user: '.$userId);

        return false;
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
     * @param  Request|array  $request
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

    public function fetchReAssignmentLeads($managerId)
    {
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        LoggerService::info(self::class."::fetchReAssignmentLeads - leads will be picked up in reassignment from : {$from}");

        return ClaimRequest::whereBetween('created_at', [$from, now()])
            ->when($managerId, function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            }, function ($q) {
                $managers = $this->getUnavailableManager();
                $managerIds = $managers->pluck('user_id');
                $q->whereIn('manager_id', $managerIds);
            })
            ->get();
    }

    public function getUnavailableManager()
    {
        // Query to fetch unavailable advisors
        $query = ClaimsLeadAllocationConfig::with('user')
            ->whereHas('user', function ($query) {
                $query->where('is_active', 1)
                    ->whereIn('status', [
                        UserStatusEnum::UNAVAILABLE,
                        UserStatusEnum::LEAVE,
                        UserStatusEnum::SICK,
                    ]);
            })
            ->orderBy('last_allocated');

        return $query->get();
    }

}
