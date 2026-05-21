<?php

declare(strict_types=1);

use App\Enums\EmbeddedTransactionEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\SyncSukoonDocumentsJob;
use App\Models\EmbeddedTransaction;
use App\Services\SukoonMedexService;

test('SyncSukoonDocumentsJob passes captured policy status and isSendEmail true to maybeSendDocumentsEmail', function () {
    $quote = (object) ['code' => 'SYNCJOB-001', 'id' => 88];

    $transaction = new EmbeddedTransaction([
        'policy_status' => EmbeddedTransactionEnum::STATUS_BOOKED,
    ]);

    $mock = Mockery::mock(SukoonMedexService::class);
    $mock->shouldReceive('initiatePurchaseFlow')->once()->with($quote, QuoteTypeId::Car, $transaction);
    $mock->shouldReceive('syncAndProcessSukoonDocuments')->once()->ordered();
    $mock->shouldReceive('maybeSendDocumentsEmail')
        ->once()
        ->ordered()
        ->with(true, EmbeddedTransactionEnum::STATUS_BOOKED);

    app()->instance(SukoonMedexService::class, $mock);

    (new SyncSukoonDocumentsJob($quote, QuoteTypeId::Car, $transaction, true))->handle();
});

test('SyncSukoonDocumentsJob passes isSendEmail false when fourth constructor argument is omitted', function () {
    $quote = (object) ['code' => 'SYNCJOB-002', 'id' => 89];

    $transaction = new EmbeddedTransaction([
        'policy_status' => EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
    ]);

    $mock = Mockery::mock(SukoonMedexService::class);
    $mock->shouldReceive('initiatePurchaseFlow')->once()->with($quote, QuoteTypeId::Car, $transaction);
    $mock->shouldReceive('syncAndProcessSukoonDocuments')->once()->ordered();
    $mock->shouldReceive('maybeSendDocumentsEmail')
        ->once()
        ->ordered()
        ->with(false, EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED);

    app()->instance(SukoonMedexService::class, $mock);

    (new SyncSukoonDocumentsJob($quote, QuoteTypeId::Car, $transaction))->handle();
});
