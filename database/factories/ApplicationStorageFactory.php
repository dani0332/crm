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
     * Create VAT_VALUE record for SQLite test database.
     * Since test database is reset each time, we can directly create without checking.
     *
     * @param string $value VAT value as percentage (e.g., "5" for 5%)
     * @return ApplicationStorage
     */
    public static function createVatValueForSqlite(string $value = '5'): ApplicationStorage
    {
        $db = \Illuminate\Support\Facades\DB::connection('sqlite');
        
        // Insert directly using DB facade to avoid mass assignment issues
        $id = $db->table('application_storage')->insertGetId([
            'key_name' => 'VAT_VALUE',
            'value' => $value,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Load and return the model instance
        return ApplicationStorage::on('sqlite')->find($id);
    }
}

