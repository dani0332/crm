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
use Tests\Helpers\RetargetingEpReminderTestDataHelper;

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
 * Build raw quote data as returned by EmbeddedTransactionRepository::getRetargetingCarEpReminderData
 * (dot-notation keys, e.g. quote.id, embeddedProduct.short_code).
 *
 * @param  array<string, mixed>  $overrides  Keys to override (e.g. 'quote.quote_status_id' => 1)
 */
function embeddedTransactionServiceRawQuoteData(array $overrides = []): object
{
    $defaults = [
        'quote.id' => 3,
        'quote.uuid' => 'RETARGET003',
        'quote.quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'quote.policy_booking_date' => '2025-01-01',
        'quote.customer_id' => 10,
        'quote.email' => 'customer@example.com',
        'quote.first_name' => 'John',
        'quote.last_name' => 'Doe',
        'embeddedProduct.short_code' => EmbeddedProductEnum::MDX,
        'embeddedTransaction.code' => 'MDX-CAR-RETARGET003',
        'embeddedTransaction.is_selected' => false,
        'embeddedTransaction.payment_status_id' => PaymentStatusEnum::DRAFT,
        'embeddedTransaction.product_id' => 1,
        'vehicle.make' => 'Toyota',
        'vehicle.model' => 'Camry',
        'advisor.id' => 2,
        'advisor.email' => 'advisor@example.com',
        'plan.id' => 5,
        'plan.provider_code' => 'PROV01',
    ];

    return (object) array_merge($defaults, $overrides);
}

describe('getRetargetingCarEpReminderData', function () {
    test('return 404 when repository returns empty quote data', function () {
        $this->mock(EmbeddedTransactionRepository::class, function ($mock) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with(1, 'et-code-123')
                ->andReturn(null);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData(1, 'et-code-123');

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Quote not found');
        expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
    });
    
    test('return 404 when required data is missing', function () {
        $rawData = embeddedTransactionServiceRawQuoteData([
            'quote.uuid' => null,
        ]);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Required data not found');
        expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
    });

    test('return 404 when customer email is missing', function () {
        $rawData = embeddedTransactionServiceRawQuoteData([
            'quote.email' => '',
        ]);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Required data not found');
    });

    test('return 400 when quote is not booked', function () {
        $rawData = embeddedTransactionServiceRawQuoteData([
            'quote.quote_status_id' => QuoteStatusEnum::Quoted,
        ]);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Quote is not booked');
        expect($json['status'])->toBe(Response::HTTP_BAD_REQUEST);
    });

    test('return 400 when embedded transaction payment status is not draft', function () {
        $rawData = embeddedTransactionServiceRawQuoteData([
            'embeddedTransaction.payment_status_id' => PaymentStatusEnum::AUTHORISED,
        ]);

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
        });

        $service = app(EmbeddedTransactionService::class);
        $response = $service->getRetargetingCarEpReminderData($this->quoteId, $this->embeddedTransactionCode);

        expect($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);
        $json = $response->getData(true);
        expect($json['message'])->toBe('Embedded transaction payment status is not draft');
        expect($json['status'])->toBe(Response::HTTP_BAD_REQUEST);
    });

    test('return 400 when template ID is not found for EP short code', function () {
        $rawData = embeddedTransactionServiceRawQuoteData();

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
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
        $rawData = embeddedTransactionServiceRawQuoteData();

        $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($rawData) {
            $mock->shouldReceive('getRetargetingCarEpReminderData')
                ->once()
                ->with($this->quoteId, $this->embeddedTransactionCode)
                ->andReturn($rawData);
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
    test('return false when retargeting is not enabled', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldNotReceive('getDraftEpTransactions');

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(false);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeFalse();
    });

    test('return false when no draft EP transactions exist', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('getDraftEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([]));

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(true);

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($result)->toBeFalse();
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

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(true);
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

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(true);
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

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(true);
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
            'status_code' => Response::HTTP_NOT_FOUND
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

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
        $service->shouldReceive('isRetargetingEpReminderEnabled')->once()->andReturn(true);
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
        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
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

        $service = Mockery::mock(EmbeddedTransactionServiceTestDouble::class, [$repoMock])->makePartial();
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
        $service = new EmbeddedTransactionServiceTestDouble($repoMock);

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
    public function __construct(EmbeddedTransactionRepository $repo)
    {
        $this->embeddedTransactionRepo = $repo;
    }
}
