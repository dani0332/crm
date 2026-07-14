<?php

use App\Exports\HealthQuotesExport;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use App\Services\LookupService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

it('includes unassigned and age sixty columns in export and maps values correctly', function () {
    $healthQuoteService = Mockery::mock(HealthQuoteService::class);
    $lookupService = Mockery::mock(LookupService::class);
    $lookupService->shouldReceive('getHealthGenderDisplayMap')->andReturn([]);
    $healthQuoteService->shouldReceive('isPQAQualified')->andReturn(0);

    $export = new HealthQuotesExport($healthQuoteService, $lookupService);
    $headings = $export->headings();

    expect($headings)->toContain('UNASSIGNED')
        ->and($headings)->toContain('IS AGE 60 AND ABOVE');

    $quote = new HealthQuote;
    $quote->setRawAttributes([
        'id' => 1,
        'code' => 'H-001',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'advisor_id' => null,
        'is_branch_applicable' => 0,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
        'price_starting_from' => 1000,
        'premium' => 1200,
        'dob' => Carbon::now()->subYears(30)->toDateString(),
        'health_plan_type_id' => 3,
    ], true);
    $quote->setRelation('quoteStatus', (object) ['text' => 'Quoted']);
    $quote->setRelation('activeMembers', new Collection([
        (object) ['dob' => Carbon::now()->subYears(61)->toDateString()],
    ]));

    $row = $export->map($quote);
    $unassignedIndex = array_search('UNASSIGNED', $headings, true);
    $ageSixtyIndex = array_search('IS AGE 60 AND ABOVE', $headings, true);

    expect($row[$unassignedIndex])->toBe('Yes')
        ->and($row[$ageSixtyIndex])->toBe('Yes');
});
