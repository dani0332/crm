<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\Permission;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

if (! defined('QUOTE_TYPE')) {
    define('QUOTE_TYPE', QuoteTypes::CAR);
}

if (! defined('EMAIL_CUSTOMER_A')) {
    define('EMAIL_CUSTOMER_A', 'customera@test.com');
}
if (! defined('EMAIL_CUSTOMER_B')) {
    define('EMAIL_CUSTOMER_B', 'customerb@test.com');
}
if (! defined('EMAIL_CUSTOMER_C')) {
    define('EMAIL_CUSTOMER_C', 'customerc@test.com');
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    // Fake the queue to prevent jobs from being dispatched during tests
    Queue::fake();

    // Create permission (events needed so Spatie's permission cache is updated)
    Permission::create([
        'name' => PermissionsEnum::DELETE_ADDITIONAL_CONTACT,
        'guard_name' => 'web',
    ]);

    $this->user = TestDataSeeder::createUser();
    $this->actingAs($this->user);

    // Create common customers (used by all tests)
    $this->customerA = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $this->customerB = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_B)
        ->create();

    // Create common quote (used by all tests)
    $this->quoteA = CarQuote::factory()
        ->forCustomer($this->customerA->id)
        ->withEmail(EMAIL_CUSTOMER_A)
        ->create();

    $this->personalQuoteId = createPersonalQuoteForCarQuote($this->quoteA);

    // Create common additional contact (used by all tests)
    $this->additionalContactB = CustomerAdditionalContact::factory()
        ->forCustomer($this->customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_B)
        ->create();
});

/**
 * Helper function to create a personal_quotes entry for a CarQuote
 * Returns the ID of the created PersonalQuote
 */
function createPersonalQuoteForCarQuote(CarQuote $quote): int
{
    $personalQuote = PersonalQuote::withoutEvents(fn () => PersonalQuote::create([
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
    ]));

    return $personalQuote->id;
}

afterEach(function () {
    Mockery::close();
});

test('switch primary email keeps existing primary email when keep_existing_primary_email is true', function () {
    // Setup: Create CustomerC and additional contact for CustomerC (only needed for this test)
    $customerC = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_C)
        ->create();

    CustomerAdditionalContact::factory()
        ->forCustomer($this->customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_C)
        ->create();

    // Action: Make HTTP POST request to make CustomerB's email primary with keep_existing_primary_email = true
    $response = $this->post("/customer-additional-contact/{$this->additionalContactB->id}/make-primary", [
        'isInertia' => true,
        'quote_id' => $this->quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => 'email',
        'value' => $this->additionalContactB->value,
        'quote_customer_id' => $this->quoteA->customer_id,
        'quote_primary_email_address' => $this->quoteA->email,
        'quote_primary_mobile_no' => $this->quoteA->mobile_no,
        'keep_existing_primary_email' => 1,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull();

    // QuoteA's email should be updated to CustomerB's email
    $switchedQuote = CarQuote::find($this->quoteA->id);
    expect($switchedQuote->email)->toBe(EMAIL_CUSTOMER_B);

    // CustomerB's additional contacts should contain CustomerA's email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $switchedQuote->customer_id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    expect($customerBAdditionalContacts)->not->toBeEmpty();
});

test('switch primary email does not keep existing primary email when keep_existing_primary_email is false', function () {
    // Setup: Create CustomerC and additional contact for CustomerC (only needed for this test)
    $customerC = Customer::factory()
        ->withEmail(EMAIL_CUSTOMER_C)
        ->create();

    CustomerAdditionalContact::factory()
        ->forCustomer($this->customerA->id)
        ->email()
        ->withValue(EMAIL_CUSTOMER_C)
        ->create();

    // Action: Make HTTP POST request to make CustomerB's email primary with keep_existing_primary_email = false
    $response = $this->post("/customer-additional-contact/{$this->additionalContactB->id}/make-primary", [
        'isInertia' => true,
        'quote_id' => $this->quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => 'email',
        'value' => $this->additionalContactB->value,
        'quote_customer_id' => $this->quoteA->customer_id,
        'quote_primary_email_address' => $this->quoteA->email,
        'quote_primary_mobile_no' => $this->quoteA->mobile_no,
        'keep_existing_primary_email' => 0,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull();

    // QuoteA's email should be updated to CustomerB's email
    $switchedQuote = CarQuote::find($this->quoteA->id);
    expect($switchedQuote->email)->toBe(EMAIL_CUSTOMER_B);

    // CustomerB's additional contacts should NOT contain CustomerA's email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $switchedQuote->customer_id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    expect($customerBAdditionalContacts)->toBeEmpty();
});

test('delete additional contact with permission deletes all matching records', function () {
    // Setup: User has DELETE_ADDITIONAL_CONTACT permission
    $this->user->syncPermissions(PermissionsEnum::DELETE_ADDITIONAL_CONTACT);

    // Count additional contacts before deletion
    $beforeCount = CustomerAdditionalContact::where([
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
        'customer_id' => $this->quoteA->customer_id,
    ])->count();

    // Action: Make HTTP POST request to delete additional contact
    $response = $this->post("/customer-additional-contact/{$this->additionalContactB->id}/delete", [
        'isInertia' => true,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull();

    // Verify record is deleted
    $recordDeleted = CustomerAdditionalContact::find($this->additionalContactB->id);
    expect($recordDeleted)->toBeNull();

    // Verify all matching records are deleted
    $afterCount = CustomerAdditionalContact::where([
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
        'customer_id' => $this->quoteA->customer_id,
    ])->count();

    expect($afterCount)->toBe(0)
        ->and($beforeCount)->toBe($afterCount + 1);
});

test('delete additional contact without permission returns error and does not delete record', function () {
    // Setup: User does NOT have DELETE_ADDITIONAL_CONTACT permission (default state)

    // Action: Make HTTP POST request to delete additional contact (without permission)
    $response = $this->post("/customer-additional-contact/{$this->additionalContactB->id}/delete", [
        'isInertia' => true,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBe('You are not authorized to delete additional contact.');

    // Verify record still exists
    $recordStillExists = CustomerAdditionalContact::find($this->additionalContactB->id);
    expect($recordStillExists)->not->toBeNull();
});

test('change primary contact via personal-quotes endpoint keeps existing primary email when keep_existing_primary_email is true', function () {
    // Action: Make HTTP PATCH request to change primary contact with keep_existing_primary_email = true
    $response = $this->patch("/personal-quotes/{$this->personalQuoteId}/change-primary-contact", [
        'isInertia' => true,
        'quote_id' => $this->quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
        'quote_customer_id' => $this->quoteA->customer_id,
        'quote_primary_email_address' => $this->quoteA->email,
        'quote_primary_mobile_no' => $this->quoteA->mobile_no,
        'keep_existing_primary_email' => 1,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull();

    // QuoteA's email should be updated to CustomerB's email
    $switchedQuote = PersonalQuote::find($this->personalQuoteId);
    expect($switchedQuote->email)->toBe(EMAIL_CUSTOMER_B);

    // CustomerB's additional contacts should contain CustomerA's email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $switchedQuote->customer_id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    expect($customerBAdditionalContacts)->not->toBeEmpty();

    // The additional contact with CustomerB's email should be removed from CustomerA
    $contactWithCustomerBEmailInCustomerA = CustomerAdditionalContact::where([
        'customer_id' => $this->customerA->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->first();

    expect($contactWithCustomerBEmailInCustomerA)->toBeNull();
});

test('change primary contact via personal-quotes endpoint does not keep existing primary email when keep_existing_primary_email is false', function () {
    // Action: Make HTTP PATCH request to change primary contact with keep_existing_primary_email = false
    $response = $this->patch("/personal-quotes/{$this->personalQuoteId}/change-primary-contact", [
        'isInertia' => true,
        'quote_id' => $this->quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
        'quote_customer_id' => $this->quoteA->customer_id,
        'quote_primary_email_address' => $this->quoteA->email,
        'quote_primary_mobile_no' => $this->quoteA->mobile_no,
        'keep_existing_primary_email' => 0,
    ]);

    // Assertions
    expect($response->isRedirect())->toBeTrue()
        ->and($response->getSession()->get('error'))->toBeNull();

    // QuoteA's email should be updated to CustomerB's email
    $switchedQuote = PersonalQuote::find($this->personalQuoteId);
    expect($switchedQuote->email)->toBe(EMAIL_CUSTOMER_B);

    // CustomerB's additional contacts should NOT contain CustomerA's email
    $customerBAdditionalContacts = CustomerAdditionalContact::where([
        'customer_id' => $switchedQuote->customer_id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_A,
    ])->get();

    expect($customerBAdditionalContacts)->toBeEmpty();

    // The additional contact with CustomerB's email should be removed from CustomerA
    $contactWithCustomerBEmailInCustomerA = CustomerAdditionalContact::where([
        'customer_id' => $this->customerA->id,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
    ])->first();

    expect($contactWithCustomerBEmailInCustomerA)->toBeNull();
});

test('change primary contact via personal-quotes endpoint returns 404 when quote not found', function () {
    // Action: Make HTTP PATCH request with non-existent quote ID
    // findOrFail will throw ModelNotFoundException which Laravel converts to 404
    $response = $this->patch('/personal-quotes/99999/change-primary-contact', [
        'isInertia' => true,
        'quote_id' => $this->quoteA->id,
        'quote_type' => QUOTE_TYPE->value,
        'key' => GenericRequestEnum::EMAIL,
        'value' => EMAIL_CUSTOMER_B,
        'quote_customer_id' => $this->quoteA->customer_id,
        'quote_primary_email_address' => $this->quoteA->email,
        'quote_primary_mobile_no' => $this->quoteA->mobile_no,
        'keep_existing_primary_email' => 1,
    ]);

    // Assertions: findOrFail throws ModelNotFoundException which results in 404
    expect($response->status())->toBe(404);
});

test('change primary contact via personal-quotes endpoint validates required fields', function () {
    // Action: Make HTTP PATCH request without required fields
    $response = $this->patch("/personal-quotes/{$this->personalQuoteId}/change-primary-contact", [
        'isInertia' => true,
        // Missing required fields: key, value, quote_id, quote_type
    ]);

    // Assertions - should return validation errors
    expect($response->status())->toBe(302) // Redirect with validation errors
        ->and($response->getSession()->has('errors'))->toBeTrue();
});
