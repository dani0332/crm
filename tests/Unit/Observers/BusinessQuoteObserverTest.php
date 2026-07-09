<?php

declare(strict_types=1);

use App\Jobs\SendGroupHealthPqaIntroEmailJob;
use App\Models\BusinessQuote;
use App\Models\User;
use App\Services\EmailActivityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    config([
        'constants.SIB_URL' => 'https://brevo.test/v3/smtp/email',
        'constants.ECOM_GROUP_MEDICAL_INSURANCE_QUOTE_URL' => 'https://im.example.com/group-health-insurance/quote',
    ]);

    Queue::fake();
});

test('dispatches SendGroupHealthPqaIntroEmailJob with a 30 second delay when pq_advisor_id is added to a group medical lead', function () {
    $advisor = User::factory()->create(['name' => 'PQA Advisor', 'email' => 'pqa@example.com']);
    $quote = BusinessQuote::factory()->groupMedical()->create(['pq_advisor_id' => null]);

    $quote->update(['pq_advisor_id' => $advisor->id]);

    Queue::assertPushed(SendGroupHealthPqaIntroEmailJob::class, function ($job) use ($quote) {
        return $job->businessQuote->is($quote) && $job->delay !== null;
    });
});

test('does not dispatch SendGroupHealthPqaIntroEmailJob for corpline leads', function () {
    $advisor = User::factory()->create();
    $quote = BusinessQuote::factory()->corpline()->create(['pq_advisor_id' => null]);

    $quote->update(['pq_advisor_id' => $advisor->id]);

    Queue::assertNotPushed(SendGroupHealthPqaIntroEmailJob::class);
});

test('does not dispatch SendGroupHealthPqaIntroEmailJob on reassignment', function () {
    $firstAdvisor = User::factory()->create();
    $secondAdvisor = User::factory()->create();
    $quote = BusinessQuote::factory()->groupMedical()->create(['pq_advisor_id' => $firstAdvisor->id]);

    $quote->update(['pq_advisor_id' => $secondAdvisor->id]);

    Queue::assertNotPushed(SendGroupHealthPqaIntroEmailJob::class);
});

test('job handle sends group health pqa intro email to brevo', function () {
    $advisor = User::factory()->create(['name' => 'PQA Advisor', 'email' => 'pqa@example.com']);
    $quote = BusinessQuote::factory()->groupMedical()->create(['pq_advisor_id' => $advisor->id]);

    Http::fake(['brevo.test/*' => Http::response(null, 201)]);
    $this->mock(EmailActivityService::class, function ($mock) {
        $mock->shouldReceive('addEmailActivity')->once();
    });

    (new SendGroupHealthPqaIntroEmailJob($quote))->handle();

    Http::assertSent(function ($request) use ($quote, $advisor) {
        $body = $request->data();

        return $body['to'][0]['email'] === $quote->email
            && $body['templateId'] === 906
            && $body['params']['advisorEmail'] === $advisor->email
            && str_contains($body['params']['resumeApplicationUrl'], $quote->uuid);
    });
});
