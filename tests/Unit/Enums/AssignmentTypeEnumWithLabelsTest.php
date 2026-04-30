<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;

it('includes All and numeric assignment type options for lead list filters', function () {
    $labels = AssignmentTypeEnum::withLabels();

    expect($labels[0])->toMatchArray(['value' => 'all', 'label' => 'All']);
    expect(collect($labels)->pluck('value')->all())->toContain('1', '3', '7');
    expect(collect($labels)->pluck('label')->all())->toContain('System Assigned', 'Self Assigned');
});
