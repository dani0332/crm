<?php

declare(strict_types=1);

use App\Exports\GroupMedicalExport;

it('includes advisor assigned date column after assignment type in group medical export', function () {
    $export = new GroupMedicalExport;
    $headings = $export->headings();

    expect($headings)->toContain('ADVISOR ASSIGNED DATE')
        ->and($headings)->toContain('CREATED DATE');

    $assignmentTypeIdx = array_search('ASSIGNMENT TYPE', $headings, true);
    $assignedIdx = array_search('ADVISOR ASSIGNED DATE', $headings, true);

    expect($assignmentTypeIdx)->not->toBeFalse()
        ->and($assignedIdx)->toBe($assignmentTypeIdx + 1);
});
