<?php

declare(strict_types=1);

use App\Exports\GroupMedicalExport;

it('includes advisor assigned date column after created date in group medical export', function () {
    $export = new GroupMedicalExport;
    $headings = $export->headings();

    expect($headings)->toContain('ADVISOR ASSIGNED DATE');

    $createdIdx = array_search('CREATED DATE', $headings, true);
    $assignedIdx = array_search('ADVISOR ASSIGNED DATE', $headings, true);

    expect($createdIdx)->not->toBeFalse()
        ->and($assignedIdx)->toBe($createdIdx + 1);
});
