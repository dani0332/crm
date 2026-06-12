<?php

namespace Database\Factories;

use App\Models\NationalityPool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NationalityPool>
 */
class NationalityPoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }

    public function createForSqlite(): NationalityPool
    {
        return NationalityPool::forceCreate([
            'health_nationality_group_ids' => '1,2,3',
            'canonical_nationality_codes' => 'CN0001,CN0002',
            'effective_from' => now(),
            'effective_to' => '2099-12-31',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
