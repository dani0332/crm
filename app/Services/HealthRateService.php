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
use Illuminate\Support\Collection;
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
                'text',
                'text_ar',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
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
                'text',
                'text_ar',
                'emirate_type',
                'cohort', 'gender',
                'marital_status',
                'min_age',
                'max_age',
                'premium',
                'status',
                'version',
                'created_at',
                'updated_at',
            )
            ->firstWhere('id', $id);
    }

    public function create(array $data)
    {
        // Fetch related plan ids (if exists)
        // We have to check against active and draft plans only
        $planIds = $this->healthPlanService->getRelatedPlanIds($data['health_plan_id']);

        // Get draft rate sheet against plan ids
        $draftRatesSheet = HealthRateControl::whereIn('health_plan_id', $planIds)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        // If not found, add new draft rate sheet
        if (! $draftRatesSheet) {
            return DB::transaction(function () use ($data, $planIds) {
                $newRate = $this->addRate($data, $planIds);

                // If it's active plan, link rate to existing draft plan it exists
                $this->linkRateToExistingDraftPlan($data['health_plan_id'], $newRate);

                return $newRate;
            });
        }

        // Else add rate to existing draft rate sheet
        return $this->addRate($data, $planIds, $draftRatesSheet);
    }

    private function addRate(array $data, array $planIds, ?HealthRateControl $healthRateControl = null, ?Collection $existingRates = null): HealthRate
    {
        $plan = $this->healthPlanService->getPlanById($data['health_plan_id']);

        $healthRate = DB::transaction(function () use ($data, $plan, $planIds, $healthRateControl, $existingRates) {
            // Add health rates control (rate sheet) if not exists
            if (! $healthRateControl) {
                $data['version'] = $this->deriveVersion($planIds);

                $healthRateControl = HealthRateControl::create([
                    'health_plan_id' => $data['health_plan_id'],
                    'version' => $data['version'],
                    'effective_from' => $data['effective_from'],
                    'effective_to' => $data['effective_to'],
                    'total_records' => count($existingRates ?? []) + 1,
                    'created_by' => $data['user_id'],
                ]);
            } else {
                // Update existing health rate control
                $healthRateControl->total_records++;
                $healthRateControl->effective_from = $data['effective_from'];
                $healthRateControl->effective_to = $data['effective_to'];
                $healthRateControl->health_plan_id = $data['health_plan_id'];
                $healthRateControl->save();

                // Get version of existing health rate control
                $data['version'] = $healthRateControl->version;
            }

            // Add existing active rates if exist (in case active plan)
            if ($existingRates) {
                $bulkRates = [];

                foreach ($existingRates as $existingRate) {
                    $bulkRates[] = [
                        'health_plan_id' => $data['health_plan_id'],
                        'health_rate_control_id' => $healthRateControl->id,
                        'version' => $data['version'],
                        'health_plan_co_payment_id' => $existingRate->health_plan_co_payment_id,
                        'text' => $existingRate->text,
                        'text_ar' => $existingRate->text_ar,
                        'premium' => $existingRate->premium,
                        'is_active' => $existingRate->is_active,
                        'emirate_type' => $existingRate->emirate_type,
                        'min_age' => $existingRate->min_age,
                        'max_age' => $existingRate->max_age,
                        'gender' => $existingRate->gender,
                        'cohort' => $existingRate->cohort,
                        'marital_status' => $existingRate->marital_status,
                        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($bulkRates)) {
                    HealthRate::insert($bulkRates);
                }
            }

            // Check duplicate rates under same sheet
            $this->checkDuplicateRates($healthRateControl->id, $data, $plan);

            // Add given health rate
            $healthRate = HealthRate::create([
                'health_plan_id' => $data['health_plan_id'],
                'health_rate_control_id' => $healthRateControl->id,
                'version' => $data['version'],
                'health_plan_co_payment_id' => $data['health_plan_co_payment_id'],
                'premium' => $data['premium'],
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

    private function linkRateToExistingDraftPlan(int $currentPlanId, HealthRate $newRate): void
    {
        // Check if draft plan exists
        $draftPlan = HealthPlan::where('parent_id', $currentPlanId)
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        if (! $draftPlan) {
            return;
        }

        // Link rate sheet and rate to draft plan
        DB::table('health_rates_control')
            ->where('id', $newRate->health_rate_control_id)
            ->update([
                'health_plan_id' => $draftPlan->id,
            ]);

        $newRate->health_plan_id = $draftPlan->id;
        $newRate->save();
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
        $existingRates = HealthRate::where('health_rate_control_id', $healthRateControlId);

        // Exclude current rate id if update request
        if (isset($data['id'])) {
            $existingRates->where('id', '!=', $data['id']);
        }

        $existingRates = $existingRates->get();

        // Prepare fields for duplicate check
        $matchingFields = ['health_plan_co_payment_id'];

        if ($plan->gender_enabled) {
            $matchingFields[] = 'gender';
        }

        if ($plan->marital_status_enabled and ! empty($data['gender']) and strtolower($data['gender']) == strtolower(GenderEnum::FEMALE->value)) {
            $matchingFields[] = 'marital_status';
        }

        if ($plan->cohort_enabled) {
            $matchingFields[] = 'cohort';
        }

        // Iterate through existing rates to check duplicate
        if ($existingRates) {
            foreach ($existingRates as $rate) {
                $duplicate = true;

                // Loop through composite key fields to check duplicate
                foreach ($matchingFields as $field) {
                    // Check duplicate by composite key
                    // Compare, treating null and missing as equivalent
                    $rateValue = $rate->{$field} !== null ? strtolower($rate->{$field}) : null;
                    $dataValue = isset($data[$field]) && $data[$field] !== null ? strtolower($data[$field]) : null;

                    if ($rateValue !== $dataValue) {
                        $duplicate = false;
                        break;
                    }
                }

                // If basic combination is not duplicate, continue
                if (! $duplicate) {
                    continue;
                }

                // If composite combination matches and age overlaps, throw an error
                if ($duplicate && $rate->min_age <= $data['max_age'] && $rate->max_age >= $data['min_age']) {
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
        }
    }

    public function update(int $id, array $data): HealthRate
    {
        $rate = HealthRate::with('healthPlan')->find($id);
        $plan = $rate->healthPlan;

        // If draft, update same version
        if ($rate->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
            return DB::transaction(function () use ($data, $rate) {
                // Get plan to validate cohort, gender
                $plan = $rate->healthPlan; // $this->healthPlanService->getPlanById($rate->health_plan_id);

                // Check duplicate rates under same sheet
                $this->checkDuplicateRates($rate->health_rate_control_id, $data, $rate->healthPlan);

                // Update gender, cohort, marital status if plan has enabled
                $data['cohort'] = $plan->cohort_enabled ? $data['cohort'] : null;
                $data['gender'] = $plan->gender_enabled ? $data['gender'] : null;
                $data['marital_status'] = $plan->marital_status_enabled && $plan->gender_enabled
                    && strtolower($data['gender']) == strtolower(GenderEnum::FEMALE->value) ? $data['marital_status'] : null;

                $rate->fill($data);
                $rate->save();

                // Update health rate control
                HealthRateControl::where('id', $rate->health_rate_control_id)->update([
                    'effective_from' => $data['effective_from'],
                    'effective_to' => $data['effective_to'],
                ]);

                return $rate;
            });
        }

        // As per bot comment
        if ($data['health_plan_id'] != $rate->health_plan_id) {
            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'errors' => [
                        'Health plan for given rate and request does not match.',
                    ],
                ], 422)
            );
        }

        // Else (active/archive)
        // Get existing rate sheet against plan id and exclude current rate id
        $existingRates = HealthRate::where('health_plan_id', $rate->health_plan_id)
            ->where('id', '!=', $id)
            ->where('status', $rate->status)
            ->get();

        // Get draft version of rate sheet
        // Since we need to append existing active/archived rates to draft rate sheet
        $draftRateSheet = HealthRateControl::whereIn('health_plan_id', [$rate->health_plan_id, $plan->parent_id])
            ->where('status', HealthPlanRateSheetStatusEnum::DRAFT->value)
            ->first();

        // Validate duplicate between existing active rates and draft rates
        // Since we are copying exting active rates into draft rates
        if ($draftRateSheet and $existingRates) {
            foreach ($existingRates as $activeRate) {
                $this->checkDuplicateRates($draftRateSheet->id, $activeRate->toArray(), $rate->healthPlan);
            }
        }

        $data['health_plan_id'] = $rate->status == HealthPlanRateSheetStatusEnum::ACTIVE->value ? $rate->health_plan_id : $plan->parent_id;

        return $this->addRate($data, [$rate->health_plan_id, $plan->parent_id], $draftRateSheet, $existingRates);
    }

    public function publishRateControl(int $rateControlId, int $publishedById): void
    {
        DB::transaction(function () use ($rateControlId, $publishedById) {
            $rateControl = HealthRateControl::with('healthPlan')->find($rateControlId);

            if (! $rateControl) {
                return;
            }

            // Check it associated plan is draft, if so, make it scheduled
            $plan = $rateControl->healthPlan;
            if ($plan->status == HealthPlanRateSheetStatusEnum::DRAFT->value) {
                $plan->status = HealthPlanRateSheetStatusEnum::SCHEDULED->value;
                $plan->save();
            }

            $rateControl->rates()->update([
                'status' => HealthPlanRateSheetStatusEnum::SCHEDULED->value,
            ]);

            $rateControl->status = HealthPlanRateSheetStatusEnum::SCHEDULED->value;
            $rateControl->published_at = now()->toDateString();
            $rateControl->published_by = $publishedById;
            $rateControl->save();
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            // Fetch health rate control id
            $rate = HealthRate::select('health_rate_control_id')->find($id);
            $healthRateControlId = $rate->health_rate_control_id;

            // Delete health rate
            HealthRate::destroy($id);

            // After deleteing check if there is any rate exists with the same health rate control id
            $rates = HealthRate::where('health_rate_control_id', $healthRateControlId)->count();

            // If not, delete the health rate control
            if ($rates == 0) {
                HealthRateControl::destroy($healthRateControlId);
            }

            // If rate exists, reduce total count
            if ($rates > 0) {
                HealthRateControl::where('id', $healthRateControlId)->decrement('total_records');
            }
        });
    }
}
