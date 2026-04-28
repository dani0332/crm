<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Jobs\DicPolicyIssuanceStepJob;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('sets unique lock window from application storage retry delay', function (): void {
    DB::table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::DIC_TRAVEL_ASYNC_RETRY_DELAY_SECONDS,
        'value' => '100',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    $job = new DicPolicyIssuanceStepJob(1, 'IssuePolicy');

    expect($job->uniqueFor)->toBe(130);
});

it('defaults unique lock window when retry delay storage is missing', function (): void {
    $job = new DicPolicyIssuanceStepJob(1, 'IssuePolicy');

    expect($job->uniqueFor)->toBe(120);
});
