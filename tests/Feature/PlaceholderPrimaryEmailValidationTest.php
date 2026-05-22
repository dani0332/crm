<?php

declare(strict_types=1);

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\SaveBookingDetailsRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Http\Requests\SendUpdateCustomerValidationRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\SendUpdateLog;
use App\Rules\PlaceholderPrimaryEmail;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    Queue::fake();

    CarQuote::unsetEventDispatcher();
    Payment::unsetEventDispatcher();
    PaymentSplits::unsetEventDispatcher();
    SendUpdateLog::unsetEventDispatcher();

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);

    $this->customer = Customer::factory()
        ->withEmail('noemail@gmail.com')
        ->create();

    $this->quoteType = QuoteType::factory()->createForSqlite([
        'id' => QuoteTypeId::Car,
        'code' => QuoteTypes::CAR->value,
        'text' => QuoteTypes::CAR->value,
        'is_active' => 1,
    ]);

    $this->quote = CarQuote::factory()
        ->forCustomer($this->customer->id)
        ->withEmail('noemail@gmail.com')
        ->create([
            'advisor_id' => $this->user->id,
            'quote_status_id' => null,
        ]);

    $this->personalQuote = PersonalQuote::factory()->createForSqlite([
        'code' => $this->quote->code,
        'quote_type_id' => $this->quoteType->id,
        'customer_id' => $this->customer->id,
        'first_name' => $this->quote->first_name,
        'last_name' => $this->quote->last_name,
        'email' => $this->quote->email,
        'mobile_no' => $this->quote->mobile_no,
        'advisor_id' => $this->user->id,
    ]);

    $this->payment = Payment::factory()->create([
        'code' => $this->quote->code,
        'paymentable_id' => $this->quote->id,
        'paymentable_type' => CarQuote::class,
        'payment_status_id' => PaymentStatusEnum::NEW,
        'payment_methods_code' => PaymentMethodsEnum::InsurerPayment,
        'insurer_invoice_date' => now()->toDateString(),
        'insurer_tax_number' => 'TAX-001',
        'insurer_commmission_invoice_number' => 'COMM-001',
        'commission_vat_not_applicable' => 10,
        'commission_vat_applicable' => 0,
        'commission' => 10,
        'commission_vat' => 0,
        'commmission_percentage' => 10,
        'invoice_description' => 'Quote invoice',
        'total_payments' => 1,
    ]);

    $this->paymentSplit = PaymentSplits::factory()->forPayment($this->payment)->create([
        'payment_method' => PaymentMethodsEnum::BankTransfer,
        'payment_status_id' => PaymentStatusEnum::PAID,
    ]);

    $this->sendUpdate = SendUpdateLog::factory()->create([
        'code' => 'SU-PLACEHOLDER',
        'quote_uuid' => $this->quote->uuid,
        'quote_type_id' => $this->quoteType->id,
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
        'personal_quote_id' => $this->personalQuote->id,
    ]);
});

afterEach(function () {
    Mockery::close();
});

function bindFormRequestInput(array $input): void
{
    $request = Request::create('/', 'POST', $input);
    $request->setUserResolver(fn () => auth()->user());

    app()->instance('request', $request);
}

function runAfterValidation(FormRequest $request): MessageBag
{
    $request = FormRequest::createFrom(app('request'), $request);
    $request->setUserResolver(fn () => auth()->user());

    $validator = Validator::make([], []);

    $request->withValidator($validator);
    $validator->fails();

    return $validator->errors();
}

it('detects placeholder primary email patterns', function (string $email, bool $expected) {
    expect(PlaceholderPrimaryEmail::isPlaceholder($email))->toBe($expected);
})->with([
    ['noemail@gmail.com', true],
    ['noemail+1@gmail.com', true],
    ['NoEmail+42@gmail.com', true],
    ['1noemail@gmail.com', true],
    ['10noemail@gmail.com', true],
    ['customer-noemail@gmail.com', true],
    ['noemailcustomer@gmail.com', true],
    ['noemail+abc@gmail.com', true],
    ['customer@example.com', false],
    ['noemail@outlook.com', false],
    ['no-email@gmail.com', false],
]);

it('treats a null quote as not using a placeholder email', function () {
    expect(PlaceholderPrimaryEmail::hasPlaceholderPrimaryEmail(null))->toBeFalse();
});

it('blocks transaction approval when primary email is a placeholder', function () {
    bindFormRequestInput([
        'modelType' => QuoteTypes::CAR->value,
        'leadId' => $this->quote->id,
        'leadStatus' => QuoteStatusEnum::TransactionApproved,
    ]);

    $errors = runAfterValidation(new UpdateLeadStatusRequest);

    expect($errors->get('value'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks transaction approval when customer email is placeholder even if quote email is real', function () {
    $this->quote->update(['email' => 'realcustomer@example.com']);

    bindFormRequestInput([
        'modelType' => QuoteTypes::CAR->value,
        'leadId' => $this->quote->id,
        'leadStatus' => QuoteStatusEnum::TransactionApproved,
    ]);

    $errors = runAfterValidation(new UpdateLeadStatusRequest);

    expect($errors->get('value'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks booking details updates when primary email is a placeholder', function () {
    bindFormRequestInput([
        'model_type' => QuoteTypes::CAR->value,
        'quote_id' => $this->quote->id,
        'payment_code' => $this->payment->code,
        'invoice_date' => now()->toDateString(),
        'insurer_tax_invoice_number' => 'TAX-NEW-001',
        'insurer_commmission_invoice_number' => 'COMM-NEW-001',
        'invoice_description' => 'Booking details',
        'commission_vat_not_applicable' => 10,
        'commission_vat_applicable' => 0,
        'vat_on_commission' => 0,
        'commission_percentage' => 10,
        'total_commission' => 10,
        'discount' => 0,
        'broker_invoice_number' => 'BROKER-001',
    ]);

    $errors = runAfterValidation(new BookPolicyRequest);

    expect($errors->get('value'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks sage booking when primary email is a placeholder', function () {
    bindFormRequestInput([
        'model_type' => QuoteTypes::CAR->value,
        'quote_id' => $this->quote->id,
        'send_policy_type' => 'sage',
        'through_automation' => true,
    ]);

    $errors = runAfterValidation(new SendBookPolicyRequest);

    expect($errors->get('value'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks send update booking details when primary email is a placeholder', function () {
    bindFormRequestInput([
        'id' => $this->sendUpdate->id,
        'invoice_description' => 'Send update booking',
        'invoice_date' => now()->toDateString(),
        'insurer_tax_invoice_number' => 'SU-TAX-001',
        'insurer_commission_invoice_number' => 'SU-COMM-001',
        'discount' => 0,
        'commission_percentage' => 10,
        'commission_vat_applicable' => 0,
        'commission_vat_not_applicable' => 10,
        'vat_on_commission' => 0,
        'total_commission' => 10,
        'price_with_vat' => 100,
        'transaction_payment_status' => 'Paid',
    ]);

    $errors = runAfterValidation(new SaveBookingDetailsRequest);

    expect($errors->get('error'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks send and book update validation when primary email is a placeholder', function () {
    bindFormRequestInput([
        'sendUpdateId' => $this->sendUpdate->id,
        'action' => SendUpdateLogStatusEnum::ACTION_SNBU,
        'inslyMigrated' => false,
        'paymentValidated' => true,
    ]);

    $errors = runAfterValidation(new SendUpdateCustomerValidationRequest);

    expect($errors->get('error'))->toContain(PlaceholderPrimaryEmail::message());
});

it('blocks direct send update booking preparation when primary email is a placeholder', function () {
    $request = (object) [
        'sendUpdateId' => $this->sendUpdate->id,
        'quoteType' => QuoteTypes::CAR->value,
        'quoteUuid' => $this->quote->uuid,
        'quoteRefId' => $this->quote->id,
        'quoteCode' => $this->quote->code,
    ];

    $response = app(SendUpdateLogService::class)->preparedDataForEndorsement($request);

    expect($response['status'])->toBeFalse()
        ->and($response['message'])->toBe(PlaceholderPrimaryEmail::message());
});

it('blocks split payment approval when primary email is a placeholder', function () {
    $result = app(SplitPaymentService::class)->processMasterPaymentApprove(
        QuoteTypes::CAR->value,
        $this->quote->id,
        0
    );

    $this->payment->refresh();
    $this->quote->refresh();

    expect($result)->toBe(PlaceholderPrimaryEmail::message())
        ->and($this->payment->is_approved)->not->toBe(1)
        ->and($this->quote->quote_status_id)->toBeNull();
});
