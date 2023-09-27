<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\LeadAllocation;
use App\Models\Tier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function updateLeadAllocationCounts($userId): void
    {
        $timestamp = Carbon::now()->timestamp;

        DB::table('lead_allocation')
            ->where('user_id', $userId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'auto_assignment_count' => DB::raw('auto_assignment_count + 1'),
                'last_allocated' => $timestamp,
                'updated_at' => now(),
            ]);
    }

    public function getValuation($carModelDetailId, $yearOfManufacture)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-vehicle-value';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');

        $client = new \GuzzleHttp\Client();
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
            $getdecodeContents = json_decode($getContents);

            return $getdecodeContents;
        } else {
            info(' call to ken api failed for getting car valuation ');

            return 'API failed';
        }
    }

    public function getLeadAllocationRecordByUserId($userId)
    {
        try {
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();

            return $leadAllocation;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function addAllocationCounts($userId)
    {
        $allocationRecord = $this->getLeadAllocationRecordByUserId($userId);
        $allocationRecord->auto_assignment_count = $allocationRecord->auto_assignment_count + 1;
        $allocationRecord->allocation_count = $allocationRecord->allocation_count + 1;
        $allocationRecord->updated_at = now();
        $allocationRecord->last_allocated = now()->timestamp;
        $allocationRecord->save();

    }

    public function adjustAllocationCounts($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate)
    {
        info('lead current advisor_id is : '.json_encode($previousAdvisorId).' and lead created date is : '.$lead->created_at);
        $shouldUpdateAllocationRecord = $userId != $previousAdvisorId;
        $newAdvisorAllocationRecord = $this->getLeadAllocationRecordByUserId($userId);
        $previousAdvisorAllocationRecord = null;
        if ($previousAdvisorId != null) {
            $previousAdvisorAllocationRecord = $this->getLeadAllocationRecordByUserId($previousAdvisorId);
        }
        if ($shouldUpdateAllocationRecord) {
            info('new advisor ('.$userId.')  manual count before update is : '.$newAdvisorAllocationRecord->manual_assignment_count.' and auto assignment count is : '.$newAdvisorAllocationRecord->auto_assignment_count);
            if ($lead->assignment_type == AssignmentTypeEnum::SYSTEM_ASSIGNED || $lead->assignment_type == AssignmentTypeEnum::SYSTEM_REASSIGNED) {
                $newAdvisorAllocationRecord->auto_assignment_count = $newAdvisorAllocationRecord->auto_assignment_count + 1;
            } else {
                $newAdvisorAllocationRecord->manual_assignment_count = $newAdvisorAllocationRecord->manual_assignment_count + 1;
            }
            $newAdvisorAllocationRecord->allocation_count = $newAdvisorAllocationRecord->allocation_count + 1;
            $newAdvisorAllocationRecord->last_allocated = now()->timestamp;
            $newAdvisorAllocationRecord->updated_at = now();
            $newAdvisorAllocationRecord->save();
        }
        info('new advisor after update is : '.json_encode($newAdvisorAllocationRecord));
        if ($previousAdvisorId != null && Carbon::parse($oldAdvisorAssignedDate)->startOfDay() == now()->startOfDay()) { // will remove manual count from previous advisor lead is from current day only
            if ($lead->assignment_type == AssignmentTypeEnum::SYSTEM_ASSIGNED || $lead->assignment_type == AssignmentTypeEnum::SYSTEM_REASSIGNED) {
                if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->auto_assignment_count > 0) {
                    info('previous advisor ('.$userId.')  auto assignment count is : '.$previousAdvisorAllocationRecord->auto_assignment_count);
                    $previousAdvisorAllocationRecord->auto_assignment_count = $previousAdvisorAllocationRecord->auto_assignment_count - 1;
                }
            } else {
                if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->manual_assignment_count > 0) {
                    info('previous advisor ('.$userId.')  manual count before update is : '.$previousAdvisorAllocationRecord->manual_assignment_count);
                    $previousAdvisorAllocationRecord->manual_assignment_count = $previousAdvisorAllocationRecord->manual_assignment_count - 1;
                }
            }
            if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->allocation_count > 0) { // will reduce count for previous advisor if the count is greater than 0 to avoid going in -1
                info('previous advisor ('.$userId.')  allocation_count count before update is : '.$previousAdvisorAllocationRecord->allocation_count);
                $previousAdvisorAllocationRecord->allocation_count = $previousAdvisorAllocationRecord->allocation_count - 1;
                $previousAdvisorAllocationRecord->updated_at = now();
                $previousAdvisorAllocationRecord->last_allocated = now()->timestamp;
                $previousAdvisorAllocationRecord->save();
                info('previous advisor after update is : '.json_encode($previousAdvisorAllocationRecord));
            }
        }
        info('new advisor alloc. count :'.$newAdvisorAllocationRecord->allocation_count.', manual count :'.$newAdvisorAllocationRecord->manual_assignment_count.', auto count :'.$newAdvisorAllocationRecord->auto_assignment_count);
        if ($previousAdvisorAllocationRecord != null) {
            info('previous advisor alloc. count :'.$previousAdvisorAllocationRecord->allocation_count.', manual count :'.$previousAdvisorAllocationRecord->manual_assignment_count.', auto count :'.$previousAdvisorAllocationRecord->auto_assignment_count);
        }
        info('assignment count update for userId : '.$userId.', and lead code :  '.$lead->code);
    }

    public function updateExistingQuoteDetail($quoteDetail, $uuid): void
    {
        $quoteDetail->advisor_assigned_date = now();
        $quoteDetail->advisor_assigned_by_id = auth()->id();
        $quoteDetail->save();

        info('Quote detail update for lead : '.$uuid);
    }

    public function createNewQuoteDetail($leadId, $quoteModel, $keyColumn): void
    {
        $quoteModel::create([
            $keyColumn => $leadId,
            'advisor_assigned_date' => now(),
            'advisor_assigned_by_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        info('Quote request detail record not found, creating new entry');
    }

    public function getAssignmentTypeText($assignmentType)
    {
        $assignmentText = '';
        switch ($assignmentType) {
            case 1:
                $assignmentText = 'System Assigned';
                break;
            case 2:
                $assignmentText = 'System ReAssigned';
                break;
            case 3:
                $assignmentText = 'Manual Assigned';
                break;
            case 4:
                $assignmentText = 'Manual ReAssigned';
                break;
            default:
                break;
        }

        return $assignmentText;
    }
}
