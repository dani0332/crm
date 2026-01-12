<?php

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

const QUOTE_TYPE = QuoteTypes::CAR;

const EMAIL_TEST = 'test@testing.com';
const EMAIL_EXAMPLE = 'test@example.com';
const EMAIL_YOPMAIL = 'test@yopmail.com';

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    
    // Fake the queue to prevent jobs from being dispatched during tests
    Queue::fake();
    
    // Create permission
    DB::connection('sqlite')->table('permissions')->insert([
        'name' => PermissionsEnum::DELETE_ADDITIONAL_CONTACT,
        'guard_name' => 'web'
    ]);

    // Create customers with specific emails using factories
    $customerA = Customer::factory()
        ->withEmail(EMAIL_TEST)
        ->create();
    
    $customerB = Customer::factory()
        ->withEmail(EMAIL_EXAMPLE)
        ->create();
    
    $customerC = Customer::factory()
        ->withEmail(EMAIL_YOPMAIL)
        ->create();

    // Create quotes for customers using factories
    $quoteA1 = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_TEST)
        ->create();
    
    $quoteA2 = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_TEST)
        ->create();
    
    $quoteB1 = CarQuote::factory()
        ->forCustomer($customerB->id)
        ->withEmail(EMAIL_EXAMPLE)
        ->create();
    
    $quoteB2 = CarQuote::factory()
        ->forCustomer($customerB->id)
        ->withEmail(EMAIL_EXAMPLE)
        ->create();
    
    $quoteC1 = CarQuote::factory()
        ->forCustomer($customerC->id)
        ->withEmail(EMAIL_YOPMAIL)
        ->create();

    // Create personal_quotes entries for each CarQuote
    foreach ([$quoteA1, $quoteA2, $quoteB1, $quoteB2, $quoteC1] as $quote) {
        DB::connection('sqlite')->table('personal_quotes')->insert([
            'code' => $quote->code,
            'uuid' => $quote->uuid,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'customer_id' => $quote->customer_id,
            'quote_type_id' => QUOTE_TYPE->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Create additional contacts linking different customers' emails
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_EXAMPLE)
        ->create();
    
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_YOPMAIL)
        ->create();
    
    CustomerAdditionalContact::factory()
        ->forCustomer($customerB->id)
        ->email()
        ->withValue(EMAIL_TEST)
        ->create();
    
    CustomerAdditionalContact::factory()
        ->forCustomer($customerB->id)
        ->email()
        ->withValue(EMAIL_YOPMAIL)
        ->create();
    
    CustomerAdditionalContact::factory()
        ->forCustomer($customerC->id)
        ->email()
        ->withValue(EMAIL_TEST)
        ->create();
    
    CustomerAdditionalContact::factory()
        ->forCustomer($customerC->id)
        ->email()
        ->withValue(EMAIL_EXAMPLE)
        ->create();

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
    Cache::flush();
});

test('switch primary email id WITH keeping existing primary email id', function () {
    $customerAdditionalContacts = getCustomerAdditionalContactData();
    @[$quoteAList, $quoteBList] = getQuoteData();

    $quoteA = (object) array_first($quoteAList);
    $quoteBFirstData = $customerAdditionalContacts->where('key', GenericRequestEnum::EMAIL)
        ->where('value', array_first($quoteBList)['email'])
        ->where('customer_id', array_first($quoteAList)['customer_id'])
        ->first();

    $customerBAdditionalContacts = getCustomerEmailAdditionalContacts($quoteBFirstData['value'], $quoteBFirstData['customer_id'])->first();
    $response = makePrimaryEmailRequest($quoteA, $customerBAdditionalContacts, true);

    $existingPrimaryEmailAdditionalContacts = getCustomerEmailAdditionalContacts($quoteA->email);
    $switchedQuote = CarQuote::find($quoteA->id);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull()
        ->and($switchedQuote->email)->toBe($customerBAdditionalContacts->value)
        ->and($existingPrimaryEmailAdditionalContacts)->not->toBeEmpty();
});

test('switch primary email id WITHOUT keeping existing primary email id', function () {
    $customerAdditionalContacts = getCustomerAdditionalContactData();
    @[$quoteAList, $quoteBList] = getQuoteData();

    $quoteA = (object) array_first($quoteAList);
    $quoteBFirstData = $customerAdditionalContacts->where('key', GenericRequestEnum::EMAIL)
        ->where('value', array_first($quoteBList)['email'])
        ->where('customer_id', array_first($quoteAList)['customer_id'])
        ->first();

    $customerBAdditionalContacts = getCustomerEmailAdditionalContacts($quoteBFirstData['value'], $quoteBFirstData['customer_id'])->first();
    $response = makePrimaryEmailRequest($quoteA, $customerBAdditionalContacts, false);

    $switchedQuote = CarQuote::find($quoteA->id);
    $existingPrimaryEmailAdditionalContacts = getCustomerEmailAdditionalContacts($customerBAdditionalContacts->email, $switchedQuote->customer_id);

    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull()
        ->and($switchedQuote->email)->toBe($customerBAdditionalContacts->value)
        ->and($existingPrimaryEmailAdditionalContacts)->toBeEmpty();
});

test('delete additional contact without permission', function () {
    $customerAdditionalContacts = getCustomerAdditionalContactData();
    @[$quoteAList] = getQuoteData();

    $quoteA = (object) array_first($quoteAList);
    $quoteBFirstData = $customerAdditionalContacts->where('key', GenericRequestEnum::EMAIL)
        ->where('customer_id', $quoteA->customer_id)
        ->first();

    $customerBAdditionalContacts = getCustomerEmailAdditionalContacts($quoteBFirstData['value'], $quoteBFirstData['customer_id'])->first();

    // User does NOT have DELETE_ADDITIONAL_CONTACT permission
    $response = deleteAdditionalContactRequest($customerBAdditionalContacts->id);

    $recordStillExists = CustomerAdditionalContact::find($customerBAdditionalContacts->id);

    // Verify redirect with error message and record NOT deleted
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBe('You are not authorized to delete additional contact.')
        ->and($recordStillExists)->not->toBeNull();
});

test('delete additional contact with permission', function () {
    $this->user->syncPermissions(PermissionsEnum::DELETE_ADDITIONAL_CONTACT);

    $customerAdditionalContacts = getCustomerAdditionalContactData();
    @[$quoteAList] = getQuoteData();

    $quoteA = (object) array_first($quoteAList);
    $quoteBFirstData = $customerAdditionalContacts->where('key', GenericRequestEnum::EMAIL)
        ->where('customer_id', $quoteA->customer_id)
        ->first();

    $customerBAdditionalContacts = getCustomerEmailAdditionalContacts($quoteBFirstData['value'], $quoteBFirstData['customer_id'])->first();
    $beforeTestAdditionalContacts = getCustomerEmailAdditionalContacts($customerBAdditionalContacts['value']);

    $response = deleteAdditionalContactRequest($customerBAdditionalContacts->id);

    $afterTestAdditionalContacts = getCustomerEmailAdditionalContacts($customerBAdditionalContacts['value']);
    $recordDeleted = CustomerAdditionalContact::find($customerBAdditionalContacts->id);

    // Verify redirect with NO error and record IS deleted
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull()
        ->and($recordDeleted)->toBeNull()
        ->and($beforeTestAdditionalContacts->count())->toBe($afterTestAdditionalContacts->count() + 1);
});

function deleteAdditionalContactRequest($customerAdditionalContactId) {
    return test()->post("/customer-additional-contact/{$customerAdditionalContactId}/delete", [
        'isInertia' => true,
    ]);
}

function makePrimaryEmailRequest($quoteA, $switchWithAdditionalContacts, $keepExistingPrimaryEmail) 
{
    return test()->post("/customer-additional-contact/{$switchWithAdditionalContacts->id}/make-primary", [
        'isInertia' => true,
        'quote_id' => $quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => 'email',
        'value' => $switchWithAdditionalContacts->value,
        'quote_customer_id' => $quoteA->customer_id,
        'quote_primary_email_address' => $quoteA->email,
        'quote_primary_mobile_no' => $quoteA->mobile_no,
        'keep_existing_primary_email' => $keepExistingPrimaryEmail ? 1 : 0,
    ]);
}

function getQuoteData(): array
{
    $grouped = CarQuote::all()->groupBy('email');

    return [
        $grouped->get(EMAIL_TEST)?->toArray() ?? [],
        $grouped->get(EMAIL_EXAMPLE)?->toArray() ?? [],
        $grouped->get(EMAIL_YOPMAIL)?->toArray() ?? [],
    ];
}
function getCustomerAdditionalContactData(): Collection
{
    return CustomerAdditionalContact::all();
}

function getCustomerEmailAdditionalContacts($email, $customerId = null)
{
    return CustomerAdditionalContact::where('key', GenericRequestEnum::EMAIL)
        ->where('value', trim($email))
        ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
        ->get();
}
