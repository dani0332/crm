<?php

declare(strict_types=1);

use App\Jobs\Health\ProcessHealthSicWorkflowJob;
use App\Models\HealthQuote;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    Queue::fake();
});

test('dispatches ProcessHealthSicWorkflowJob when pq_advisor_id is added', function () {
    $advisor = User::factory()->create();
    $quote = HealthQuote::factory()->create(['pq_advisor_id' => null]);

    $quote->update(['pq_advisor_id' => $advisor->id]);

    Queue::assertPushed(ProcessHealthSicWorkflowJob::class, function ($job) use ($quote) {
        return $job->healthQuote->is($quote);
    });
});

test('does not dispatch ProcessHealthSicWorkflowJob when pq_advisor_id is reassigned', function () {
    $firstAdvisor = User::factory()->create();
    $secondAdvisor = User::factory()->create();
    $quote = HealthQuote::factory()->create(['pq_advisor_id' => $firstAdvisor->id]);

    $quote->update(['pq_advisor_id' => $secondAdvisor->id]);

    Queue::assertNotPushed(ProcessHealthSicWorkflowJob::class);
});

test('does not dispatch ProcessHealthSicWorkflowJob when unrelated fields change', function () {
    $quote = HealthQuote::factory()->create(['pq_advisor_id' => null]);

    $quote->update(['first_name' => 'Updated']);

    Queue::assertNotPushed(ProcessHealthSicWorkflowJob::class);
});
