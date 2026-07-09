<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;
use App\Exports\GroupMedicalExport;
use App\Models\BusinessQuote;
use App\Services\BusinessQuoteService;

it('populates assignment type text in group medical export', function () {
    $businessQuoteService = Mockery::mock(BusinessQuoteService::class);
    $businessQuoteService->shouldReceive('isPQAQualified')->andReturn(0);

    $export = new GroupMedicalExport($businessQuoteService);

    $quote = new BusinessQuote;
    $quote->id = 1;
    $quote->assignment_type = AssignmentTypeEnum::SYSTEM_ASSIGNED;

    $headings = $export->headings();
    $row = $export->map($quote);

    $assignmentTypeIdx = array_search('ASSIGNMENT TYPE', $headings, true);

    expect($row[$assignmentTypeIdx])->toBe('System Assigned');
});
