<?php

namespace App\Services;

use App\Enums\GenderEnum;
use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HealthRateService extends BaseService
{
    public function __construct(private HealthPlanService $healthPlanService) {}
    public function getList(Request $request): LengthAwarePaginator
    {
        $query = HealthRate::with('healthPlan', 'healthPlanCoPayment')
            ->select(
                'id',
                'health_plan_id',
                'health_plan_co_payment_id',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
                'is_active',
                'created_at',
                'updated_at',
            )
            ->where('health_plan_id', $request->id)
            ->where('status', strtolower($request->status ?? HealthPlanRateSheetStatusEnum::ACTIVE->value))
            ->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 10);

        return $query->paginate($perPage);
    }

    public function getRateById(int $id): ?HealthRate
    {
        return HealthRate::with('healthPlan', 'healthPlanCoPayment')
            ->select(
                'id',
                'health_plan_id',
                'health_plan_co_payment_id',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
                'is_active',
                'created_at',
                'updated_at',
            )
            ->firstWhere('id', $id);
    }

    public function create(array $data)
    {
        // Fetch parent active plan (if exists)
        // We have to check against active and draft plan rates only
        $planIds = $this->healthPlanService->getRelatedPlanIds($data['health_plan_id']);

        // Get draft rate against plan ids
        $draftRatesSheet = HealthRateControl::whereIn('health_plan_id', $planIds)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        // If not found, add new draft rate sheet
        if (! $draftRatesSheet) {
            return $this->addRate($data, $planIds);
        }

        // Else add rate to existing draft rate sheet
        return $this->addRate($data, $planIds, $draftRatesSheet);
    }

    private function addRate(array $data, array $planIds, ?HealthRateControl $healthRateControl = null): HealthRate
    {
        $plan = $this->healthPlanService->getPlanById($data['health_plan_id']);

        $healthRate = DB::transaction(function () use ($data, $plan, $planIds, $healthRateControl) {
            // Add health rates control (rate sheet) if not exists
            if (! $healthRateControl) {
                $data['version'] = $this->deriveVersion($planIds);

                $healthRateControl = HealthRateControl::create([
                    'health_plan_id' => $data['health_plan_id'],
                    'version' => $data['version'],
                    'effective_from' => $data['effective_from'],
                    'effective_to' => $data['effective_to'],
                    'total_records' => 1,
                    'created_by' => $data['user_id'],
                ]);
            } else {
                // Check duplicate rates under same sheet
                $this->checkDuplicateRates($healthRateControl->id, $data, $plan);

                // Update existing health rate control
                $healthRateControl->total_records++;
                $healthRateControl->effective_from = $data['effective_from'];
                $healthRateControl->effective_to = $data['effective_to'];
                $healthRateControl->save();

                // Get version of existing health rate control
                $data['version'] = $healthRateControl->version;
            }

            // Add health rate
            $healthRate = HealthRate::create([
                'health_plan_id' => $data['health_plan_id'],
                'health_rate_control_id' => $healthRateControl->id,
                'version' => $data['version'],
                'health_plan_co_payment_id' => $data['health_plan_co_payment_id'],
                'text' => $data['text'],
                'text_ar' => $data['text_ar'],
                'premium' => $data['premium'],
                'is_active' => $data['is_active'],
                'emirate_type' => $data['emirate_type'],
                'min_age' => $data['min_age'],
                'max_age' => $data['max_age'],
                'gender' => $plan->gender_enabled ? $data['gender'] : null,
                'cohort' => $plan->cohort_enabled ? $data['cohort'] : null,
                'marital_status' => $plan->marital_status_enabled
                    && strtolower($data['gender']) == strtolower(GenderEnum::FEMALE->value) ? $data['marital_status'] : null,
            ]);

            return $healthRate;
        });

        return $healthRate;
    }

    private function deriveVersion(array $planIds): float
    {
        // Get active rate sheet version
        $activeRateSheet = HealthRateControl::whereIn('health_plan_id', $planIds)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->first();

        // If found, return next version
        if ($activeRateSheet) {
            return $activeRateSheet->version + 0.1;
        }

        return 1.0;
    }

    private function checkDuplicateRates(int $healthRateControlId, array $data, HealthPlan $plan): void
    {
        // Get all rates of sheet
        $existingRates = HealthRate::where('health_rate_control_id', $healthRateControlId)->get();

        // Prepare fields for duplicate check
        $matchingFields = [
            'health_plan_co_payment_id',
            'emirate_type',
        ];

        if ($plan->gender_enabled) {
            $matchingFields[] = 'gender';
        }

        if ($plan->marital_status_enabled) {
            $matchingFields[] = 'marital_status';
        }

        if ($plan->cohort_enabled) {
            $matchingFields[] = 'cohort';
        }

        // Iterate through existing rates to check duplicate
        foreach ($existingRates as $rate) {
            $duplicate = true;
            foreach ($matchingFields as $field) {
                if ($rate->{$field} && $rate->{$field} != $data[$field]) {
                    $duplicate = false;
                    break;
                }
            }

            if ($duplicate) {
                // Same format as api response
                throw new HttpResponseException(
                    response()->json([
                        'status' => false,
                        'errors' => [
                            'Duplicate rate detected in same rate sheet.',
                        ],
                    ], 422)
                );
            }
        }

        // Check age overlap
        $overlapExists = $existingRates->contains(function ($rate) use ($data) {
            return $rate->min_age <= $data['max_age'] &&
                   $rate->max_age >= $data['min_age'];
        });

        if ($overlapExists) {
            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'errors' => [
                        'Age range overlaps with an existing rate sheet.',
                    ],
                ], 422)
            );
        }
    }

    public function update(int $id, array $data): HealthRate
    {
        $rate = HealthRate::find($id);

        // If draft, update same version
        if ($rate->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
            // Check duplicate rates under same sheet
            // $this->checkDuplicateRates($rate->health_rate_control_id, $data);

            $rate->fill($data);
            $rate->save();

            return $rate;
        }

        // Else (active)

        return $rate;
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Fetch health rate control id
            $rate = HealthRate::select('health_rate_control_id')->find($id);
            $healthRateControlId = $rate->health_rate_control_id;

            HealthRate::destroy($id);

            // After deleteing check if there is any rate exists with the same health rate control id
            $rates = HealthRate::where('health_rate_control_id', $healthRateControlId)->count();

            // If not, delete the health rate control
            if ($rates == 0) {
                HealthRateControl::destroy($healthRateControlId);
            }
        });
    }
}
