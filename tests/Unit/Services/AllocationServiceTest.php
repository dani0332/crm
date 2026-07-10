<?php

declare(strict_types=1);

use App\Models\PersonalQuoteDetail;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\QueryException;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->user = TestDataSeeder::createUser();
    $this->actingAs($this->user);

    $this->allocationService = new AllocationService;
});

test('upsertQuoteDetail creates personal_quote_details record with advisor data', function () {
    $leadId = 1001;

    $this->allocationService->upsertQuoteDetail($leadId, PersonalQuoteDetail::class, 'personal_quote_id');

    $detail = PersonalQuoteDetail::where('personal_quote_id', $leadId)->first();

    expect($detail)->not->toBeNull()
        ->and($detail->advisor_assigned_date)->not->toBeNull()
        ->and($detail->advisor_assigned_by_id)->toBe($this->user->id);
});

test('upsertQuoteDetail retries update when unique constraint violation occurs', function () {
    FakeQuoteDetailModel::reset();

    $leadId = 2002;

    $this->allocationService->upsertQuoteDetail($leadId, FakeQuoteDetailModel::class, 'personal_quote_id');

    expect(FakeQuoteDetailModel::$updatedRecords)->toHaveCount(1);

    $updateCall = FakeQuoteDetailModel::$updatedRecords[0];

    expect($updateCall['key'])->toBe('personal_quote_id')
        ->and($updateCall['value'])->toBe($leadId)
        ->and($updateCall['values'])->toHaveKey('advisor_assigned_date')
        ->and($updateCall['values'])->toHaveKey('advisor_assigned_by_id')
        ->and($updateCall['values'])->toHaveKey('updated_at')
        ->and($updateCall['values']['advisor_assigned_by_id'])->toBe($this->user->id);
});

class FakeQuoteDetailModel
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public static array $updatedRecords = [];

    private string $whereColumn;
    private mixed $whereValue;

    public static function reset(): void
    {
        self::$updatedRecords = [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     *
     * @throws QueryException
     */
    public static function updateOrCreate(array $attributes, array $values): void
    {
        LoggerService::info('Simulating unique constraint violation in FakeQuoteDetailModel::updateOrCreate');

        $previous = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry', 23000);

        throw new QueryException(
            connectionName: 'sqlite',
            sql: 'insert into personal_quote_details (personal_quote_id) values (?)',
            bindings: [$attributes['personal_quote_id'] ?? null],
            previous: $previous
        );
    }

    public static function where(string $column, mixed $value): self
    {
        $instance = new self;
        $instance->whereColumn = $column;
        $instance->whereValue = $value;

        return $instance;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        self::$updatedRecords[] = [
            'key' => $this->whereColumn,
            'value' => $this->whereValue,
            'values' => $values,
        ];

        return 1;
    }
}
