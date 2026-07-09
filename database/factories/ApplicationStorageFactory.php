<?php

namespace Database\Factories;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\HighRiskScoreBirdNotificationService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStorage>
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
     * High-risk AML score notification "to" recipient (see {@see HighRiskScoreBirdNotificationService}).
     */
    public function highRiskScoreNotificationToRecipient(?string $value = null): static
    {
        return $this->state(fn (): array => [
            'key_name' => ApplicationStorageEnums::HIGH_RISK_SCORE_NOTIFICATION_TO_RECIPIENT,
            'value' => $value ?? $this->faker->unique()->safeEmail(),
            'is_active' => 1,
        ]);
    }

    /**
     * High-risk AML score notification "cc" recipient (see {@see HighRiskScoreBirdNotificationService}).
     */
    public function highRiskScoreNotificationCcRecipient(?string $value = null): static
    {
        return $this->state(fn (): array => [
            'key_name' => ApplicationStorageEnums::HIGH_RISK_SCORE_NOTIFICATION_CC_RECIPIENT,
            'value' => $value ?? $this->faker->unique()->safeEmail(),
            'is_active' => 1,
        ]);
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

    public function travelAmlRetriggerEnabled(int $value = 1): static
    {
        return $this->state(fn (): array => [
            'key_name' => ApplicationStorageEnums::TRAVEL_AML_RETRIGGER_ENABLED,
            'value' => $value,
            'is_active' => 1,
        ]);
    }

    public function createLeadSourceEcommerceForSqlite(string $value): ApplicationStorage
    {
        return ApplicationStorage::forceCreate([
            'key_name' => ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE,
            'value' => $value,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createHealthTeamRoutingEnabledForSqlite(int $value): ApplicationStorage
    {
        return ApplicationStorage::forceCreate([
            'key_name' => ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED,
            'value' => $value,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
