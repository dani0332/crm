<?php

namespace App\Services;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRateControl;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HealthPlanService extends BaseService
{
    public function getPlanByCode(string $code): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled')
            ->firstWhere('code', $code);
    }

    public function getRelatedPlanIds(int $planId): array
    {
        $ids = [$planId];
        $plan = HealthPlan::find($planId);

        // If draft, add parent id
        if ($plan->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
            $plan->parent_id && $ids[] = $plan->parent_id;

            return $ids;
        }

        // If active, add child plan id (only draft)
        if ($plan->status == HealthPlanRateSheetStatusEnum::ACTIVE->value) {
            $childPlan = HealthPlan::where('parent_id', $planId)
                ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
                ->first();

            $childPlan && $ids[] = $childPlan->id;

            return $ids;
        }

        return $ids;
    }

    public function getPlanById(int $id): ?HealthPlan
    {
        return HealthPlan::select('id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
            'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'maf_link', 'is_hidden', 'is_active',
            'status', 'version', 'cohort_enabled', 'gender_enabled', 'marital_status_enabled', 'created_at', 'updated_at', 'deleted_at')
            ->firstWhere('id', $id);
    }

    public function getList(Request $request): LengthAwarePaginator
    {
        // Apply pagination if 'per_page' is set in the request, otherwise return all results
        $query = HealthPlan::with('insuranceProvider', 'healthNetwork', 'healthRatingEligibility', 'healthPlanType')
            ->select(
                'id', 'code', 'text', 'text_ar', 'provider_id', 'health_business_type',
                'plan_type_id', 'health_rating_eligibility_id', 'health_network_id', 'is_hidden', 'is_active',
                'status', 'version', 'created_at', 'updated_at', 'deleted_at'
            )
            ->where('status', strtolower($request->status ?? HealthPlanRateSheetStatusEnum::ACTIVE->value))
            ->when($request->provider_id, function ($query) use ($request) {
                $query->where('provider_id', $request->provider_id);
            })
            ->when($request->code, function ($query) use ($request) {
                $query->where('code', 'like', '%'.$request->code.'%');
            })
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 25);

        return $query->paginate($perPage);
    }

    public function create(array $data): HealthPlan
    {
        return HealthPlan::create($data);
    }

    public function update(int $id, array $data): HealthPlan
    {
        $currentPlan = HealthPlan::find($id);

        // Check if it's draft, update same version
        if ($currentPlan->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
            $currentPlan->fill($data);
            $currentPlan->save();

            return $currentPlan;
        }

        // Check already has a draft version
        // If active plan, check against parent id
        // If archived plan, check against parent id
        $existingDraft = HealthPlan::where(
            'parent_id',
            $currentPlan->status == HealthPlanRateSheetStatusEnum::ACTIVE->value ? $id : $currentPlan->parent_id
        )
            ->whereIn('status', [HealthPlanRateSheetStatusEnum::DRAFT->value, HealthPlanRateSheetStatusEnum::SCHEDULED->value])
            ->first();

        if ($existingDraft) {
            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'errors' => [
                        'Draft or scheduled version already exists for this plan.',
                    ],
                ], 422)
            );
        }

        // Otherwise create new draft version
        $data['version'] = $this->deriveVersion($currentPlan);
        $data['parent_id'] = $currentPlan->status == HealthPlanRateSheetStatusEnum::ARCHIVED->value ? $currentPlan->parent_id : $id;
        $data['code'] = $currentPlan->code; // Keep current code

        $newDraftPlan = HealthPlan::create($data);

        // Link existing draft sheet (if any)
        $this->linkExistingDraftSheet($currentPlan->id, $newDraftPlan->id);

        return $newDraftPlan;
    }

    private function linkExistingDraftSheet(int $currentPlanId, int $newDraftPlanId): void
    {
        // Fetch existing draft sheet
        $existingDraftSheet = HealthRateControl::where('health_plan_id', $currentPlanId)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        if ($existingDraftSheet) {
            $existingDraftSheet->health_plan_id = $newDraftPlanId;
            $existingDraftSheet->save();
        }

        // Update draft rates plan id
        DB::table('health_rates')
            ->where('health_rate_control_id', $existingDraftSheet->id)
            ->update([
                'health_plan_id' => $newDraftPlanId,
            ]);
    }

    public function publish(int $id, int $publishedById): void
    {
        DB::transaction(function () use ($id, $publishedById) {
            $plan = HealthPlan::with('draftRateControl.rates')->find($id);

            $plan->status = HealthPlanRateSheetStatusEnum::SCHEDULED->value;
            $plan->save();

            $draftRateControl = $plan->draftRateControl;
            $draftRateControl->rates()->update([
                'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
            ]);

            $draftRateControl->status = HealthPlanRateSheetStatusEnum::SCHEDULED->value;
            $draftRateControl->published_at = now()->toDateString();
            $draftRateControl->published_by = $publishedById;
            $draftRateControl->save();
        });
    }

    private function deriveVersion(HealthPlan $plan): float
    {
        // When we edit active version, get next draft version
        if ($plan->status == HealthPlanRateSheetStatusEnum::ACTIVE->value) {
            return $plan->version + 0.1;
        }

        // When we publish draft version
        if ($plan->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
            $version = ceil($plan->version).'.0';

            return (float) $version;
        }

        // Else editing archived version, Get active version and return next draft version
        $activeVersion = HealthPlan::where('id', $plan->parent_id)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->first();

        if ($activeVersion) {
            return $activeVersion->version + 0.1;
        }

        return 1.0;
    }

    public function getStatusVersions(int $parentId, string $status): Collection
    {
        return HealthPlan::where('parent_id', $parentId)
            ->where('status', $status)
            ->orderByDesc('id')
            ->get();
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $healthPlan = HealthPlan::findOrFail($id);
            $healthPlan->rates()->delete();
            $healthPlan->healthRateControls()->delete();
            $healthPlan->delete();
        });
    }
}
