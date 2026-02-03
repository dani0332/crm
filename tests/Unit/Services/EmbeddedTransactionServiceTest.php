<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\BirdService;
use App\Services\EmailStatusService;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->quoteUuid = 'RETARGET003';
    $this->quoteCode = 'CAR-RETARGET003';
    $this->quoteId = 3;
    $this->embeddedTransactionCode = "MDX-CAR-{$this->quoteUuid}";
});

afterEach(function () {
    Mockery::close();
});

/**
 * Build EmbeddedTransaction-like object as returned by getRetargetingCarEpReminderData repo.
 * Service expects ->quoteRequest (quote), ->product->embeddedProduct->short_code, ->payment_status_id, ->code, ->only().
 *
 * @param  array<string, mixed>  $overrides  Override keys: quote_status_id, payment_status_id, email, uuid, etc. (nested under quoteRequest or top-level)
 */
function getRetargetingCarEpReminderDataMock(array $overrides = []): object
{
    $quoteId = 3;
    $quoteUuid = 'RETARGET003';
    $embeddedTransactionCode = 'MDX-CAR-RETARGET003';
    $quote = Mockery::mock(CarQuote::class)->makePartial();
    $quote->id = $overrides['quote_id'] ?? $quoteId;
    $quote->uuid = $overrides['quote_uuid'] ?? $quoteUuid;
    $quote->quote_status_id = $overrides['quote_status_id'] ?? QuoteStatusEnum::PolicyBooked;
    $quote->policy_booking_date = $overrides['quote_policy_booking_date'] ?? '2025-01-01';
    $quote->customer_id = $overrides['quote_customer_id'] ?? 10;
    $quote->email = $overrides['quote_email'] ?? 'customer@example.com';
    $quote->first_name = $overrides['quote_first_name'] ?? 'John';
    $quote->last_name = $overrides['quote_last_name'] ?? 'Doe';
    $quote->full_name = ($quote->first_name ?? '').' '.($quote->last_name ?? '');
    $quote->carMake = !array_key_exists('vehicle_make', $overrides)
        ? (object) ['text' => 'Toyota']
        : ($overrides['vehicle_make'] === null ? null : (object) ['text' => $overrides['vehicle_make']]);
    $quote->carModel = !array_key_exists('vehicle_model', $overrides)
        ? (object) ['text' => 'Camry']
        : ($overrides['vehicle_model'] === null ? null : (object) ['text' => $overrides['vehicle_model']]);
    $quote->advisor = !array_key_exists('advisor_email', $overrides)
        ? (object) ['email' => 'advisor@example.com']
        : ($overrides['advisor_email'] === null ? null : (object) ['email' => $overrides['advisor_email']]);
    $quote->plan = (array_key_exists('plan_id', $overrides) && $overrides['plan_id'] == null) 
        ? null
        : (object) [
            'id' => $overrides['plan_id'] ?? 5,
            'provider_id' => 1,
            'insuranceProvider' => (object) ['code' => $overrides['plan_provider_code'] ?? 'PROV01'],
        ];

    $quote->shouldReceive('only')->andReturnUsing(function (array $keys) use ($quote) {
        $all = ['id' => $quote->id, 'uuid' => $quote->uuid, 'quote_status_id' => $quote->quote_status_id, 'policy_booking_date' => $quote->policy_booking_date];

        return array_intersect_key($all, array_flip($keys));
    });

    $embeddedTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
    $embeddedTransaction->id = $overrides['et_id'] ?? 1;
    $embeddedTransaction->code = $overrides['et_code'] ?? $embeddedTransactionCode;
    $embeddedTransaction->quote_type_id = 1;
    $embeddedTransaction->quote_request_id = $quoteId;
    $embeddedTransaction->quote_request_type = CarQuote::class;
    $embeddedTransaction->is_selected = false;
    $embeddedTransaction->payment_status_id = $overrides['payment_status_id'] ?? PaymentStatusEnum::DRAFT;
    $embeddedTransaction->product_id = 1;
    $embeddedTransaction->quoteRequest = $quote;
    $embeddedTransaction->product = (object) [
        'embeddedProduct' => (object) ['short_code' => $overrides['ep_short_code'] ?? EmbeddedProductEnum::MDX],
    ];
    $embeddedTransaction->shouldReceive('only')->andReturnUsing(fn (array $keys) => [
        'id' => $embeddedTransaction->id,
        'code' => $embeddedTransaction->code,
        'quote_type_id' => $embeddedTransaction->quote_type_id,
        'quote_request_id' => $embeddedTransaction->quote_request_id,
        'quote_request_type' => $embeddedTransaction->quote_request_type,
        'is_selected' => $embeddedTransaction->is_selected,
        'payment_status_id' => $embeddedTransaction->payment_status_id,
        'product_id' => $embeddedTransaction->product_id,
    ]);

    return $embeddedTransaction;
}

describe('getRetargetingCarEpReminderData', function () {
    test('return 404 when repository returns empty data', function () {
        $this->mock(EmbeddedTransactionRepository::class, function ($mock) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn(null);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Record not found');
        expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
    });

    test('return 404 when car make is missing', function () {
        $mockData = getRetargetingCarEpReminderDataMock(['vehicle_make' => null]);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($mockData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Required data not found');
        expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
    });
    
    test('return 404 when car model is empty string', function () {
        $mockData = getRetargetingCarEpReminderDataMock(['vehicle_model' => '']);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($mockData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Required data not found');
        expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
    });

    test('return 404 when customer email is missing', function () {
        $mockData = getRetargetingCarEpReminderDataMock(['quote_email' => '']);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($mockData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Required data not found');
    });

    test('return 400 when template ID is not found for EP short code', function () {
        $mockData = getRetargetingCarEpReminderDataMock();

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($mockData);
            $mock->shouldReceive('getEpRetargetingReminderEmailTemplateId')
                ->once()
                ->with(EmbeddedProductEnum::MDX)
                ->andReturn(false);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Template / Email Workflow URL not found');
        expect($json['status'])->toBe(Response::HTTP_BAD_REQUEST);
    });

    test('return 400 when template ID is empty string', function () {
        $mockData = getRetargetingCarEpReminderDataMock();

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($mockData);
            $mock->shouldReceive('getEpRetargetingReminderEmailTemplateId')
                ->once()
                ->with(EmbeddedProductEnum::MDX)
                ->andReturn('');
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Template / Email Workflow URL not found');
    });
});

describe('retargetingCarEpReminderCallback', function () {
    test('return 404 when EmailStatusService reports lead not found', function () {
        $request = RetargetingEpReminderCallbackRequest::create('/api/retargeting-ep-reminder-callback', 'POST', [
            'uuid' => 'unknown-uuid',
            'quoteTypeId' => 1,
            'quoteId' => 999,
            'templateId' => 'tpl-1',
            'message_id' => 'msg-1',
            'customerId' => 1,
            'customer_email' => 'user@example.com',
            'subject' => 'Reminder',
        ]);
        $request->setContainer(app());

        $this->mock(EmailStatusService::class, function ($mock) {
            $mock->shouldReceive('addBirdEmailStatus')
                ->once()
                ->andReturn((object) ['message' => 'lead not found', 'status' => false]);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->retargetingCarEpReminderCallback($request);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('lead not found');
    });

    test('return 200 when EmailStatusService adds email status successfully', function () {
        $request = RetargetingEpReminderCallbackRequest::create('/api/retargeting-ep-reminder-callback', 'POST', [
            'uuid' => $this->quoteUuid,
            'quoteTypeId' => QuoteTypeId::Car,
            'quoteId' => $this->quoteId,
            'templateId' => 'templateId-1',
            'message_id' => 'msg-1',
            'customerId' => 1,
            'customer_email' => 'user@example.com',
            'subject' => 'Reminder',
        ]);
        $request->setContainer(app());

        $this->mock(EmailStatusService::class, function ($mock) {
            $mock->shouldReceive('addBirdEmailStatus')
                ->once()
                ->andReturn((object) ['message' => 'Email event logged successfully', 'status' => true]);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->retargetingCarEpReminderCallback($request);

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Email event logged successfully');
    });
});

describe('retargetEpReminder', function () {
    test('return empty array when no draft EP transactions exist', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([]));

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(BirdService::class), app(EmailStatusService::class));
        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);
        expect($result)->toBeArray();
        expect($result)->toHaveCount(0);
    });

    test('return response array and triggers Bird workflow when draft EP transactions exist', function () {
        $epTransaction1 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction1->code = 'MDX-'.$this->quoteCode;
        $epTransaction2 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction2->code = 'ECB-'.$this->quoteCode;
        $epTransactions = collect([$epTransaction1, $epTransaction2]);

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn($epTransactions);

        $workflowUrl = 'https://test-bird.example/ep-reminder/invoke-sync';
        $this->mock(BirdService::class, function ($mock) use ($workflowUrl) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->twice()
                ->with($workflowUrl, Mockery::type('object'))
                ->andReturn((object) ['status_code' => Response::HTTP_OK, 'message' => 'OK']);
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn($workflowUrl);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toMatchArray([
            'embeddedTransactionCode' => 'MDX-'.$this->quoteCode,
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
        ]);
        expect($result[1])->toMatchArray([
            'embeddedTransactionCode' => 'ECB-'.$this->quoteCode,
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
        ]);
    });

    test('returns first success, second failure entry & and skip third transaction when BirdService throws on second transaction', function () {
        $epTransaction1 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction1->code = 'MDX-'.$this->quoteCode;
        $epTransaction2 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction2->code = 'ECB-'.$this->quoteCode;
        $epTransaction3 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction3->code = 'COU-'.$this->quoteCode;
        $epTransactions = collect([$epTransaction1, $epTransaction2, $epTransaction3]);

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn($epTransactions);

        $workflowUrl = 'https://test-bird.example/ep-reminder/invoke-sync';
        $callCount = 0;
        $this->mock(BirdService::class, function ($mock) use ($workflowUrl, &$callCount) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->twice()
                ->with($workflowUrl, Mockery::type('object'))
                ->andReturnUsing(function () use (&$callCount) {
                    $callCount++;
                    if ($callCount === 2) {
                        throw new \RuntimeException('Bird API connection failed');
                    }

                    return (object) ['status_code' => Response::HTTP_OK, 'message' => 'OK'];
                });
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn($workflowUrl);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result[0])->toMatchArray([
            'embeddedTransactionCode' => 'MDX-'.$this->quoteCode,
            'status_code' => Response::HTTP_OK,
            'message' => 'OK',
        ]);
        expect($result[1])->toMatchArray([
            'embeddedTransactionCode' => 'ECB-'.$this->quoteCode,
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Bird API connection failed',
        ]);
    });
});

describe('triggerBirdWorkflowRetargetEpReminder (via retargetEpReminder)', function () {
    test('when workflow URL is empty, response contains 404 and workflow URL not found message', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = 'MDX-'.$this->quoteCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([$epTransaction]));

        $this->mock(BirdService::class, function ($mock) {
            $mock->shouldNotReceive('triggerWebHookRequest');
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn('');

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toMatchArray([
            'embeddedTransactionCode' => 'MDX-'.$this->quoteCode,
            'status_code' => Response::HTTP_NOT_FOUND,
            'message' => 'Bird EP Reminder Workflow URL not found',
        ]);
    });

    test('when BirdService returns error status, response contains that status and body', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = 'MDX-'.$this->quoteCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([$epTransaction]));

        $workflowUrl = 'https://api.bird.com/workspaces/invoke-sync';
        $this->mock(BirdService::class, function ($mock) use ($workflowUrl) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($workflowUrl, Mockery::type('object'))
                ->andReturn((object) ['status_code' => Response::HTTP_NOT_FOUND, 'body' => `{"code":"NotFound","message":"The resource doesn't exist or you don't have access to it."}`]);
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn($workflowUrl);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toMatchArray([
            'embeddedTransactionCode' => 'MDX-'.$this->quoteCode,
            'status_code' => Response::HTTP_NOT_FOUND,
        ]);
    });

    test('passes correct birdEmailData to BirdService triggerWebHookRequest', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = $this->embeddedTransactionCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([$epTransaction]));

        $workflowUrl = 'https://test-bird.example/invoke-sync';
        $capturedData = null;
        $this->mock(BirdService::class, function ($mock) use ($workflowUrl, &$capturedData) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($workflowUrl, Mockery::on(function ($data) use (&$capturedData) {
                    $capturedData = $data;

                    return true;
                }))
                ->andReturn((object) ['status_code' => Response::HTTP_OK, 'message' => 'OK']);
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn($workflowUrl);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($capturedData)->not->toBeNull();
        expect($capturedData->quoteId)->toBe($this->quoteId);
        expect($capturedData->quoteTypeId)->toBe(QuoteTypeId::Car);
        expect($capturedData->refId)->toBe($this->quoteCode);
        expect($capturedData->embeddedTransactionCode)->toBe($this->embeddedTransactionCode);
        expect($capturedData->workflowType)->toBe(WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('quoteId='.$this->quoteId);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('quoteTypeId='.QuoteTypeId::Car);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('embeddedTransactionCode='.$this->embeddedTransactionCode);
    });
});

describe('triggerBirdWorkflowRetargetEpReminder (direct via Reflection)', function () {
    /**
     * Invoke private triggerBirdWorkflowRetargetEpReminder on the service instance using Reflection.
     */
    function invokeTriggerBirdWorkflowRetargetEpReminder(object $service, $quote, int $quoteTypeId, object $epTransaction)
    {
        $ref = new ReflectionClass(EmbeddedTransactionService::class);
        $method = $ref->getMethod('triggerBirdWorkflowRetargetEpReminder');
        $method->setAccessible(true);

        return $method->invoke($service, $quote, $quoteTypeId, $epTransaction);
    }

    test('when workflow URL is empty, response contains 404 and workflow URL not found message', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = $this->embeddedTransactionCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn('');

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = invokeTriggerBirdWorkflowRetargetEpReminder($service, $quote, QuoteTypeId::Car, $epTransaction);

        expect($result)->toBeObject();
        expect($result->status_code)->toBe(Response::HTTP_NOT_FOUND);
        expect($result->message)->toBe('Bird EP Reminder Workflow URL not found');
    });

    test('when call BirdService and return its result, response contains 200', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = $this->embeddedTransactionCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $workflowUrl = 'https://test-bird.example/invoke-sync';
        $this->mock(BirdService::class, function ($mock) use ($workflowUrl) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($workflowUrl, Mockery::type('object'))
                ->andReturn((object) ['status_code' => Response::HTTP_OK]);
        });

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock, app(BirdService::class), app(EmailStatusService::class)])->makePartial();
        $service->shouldReceive('getAppStorageValueByKey')
            ->with(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL)
            ->andReturn($workflowUrl);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = invokeTriggerBirdWorkflowRetargetEpReminder($service, $quote, QuoteTypeId::Car, $epTransaction);

        expect($result)->toBeObject();
        expect($result->status_code)->toBe(Response::HTTP_OK);
    });
});

describe('isRetargetingEpReminderEnabled', function () {
    test('return a boolean', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(BirdService::class), app(EmailStatusService::class));

        $result = $service->isRetargetingEpReminderEnabled();

        expect($result)->toBeBool();
    });
});

/**
 * Test double that skips BaseService constructor (getGenericModel) so unit tests
 * can mock only EmbeddedTransactionRepository.
 */
class EmbeddedTransactionServiceTestDouble extends EmbeddedTransactionService
{
    public function __construct(
        EmbeddedTransactionRepository $repo,
        BirdService $birdService,
        EmailStatusService $emailStatusService
    ) {
        $this->embeddedTransactionRepo = $repo;
        $this->birdService = $birdService;
        $this->emailStatusService = $emailStatusService;
    }
}
