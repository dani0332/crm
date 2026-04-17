<?php

namespace Database\Factories;

use App\Models\CanonicalNationality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CanonicalNationality>
 */
class CanonicalNationalityFactory extends Factory
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

    public function createForSqlite(): CanonicalNationality
    {
        return CanonicalNationality::forceCreate([
            'nationality_id' => 1,
            'canonical_nationality_code' => 'CN0001',
            'canonical_nationality_name' => 'Afghan',
            'nationality_synonym' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
