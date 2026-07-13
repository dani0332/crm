<?php

declare(strict_types=1);

use App\Exports\GroupMedicalExport;
use App\Models\BusinessQuote;
use App\Services\BusinessQuoteService;
use App\Services\HealthPlanTypeService;

it('populates plan type and number of people to be insured in group medical export', function () {
    $this->mock(HealthPlanTypeService::class, function ($mock) {
        $mock->shouldReceive('getById')->once()->with(5)->andReturn('Group PPO Plan');
    });
    $this->mock(BusinessQuoteService::class, function ($mock) {
        $mock->shouldReceive('isPQAQualified')->once()->andReturn(0);
    });

    $export = app(GroupMedicalExport::class);

    $quote = new BusinessQuote;
    $quote->id = 1;
    $quote->health_plan_type_id = 5;
    $quote->number_of_employees = 42;

    $row = $export->map($quote);
    $headings = $export->headings();

    $planTypeIdx = array_search('PLAN TYPE', $headings, true);
    $peopleInsuredIdx = array_search('NO OF PEOPLE TO BE INSURED', $headings, true);

    expect($row[$planTypeIdx])->toBe('Group PPO Plan')
        ->and($row[$peopleInsuredIdx])->toBe(42);
});

it('returns empty plan type when health plan type id is missing', function () {
    $this->mock(BusinessQuoteService::class, function ($mock) {
        $mock->shouldReceive('isPQAQualified')->once()->andReturn(0);
    });

    $export = app(GroupMedicalExport::class);

    $quote = new BusinessQuote;
    $quote->id = 1;
    $quote->number_of_employees = null;

    $row = $export->map($quote);
    $headings = $export->headings();

    $planTypeIdx = array_search('PLAN TYPE', $headings, true);

    expect($row[$planTypeIdx])->toBe('');
});
