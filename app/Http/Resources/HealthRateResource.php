<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emirate_type' => $this->emirate_type,
            'cohort' => $this->cohort,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'premium' => $this->premium,
            'status' => $this->status,
            'version' => $this->version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'health_plan_id' => $this->health_plan_id,
            'health_plan' => $this->whenLoaded('healthPlan', function () {
                return $this->healthPlan->code;
            }),
            'health_plan_co_payment_id' => $this->health_plan_co_payment_id,
            'health_plan_co_payment' => $this->whenLoaded('healthPlanCoPayment', function () {
                return $this->healthPlanCoPayment->code;
            }),
        ];
    }
}
