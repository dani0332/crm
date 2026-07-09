<?php

namespace Database\Factories;

use App\Models\YachtQuoteRequestDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YachtQuoteRequestDetail>
 */
class YachtQuoteRequestDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'yacht_quote_request_id' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'advisor_assigned_date' => null,
            'advisor_assigned_by_id' => null,
            'risk_score_override' => null,
            'risk_score_override_by' => null,
            'risk_score_override_date' => null,
            'is_deleted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
