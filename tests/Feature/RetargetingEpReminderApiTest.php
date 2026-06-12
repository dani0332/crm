<?php

use App\Enums\QuoteTypeId;
use App\Http\Middleware\BasicAuth;
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
