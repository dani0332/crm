<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Payment;
use App\Models\PaymentAction;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusHistory;
use App\Models\PaymentStatusLog;
use App\Models\QuoteDocument;
use App\Models\TravelQuote;
use App\Services\CentralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();

    if (! Schema::hasTable('payment_status_history')) {
        Schema::create('payment_status_history', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('payment_actions')) {
        Schema::create('payment_actions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->nullable();
            $table->timestamps();
        });
    }
});

test('deleting child travel payment removes splits, status history, and quote documents tied to that payment code', function () {
    $quote = TravelQuote::query()->create([
        'uuid' => 'travel-del-child-uuid',
        'code' => 'TRA-CHILDCLN',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    $mainPayment = Payment::query()->create([
        'code' => 'TRA-CHILDCLN',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 1000,
        'total_amount' => 1000,
        'send_update_log_id' => null,
    ]);

    $childPayment = Payment::query()->create([
        'code' => 'TRA-CHILDCLN-1',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 500,
        'total_amount' => 500,
        'send_update_log_id' => null,
    ]);

    $split = PaymentSplits::query()->create([
        'code' => 'TRA-CHILDCLN-1',
        'payment_amount' => 500,
        'payment_status_id' => PaymentStatusEnum::NEW,
    ]);

    PaymentStatusHistory::forceCreate([
        'payment_code' => 'TRA-CHILDCLN-1',
    ]);

    QuoteDocument::forceCreate([
        'doc_name' => 'test.pdf',
        'payment_split_id' => $split->id,
        'quote_documentable_id' => $quote->id,
        'quote_documentable_type' => TravelQuote::class,
    ]);

    $result = app(CentralService::class)->deletePayment((object) [
        'payment_code' => 'TRA-CHILDCLN-1',
        'payment_id' => $childPayment->id,
    ]);

    expect($result['status'])->toBeTrue()
        ->and(Payment::query()->find($childPayment->id))->toBeNull()
        ->and(Payment::query()->find($mainPayment->id))->not->toBeNull()
        ->and(PaymentSplits::query()->where('id', $split->id)->exists())->toBeFalse()
        ->and(PaymentStatusHistory::query()->where('payment_code', 'TRA-CHILDCLN-1')->exists())->toBeFalse()
        ->and(QuoteDocument::withTrashed()->where('payment_split_id', $split->id)->exists())->toBeFalse();
});

test('deleting child travel payment sets quote to TransactionApproved when the remaining main lead payment is paid', function () {
    $quote = TravelQuote::query()->create([
        'uuid' => 'travel-del-child-paid-uuid',
        'code' => 'TRA-CHILDPAID',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    Payment::query()->create([
        'code' => 'TRA-CHILDPAID',
        'payment_status_id' => PaymentStatusEnum::PAID,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 1000,
        'total_amount' => 1000,
        'send_update_log_id' => null,
    ]);

    $childPayment = Payment::query()->create([
        'code' => 'TRA-CHILDPAID-1',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 500,
        'total_amount' => 500,
        'send_update_log_id' => null,
    ]);

    $result = null;
    TravelQuote::withoutEvents(function () use (&$result, $childPayment): void {
        $result = app(CentralService::class)->deletePayment((object) [
            'payment_code' => 'TRA-CHILDPAID-1',
            'payment_id' => $childPayment->id,
        ]);
    });

    expect($result['status'])->toBeTrue()
        ->and($quote->fresh()->quote_status_id)->toBe(QuoteStatusEnum::TransactionApproved);
});

test('deleting parent travel payment removes its splits and history then renames child payment code and related mappings to the quote code', function () {
    $quote = TravelQuote::query()->create([
        'uuid' => 'travel-del-parent-uuid',
        'code' => 'TRA-PARENTMAP',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    $parentPayment = Payment::query()->create([
        'code' => 'TRA-PARENTMAP',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 1000,
        'total_amount' => 1000,
        'send_update_log_id' => null,
    ]);

    $childPayment = Payment::query()->create([
        'code' => 'TRA-PARENTMAP-1',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 500,
        'total_amount' => 500,
        'send_update_log_id' => null,
    ]);

    $parentSplit = PaymentSplits::query()->create([
        'code' => 'TRA-PARENTMAP',
        'payment_amount' => 1000,
        'payment_status_id' => PaymentStatusEnum::NEW,
    ]);

    $childSplit = PaymentSplits::query()->create([
        'code' => 'TRA-PARENTMAP-1',
        'payment_amount' => 500,
        'payment_status_id' => PaymentStatusEnum::NEW,
    ]);

    foreach (['TRA-PARENTMAP', 'TRA-PARENTMAP-1'] as $code) {
        PaymentStatusHistory::forceCreate([
            'payment_code' => $code,
        ]);
    }

    PaymentAction::query()->create(['payment_code' => 'TRA-PARENTMAP-1']);

    PaymentStatusLog::query()->create([
        'current_payment_status_id' => PaymentStatusEnum::NEW,
        'previous_payment_status_id' => null,
        'payment_code' => 'TRA-PARENTMAP-1',
    ]);

    $result = app(CentralService::class)->deletePayment((object) [
        'payment_code' => 'TRA-PARENTMAP',
        'payment_id' => $parentPayment->id,
    ]);

    expect($result['status'])->toBeTrue()
        ->and(Payment::query()->find($parentPayment->id))->toBeNull()
        ->and(PaymentSplits::query()->where('id', $parentSplit->id)->exists())->toBeFalse();

    $childFresh = Payment::query()->find($childPayment->id);
    expect($childFresh)->not->toBeNull()
        ->and($childFresh->code)->toBe('TRA-PARENTMAP');

    expect(PaymentSplits::query()->find($childSplit->id)?->code)->toBe('TRA-PARENTMAP')
        ->and(PaymentAction::query()->where('payment_code', 'TRA-PARENTMAP')->exists())->toBeTrue()
        ->and(PaymentAction::query()->where('payment_code', 'TRA-PARENTMAP-1')->exists())->toBeFalse()
        ->and(PaymentStatusHistory::query()->where('payment_code', 'TRA-PARENTMAP')->exists())->toBeTrue()
        ->and(PaymentStatusHistory::query()->where('payment_code', 'TRA-PARENTMAP-1')->exists())->toBeFalse()
        ->and(PaymentStatusLog::query()->where('payment_code', 'TRA-PARENTMAP')->exists())->toBeTrue()
        ->and(PaymentStatusLog::query()->where('payment_code', 'TRA-PARENTMAP-1')->exists())->toBeFalse();
});

test('deleting parent travel payment sets quote to TransactionApproved when the surviving renamed payment is paid', function () {
    $quote = TravelQuote::query()->create([
        'uuid' => 'travel-del-parent-paid-uuid',
        'code' => 'TRA-PARENTPAID',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    $parentPayment = Payment::query()->create([
        'code' => 'TRA-PARENTPAID',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 1000,
        'total_amount' => 1000,
        'send_update_log_id' => null,
    ]);

    $childPayment = Payment::query()->create([
        'code' => 'TRA-PARENTPAID-1',
        'payment_status_id' => PaymentStatusEnum::PAID,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 500,
        'total_amount' => 500,
        'send_update_log_id' => null,
    ]);

    $result = null;
    TravelQuote::withoutEvents(function () use (&$result, $parentPayment): void {
        $result = app(CentralService::class)->deletePayment((object) [
            'payment_code' => 'TRA-PARENTPAID',
            'payment_id' => $parentPayment->id,
        ]);
    });

    expect($result['status'])->toBeTrue()
        ->and(Payment::query()->find($childPayment->id)?->code)->toBe('TRA-PARENTPAID')
        ->and($quote->fresh()->quote_status_id)->toBe(QuoteStatusEnum::TransactionApproved);
});

test('travel delete payment is rejected when the quote has only one payment', function () {
    $quote = TravelQuote::query()->create([
        'uuid' => 'travel-del-single-uuid',
        'code' => 'TRA-SINGLE',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    $onlyPayment = Payment::query()->create([
        'code' => 'TRA-SINGLE',
        'payment_status_id' => PaymentStatusEnum::NEW,
        'paymentable_id' => $quote->id,
        'paymentable_type' => TravelQuote::class,
        'total_price' => 1000,
        'total_amount' => 1000,
        'send_update_log_id' => null,
    ]);

    $result = app(CentralService::class)->deletePayment((object) [
        'payment_code' => 'TRA-SINGLE',
        'payment_id' => $onlyPayment->id,
    ]);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toBe('Payment cannot be deleted')
        ->and(Payment::query()->find($onlyPayment->id))->not->toBeNull();
});
