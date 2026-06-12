<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

if (! defined('EMAIL_CUSTOMER_A')) {
    define('EMAIL_CUSTOMER_A', 'customera@test.com');
}
if (! defined('EMAIL_CUSTOMER_B')) {
    define('EMAIL_CUSTOMER_B', 'customerb@test.com');
}
if (! defined('EMAIL_CUSTOMER_C')) {
    define('EMAIL_CUSTOMER_C', 'customerc@test.com');
}
if (! defined('EMAIL_INSURANCE_MARKET')) {
    define('EMAIL_INSURANCE_MARKET', 'advisor@insurancemarket.ae');
}
if (! defined('EMAIL_AFIA')) {
    define('EMAIL_AFIA', 'advisor@afia.ae');
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    // Fake the queue to prevent jobs from being dispatched during tests
    Queue::fake();
});

afterEach(function () {
    Mockery::close();
});

test('makeAdditionalContactPrimary creates additional contact when keepExistingPrimaryEmail is true and customer exists', function () {
    // Setup: Quote with CustomerA, CustomerB exists
    $customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create additional contact for CustomerA
    $additionalContact = CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: CustomerB.additional_contacts contains CustomerA.email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    // Assert: The additional contact with CustomerB's email should be removed from CustomerA (line 241)
    $contactWithCustomerBEmailInCustomerA = CustomerAdditionalContact::where([
        'customer_id' => $customerA->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->first();

    expect($customerBAdditionalContacts)->not->toBeEmpty()
        ->and($contactWithCustomerBEmailInCustomerA)->toBeNull()
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});

test('makeAdditionalContactPrimary does not create additional contact when keepExistingPrimaryEmail is false and customer exists', function () {
    // Setup: Quote with CustomerA, CustomerB exists
    $customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create additional contact for CustomerA
    $additionalContact = CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, false);

    // Assert: CustomerB.additional_contacts should NOT contain CustomerA.email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    // Assert: The additional contact with CustomerB's email should be removed from CustomerA (line 241)
    $contactWithCustomerBEmailInCustomerA = CustomerAdditionalContact::where([
        'customer_id' => $customerA->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->first();

    expect($customerBAdditionalContacts)->toBeEmpty()
        ->and($contactWithCustomerBEmailInCustomerA)->toBeNull()
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});

test('makeAdditionalContactPrimary creates new customer when customer does not exist', function () {
    // Setup: Quote with CustomerA, CustomerB does NOT exist
    $customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Create another additional contact to verify migration
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_C)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: New CustomerB created, Quote linked to CustomerB
    $newCustomer = Customer::where('email', EMAIL_CUSTOMER_B)->first();
    expect($newCustomer)->not->toBeNull()
        ->and($newCustomer->code)->toStartWith('IND-')
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($newCustomer->id)
        ->and($newCustomer->first_name)->toBe($quote->first_name)
        ->and($newCustomer->last_name)->toBe($quote->last_name)
        ->and($newCustomer->mobile_no)->toBe($quote->mobile_no);

    // Verify additional contacts were migrated
    $migratedContacts = CustomerAdditionalContact::where('customer_id', $newCustomer->id)->get();
    expect($migratedContacts)->not->toBeEmpty();
});

test('makeAdditionalContactPrimary handles insurancemarket.ae emails correctly', function () {
    // Setup: Quote with CustomerA, CustomerB exists, quote email is @insurancemarket.ae
    $customerA = Customer::factory()
        ->withEmail(EMAIL_INSURANCE_MARKET)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_INSURANCE_MARKET)
        ->create();

    // Create additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: CustomerB should not have the insurancemarket.ae email as additional contact
    $insuranceMarketContact = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_INSURANCE_MARKET,
    ])->first();

    expect($insuranceMarketContact)->toBeNull()
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});

test('makeAdditionalContactPrimary handles afia.ae emails correctly', function () {
    // Setup: Quote with CustomerA, CustomerB exists, quote email is @afia.ae
    $customerA = Customer::factory()
        ->withEmail(EMAIL_AFIA)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_AFIA)
        ->create();

    // Create additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: CustomerB should not have the afia.ae email as additional contact
    $afiaContact = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_AFIA,
    ])->first();

    expect($afiaContact)->toBeNull()
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});

test('makeAdditionalContactPrimary handles existing additional contact', function () {
    // Setup: Quote with CustomerA, CustomerB exists, and CustomerB already has CustomerA email as additional contact
    $customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Pre-create the additional contact that would be created (simulating existing)
    CustomerAdditionalContact::factory()
        ->forCustomer($customerB->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_A)
        ->create();

    $beforeCount = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->count();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: Should not create duplicate, should use firstOrCreate
    $afterCount = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->count();

    expect($afterCount)->toBe($beforeCount)
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});

test('deleteCustomerAdditionalContacts deletes all matching records', function () {
    // Setup: Multiple CustomerAdditionalContact with same key/value/customer_id
    $customer = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create multiple additional contacts with same key, value, and customer_id
    $contact1 = CustomerAdditionalContact::factory()
        ->forCustomer($customer->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    $contact2 = CustomerAdditionalContact::factory()
        ->forCustomer($customer->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    $contact3 = CustomerAdditionalContact::factory()
        ->forCustomer($customer->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    $beforeCount = CustomerAdditionalContact::where([
        'customer_id' => $customer->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->count();

    expect($beforeCount)->toBe(3);

    // Action: Call service method
    $customerService = new CustomerService;
    $result = $customerService->deleteCustomerAdditionalContacts($contact1->id);

    // Assert: All matching records deleted, correct message returned
    $afterCount = CustomerAdditionalContact::where([
        'customer_id' => $customer->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->count();

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('3 additional contact deleted.')
        ->and($afterCount)->toBe(0)
        ->and(CustomerAdditionalContact::find($contact1->id))->toBeNull()
        ->and(CustomerAdditionalContact::find($contact2->id))->toBeNull()
        ->and(CustomerAdditionalContact::find($contact3->id))->toBeNull();
});

test('deleteCustomerAdditionalContacts deletes single record and returns correct message', function () {
    // Setup: Single CustomerAdditionalContact
    $customer = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $contact = CustomerAdditionalContact::factory()
        ->forCustomer($customer->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method
    $customerService = new CustomerService;
    $result = $customerService->deleteCustomerAdditionalContacts($contact->id);

    // Assert: Record deleted, correct message returned
    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('1 additional contact deleted.')
        ->and(CustomerAdditionalContact::find($contact->id))->toBeNull();
});

test('deleteCustomerAdditionalContacts returns error when contact not found', function () {
    // Action: Call service with non-existent ID
    $customerService = new CustomerService;
    $result = $customerService->deleteCustomerAdditionalContacts(99999);

    // Assert: Returns ['success' => false, 'message' => '...']
    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Additional contact not found.');
});

test('deleteCustomerAdditionalContacts handles NULL customer_id correctly', function () {
    // Setup: Create contact with NULL customer_id
    $contact = CustomerAdditionalContact::factory()
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create(['customer_id' => null]);

    // Create another contact with same key/value but different customer_id to ensure it's not deleted
    $customer = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $otherContact = CustomerAdditionalContact::factory()
        ->forCustomer($customer->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Action: Call service method
    $customerService = new CustomerService;
    $result = $customerService->deleteCustomerAdditionalContacts($contact->id);

    // Assert: Only the NULL customer_id contact is deleted
    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('1 additional contact deleted.')
        ->and(CustomerAdditionalContact::find($contact->id))->toBeNull()
        ->and(CustomerAdditionalContact::find($otherContact->id))->not->toBeNull();
});

test('makeAdditionalContactPrimary removes advisor emails from additional contacts', function () {
    // Setup: Quote with CustomerA, CustomerB exists, CustomerA has advisor email as additional contact
    $customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    $quote = CarQuote::factory()
        ->forCustomer($customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    // Create additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();

    // Create advisor email as additional contact for CustomerA
    CustomerAdditionalContact::factory()
        ->forCustomer($customerA->id)
        ->email()
        ->withValue(EMAIL_INSURANCE_MARKET)
        ->create();

    // Action: Call service method directly
    // Reload quote to ensure customer relationship is available
    $quote->load('customer');
    $customerService = new CustomerService;
    $customerService->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, EMAIL_CUSTOMER_B, true);

    // Assert: Advisor email should be removed from CustomerB's additional contacts
    $advisorContact = CustomerAdditionalContact::where([
        'customer_id' => $customerB->id,
        'key' => GenericRequestEnum::EMAIL,
    ])->where(function ($query) {
        $query->where('value', 'like', '%@insurancemarket.ae')
            ->orWhere('value', 'like', '%@afia.ae');
    })->first();

    expect($advisorContact)->toBeNull()
        ->and($quote->fresh()->email)->toBe(EMAIL_CUSTOMER_B)
        ->and($quote->fresh()->customer_id)->toBe($customerB->id);
});
