<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PqaLeadAllocationConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PqaLeadAllocationConfig>
 */
class PqaLeadAllocationConfigFactory extends Factory
{
    protected $model = PqaLeadAllocationConfig::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quote_type_id' => 1,
            'max_capacity' => 100,
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'last_allocated' => null,
            'reset_cap' => 0,
        ];
    }
}
