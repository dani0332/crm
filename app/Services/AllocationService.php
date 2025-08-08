<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\BuyLeadRequestLog;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\LeadAllocation;
use App\Models\Tier;
use App\Models\User;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;

class AllocationService extends BaseService
{
    public function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function getTierById($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function getValuation($carModelDetailId, $yearOfManufacture)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-vehicle-value';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');

        $client = new \GuzzleHttp\Client;
        $request = $client->post(
            $apiEndPoint,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $apiToken],
                'body' => json_encode([
                    'carModelDetailId' => $carModelDetailId,
                    'yearOfManufacture' => $yearOfManufacture,
                ]),
                'timeout' => $apiTimeout,
            ]
        );

        $getStatusCode = $request->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = $request->getBody();

            return json_decode($getContents);
        } else {
            return 'API failed';
        }
    }

    public function getLeadAllocationRecordByUserId($userId, $quoteTypeId = null)
    {
        try {
            $leadAllocation = LeadAllocation::latest();
            if (! empty($quoteTypeId)) {
                $leadAllocation = $leadAllocation->where('quote_type_id', $quoteTypeId);
            }
            $leadAllocation = $leadAllocation->where('user_id', $userId)->first();

            return $leadAllocation;
        } catch (\Exception $e) {
            LoggerService::error($e->getMessage());
        }
    }

    public function addAllocationCounts($userId, $quoteTypeId = null, bool $isBuyLead = false)
    {
        $allocationRecord = $this->getLeadAllocationRecordByUserId($userId, $quoteTypeId);
        if (! empty($allocationRecord)) {
            $allocationRecord->adjustAssignmentCounts($isBuyLead);
        } else {
            LoggerService::info('Allocation record not found against advisor');
        }
    }

    public function upsertQuoteDetail($leadId, $quoteModel, $keyColumn): void
    {
        $quoteModel::updateOrCreate(
            [$keyColumn => $leadId],
            [
                'advisor_assigned_date' => now(),
                'advisor_assigned_by_id' => auth()->id(),
            ]
        );
    }

    public function adjustAllocationCounts($newAdvisorId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $quoteTypeId = null, bool $isBuyLead = false)
    {
        // Check if $lead or $newAdvisorId is not provided
        if ($lead === null || $newAdvisorId === null) {
            return;
        }

        LoggerService::info('Previous assignment type is : '.$previousAssignmentType);

        if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])) {
            BuyLeadRequestLog::reAssign($quoteTypeId, $lead, $newAdvisorId, $previousAdvisorId);
        }

        // Constants for system assigned types
        $systemAssignedTypes = [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD];

        // Get the allocation record for the new advisor
        LoggerService::info('adjust Allocation Quote Type Id : '.$quoteTypeId);
        $newAdvisorAllocationRecord = $this->getLeadAllocationRecordByUserId($newAdvisorId, $quoteTypeId);

        // Update allocation counts for the new advisor
        $this->updateAllocationCountsForNewAdvisor($newAdvisorAllocationRecord, $lead, $systemAssignedTypes, $isBuyLead);

        // Get the allocation record for the previous advisor (if applicable)
        if ($previousAdvisorId !== null) {

            $previousAdvisorAllocationRecord = $this->getLeadAllocationRecordByUserId($previousAdvisorId, $quoteTypeId);

            // Update allocation counts for the previous advisor (if applicable)
            $this->updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes);
        }
    }

    public function getTodayCounts($userId)
    {
        $allocationCount = LeadAllocation::where('user_id', $userId)->select('auto_assignment_count', 'manual_assignment_count', 'max_capacity')
            ->first();

        $leads = CarQuote::select('assignment_type')->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', '=', 'car_quote_request.id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('car_quote_request_detail.advisor_assigned_date', [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()])
            ->where('advisor_id', $userId)->get();

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return [
            'auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0,
            'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0,
            'max_capacity' => isset($allocationCount->max_capacity) ? $allocationCount->max_capacity : 0,
        ];
    }

    public function getHealthTodaysCount($userId)
    {
        $allocationCount = LeadAllocation::where('user_id', $userId)->select('auto_assignment_count', 'manual_assignment_count', 'max_capacity')
            ->first();

        $leads = HealthQuote::join('health_quote_request_detail', 'health_quote_request_detail.health_quote_request_id', '=', 'health_quote_request.id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('health_quote_request_detail.advisor_assigned_date', [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()])
            ->where('advisor_id', $userId)->get();

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return [
            'auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0,
            'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0,
            'max_capacity' => isset($allocationCount->max_capacity) ? $allocationCount->max_capacity : 0,
        ];
    }

    public function getYesterdayCounts($userId)
    {
        $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
        $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();
        $leads = CarQuote::join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', '=', 'car_quote_request.id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('car_quote_request_detail.advisor_assigned_date', [$yesterdayStart, $yesterdayEnd])
            ->where('advisor_id', $userId)->get();

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return ['auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0, 'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0];
    }

    public function getHealthYesterdayCounts($userId)
    {
        $yesterdayStart = Carbon::yesterday()->startOfDay()->toDateTimeString();
        $yesterdayEnd = Carbon::yesterday()->endOfDay()->toDateTimeString();
        $leads = HealthQuote::join('health_quote_request_detail', 'health_quote_request_detail.health_quote_request_id', '=', 'health_quote_request.id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('health_quote_request_detail.advisor_assigned_date', [$yesterdayStart, $yesterdayEnd])
            ->where('advisor_id', $userId)->get();

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return ['auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0, 'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0];
    }

    public function getUnavailableAdvisor()
    {
        // Query to fetch unavailable advisors
        $query = LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) {
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

    public function deductLeadAllocationCount($quoteModel, $quoteUuid)
    {
        $quote = $quoteModel::with('advisor')->where('uuid', $quoteUuid)->first();

        if ($quote->advisor) {
            $leadAllocation = LeadAllocation::where('user_id', $quote->advisor->id)->first();
            if (in_array($quote->assignment_type, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])) {
                if ($leadAllocation->buy_lead_allocation_count > 0) {
                    $leadAllocation->buy_lead_allocation_count = $leadAllocation->buy_lead_allocation_count - 1;
                }
            } elseif (in_array($quote->assignment_type, [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])) {
                if ($leadAllocation->allocation_count > 0) {
                    $leadAllocation->allocation_count = $leadAllocation->allocation_count - 1;
                }
            }

            if (in_array($quote->assignment_type, [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD])) {
                $leadAllocation->auto_assignment_count = $leadAllocation->auto_assignment_count - 1;
            } elseif (in_array($quote->assignment_type, [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])) {
                $leadAllocation->manual_assignment_count = $leadAllocation->manual_assignment_count - 1;
            }
            $leadAllocation->save();
        }
    }

    public function leadAllocationFailed(string $uuid, QuoteTypes $quoteType)
    {
        $quote = $quoteType->model()->where('uuid', $uuid)->first();

        if ($quote) {
            $quote->markLeadAllocationFailed();
        }
    }

    public function createResponse(int $advisorId, string $message, int $status, ?Tier $tier = null): array
    {
        $resp = [
            'advisorId' => $advisorId,
            'message' => $message,
            'status' => $status,
        ];

        if ($tier) {
            $resp['tierId'] = $tier->id;
            $resp['tierName'] = $tier->name;
        }

        return $resp;
    }

    public function shouldProceedWithReAllocation($allocationSwitchName)
    {
        // Fetch reassignment start and end times
        $startTime = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $endTime = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));

        // Check if current time is within reassignment window and master switch is ON
        $shouldProceed = now()->between($startTime, $endTime) && (config($allocationSwitchName) == 1);
        LoggerService::info('Reassignment with current time check: '.$shouldProceed);
        // Fetch public holiday start and end
        $publicHolidayStart = $this->getAppStorageValueByKey(ApplicationStorageEnums::PUBLIC_HOLIDAY_START_DATE);
        $publicHolidayEnd = $this->getAppStorageValueByKey(ApplicationStorageEnums::PUBLIC_HOLIDAY_END_DATE);

        if ($publicHolidayStart && $publicHolidayEnd) {
            // Parse public holiday dates with start and end times for accurate range
            $publicHolidayStartDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $publicHolidayStart);
            $publicHolidayEndDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $publicHolidayEnd);

            // Ensure the current time is not within the public holiday period
            $shouldProceed = $shouldProceed && ! now()->between($publicHolidayStartDateTime, $publicHolidayEndDateTime);
        }
        LoggerService::info('Reassignment with public holiday check: '.$shouldProceed);

        return $shouldProceed;
    }

    public function resolveAllocationResponse(AllocationRequest $request, ?Exception $exception = null): array
    {
        if ($lead = $request->getLead()) {
            $lead->endAllocation();
        }

        $request->endBuyLeadProcessing();

        if ($request->isEvaluateTierOnlyRequest() && $request->getTier()) {
            $tier = $request->getTier();

            return [
                'advisorId' => $lead->advisor_id,
                'message' => 'Tier evaluated successfully',
                'status' => Response::HTTP_OK,
                'tierId' => $tier->id,
                'tierName' => $tier->name,
            ];
        }

        if ($request->isAllocated() || $request->isSameAdvisor()) {
            $message = 'Advisor assigned successfully!';

            if ($request->isSameAdvisor()) {
                $message = 'Found same advisor as previous advisor so further allocation is skipped';
            }

            $data = [
                'advisorId' => $request->getAdvisor()?->id ?? $lead?->advisor_id,
                'isAIAdvisor' => $request->getAdvisor()?->isAi(),
                'message' => $message,
                'status' => Response::HTTP_OK,
            ];

            $tier = $request->getTier() ?? $lead->tier;

            if ($tier) {
                $data['tierId'] = $tier->id;
                $data['tierName'] = $tier->name;
            }

            return $data;
        }

        if ($request->isFailed()) {
            $this->leadAllocationFailed($request->getQuoteUUID(), $request->getQuoteType());
        }

        return [
            'advisorId' => 0,
            'message' => $exception ? $exception->getMessage() : 'Lead allocation failed',
            'status' => $exception ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
    }

    public function isBusinessHours(): bool
    {
        try {
            $startTime = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
            $endTime = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));

            $currentTime = now();
            $isWeekend = $currentTime->isWeekend();
            $isWithinTimeRange = $currentTime->between($startTime, $endTime);

            $isBusinessHours = ! $isWeekend && $isWithinTimeRange;

            LoggerService::info(self::class.' - isBusinessHours: Business hours calculation', extra: [
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'current_time' => $currentTime->format('H:i'),
                'is_weekend' => $isWeekend,
                'is_within_time_range' => $isWithinTimeRange,
                'is_business_hours' => $isBusinessHours,
            ]);

            return $isBusinessHours;
        } catch (\Exception $e) {
            LoggerService::error(self::class.' - isBusinessHours: Error checking business hours', exception: $e);

            return false;
        }
    }

    public function isMaxCapReached(User $advisor, $quoteTypeId): bool
    {
        $leadAllocation = $advisor->getFirstFromLeadAllocation($quoteTypeId);

        if (! $leadAllocation) {
            LoggerService::warning(self::class.' - isMaxCapReached: Advisor has no leadAllocation record', extra: [
                'advisor_id' => $advisor->id,
                'advisor_name' => $advisor->name,
            ]);

            return true;
        }

        $allocationCount = $leadAllocation->allocation_count;
        $maxCapacity = $leadAllocation->max_capacity;

        $isAdvisorAvailable = $allocationCount < $maxCapacity || $maxCapacity == -1;

        return ! $isAdvisorAvailable;
    }

    public function getValidAdvisorStatuses(): array
    {
        $isBusinessHours = $this->isBusinessHours();

        LoggerService::info(self::class.' - getValidAdvisorStatuses: Business hours check', extra: [
            'is_business_hours' => $isBusinessHours,
        ]);

        if ($isBusinessHours) {
            return [UserStatusEnum::ONLINE, UserStatusEnum::OFFLINE];
        } else {
            return [UserStatusEnum::ONLINE, UserStatusEnum::OFFLINE, UserStatusEnum::UNAVAILABLE];
        }
    }
}
