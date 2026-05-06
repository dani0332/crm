<?php

use App\Exports\HealthQuotesExport;
use App\Models\HealthQuote;
use App\Services\CRUDService;
use App\Services\HealthQuoteService;
use Carbon\Carbon;

it('includes unassigned column and maps unassigned as yes when advisor is missing', function () {
    $healthQuoteService = Mockery::mock(HealthQuoteService::class);
    $crudService = Mockery::mock(CRUDService::class);
    $crudService->shouldReceive('getGenderOptions')->andReturn([]);

    $export = new HealthQuotesExport($healthQuoteService, $crudService);
    $headings = $export->headings();

    expect($headings)->toContain('UNASSIGNED');

    $quote = new HealthQuote;
    $quote->setRawAttributes([
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

    $row = $export->map($quote);

    expect($row[7])->toBe('Yes');
});
