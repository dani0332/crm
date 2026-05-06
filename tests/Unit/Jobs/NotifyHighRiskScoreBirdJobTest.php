<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Jobs\NotifyHighRiskScoreBirdJob;
use App\Services\BirdService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    DB::table('application_storage')->delete();
});

test('invokes bird webhook when workflow url is stored', function () {
    DB::table('application_storage')->insert([
        'key_name' => ApplicationStorageEnums::BIRD_HIGH_RISK_AML_SCORE_NOTIFICATION_WORKFLOW_URL,
        'value' => 'https://bird.example/flow',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payload = ['uuid' => 'HEA-1', 'risk_score' => 40];

    $bird = $this->mock(BirdService::class, function ($mock) use ($payload) {
        $mock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->with('https://bird.example/flow', $payload)
            ->andReturn((object) ['status_code' => 200, 'body' => '', 'headers' => []]);
    });

    $job = new NotifyHighRiskScoreBirdJob($payload);
    $job->handle($bird);
});

test('skips bird when workflow url missing', function () {
    $bird = $this->mock(BirdService::class, function ($mock) {
        $mock->shouldNotReceive('triggerWebHookRequest');
    });

    $job = new NotifyHighRiskScoreBirdJob(['uuid' => 'x', 'risk_score' => 40]);
    $job->handle($bird);
});
