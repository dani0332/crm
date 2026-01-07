<?php

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\CustomerController;
use App\Models\CustomerAdditionalContact;
use App\Models\CarQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\PrimaryEmailSwitchTestDataSeeder;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    PrimaryEmailSwitchTestDataSeeder::seedLookups();

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
    Cache::flush();
});

const QUOTE_TYPE = QuoteTypes::CAR;

enum Email {
    case TEST;
    case EXAMPLE;
    case YOPMAIL;
}

function getEmail(Email $email)
{
    return match ($email) {
        Email::TEST => 'test@testing.com',
        Email::EXAMPLE => 'test@example.com',
        Email::YOPMAIL => 'test@yopmail.com',
        default => throw new Exception('Email not found'. $email),
    };
}

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
    return app(CustomerController::class)->deleteAdditionalContact($customerAdditionalContactId, new Request(['isInertia' => true]));
}

function makePrimaryEmailRequest($quoteA, $switchWithAdditionalContacts, $keepExistingPrimaryEmail) {
    
    $payload = [
        'isInertia' => true,
        'quote_id' => $quoteA->id,
        'quote_type' => QUOTE_TYPE->name,
        'key' => 'email',
        'value' => $switchWithAdditionalContacts->value,
        'quote_customer_id' => $quoteA->customer_id,
        'quote_primary_email_address' => $quoteA->email,
        'quote_primary_mobile_no' => $quoteA->mobile_no,
        'keep_existing_primary_email' => $keepExistingPrimaryEmail ? 1 : 0,
    ];
    $request = new Request($payload);
    return app(CustomerController::class)->makeAdditionalContactPrimary($request);
}

function getQuoteData(): array
{
    $grouped = CarQuote::all()->groupBy('email');

    return [
        $grouped->get(getEmail(Email::TEST))?->toArray() ?? [],
        $grouped->get(getEmail(Email::EXAMPLE))?->toArray() ?? [],
        $grouped->get(getEmail(Email::YOPMAIL))?->toArray() ?? [],
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
