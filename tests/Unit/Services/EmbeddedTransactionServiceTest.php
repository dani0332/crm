<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarVehicleUse;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\BirdService;
use App\Services\EmbeddedTransactionService;
use App\Services\SendEmailCustomerService;
use Illuminate\Http\Response;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->quoteUuid = 'RETARGET003';
    $this->quoteCode = 'CAR-RETARGET003';
    $this->quoteId = 3;
    $this->quoteTypeId = QuoteTypeId::Car;
    $this->embeddedTransactionCode = 'MDX-CAR-RETARGET003';
    $this->dummyBirdEpWorkflowUrl = 'https://test-bird.example/ep-reminder/invoke-sync';
    ApplicationStorage::updateOrInsert(
        ['key_name' => ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL],
        ['value' => $this->dummyBirdEpWorkflowUrl, 'created_at' => now(), 'updated_at' => now()]
    );
});

afterEach(function () {
    Mockery::close();
});

describe('getEpRetargetingReminderData', function () {
    describe('return 404', function () {
        test('when repository returns empty data', function () {
            $this->mock(EmbeddedTransactionRepository::class, function ($mock) {
                $mock->shouldReceive('fetchFindEmbededTransactionWithDetails')
                    ->once()
                    ->with(
                        $this->quoteId,
                        $this->quoteTypeId,
                        $this->embeddedTransactionCode,
                        null,
                        true,
                        PaymentStatusEnum::DRAFT,
                        QuoteStatusEnum::PolicyBooked
                    )
                    ->andReturn(null);
            });

            $service = app(EmbeddedTransactionService::class);
            $response = $service->getEpRetargetingReminderData($this->quoteId, $this->quoteTypeId, $this->embeddedTransactionCode);

            expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
            $json = $response->getData(true);
            expect($json['message'])->toBe('Record not found');
            expect($json['status'])->toBe(Response::HTTP_NOT_FOUND);
        });

        test('when required data is missing returns 400 not eligible for reminder', function (string $propertyKey, array $overrides) {
            $mockData = RetargetingEpReminderTestDataHelper::getEpRetargetingReminderDataMock($overrides);

            $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
                $mock->shouldReceive('fetchFindEmbededTransactionWithDetails')
                    ->once()
                    ->with(
                        $this->quoteId,
                        $this->quoteTypeId,
                        $this->embeddedTransactionCode,
                        null,
                        true,
                        PaymentStatusEnum::DRAFT,
                        QuoteStatusEnum::PolicyBooked
                    )
                    ->andReturn($mockData);
            });

            $service = app(EmbeddedTransactionService::class);
            $response = $service->getEpRetargetingReminderData($this->quoteId, $this->quoteTypeId, $this->embeddedTransactionCode);

            expect($response->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);
            $json = $response->getData(true);
            expect($json['status'])->toBe(Response::HTTP_BAD_REQUEST);
            expect($json['message'])->toBe('Not eligible for reminder');
        })->with([
            ['quote_email', ['quote_email' => '']],
            ['plan_id', ['plan_id' => null]],
            ['plan_insurance_provider', ['plan_insurance_provider' => null]],
            ['plan_provider_code', ['plan_provider_code' => '']],
            ['ep_short_code null (missing product or short_code)', ['ep_short_code' => null]],
            ['ep_short_code empty string', ['ep_short_code' => '']],
            ['ep_short_code not in allowed list', ['ep_short_code' => EmbeddedProductEnum::COURIER]],
            ['ep disabled when payment is AUTHORISED', ['payment_status_id' => PaymentStatusEnum::AUTHORISED]],
            ['ep disabled when ECB and policy_booking_date over 30 days', [
                'ep_short_code' => EmbeddedProductEnum::ECB,
                'quote_policy_booking_date' => now()->subDays(31)->toDateString(),
                'vehicle_make_code' => 1,
                'vehicle_model_code' => 1,
                'plan_repair_type' => 'COMP',
            ]],
            ['ep disabled when ECB and vehicle_use is COMMERCIAL', [
                'ep_short_code' => EmbeddedProductEnum::ECB,
                'vehicle_use' => CarVehicleUse::COMMERCIAL,
                'vehicle_make_code' => 1,
                'vehicle_model_code' => 1,
                'plan_repair_type' => 'COMP',
            ]],
            ['ep disabled when ECB and is_modified is true', [
                'ep_short_code' => EmbeddedProductEnum::ECB,
                'is_modified' => true,
                'vehicle_make_code' => 1,
                'vehicle_model_code' => 1,
                'plan_repair_type' => 'COMP',
            ]],
        ]);
    });

    describe('return 200', function () {
        test('successfully returns data', function () {
            $mockData = RetargetingEpReminderTestDataHelper::getEpRetargetingReminderDataMock();
            $quote = $mockData->quoteRequest;

            $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
                $mock->shouldReceive('fetchFindEmbededTransactionWithDetails')
                    ->once()
                    ->with(
                        $this->quoteId,
                        $this->quoteTypeId,
                        $this->embeddedTransactionCode,
                        null,
                        true,
                        PaymentStatusEnum::DRAFT,
                        QuoteStatusEnum::PolicyBooked
                    )
                    ->andReturn($mockData);
            });

            $service = app(EmbeddedTransactionService::class);
            $response = $service->getEpRetargetingReminderData($this->quoteId, $this->quoteTypeId, $this->embeddedTransactionCode);

            expect($response->getStatusCode())->toBe(Response::HTTP_OK);
            $json = $response->getData(true);
            expect($json['message'])->toBe('Retargeting EP Reminder data');
            expect($json['data']['quote']['id'])->toBe($this->quoteId);
            expect($json['data']['quote']['uuid'])->toBe($this->quoteUuid);
            expect($json['data']['embeddedTransaction']['code'])->toBe($this->embeddedTransactionCode);
            expect($json['data']['emailWorkflowData']['customerEmail'])->toBe($quote->email);
            expect($json['data']['emailWorkflowData']['customerName'])->toBe($quote->full_name);
            expect($json['data']['emailWorkflowData']['advisorEmail'])->toBe($quote->advisor?->email);
            expect($json['data']['emailWorkflowData']['buyNowUrl'])->toContain($this->quoteUuid)
                ->and($json['data']['emailWorkflowData']['buyNowUrl'])->toContain('/payment/')
                ->and($json['data']['emailWorkflowData']['buyNowUrl'])->toContain('selectEpShortCode='.EmbeddedProductEnum::MDX);
            expect($json['data']['emailWorkflowData']['epShortCode'])->toBe(EmbeddedProductEnum::MDX);
            expect($json['data']['emailWorkflowData']['vehicleMake'])->toBe($quote->carMake?->text);
            expect($json['data']['emailWorkflowData']['vehicleModel'])->toBe($quote->carModel?->text);
        });

        test('success with missing any optional field', function (array $overrides, ?string $expectedAdvisorEmail) {
            $mockData = RetargetingEpReminderTestDataHelper::getEpRetargetingReminderDataMock($overrides);

            $this->mock(EmbeddedTransactionRepository::class, function ($mock) use ($mockData) {
                $mock->shouldReceive('fetchFindEmbededTransactionWithDetails')
                    ->once()
                    ->with(
                        $this->quoteId,
                        $this->quoteTypeId,
                        $this->embeddedTransactionCode,
                        null,
                        true,
                        PaymentStatusEnum::DRAFT,
                        QuoteStatusEnum::PolicyBooked
                    )
                    ->andReturn($mockData);
            });

            $service = app(EmbeddedTransactionService::class);
            $response = $service->getEpRetargetingReminderData($this->quoteId, $this->quoteTypeId, $this->embeddedTransactionCode);

            expect($response->getStatusCode())->toBe(Response::HTTP_OK);
            $json = $response->getData(true);
            expect($json['data']['emailWorkflowData']['advisorEmail'])->toBe($expectedAdvisorEmail);
            $buyNowUrl = $json['data']['emailWorkflowData']['buyNowUrl'];
            expect($buyNowUrl)->toContain('planId')
                ->and($buyNowUrl)->toContain('providerCode');
        })->with([
            'advisor email missing' => [['advisor_email' => null], null],
            'advisor email empty' => [['advisor_email' => ''], ''],
        ]);
    });
});

describe('retargetEpReminder', function () {
    describe('reminder not triggered', function () {
        test('return empty array due to reminderable et not exists', function () {
            $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
            $repoMock->shouldReceive('fetchFilterEpTransactions')
                ->once()
                ->with($this->quoteId, QuoteTypeId::Car, true, PaymentStatusEnum::DRAFT, QuoteStatusEnum::PolicyBooked, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
                ->andReturn(collect([]));

            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->id = $this->quoteId;
            $quote->code = $this->quoteCode;

            $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));
            $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);
            expect($result)->toBeArray();
            expect($result)->toHaveCount(0);
        });
    });

    describe('reminder triggered', function () {
        test('returns response for et reminder 1st fail (MDX), 2nd success (ECB) and process continues after failure', function () {
            $epTransaction1 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction1->code = 'MDX-'.$this->quoteCode;
            $epTransaction2 = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction2->code = 'ECB-'.$this->quoteCode;
            $epTransactions = collect([$epTransaction1, $epTransaction2]);

            $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
            $repoMock->shouldReceive('fetchFilterEpTransactions')
                ->once()
                ->with($this->quoteId, QuoteTypeId::Car, true, PaymentStatusEnum::DRAFT, QuoteStatusEnum::PolicyBooked, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
                ->andReturn($epTransactions);

            $callCount = 0;
            $this->mock(BirdService::class, function ($mock) use (&$callCount) {
                $mock->shouldReceive('triggerWebHookRequest')
                    ->twice()
                    ->with($this->dummyBirdEpWorkflowUrl, Mockery::type('object'))
                    ->andReturnUsing(function () use (&$callCount) {
                        $callCount++;
                        if ($callCount === 1) {
                            throw new RuntimeException('Bird API connection failed');
                        }

                        return (object) ['status_code' => Response::HTTP_OK, 'message' => 'OK'];
                    });
            });

            $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

            $quote = Mockery::mock(CarQuote::class)->makePartial();
            $quote->id = $this->quoteId;
            $quote->code = $this->quoteCode;

            $result = $service->retargetEpReminder($quote, QuoteTypeId::Car);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(2);
            expect($result[0])->toMatchArray([
                'embeddedTransactionCode' => 'MDX-'.$this->quoteCode,
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Bird API connection failed',
            ]);
            expect($result[1])->toMatchArray([
                'embeddedTransactionCode' => 'ECB-'.$this->quoteCode,
                'status_code' => Response::HTTP_OK,
                'message' => 'OK',
            ]);
        });
    });
});

describe('triggerBirdWorkflowRetargetEpReminder (via retargetEpReminder)', function () {
    test('when BirdService returns error status, response contains that embeddedTransactionCode & status_code', function () {
        $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
        $epTransaction->code = 'MDX-'.$this->quoteCode;

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('fetchFilterEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, true, PaymentStatusEnum::DRAFT, QuoteStatusEnum::PolicyBooked, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([$epTransaction]));

        $this->mock(BirdService::class, function ($mock) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($this->dummyBirdEpWorkflowUrl, Mockery::type('object'))
                ->andReturn((object) ['status_code' => Response::HTTP_NOT_FOUND, 'body' => '{"code":"NotFound","message":"The resource doesn\'t exist or you don\'t have access to it."}']);
        });

        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

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
        $repoMock->shouldReceive('fetchFilterEpTransactions')
            ->once()
            ->with($this->quoteId, QuoteTypeId::Car, true, PaymentStatusEnum::DRAFT, QuoteStatusEnum::PolicyBooked, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
            ->andReturn(collect([$epTransaction]));

        $capturedData = null;
        $this->mock(BirdService::class, function ($mock) use (&$capturedData) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($this->dummyBirdEpWorkflowUrl, Mockery::on(function ($data) use (&$capturedData) {
                    $capturedData = $data;

                    return true;
                }))
                ->andReturn((object) ['status_code' => Response::HTTP_OK, 'message' => 'OK']);
        });

        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;
        $quote->uuid = $this->quoteUuid;

        $service->retargetEpReminder($quote, QuoteTypeId::Car);

        expect($capturedData)->not->toBeNull();
        expect($capturedData->quoteId)->toBe($this->quoteId);
        expect($capturedData->quoteTypeId)->toBe(QuoteTypeId::Car);
        expect($capturedData->refId)->toBe($this->quoteCode);
        expect($capturedData->uuid)->toBe($this->quoteUuid);
        expect($capturedData->embeddedTransactionCode)->toBe($this->embeddedTransactionCode);
        expect($capturedData->workflowType)->toBe(WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('quoteId='.$this->quoteId);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('quoteTypeId='.QuoteTypeId::Car);
        expect($capturedData->getRetargetingEpReminderUrl)->toContain('embeddedTransactionCode='.$this->embeddedTransactionCode);
        expect($capturedData->reminderNumber)->toBe(1);
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

        ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL)
            ->update(['value' => '']);

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

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
        $this->mock(BirdService::class, function ($mock) {
            $mock->shouldReceive('triggerWebHookRequest')
                ->once()
                ->with($this->dummyBirdEpWorkflowUrl, Mockery::type('object'))
                ->andReturn((object) ['status_code' => Response::HTTP_OK]);
        });

        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

        $quote = Mockery::mock(CarQuote::class)->makePartial();
        $quote->id = $this->quoteId;
        $quote->code = $this->quoteCode;

        $result = invokeTriggerBirdWorkflowRetargetEpReminder($service, $quote, QuoteTypeId::Car, $epTransaction);

        expect($result)->toBeObject();
        expect($result->status_code)->toBe(Response::HTTP_OK);
    });
});

describe('sendBikeEpRetargetingEmail (via Reflection)', function () {
    function invokeSendBikeEpRetargetingEmail(object $service, array $emailData): object
    {
        $ref = new ReflectionClass(EmbeddedTransactionService::class);
        $method = $ref->getMethod('sendBikeEpRetargetingEmail');
        $method->setAccessible(true);

        return $method->invoke($service, $emailData);
    }

    $emailData = fn () => [
        'quoteId' => 3,
        'quoteTypeId' => QuoteTypeId::Bike,
        'refId' => 'BIK-RETARGET003',
        'uuid' => 'RETARGET003',
        'embeddedTransactionCode' => 'MDX-BIK-RETARGET003',
        'customerEmail' => 'customer@example.com',
        'customerName' => 'John Doe',
    ];

    test('returns 404 when template ID is not configured', function () use ($emailData) {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

        $result = invokeSendBikeEpRetargetingEmail($service, $emailData());

        expect($result->status_code)->toBe(Response::HTTP_NOT_FOUND);
        expect($result->message)->toBe('Bike EP retargeting template not configured');
    });

    test('returns 200 when Brevo responds with 201', function () use ($emailData) {
        ApplicationStorage::updateOrInsert(
            ['key_name' => ApplicationStorageEnums::RDX_EP_RETARGETING_REMINDER_TEMPLATE],
            ['value' => '999', 'created_at' => now(), 'updated_at' => now()]
        );

        $sendEmailMock = Mockery::mock(SendEmailCustomerService::class);
        $sendEmailMock->shouldReceive('sendBikeEpRetargetingEmail')
            ->once()
            ->with(999, Mockery::on(function ($data) {
                return $data['customerEmail'] === 'customer@example.com'
                    && $data['refId'] === 'BIK-RETARGET003';
            }), 'bike-ep-retargeting')
            ->andReturn(Response::HTTP_CREATED);

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class), $sendEmailMock);

        $result = invokeSendBikeEpRetargetingEmail($service, $emailData());

        expect($result->status_code)->toBe(Response::HTTP_OK);
        expect($result->message)->toBe('Bike EP retargeting email sent');
    });

    test('returns 500 when Brevo responds with non-201', function () use ($emailData) {
        ApplicationStorage::updateOrInsert(
            ['key_name' => ApplicationStorageEnums::RDX_EP_RETARGETING_REMINDER_TEMPLATE],
            ['value' => '999', 'created_at' => now(), 'updated_at' => now()]
        );

        $sendEmailMock = Mockery::mock(SendEmailCustomerService::class);
        $sendEmailMock->shouldReceive('sendBikeEpRetargetingEmail')
            ->once()
            ->andReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class), $sendEmailMock);

        $result = invokeSendBikeEpRetargetingEmail($service, $emailData());

        expect($result->status_code)->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
        expect($result->message)->toBe('Failed to send bike EP retargeting email');
    });
});

describe('handleTriggerEpRetargetingEmail', function () {
    test('returns criteria not met when eligibility check fails', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $repoMock->shouldReceive('fetchFindEmbededTransactionWithDetails')
            ->once()
            ->andReturn(null);

        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));
        $result = $service->handleTriggerEpRetargetingEmail($this->quoteId, $this->quoteTypeId, $this->embeddedTransactionCode);

        expect($result)->toBeObject();
        expect($result->status_code)->toBe(Response::HTTP_OK);
        expect($result->message)->toBe('Criteria not met for triggering the email.');
    });
});

describe('isRetargetingEpReminderEnabled', function () {
    test('return a boolean', function () {
        $repoMock = Mockery::mock(EmbeddedTransactionRepository::class);
        $service = new EmbeddedTransactionServiceTestDouble($repoMock, app(EmbeddedProductRepository::class), app(BirdService::class));

        $result = $service->isRetargetingEpReminderEnabled();

        expect($result)->toBeBool();
    });
});

/**
 * Test double that skips BaseService constructor (getGenericModel) so unit tests
 * can mock only EmbeddedTransactionRepository (and optionally EmbeddedProductRepository).
 */
class EmbeddedTransactionServiceTestDouble extends EmbeddedTransactionService
{
    public function __construct(
        EmbeddedTransactionRepository $repo,
        EmbeddedProductRepository $embeddedProductRepo,
        BirdService $birdService,
        ?SendEmailCustomerService $sendEmailCustomerService = null,
    ) {
        $this->embeddedTransactionRepo = $repo;
        $this->embeddedProductRepo = $embeddedProductRepo;
        $this->birdService = $birdService;
        $this->sendEmailCustomerService = $sendEmailCustomerService ?? app(SendEmailCustomerService::class);
    }
}
