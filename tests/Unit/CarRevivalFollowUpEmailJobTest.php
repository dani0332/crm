<?php

declare(strict_types=1);

use App\Jobs\Revival\CarRevivalFollowUpEmailJob;

it('round-trips through PHP serialization when emailData is null', function () {
    $job = new CarRevivalFollowUpEmailJob(42, null);
    $restored = unserialize(serialize($job));

    expect($restored->dttRevivalId)->toBe(42)
        ->and($restored->emailData)->toBeNull();

    $reflection = new ReflectionProperty($restored, 'emailData');
    expect($reflection->isInitialized($restored))->toBeTrue();
});

it('round-trips through PHP serialization when emailData is set', function () {
    $payload = (object) ['workflowType' => 'x'];
    $job = new CarRevivalFollowUpEmailJob(7, $payload);
    $restored = unserialize(serialize($job));

    expect($restored->dttRevivalId)->toBe(7)
        ->and($restored->emailData)->toEqual($payload);
});
