<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use Tests\Helpers\RetargetingEpReminderTestDataHelper;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->repository = new EmbeddedTransactionRepository;
});

describe('EmbeddedTransactionRepository', function () {
    describe('model', function () {
        test('returns EmbeddedTransaction class', function () {
            expect($this->repository->model())->toBe(EmbeddedTransaction::class);
        });
    });

    describe('getEpRetargetingReminderEmailTemplateId', function () {
        test('returns false for invalid short code', function () {
            expect($this->repository->getEpRetargetingReminderEmailTemplateId('INVALID_SHORT_CODE'))->toBeFalse();
        });

        test('returns false when template id is not in application storage', function () {
            expect($this->repository->getEpRetargetingReminderEmailTemplateId(EmbeddedProductEnum::MDX))->toBeFalse();
        });

        test('returns template id when template exists in application storage', function (string $shortCode, string $storageKey, string $templateValue) {
            ApplicationStorage::forceCreate([
                'key_name' => $storageKey,
                'value' => $templateValue,
                'is_active' => 1,
            ]);

            $result = $this->repository->getEpRetargetingReminderEmailTemplateId($shortCode);

            expect($result)->toBe($templateValue);
        })->with([
            [EmbeddedProductEnum::MDX, ApplicationStorageEnums::CAR_EP_REMINDER_MDX_EMAIL_TEMPLATE, 'template-123'],
            [EmbeddedProductEnum::ECB, ApplicationStorageEnums::CAR_EP_REMINDER_ECB_EMAIL_TEMPLATE, 'template-456'],
        ]);
    });

    describe('getDraftEpTransactions', function () {
        test('returns empty collection when ep short codes array is empty', function () {
            $data = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();

            $result = $this->repository->getDraftEpTransactions($data->carQuotePolicyBooked->id, QuoteTypeId::Car, []);

            expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result->isEmpty())->toBeTrue();
        });

        test('returns only allowed-reminder-ep draft transactions those quote-status is policy booked', function () {
            $data = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();

            $firstTestResult = $this->repository->getDraftEpTransactions($data->carQuotePolicyIssued->id, QuoteTypeId::Car, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::ECB]);
            expect($firstTestResult)->toHaveCount(0);

            $secondTestResult = $this->repository->getDraftEpTransactions($data->carQuotePolicyBooked->id, QuoteTypeId::Car, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::ECB]);
            expect($secondTestResult)->toHaveCount(2);
            expect($secondTestResult->pluck('id')->all())->toContain($data->validTransaction->id, $data->epECBTransaction->id);
        });
    });

    describe('getRetargetingCarEpReminderData', function () {
        test('returns null when no transaction matches code and quote request id', function () {
            $result = $this->repository->getRetargetingCarEpReminderData(99999, 'NON-EXISTENT-CODE');

            expect($result)->toBeNull();
        });

        test('returns transaction only when quote is policy booked, embedded-transaction is draft and EP is allowed otherwise returns null', function () {
            $data = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();

            $validResult = $this->repository->getRetargetingCarEpReminderData($data->carQuotePolicyBooked->id, $data->validTransaction->code);
            expect($validResult)->not->toBeNull()
                ->and($validResult->id)->toBe($data->validTransaction->id)
                ->and($validResult->code)->toBe($data->validTransaction->code)
                ->and($validResult->quote_request_type)->toBe(CarQuote::class)
                ->and($validResult->payment_status_id)->toBe(PaymentStatusEnum::DRAFT)
                ->and($validResult->relationLoaded('quoteRequest'))->toBeTrue()
                ->and($validResult->quoteRequest->quote_status_id)->toBe(QuoteStatusEnum::PolicyBooked)
                ->and($validResult->relationLoaded('product'))->toBeTrue()
                ->and($validResult->product->embeddedProduct->short_code)->toBe(EmbeddedProductEnum::MDX);

            expect($this->repository->getRetargetingCarEpReminderData($data->notBookedTransaction->quote_request_id, $data->notBookedTransaction->code))->toBeNull();
            expect($this->repository->getRetargetingCarEpReminderData($data->notAllowedEpTransaction->quote_request_id, $data->notAllowedEpTransaction->code))->toBeNull();
            expect($this->repository->getRetargetingCarEpReminderData($data->notDraftTransaction->quote_request_id, $data->notDraftTransaction->code))->toBeNull();
        });
    });
});
