<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyIssuanceLog>
 */
class PolicyIssuanceLogFactory extends Factory
{
    protected $model = PolicyIssuanceLog::class;

    public function definition(): array
    {
        return [
            'policy_issuance_id' => PolicyIssuance::factory(),
            'model_type' => null,
            'model_id' => null,
            'step' => null,
            'status' => null,
            'payload' => null,
            'response' => null,
            'endPoint' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
