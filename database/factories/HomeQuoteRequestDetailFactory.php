<?php

namespace Database\Factories;

use App\Models\HomeQuoteRequestDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeQuoteRequestDetail>
 */
class HomeQuoteRequestDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'home_quote_request_id' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_id' => null,
            'utm_term' => null,
            'utm_content' => null,
            'advisor_assigned_date' => null,
            'advisor_assigned_by_id' => null,
            'next_followup_date' => null,
            'notes' => null,
            'lost_reason_id' => null,
            'transapp_code' => null,
            'insly_id' => null,
            'insly_advisor_name' => null,
            'risk_score_override' => null,
            'risk_score_override_by' => null,
            'risk_score_override_date' => null,
            'is_deleted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
