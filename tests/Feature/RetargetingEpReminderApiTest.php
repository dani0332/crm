<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Http\Middleware\BasicAuth;
use App\Models\ApplicationStorage;
use App\Models\EmbeddedTransaction;
use App\Models\PersonalQuote;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\Response;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(BasicAuth::class);
});

afterEach(function () {
    Mockery::close();
});

describe('POST /api/imcrm/trigger-ep-retargeting-email', function () {
    describe('returns 422 Validation', function () {
        test('missing required fields', function () {
            $response = $this->postJson(route('trigger.ep-retargeting-email'), []);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors([
                'attributes.data.quoteId',
                'attributes.data.quoteTypeId',
                'attributes.data.embeddedTransactionCode',
            ]);
        });

        test('quoteId is not an integer', function () {
            $response = $this->postJson(route('trigger.ep-retargeting-email'), [
                'attributes' => ['data' => [
                    'quoteId' => 'not-an-int',
                    'quoteTypeId' => QuoteTypeId::Bike,
                    'embeddedTransactionCode' => 'RDX-BIK-TEST',
                ]],
            ]);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['attributes.data.quoteId']);
        });
    });

    describe('returns 200 criteria not met', function () {
        test('when service returns criteria not met', function () {
            $quote = PersonalQuote::forceCreate(['uuid' => 'BIKE-TEST-01', 'quote_type_id' => QuoteTypeId::Bike]);
            $transaction = EmbeddedTransaction::forceCreate(['code' => 'RDX-BIK-CRIT01', 'quote_type_id' => QuoteTypeId::Bike]);

            $this->mock(EmbeddedTransactionService::class, function ($mock) {
                $mock->shouldReceive('handleTriggerEpRetargetingEmail')
                    ->once()
                    ->andReturn((object) ['status_code' => Response::HTTP_OK, 'message' => 'Criteria not met for triggering the email.']);
            });

            $response = $this->postJson(route('trigger.ep-retargeting-email'), [
                'attributes' => ['data' => [
                    'quoteId' => $quote->id,
                    'quoteTypeId' => QuoteTypeId::Bike,
                    'embeddedTransactionCode' => $transaction->code,
                ]],
            ]);

            $response->assertStatus(Response::HTTP_OK);
            $response->assertJson(['message' => 'Criteria not met for triggering the email.']);
        });
    });

    describe('returns 200 success', function () {
        test('when email is triggered successfully', function () {
            $quote = PersonalQuote::forceCreate(['uuid' => 'BIKE-TEST-02', 'quote_type_id' => QuoteTypeId::Bike]);
            $transaction = EmbeddedTransaction::forceCreate(['code' => 'RDX-BIK-SUCC01', 'quote_type_id' => QuoteTypeId::Bike]);

            ApplicationStorage::updateOrInsert(
                ['key_name' => ApplicationStorageEnums::RDX_EP_RETARGETING_REMINDER_TEMPLATE],
                ['value' => '999', 'created_at' => now(), 'updated_at' => now()]
            );

            $this->mock(EmbeddedTransactionService::class, function ($mock) {
                $mock->shouldReceive('handleTriggerEpRetargetingEmail')
                    ->once()
                    ->andReturn((object) ['status_code' => Response::HTTP_OK, 'message' => 'Bike EP retargeting email sent']);
            });

            $response = $this->postJson(route('trigger.ep-retargeting-email'), [
                'attributes' => ['data' => [
                    'quoteId' => $quote->id,
                    'quoteTypeId' => QuoteTypeId::Bike,
                    'embeddedTransactionCode' => $transaction->code,
                ]],
            ]);

            $response->assertStatus(Response::HTTP_OK);
            $response->assertJson(['message' => 'Bike EP retargeting email sent']);

        });
    });
});

describe('GET /api/get-ep-workflow-data', function () {
    describe('returns 422 Validation', function () {
        test('missing required fields', function () {
            $response = $this->getJson(route('get.ep-workflow-data', []));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors([
                'quoteId',
                'quoteTypeId',
                'embeddedTransactionCode',
            ]);
        });

        describe('with valid quote and embedded transaction', function () {
            $validationGroupData = null;

            beforeEach(function () use (&$validationGroupData) {
                if ($validationGroupData === null) {
                    $validationGroupData = RetargetingEpReminderTestDataHelper::setupTestData();
                }
                $this->data = $validationGroupData;
            });

            test('quoteTypeId is not Car', function () {
                $data = $this->data;
                $response = $this->getJson(route('get.ep-workflow-data', [
                    'quoteId' => $data['quoteId'],
                    'quoteTypeId' => QuoteTypeId::Home,
                    'embeddedTransactionCode' => $data['epMDXTransaction']->code,
                ]));

                $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
                $response->assertJsonValidationErrors(['quoteTypeId']);
            });

            test('quoteId does not exist', function () {
                $data = $this->data;
                $response = $this->getJson(route('get.ep-workflow-data', [
                    'quoteId' => 999999,
                    'quoteTypeId' => QuoteTypeId::Car,
                    'embeddedTransactionCode' => $data['epMDXTransaction']->code,
                ]));

                $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
                $response->assertJsonValidationErrors(['quoteId']);
            });
        });
    });

    describe('returns 404 Not Found', function () {
        beforeEach(function () {
            $this->data = RetargetingEpReminderTestDataHelper::setupTestData();
        });

        test('due to quote is not booked', function () {
            $data = $this->data;
            $response = $this->getJson(route('get.ep-workflow-data', [
                'quoteId' => $data['quoteId'],
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $data['epMDXTransaction']->code,
            ]));

            $response->assertStatus(Response::HTTP_NOT_FOUND);
            $response->assertJson([
                'message' => 'Record not found',
                'status' => Response::HTTP_NOT_FOUND,
            ]);
        });

        test('when embeddedTransactionCode belongs to another quote returns 404', function () {
            $data = RetargetingEpReminderTestDataHelper::setupTestDataForApiSuccess();
            $otherQuoteData = RetargetingEpReminderTestDataHelper::setupTestData('CAR-OTHER', 'OTHER-UUID');
            $response = $this->getJson(route('get.ep-workflow-data', [
                'quoteId' => $data['quoteId'],
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $otherQuoteData['epMDXTransaction']->code,
            ]));

            $response->assertStatus(Response::HTTP_NOT_FOUND);
            $response->assertJson([
                'message' => 'Record not found',
                'status' => Response::HTTP_NOT_FOUND,
            ]);
        });
    });

    describe('returns 200 OK', function () {
        $successGroupData = null;

        beforeEach(function () use (&$successGroupData) {
            if ($successGroupData === null) {
                $successGroupData = RetargetingEpReminderTestDataHelper::setupTestDataForApiSuccess();
            }
            $this->data = $successGroupData;
        });

        test('returns 200 with retargeting reminder data from actual flow', function () {
            $data = $this->data;
            $response = $this->getJson(route('get.ep-workflow-data', [
                'quoteId' => $data['quoteId'],
                'quoteTypeId' => QuoteTypeId::Car,
                'embeddedTransactionCode' => $data['epMDXTransaction']->code,
            ]));

            $response->assertStatus(Response::HTTP_OK);
            $response->assertJson([
                'message' => 'Retargeting EP Reminder data',
                'status' => Response::HTTP_OK,
            ]);
            $response->assertJsonPath('data.quote.id', $data['quoteId']);
            $response->assertJsonPath('data.quote.uuid', $data['quoteUuid']);
            $response->assertJsonPath('data.embeddedTransaction.code', $data['epMDXTransaction']->code);
            $response->assertJsonPath('data.emailWorkflowData.customerEmail', $data['carQuote']->email);
            $response->assertJsonPath('data.emailWorkflowData.customerName', $data['carQuote']->full_name);
            $response->assertJsonPath('data.emailWorkflowData.epShortCode', $data['epMDX']->short_code);
            $response->assertJsonPath('data.emailWorkflowData.vehicleMake', $data['carMake']->text);
            $response->assertJsonPath('data.emailWorkflowData.vehicleModel', $data['carModel']->text);

            $buyNowUrl = $response->json('data.emailWorkflowData.buyNowUrl');
            expect($buyNowUrl)->toContain($data['quoteUuid'])
                ->and($buyNowUrl)->toContain('/payment/')
                ->and($buyNowUrl)->toContain('planId='.$data['carPlan']->id)
                ->and($buyNowUrl)->toContain('providerCode='.$data['insuranceProvider']->code)
                ->and($buyNowUrl)->toContain('selectEpShortCode='.$data['epMDX']->short_code);
        });
    });
});
