<?php

use App\Enums\QuoteTypeId;
use App\Services\EmailStatusService;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\Response;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $testData = RetargetingEpReminderTestDataHelper::setupTestData();
    $this->carQuote = $testData['carQuote'];
    $this->quoteId = $testData['quoteId'];
    $this->quoteUuid = $testData['quoteUuid'];
    $this->quoteCode = $testData['quoteCode'];
    $this->epMDXTransaction = $testData['epMDXTransaction'];

    $this->withoutMiddleware(\App\Http\Middleware\BasicAuth::class);
});

afterEach(function () {
    Mockery::close();
});

describe('GET /api/get-retargeting-ep-reminder', function () {
    describe('returns 422 Validation', function () {
        test('missing required fields', function () {
            $response = $this->getJson(route('get.retargeting-ep-reminder', []));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors([
                'quoteId',
                'quoteTypeId',
                'embeddedTransactionCode',
            ]);
        });

        test('quoteTypeId is not Car', function () {
            $response = $this->getJson(route('get.retargeting-ep-reminder', [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => QuoteTypeId::Home,
                'embeddedTransactionCode' => $this->epMDXTransaction->code,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['quoteTypeId']);
        });

        test('quoteId does not exist', function () {
            $response = $this->getJson(route('get.retargeting-ep-reminder', [
                'quoteId' => 999999,
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $this->epMDXTransaction->code,
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['quoteId']);
        });
    });

    describe('returns 404 Not Found', function () {
        test('due to quote is not booked', function () {
            $response = $this->getJson(route('get.retargeting-ep-reminder', [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $this->epMDXTransaction->code,
            ]));

            $response->assertStatus(Response::HTTP_NOT_FOUND);
            $response->assertJson([
                'message' => 'Record not found',
                'status' => Response::HTTP_NOT_FOUND,
            ]);
        });
    });

    describe('returns 200 OK', function () {
        test('returns 200 with data when service returns retargeting reminder data', function () {
            $payload = [
                'quote' => ['id' => $this->quoteId, 'uuid' => $this->quoteUuid],
                'embeddedTransaction' => (object) ['code' => $this->epMDXTransaction->code],
                'emailWorkflowData' => ['templateId' => 'templateId-1', 'buyNowUrl' => 'https://example.com'],
            ];

            $this->mock(EmbeddedTransactionService::class, function ($mock) use ($payload) {
                $mock->shouldReceive('getRetargetingCarEpReminderData')
                    ->once()
                    ->andReturn(response()->json([
                        'data' => $payload,
                        'message' => 'Retargeting EP Reminder data',
                        'status' => Response::HTTP_OK,
                    ], Response::HTTP_OK));
            });

            $response = $this->getJson(route('get.retargeting-ep-reminder', [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $this->epMDXTransaction->code,
            ]));

            $response->assertStatus(Response::HTTP_OK);
            $response->assertJson([
                'message' => 'Retargeting EP Reminder data',
                'status' => Response::HTTP_OK,
            ]);
            $response->assertJsonPath('data.quote.id', $this->quoteId);
            $response->assertJsonPath('data.quote.uuid', $this->quoteUuid);
            $response->assertJsonPath('data.embeddedTransaction.code', $this->epMDXTransaction->code);
            $response->assertJsonPath('data.emailWorkflowData.templateId', 'templateId-1');
            $response->assertJsonPath('data.emailWorkflowData.buyNowUrl', 'https://example.com');
        });
    });
});

describe('POST /api/retargeting-ep-reminder-callback', function () {
    function validCallbackPayload(int $quoteId, string $uuid): array
    {
        return [
            'uuid' => $uuid,
            'quoteTypeId' => QuoteTypeId::Car,
            'quoteId' => $quoteId,
            'templateId' => 'templateId-123',
            'message_id' => 'msg-'.uniqid(),
            'customerId' => 1,
            'customer_email' => 'customer@example.com',
            'subject' => 'EP Reminder',
        ];
    }
    describe('returns 422 Validation', function () {
        test('missing required fields', function () {
            $response = $this->postJson(route('retargeting-ep-reminder-callback', []));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors([
                'uuid',
                'quoteTypeId',
                'quoteId',
            ]);
        });

        test('quoteId does not exist', function () {
            $payload = validCallbackPayload(999999, $this->quoteUuid);

            $response = $this->postJson(route('retargeting-ep-reminder-callback'), $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['quoteId']);
        });

        test('customer_email is invalid', function () {
            $payload = validCallbackPayload($this->quoteId, $this->quoteUuid);
            $payload['customer_email'] = 'not-an-email';

            $response = $this->postJson(route('retargeting-ep-reminder-callback'), $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['customer_email']);
        });

        test('quoteTypeId is not Car', function () {
            $payload = validCallbackPayload($this->quoteId, $this->quoteUuid);
            $payload['quoteTypeId'] = QuoteTypeId::Home;

            $response = $this->postJson(route('retargeting-ep-reminder-callback'), $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['quoteTypeId']);
        });
    });

    describe('returns 404 Not Found', function () {
        test('lead not found', function () {
            $this->mock(EmailStatusService::class, function ($mock) {
                $mock->shouldReceive('addBirdEmailStatus')
                    ->once()
                    ->andReturn((object) [
                        'status' => false,
                        'message' => 'lead not found',
                    ]);
            });

            $payload = validCallbackPayload($this->quoteId, $this->quoteUuid);

            $response = $this->postJson(route('retargeting-ep-reminder-callback'), $payload);

            $response->assertStatus(Response::HTTP_NOT_FOUND);
            $response->assertJson([
                'message' => 'lead not found',
                'status' => Response::HTTP_NOT_FOUND,
            ]);
        });
    });

    describe('returns 200 OK', function () {
        test('email status is logged successfully', function () {
            $this->mock(EmailStatusService::class, function ($mock) {
                $mock->shouldReceive('addBirdEmailStatus')
                    ->once()
                    ->andReturn((object) [
                        'status' => true,
                        'message' => 'Email event logged successfully',
                    ]);
            });

            $payload = validCallbackPayload($this->quoteId, $this->quoteUuid);

            $response = $this->postJson(route('retargeting-ep-reminder-callback'), $payload);

            $response->assertStatus(Response::HTTP_OK);
            $response->assertJson([
                'message' => 'Email event logged successfully',
                'status' => Response::HTTP_OK,
            ]);
        });
    });
});
