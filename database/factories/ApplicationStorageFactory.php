<?php

namespace Database\Factories;

use App\Models\ApplicationStorage;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApplicationStorage>
 */
class ApplicationStorageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApplicationStorage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key_name' => $this->faker->unique()->word(),
            'value' => $this->faker->word(),
            'is_active' => 1,
        ];
    }

    /**
     * Create VAT_VALUE record for test database.
     * Since test database is reset each time, we can directly create without checking.
     * Uses the default database connection (SQLite in tests as configured in phpunit.xml).
     *
     * @param  string  $value  VAT value as percentage (e.g., "5" for 5%)
     */
    public static function createVatValueForSqlite(string $value = '5'): ApplicationStorage
    {
        // Use model-based creation with forceCreate to bypass mass assignment protection
        // Uses default connection (SQLite in tests as configured in phpunit.xml)
        return ApplicationStorage::forceCreate([
            'key_name' => 'VAT_VALUE',
            'value' => $value,
            'is_active' => 1,
        ]);
    }
}
