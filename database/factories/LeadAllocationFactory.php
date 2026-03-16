<?php

namespace Database\Factories;

use App\Models\LeadAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadAllocationFactory extends Factory
{
    protected $model = LeadAllocation::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'quote_type_id' => 1,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'max_capacity' => 0,
            'reset_cap' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
