<?php

namespace App\Services;

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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AllocationService
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
            Log::error($e->getMessage());
        }
    }

    public function addAllocationCounts($userId, $quoteTypeId = null, bool $isBuyLead = false)
    {
        $allocationRecord = $this->getLeadAllocationRecordByUserId($userId, $quoteTypeId);
        info('Allocation Quote Type Id : '.$allocationRecord->quote_type_id.'  Quote Type Id : '.$quoteTypeId);
        if (! empty($allocationRecord)) {
            $allocationRecord->adjustAssignmentCounts($isBuyLead);
        } else {
            info('Allocation record not found against advisor');
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

        info('Previous assignment type is : '.$previousAssignmentType);

        if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_TO_BOUGHT_LEAD])) {
            BuyLeadRequestLog::reAssign($quoteTypeId, $lead, $newAdvisorId);
        }

        //Constants for system assigned types
        $systemAssignedTypes = [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_TO_BOUGHT_LEAD];

        // Get the allocation record for the new advisor
        info('adjust Allocation Quote Type Id : '.$quoteTypeId);
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

    private function updateAllocationCountsForNewAdvisor(LeadAllocation $advisorAllocationRecord, $lead, $systemAssignedTypes, bool $isBuyLead = false)
    {
        if ($advisorAllocationRecord === null || $lead === null) {
            return;
        }

        // Determine if the lead was system-assigned or manually assigned
        $isSystemAssigned = in_array($lead->assignment_type, $systemAssignedTypes);

        $advisorAllocationRecord->adjustAssignmentCounts($isBuyLead, $isSystemAssigned);
    }

    private function updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes)
    {
        // Check if there is a previous advisor and the lead assignment date is today
        if ($previousAdvisorId !== null && Carbon::parse($oldAdvisorAssignedDate)->startOfDay() == now()->startOfDay() && $previousAdvisorAllocationRecord !== null) {
            // if someone's bought lead is re assigning then mark his buy_leas_status to disabled
            if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_TO_BOUGHT_LEAD])) {
                $previousAdvisorAllocationRecord->buy_lead_status = false;
            }

            // Determine if the previous assignment was system-assigned
            $isSystemAssigned = in_array($previousAssignmentType, $systemAssignedTypes);

            // Update allocation counts based on assignment type (if applicable)
            if ($isSystemAssigned && $previousAdvisorAllocationRecord->auto_assignment_count > 0) {
                info('About to deduct from auto assignment count for previous advisor');
                $previousAdvisorAllocationRecord->auto_assignment_count = $previousAdvisorAllocationRecord->auto_assignment_count - 1;
            } elseif (! $isSystemAssigned && $previousAdvisorAllocationRecord->manual_assignment_count > 0) {
                info('About to deduct from manual assignment count for previous advisor');
                $previousAdvisorAllocationRecord->manual_assignment_count = $previousAdvisorAllocationRecord->manual_assignment_count - 1;
            }

            // Decrement the total allocation count (if it's greater than 0) and update timestamps
            if (in_array($previousAssignmentType, [AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_TO_BOUGHT_LEAD]) && $previousAdvisorAllocationRecord->buy_lead_allocation_count > 0) {
                $previousAdvisorAllocationRecord->buy_lead_allocation_count = $previousAdvisorAllocationRecord->buy_lead_allocation_count - 1;
                $previousAdvisorAllocationRecord->updated_at = now();
            } elseif ($previousAdvisorAllocationRecord->allocation_count > 0) {
                $previousAdvisorAllocationRecord->allocation_count = $previousAdvisorAllocationRecord->allocation_count - 1;
                $previousAdvisorAllocationRecord->updated_at = now();
            }

            // Save the updated allocation record
            $previousAdvisorAllocationRecord->save();
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

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return [
            'auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0,
            'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0,
            'max_capacity' => isset($allocationCount->max_capacity) ? $allocationCount->max_capacity : 0];
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

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return [
            'auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0,
            'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0,
            'max_capacity' => isset($allocationCount->max_capacity) ? $allocationCount->max_capacity : 0];
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

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])->count();
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

        $systemAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])->count();
        $manualAssignedCount = $leads->whereIn('assignment_type', [AssignmentTypeEnum::MANUAL_ASSIGNED, AssignmentTypeEnum::MANUAL_REASSIGNED])->count();

        return ['auto_assignment_count' => isset($systemAssignedCount) ? $systemAssignedCount : 0, 'manual_assignment_count' => isset($manualAssignedCount) ? $manualAssignedCount : 0];
    }

    public function getUnavailableAdvisor()
    {
        // Query to fetch unavailable advisors
        $query = LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) {
                $query->whereIn('status', [UserStatusEnum::UNAVAILABLE, UserStatusEnum::LEAVE, UserStatusEnum::SICK]);
            })
            ->orderBy('last_allocated');

        return $query->get();
    }

    public function deductLeadAllocationCount($quoteModel, $quoteUuid)
    {
        $quote = $quoteModel::with('advisor')->where('uuid', $quoteUuid)->first();

        if ($quote->advisor) {
            $leadAllocation = LeadAllocation::where('user_id', $quote->advisor->id)->first();
            $leadAllocation->allocation_count = $leadAllocation->allocation_count - 1;
            if (in_array($quote->assignment_type, [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED])) {
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
}
