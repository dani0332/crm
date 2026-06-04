<?php

declare(strict_types=1);

use App\Jobs\SendBookPolicyDocumentsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

test('SendBookPolicyDocumentsJob is queueable and accepts payload and quote code', function () {
    $payload = (object) [
        'model_type' => 'car',
        'quote_id' => 1,
    ];

    $job = new SendBookPolicyDocumentsJob($payload, 'CODE');

    expect($job)->toBeInstanceOf(ShouldQueue::class);
});
