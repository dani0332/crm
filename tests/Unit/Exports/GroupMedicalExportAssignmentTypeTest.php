<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;
use App\Exports\GroupMedicalExport;
use App\Models\BusinessQuote;
use App\Services\BusinessQuoteService;

it('populates assignment type text in group medical export', function () {
    $export = new GroupMedicalExport(Mockery::mock(BusinessQuoteService::class));

    $quote = new BusinessQuote;
    $quote->assignment_type = AssignmentTypeEnum::SYSTEM_ASSIGNED;

    $row = $export->map($quote);

    // Headings:
    // 0 REF-ID
    // ...
    // 7 BRANCH
    // 8 ASSIGNMENT TYPE
    expect($row[8])->toBe('System Assigned');
});
