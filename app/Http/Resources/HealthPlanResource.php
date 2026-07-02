<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthPlanResource extends JsonResource
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
            'code' => $this->code,
            'text' => $this->text,
            'text_ar' => $this->text_ar,
            'provider_id' => $this->provider_id,
            'health_business_type' => $this->health_business_type,
            'plan_type_id' => $this->plan_type_id,
            'health_rating_eligibility_id' => $this->health_rating_eligibility_id,
            'health_network_id' => $this->health_network_id,
            'provider' => $this->whenLoaded('insuranceProvider', function () {
                return $this->insuranceProvider->text;
            }),
            'plan_type' => $this->whenLoaded('healthPlanType', function () {
                return $this->healthPlanType->code;
            }),
            'health_rating_eligibility' => $this->whenLoaded('healthRatingEligibility', function () {
                return $this->healthRatingEligibility->code;
            }),
            'health_network' => $this->whenLoaded('healthNetwork', function () {
                return $this->healthNetwork->code;
            }),
            'maf_link' => $this->maf_link,
            'is_hidden' => $this->is_hidden,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'version' => $this->version,
            'cohort_enabled' => $this->cohort_enabled,
            'gender_enabled' => $this->gender_enabled,
            'marital_status_enabled' => $this->marital_status_enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
