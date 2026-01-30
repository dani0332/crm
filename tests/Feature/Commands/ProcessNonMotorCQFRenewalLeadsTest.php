<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('registers command leads:process-non-motor-cqf-renewals', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('leads:process-non-motor-cqf-renewals')
        ->and($commands['leads:process-non-motor-cqf-renewals']->getName())->toBe('leads:process-non-motor-cqf-renewals');
});
