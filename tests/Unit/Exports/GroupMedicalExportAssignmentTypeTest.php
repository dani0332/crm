<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;
use App\Exports\GroupMedicalExport;
use App\Models\BusinessQuote;

it('populates assignment type text in group medical export', function () {
    $export = new GroupMedicalExport;

    $quote = new BusinessQuote;
    $quote->assignment_type = AssignmentTypeEnum::SYSTEM_ASSIGNED;

    $row = $export->map($quote);

    // Headings:
    // 0 REF-ID
    // ...
    // 7 ASSIGNMENT TYPE
    expect($row[7])->toBe('System Assigned');
});
