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
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
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

        $availableUsers = 0;
        $unAvailableUsers = 0;

        $advisors = $this->getPreQualificationAdvisors();
        foreach ($advisors as $value) {
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        $assignedCountsByLob = $this->getAssignedCountsByLob();
        $unassignedCountsByLob = $this->getUnassignedCountsByLob();

        return inertia('PqaAllocation/Index', [
            'assignedCountsByLob' => $assignedCountsByLob,
            'unassignedCountsByLob' => $unassignedCountsByLob,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'data' => $advisors,
            'lobSpecificLeadAllocation' => null,
            'canMutatePqaAllocation' => $canMutate,
        ]);
    }

    /**
     * Count assigned leads per LOB for today (leads with a PQA advisor assigned today).
     *
     * @return array{Health: int, CorpLine: int, Group Medical: int, total: int}
     */
    private function getAssignedCountsByLob(): array
    {
        $healthNewLeadStatus = QuoteStatusEnum::NewLead;
        $corplineQualPendingStatus = QuoteStatusEnum::QualificationPending;
        $groupMedicalTypeId = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
        $today = now()->toDateString();

        $healthCount = HealthQuote::query()
            ->whereNotNull('pq_advisor_id')
            // ->where('quote_status_id', $healthNewLeadStatus)
            ->whereDate('pq_assigned_at', $today)
            ->whereDate('created_at', $today)
            ->count();

        $corplineCount = BusinessQuote::query()
            ->whereNotNull('pq_advisor_id')
            // ->where('quote_status_id', $corplineQualPendingStatus)
            ->where('business_type_of_insurance_id', '!=', $groupMedicalTypeId)
            ->whereDate('pq_assigned_at', $today)
            ->whereDate('created_at', $today)
            ->count();

        $groupMedicalCount = BusinessQuote::query()
            ->whereNotNull('pq_advisor_id')
            ->where('business_type_of_insurance_id', $groupMedicalTypeId)
            ->whereDate('pq_assigned_at', $today)
            ->whereDate('created_at', $today)
            ->count();

        return [
            QuoteTypes::HEALTH->value => $healthCount,
            QuoteTypes::CORPLINE->value => $corplineCount,
            QuoteTypes::GROUP_MEDICAL->value => $groupMedicalCount,
            'total' => $healthCount + $corplineCount + $groupMedicalCount,
        ];
    }

    /**
     * Count unassigned leads per LOB (leads in PQA-eligible statuses with no PQA advisor assigned).
     *
     * @return array{Health: int, CorpLine: int, Group Medical: int, total: int}
     */
    private function getUnassignedCountsByLob(): array
    {
        $healthNewLeadStatus = QuoteStatusEnum::NewLead;
        $corplineQualPendingStatus = QuoteStatusEnum::QualificationPending;
        $groupMedicalTypeId = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;

        $today = now()->toDateString();

        $healthCount = HealthQuote::query()
            ->whereNull('pq_advisor_id')
            // ->where('quote_status_id', $healthNewLeadStatus)
            ->whereDate('created_at', $today)
            ->count();

        $corplineCount = BusinessQuote::query()
            ->whereNull('pq_advisor_id')
            // ->where('quote_status_id', $corplineQualPendingStatus)
            ->where('business_type_of_insurance_id', '!=', $groupMedicalTypeId)
            ->whereDate('created_at', $today)
            ->count();

        $groupMedicalCount = BusinessQuote::query()
            ->whereNull('pq_advisor_id')
            ->where('business_type_of_insurance_id', $groupMedicalTypeId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake])
            ->whereDate('created_at', $today)
            ->count();

        return [
            QuoteTypes::HEALTH->value => $healthCount,
            QuoteTypes::CORPLINE->value => $corplineCount,
            QuoteTypes::GROUP_MEDICAL->value => $groupMedicalCount,
            'total' => $healthCount + $corplineCount + $groupMedicalCount,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function getPreQualificationAdvisors()
    {
        try {
            $healthTypeId = QuoteTypes::HEALTH->id();
            $healthNewLeadStatus = QuoteStatusEnum::NewLead;
            $corplineQualPendingStatus = QuoteStatusEnum::QualificationPending;
            $groupMedicalTypeId = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            $businessQuoteTypeId = (int) QuoteTypes::BUSINESS->id();
            $productType = TeamTypeEnum::PRODUCT;
            $corplineName = QuoteTypes::CORPLINE->value;
            $groupMedicalName = QuoteTypes::GROUP_MEDICAL->value;
            $today = now()->toDateString();

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
                                  AND DATE(hqr.created_at) = '{$today}'
                            )
                            WHEN la.quote_type_id = {$businessQuoteTypeId} AND UPPER(t_lob.name) = UPPER('{$groupMedicalName}') THEN (
                                SELECT COUNT(*)
                                FROM business_quote_request bqr
                                WHERE bqr.pq_advisor_id = users.id
                                  AND bqr.business_type_of_insurance_id = {$groupMedicalTypeId}
                                  AND DATE(bqr.created_at) = '{$today}'
                            )
                            WHEN la.quote_type_id = {$businessQuoteTypeId} AND UPPER(t_lob.name) = UPPER('{$corplineName}') THEN (
                                SELECT COUNT(*)
                                FROM business_quote_request bqr
                                WHERE bqr.pq_advisor_id = users.id
                                  AND bqr.business_type_of_insurance_id != {$groupMedicalTypeId}
                                  AND DATE(bqr.created_at) = '{$today}'
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
                            WHEN la.quote_type_id = {$businessQuoteTypeId} THEN
                                CASE
                                    WHEN UPPER(t_lob.name) = UPPER('{$groupMedicalName}') THEN '{$groupMedicalName}'
                                    WHEN UPPER(t_lob.name) = UPPER('{$corplineName}') THEN '{$corplineName}'
                                    ELSE qt.code
                                END
                            ELSE qt.code
                        END,
                        la.quote_type
                    ) as quoteTypeCode"),
                )
                ->join('pqa_lead_allocation_config as la', 'la.user_id', '=', 'users.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->leftJoin('quote_type as qt', 'qt.id', '=', 'la.quote_type_id')
                ->leftJoin('user_products as up_lob', function ($join) use ($businessQuoteTypeId) {
                    $join->on('up_lob.user_id', '=', 'users.id')
                        ->whereRaw("la.quote_type_id = {$businessQuoteTypeId}");
                })
                ->leftJoin('teams as t_lob', function ($join) use ($productType, $corplineName, $groupMedicalName) {
                    $join->on('t_lob.id', '=', 'up_lob.product_id')
                        ->where('t_lob.type', $productType)
                        ->whereRaw("(
                             UPPER(t_lob.name) IN (UPPER('{$corplineName}'), UPPER('{$groupMedicalName}'))
                             OR UPPER(t_lob.name) = UPPER(qt.code)
                         )");
                })
                ->where('mhr.model_type', User::class)
                ->where('r.name', RolesEnum::PreQualificationAdvisor)
                ->where(function ($q) use ($businessQuoteTypeId) {
                    $q->where('la.quote_type_id', '!=', $businessQuoteTypeId)
                        ->orWhereNotNull('t_lob.id');
                })
                ->whereExists(function ($sub) use ($productType, $corplineName, $groupMedicalName, $businessQuoteTypeId) {
                    $sub->selectRaw('1')
                        ->from('user_products as up_m')
                        ->join('teams as t_m', 't_m.id', '=', 'up_m.product_id')
                        ->whereColumn('up_m.user_id', 'users.id')
                        ->where('t_m.type', $productType)
                        ->whereRaw("(
                            (UPPER(t_m.name) IN (UPPER('{$corplineName}'), UPPER('{$groupMedicalName}')) AND la.quote_type_id = {$businessQuoteTypeId})
                            OR
                            (UPPER(t_m.name) NOT IN (UPPER('{$corplineName}'), UPPER('{$groupMedicalName}')) AND la.quote_type_id = (
                                SELECT qt_m.id FROM quote_type qt_m WHERE UPPER(qt_m.code) = UPPER(t_m.name) LIMIT 1
                            ))
                        )");
                })
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
                    't_lob.name',
                )
                ->get();

            return $rows->map(function ($row) {
                $row->lastAllocation = ! empty($row->lastAllocatedTs)
                    ? Carbon::createFromTimestamp((int) $row->lastAllocatedTs, 'Asia/Dubai')->format('d-m-Y H:i:s')
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
