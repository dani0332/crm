<?php

use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Services\PaymentService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('removes health quote payments when resetting manage payments', function () {
    $quote = HealthQuote::create([
        'uuid' => 'RSTEST01',
        'code' => 'HEA-RSTEST01',
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.com',
        'quote_status_id' => QuoteStatusEnum::ApplicationPending,
        'is_quote_locked' => true,
    ]);

    $code = $quote->code;

    Payment::create([
        'code' => $code,
        'paymentable_id' => $quote->id,
        'paymentable_type' => HealthQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'payment_methods_code' => PaymentMethodsEnum::InsurerPayment,
        'total_price' => 100,
        'total_amount' => 100,
        'collection_type' => CollectionTypeEnum::INSURER,
        'frequency' => 'upfront',
    ]);

    PaymentSplits::create([
        'code' => $code,
        'sr_no' => 1,
        'payment_method' => PaymentMethodsEnum::InsurerPayment,
        'payment_amount' => 100,
        'payment_status_id' => PaymentStatusEnum::PENDING,
    ]);

    $reason = 'Integration test reset reason here';

    app(PaymentService::class)->resetHealthManagePayments($quote->fresh(), $reason);

    expect(Payment::where('code', $code)->count())->toBe(0);
    expect(PaymentSplits::where('code', $code)->count())->toBe(0);

    $quote->refresh();

    expect((bool) $quote->is_quote_locked)->toBeFalse();
    expect($quote->quote_status_id)->toBe(QuoteStatusEnum::ApplicationPending);
    expect($quote->reason_for_reset)->toBe($reason);
});
