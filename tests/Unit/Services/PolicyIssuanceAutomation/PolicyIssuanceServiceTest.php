<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    $this->service = new PolicyIssuanceService;
    $reflection = new ReflectionMethod(PolicyIssuanceService::class, 'resolvePolicyIssuanceLogResponse');
    $reflection->setAccessible(true);
    $this->resolve = fn (mixed $payload) => $reflection->invoke($this->service, $payload);
});

afterEach(function () {
    unset($this->service, $this->resolve);
});

beforeEach(function () {
    $this->service = new PolicyIssuanceService;
    $reflection = new ReflectionMethod(PolicyIssuanceService::class, 'resolvePolicyIssuanceLogResponse');
    $reflection->setAccessible(true);
    $this->resolve = fn (mixed $payload) => $reflection->invoke($this->service, $payload);
    TestSchemaCreator::createCyberSchema();
});

afterEach(function () {
    unset($this->service, $this->resolve);
});

it('persists provided insurer and api issuance statuses', function () {
    $quote = PersonalQuote::factory()
        ->cyberQuote()
        ->withCyberDependencies()
        ->create(['quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED]);

    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));
    PolicyIssuance::withoutEvents(fn () => $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]));

    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
        $quote->fresh(),
        QuoteTypes::CYBER->value,
        PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
        PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
    );

    $quote = $quote->fresh();

    expect($quote->insurer_api_status_id)->toBe(PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID)
        ->and($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
});

it('derives api issuance status when automation completes successfully', function () {
    $quote = PersonalQuote::factory()
        ->cyberQuote()
        ->withCyberDependencies()
        ->create(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));
    PolicyIssuance::withoutEvents(fn () => $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]));

    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
        $quote->fresh(),
        QuoteTypes::CYBER->value
    );

    // fresh() avoids reloading factory-stubbed cyberPlanDetail (refresh() would query Mongo).
    $quote = $quote->fresh();

    expect($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID)
        ->and($quote->insurer_api_status_id)->toBeNull();
});

dataset('policyIssuanceLogResponses', function () {
    $httpPayload = ['status' => true, 'message' => 'ok'];
    $response = new Response(new Psr7Response(200, [], json_encode($httpPayload)));

    return [
        'http client response' => [$response, $httpPayload],
        'scalar array' => [['status' => false, 'error' => 'failed'], ['status' => false, 'error' => 'failed']],
        'std class object' => [
            (object) [
                'status' => true,
                'details' => (object) ['code' => 'OK', 'value' => 100],
            ],
            ['status' => true, 'details' => ['code' => 'OK', 'value' => 100]],
        ],
        'object with toArray' => [
            new class
            {
                public function toArray(): array
                {
                    return ['foo' => 'bar'];
                }
            },
            ['foo' => 'bar'],
        ],
        'json serializable object' => [
            new class implements JsonSerializable
            {
                public function jsonSerialize(): array
                {
                    return ['baz' => 'qux'];
                }
            },
            ['baz' => 'qux'],
        ],
    ];
});

it('normalizes policy issuance log responses', function ($payload, $expected) {
    $resolve = $this->resolve;

    expect($resolve($payload))->toBe($expected);
})->with('policyIssuanceLogResponses');

it('resolves automation failure routing for device with book policy using PA user', function () {
    $m = new ReflectionMethod(PolicyIssuanceService::class, 'resolveAutomationFailureRouting');
    $m->setAccessible(true);
    [$quoteTypeId, $workflowType, $recipientUser] = $m->invoke(
        $this->service,
        QuoteTypes::DEVICE->value,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
    );

    expect($quoteTypeId)->toBe(QuoteTypeId::Device)
        ->and($workflowType)->toBe(WorkflowTypeEnum::DEVICE_AUTOMATION_FAILED)
        ->and($recipientUser)->toBe(UserNameEnum::PA_USER);
});

it('resolves automation failure routing for device without book policy with null recipient', function () {
    $m = new ReflectionMethod(PolicyIssuanceService::class, 'resolveAutomationFailureRouting');
    $m->setAccessible(true);
    [$quoteTypeId, $workflowType, $recipientUser] = $m->invoke(
        $this->service,
        QuoteTypes::DEVICE->value,
        'Some other process'
    );

    expect($quoteTypeId)->toBe(QuoteTypeId::Device)
        ->and($workflowType)->toBe(WorkflowTypeEnum::DEVICE_AUTOMATION_FAILED)
        ->and($recipientUser)->toBeNull();
});

it('resolves automation failure routing for cyber with book policy using PA user', function () {
    $m = new ReflectionMethod(PolicyIssuanceService::class, 'resolveAutomationFailureRouting');
    $m->setAccessible(true);
    [$quoteTypeId, $workflowType, $recipientUser] = $m->invoke(
        $this->service,
        QuoteTypes::CYBER->value,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
    );

    expect($quoteTypeId)->toBe(QuoteTypeId::Cyber)
        ->and($workflowType)->toBe(WorkflowTypeEnum::CYBER_AUTOMATION_FAILED)
        ->and($recipientUser)->toBe(UserNameEnum::PA_USER);
});

it('resolves automation failure routing for cyber without book policy with null recipient', function () {
    $m = new ReflectionMethod(PolicyIssuanceService::class, 'resolveAutomationFailureRouting');
    $m->setAccessible(true);
    [$quoteTypeId, $workflowType, $recipientUser] = $m->invoke(
        $this->service,
        QuoteTypes::CYBER->value,
        'capture failed'
    );

    expect($quoteTypeId)->toBe(QuoteTypeId::Cyber)
        ->and($workflowType)->toBe(WorkflowTypeEnum::CYBER_AUTOMATION_FAILED)
        ->and($recipientUser)->toBeNull();
});

it('resolves automation failure routing for car default', function () {
    $m = new ReflectionMethod(PolicyIssuanceService::class, 'resolveAutomationFailureRouting');
    $m->setAccessible(true);
    [$quoteTypeId, $workflowType, $recipientUser] = $m->invoke(
        $this->service,
        QuoteTypes::CAR->value,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
    );

    expect($quoteTypeId)->toBe(QuoteTypeId::Car)
        ->and($workflowType)->toBe(WorkflowTypeEnum::CAR_AUTOMATION_FAILED)
        ->and($recipientUser)->toBe(UserNameEnum::PA_USER);
});
