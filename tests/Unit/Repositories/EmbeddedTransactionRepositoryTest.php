<?php

declare(strict_types=1);

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use Illuminate\Support\Collection;
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

    describe('fetchFilterEpTransactions', function () {
        $filterGroupData = null;

        beforeEach(function () use (&$filterGroupData) {
            if ($filterGroupData === null) {
                $filterGroupData = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();
            }
            $this->data = $filterGroupData;
        });

        test('returns empty collection when ep short codes array is empty', function () {
            $data = $this->data;
            $result = $this->repository->fetchFilterEpTransactions(
                $data->carQuotePolicyBooked->id,
                QuoteTypeId::Car,
                null,
                null,
                null,
                []
            );

            expect($result)->toBeInstanceOf(Collection::class)
                ->and($result->isEmpty())->toBeTrue();
        });

        test('returns only allowed-reminder-ep draft transactions those quote-status is policy booked', function () {
            $data = $this->data;
            $firstTestResult = $this->repository->fetchFilterEpTransactions(
                $data->carQuotePolicyIssued->id,
                QuoteTypeId::Car,
                true,
                PaymentStatusEnum::DRAFT,
                QuoteStatusEnum::PolicyBooked,
                EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS
            );
            expect($firstTestResult)->toHaveCount(0);

            $secondTestResult = $this->repository->fetchFilterEpTransactions(
                $data->carQuotePolicyBooked->id,
                QuoteTypeId::Car,
                true,
                PaymentStatusEnum::DRAFT,
                QuoteStatusEnum::PolicyBooked,
                EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS
            );
            expect($secondTestResult)->toHaveCount(2);
            expect($secondTestResult->pluck('id')->all())->toContain($data->validTransaction->id, $data->epECBTransaction->id);
        });
    });

    describe('fetchFindEmbededTransactionWithDetails', function () {
        test('returns null when no transaction exists for the given code', function () {
            expect($this->repository->fetchFindEmbededTransactionWithDetails(
                99999,
                QuoteTypeId::Car,
                'NONEXISTENT-CODE'
            ))->toBeNull();
        });

        describe('with repository test data', function () {
            $detailsGroupData = null;

            beforeEach(function () use (&$detailsGroupData) {
                if ($detailsGroupData === null) {
                    $detailsGroupData = RetargetingEpReminderTestDataHelper::setupRepositoryTestData();
                }
                $this->data = $detailsGroupData;
            });

            test('returns transaction with eager-loaded relations when code matches', function () {
                $data = $this->data;
                $result = $this->repository->fetchFindEmbededTransactionWithDetails(
                    $data->carQuotePolicyBooked->id,
                    QuoteTypeId::Car,
                    'ET-VALID'
                );
                expect($result)->not->toBeNull()
                    ->and($result->id)->toBe($data->validTransaction->id)
                    ->and($result->code)->toBe('ET-VALID')
                    ->and($result->relationLoaded('product'))->toBeTrue()
                    ->and($result->relationLoaded('quoteRequest'))->toBeTrue();
                expect($result->product)->not->toBeNull()
                    ->and($result->product->relationLoaded('embeddedProduct'))->toBeTrue();
                expect($result->quoteRequest)->not->toBeNull()
                    ->and($result->quoteRequest->relationLoaded('carMake'))->toBeTrue()
                    ->and($result->quoteRequest->relationLoaded('carModel'))->toBeTrue()
                    ->and($result->quoteRequest->relationLoaded('advisor'))->toBeTrue()
                    ->and($result->quoteRequest->relationLoaded('plan'))->toBeTrue();
                expect($result->quoteRequest->plan)->not->toBeNull()
                    ->and($result->quoteRequest->plan->relationLoaded('insuranceProvider'))->toBeTrue();
                expect($result->quoteRequest->getAttributes())->toHaveKeys(['vehicle_use', 'is_modified']);
                if ($result->quoteRequest->carMake !== null) {
                    expect($result->quoteRequest->carMake->getAttributes())->toHaveKeys(['id', 'text', 'code']);
                }
                if ($result->quoteRequest->carModel !== null) {
                    expect($result->quoteRequest->carModel->getAttributes())->toHaveKeys(['id', 'text', 'code']);
                }
                if ($result->quoteRequest->plan !== null) {
                    expect($result->quoteRequest->plan->getAttributes())->toHaveKeys(['id', 'provider_id', 'repair_type']);
                }
            });

            test('filters', function (string $description, array $filterArgs, bool $expectNull, ?string $assertAttribute, $expectedValue, bool $setTransactionIsActive) {
                [$quoteStatusId, $paymentStatusId, $isActive] = $filterArgs;

                $data = $this->data;
                $data->validTransaction->update(['is_active' => $setTransactionIsActive]);
                $result = $this->repository->fetchFindEmbededTransactionWithDetails(
                    $data->carQuotePolicyBooked->id,
                    QuoteTypeId::Car,
                    'ET-VALID',
                    null,
                    $isActive,
                    $paymentStatusId,
                    $quoteStatusId
                );

                if ($expectNull) {
                    expect($result)->toBeNull();
                } else {
                    expect($result)->not->toBeNull()->and($result->id)->toBe($data->validTransaction->id);
                    if ($assertAttribute === 'payment_status_id') {
                        expect($result->payment_status_id)->toBe($expectedValue);
                    }
                    if ($assertAttribute === 'quote_status_id') {
                        expect($result->quoteRequest->quote_status_id)->toBe($expectedValue);
                    }
                    if ($assertAttribute === 'is_active') {
                        expect((bool) $result->is_active)->toBe($expectedValue);
                    }
                }
            })->with([
                ['quote_status_id matches', [QuoteStatusEnum::PolicyBooked, null, null], false, 'quote_status_id', QuoteStatusEnum::PolicyBooked, true],
                ['quote_status_id does not match', [QuoteStatusEnum::PolicyIssued, null, null], true, null, null, true],
                ['payment_status_id matches', [null, PaymentStatusEnum::DRAFT, null], false, 'payment_status_id', PaymentStatusEnum::DRAFT, true],
                ['payment_status_id does not match', [null, PaymentStatusEnum::AUTHORISED, null], true, null, null, true],
                ['is_active true & transaction active', [null, null, true], false, 'is_active', true, true],
                ['is_active false & transaction inactive', [null, null, false], false, 'is_active', false, false],
                ['is_active true & transaction inactive', [null, null, true], true, null, null, false],
                ['is_active false & transaction active', [null, null, false], true, null, null, true],
            ]);

            test('returns only selected attributes on the transaction', function () {
                $data = $this->data;
                $result = $this->repository->fetchFindEmbededTransactionWithDetails(
                    $data->carQuotePolicyBooked->id,
                    QuoteTypeId::Car,
                    'ET-VALID'
                );
                expect($result->getAttributes())->toHaveKeys(['id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'is_active', 'payment_status_id', 'product_id'])
                    ->and(array_key_exists('policy_status', $result->getAttributes()))->toBeFalse();
            });

            test('returns null when code matches but quoteId does not (quote-bound guard)', function () {
                $data = $this->data;
                $result = $this->repository->fetchFindEmbededTransactionWithDetails(
                    $data->carQuotePolicyIssued->id,
                    QuoteTypeId::Car,
                    'ET-VALID'
                );
                expect($result)->toBeNull();
            });

            test('returns null when code matches but quoteTypeId does not', function () {
                $data = $this->data;
                $result = $this->repository->fetchFindEmbededTransactionWithDetails(
                    $data->carQuotePolicyBooked->id,
                    QuoteTypeId::Home,
                    'ET-VALID'
                );
                expect($result)->toBeNull();
            });
        });
    });
});
