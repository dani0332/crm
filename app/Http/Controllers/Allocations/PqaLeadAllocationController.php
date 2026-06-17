<?php

declare(strict_types=1);

namespace App\Http\Controllers\Allocations;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\PqaAllocationAvailabilityRequest;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\PqaAllocation\PqaLeadAllocationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PqaLeadAllocationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        if (! $user->hasAnyPermission([
            PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::PQA_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::PQA_LEAD_ALLOCATION_VIEW_ONLY,
        ])) {
            abort(403);
        }

        $canMutate = $user->hasAnyPermission([PermissionsEnum::PQA_LEAD_ALLOCATION_EDIT])
            || $user->hasAnyRole([RolesEnum::Admin, RolesEnum::LeadPool, RolesEnum::Engineering]);

        $totalAssignedLeadCount = 0;
        $availableUsers = 0;
        $unAvailableUsers = 0;

        $todayTotalLeadCount = $this->getTodaysLeadMetricPlaceholder();
        $todayTotalUnAssignedLeadCount = $this->getTodaysUnassignedLeadMetricPlaceholder();

        $advisors = $this->getPreQualificationAdvisors();
        foreach ($advisors as $value) {
            $totalAssignedLeadCount += $value->allocationCount;
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        return inertia('PqaAllocation/Index', [
            'totalAssignedLeadCount' => $totalAssignedLeadCount,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'todayTotalLeadCount' => $todayTotalLeadCount,
            'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
            'data' => $advisors,
            'lobSpecificLeadAllocation' => null,
            'canMutatePqaAllocation' => $canMutate,
        ]);
    }

    /**
     * When PQA pipeline assigns quotes to advisors, replace these with real counts from the relevant quote tables.
     */
    private function getTodaysLeadMetricPlaceholder(): int
    {
        return 0;
    }

    private function getTodaysUnassignedLeadMetricPlaceholder(): int
    {
        return 0;
    }

    /**
     * @return Collection<int, object>
     */
    private function getPreQualificationAdvisors()
    {
        try {
            $healthTypeId = QuoteTypes::HEALTH->id();
            $corplineTypeId = QuoteTypes::CORPLINE->id();
            $healthNewLeadStatus = QuoteStatusEnum::NewLead;
            $corplineQualPendingStatus = QuoteStatusEnum::QualificationPending;
            $groupMedicalTypeId = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            $businessQuoteTypeId = (int) QuoteTypes::BUSINESS->id();
            $productType = TeamTypeEnum::PRODUCT;
            $corplineName = QuoteTypes::CORPLINE->value;
            $groupMedicalName = QuoteTypes::GROUP_MEDICAL->value;

            $rows = User::activeUser()
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::raw("(
                        CASE
                            WHEN la.quote_type_id = {$healthTypeId} THEN (
                                SELECT COUNT(*)
                                FROM health_quote_request hqr
                                WHERE hqr.pq_advisor_id = users.id
                                  AND hqr.quote_status_id = {$healthNewLeadStatus}
                            )
                            WHEN la.quote_type_id = {$corplineTypeId} THEN (
                                SELECT COUNT(*)
                                FROM business_quote_request bqr
                                WHERE bqr.pq_advisor_id = users.id
                                  AND bqr.quote_status_id = {$corplineQualPendingStatus}
                                  AND bqr.business_type_of_insurance_id != {$groupMedicalTypeId}
                            )
                            ELSE 0
                        END
                    ) as allocationCount"),
                    'la.last_allocated as lastAllocatedTs',
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    DB::raw("COALESCE(
                        CASE
                            WHEN la.quote_type_id = {$businessQuoteTypeId} THEN (
                                SELECT t.name
                                FROM user_products up2
                                JOIN teams t ON t.id = up2.product_id
                                    AND t.type = {$productType}
                                    AND t.name IN ('{$corplineName}', '{$groupMedicalName}')
                                WHERE up2.user_id = users.id
                                LIMIT 1
                            )
                            ELSE qt.code
                        END,
                        la.quote_type
                    ) as quoteTypeCode"),
                )
                ->join('pqa_lead_allocation_config as la', 'la.user_id', '=', 'users.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->leftJoin('quote_type as qt', 'qt.id', '=', 'la.quote_type_id')
                ->where('mhr.model_type', User::class)
                ->where('r.name', RolesEnum::PreQualificationAdvisor)
                ->groupBy(
                    'users.name',
                    'users.id',
                    'users.status',
                    'la.id',
                    'la.manual_assignment_count',
                    'la.auto_assignment_count',
                    'la.last_allocated',
                    'la.max_capacity',
                    'la.reset_cap',
                    'qt.code',
                )
                ->get();

            return $rows->map(function ($row) {
                $row->lastAllocation = ! empty($row->lastAllocatedTs)
                    ? Carbon::createFromTimestamp((int) $row->lastAllocatedTs)->format('d-m-Y H:i:s')
                    : null;
                unset($row->lastAllocatedTs);

                return $row;
            });
        } catch (\Throwable $e) {
            LoggerService::error('PQA allocation get advisors error: '.$e->getMessage());

            return collect();
        }
    }

    public function updateAvailability(PqaAllocationAvailabilityRequest $request): JsonResponse
    {
        $this->authorizePqaMutation();

        if (empty($request->items)) {
            return response()->json([
                'message' => 'Items not found.',
            ], 404);
        }

        app(PqaLeadAllocationService::class)->updateAvailability($request->items);

        return response()->json([
            'message' => 'Pre Qualification Advisor status updated successfully.',
        ], 200);
    }

    public function updateCaps(PqaAllocationAvailabilityRequest $request): JsonResponse
    {
        $this->authorizePqaMutation();

        if (! isset($request->items)) {
            return response()->json([
                'message' => 'Please select at least one item.',
            ], 422);
        }

        app(PqaLeadAllocationService::class)->updateCaps($request->items);

        return response()->json([
            'message' => 'Max capacity updated successfully.',
        ], 200);
    }

    public function updateResetCapSwitch(PqaAllocationAvailabilityRequest $request): JsonResponse
    {
        $this->authorizePqaMutation();

        $requester = auth()->user();
        if (isset($request->items)) {
            LoggerService::info(self::class."::updateResetCapSwitch - Requester: {$requester->id}: {$requester->name} ({$requester->email}) ".json_encode($request->all()));

            app(PqaLeadAllocationService::class)->resetCap($request->items);

            return response()->json([
                'message' => 'Reset cap updated successfully.',
            ], 200);
        }

        LoggerService::error(self::class."::updateResetCapSwitch - Requester: {$requester->id}: {$requester->name} ({$requester->email}) ".json_encode($request->all()));

        return response()->json([
            'message' => 'Please select at least one item.',
        ], 422);
    }

    private function authorizePqaMutation(): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(401);
        }

        if (! $user->hasAnyPermission([PermissionsEnum::PQA_LEAD_ALLOCATION_EDIT])
            && ! $user->hasAnyRole([RolesEnum::Admin, RolesEnum::LeadPool, RolesEnum::Engineering])) {
            abort(403);
        }
    }
}
