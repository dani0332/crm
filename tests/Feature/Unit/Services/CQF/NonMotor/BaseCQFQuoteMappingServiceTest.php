<?php

declare(strict_types=1);

use App\Models\RenewalBatch;
use App\Services\CQF\NonMotor\BaseCQFQuoteMappingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('returns the correct renewal batch id using iso week name', function () {
    $date = Carbon::parse('2024-06-15');

    $batch = RenewalBatch::create([
        'name' => 'W'.$date->isoWeek.'-'.$date->isoWeekYear,
        'start_date' => $date->copy()->startOfWeek(),
        'end_date' => $date->copy()->endOfWeek(),
        'month' => $date->month,
        'year' => $date->year,
        'quote_type_id' => null,
    ]);

    expect(BaseCQFQuoteMappingService::getRenewalBatchIdForDate('2024-06-15'))->toBe($batch->id);
});

it('returns null when no matching batch exists for the date', function () {
    expect(BaseCQFQuoteMappingService::getRenewalBatchIdForDate('2024-07-20'))->toBeNull();
});

it('does not match a motor batch (quote_type_id not null)', function () {
    $date = Carbon::parse('2024-08-10');

    // Insert directly to avoid the RenewalBatchObserver which processes Car-batch pivot tables
    // not present in the minimal test schema.
    DB::table('renewal_batches')->insert([
        'name' => 'W'.$date->isoWeek.'-'.$date->isoWeekYear,
        'start_date' => $date->copy()->startOfWeek(),
        'end_date' => $date->copy()->endOfWeek(),
        'month' => $date->month,
        'year' => $date->year,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(BaseCQFQuoteMappingService::getRenewalBatchIdForDate('2024-08-10'))->toBeNull();
});
