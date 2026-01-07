<?php

namespace Tests\Helpers;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use Email;
use Illuminate\Support\Arr;

class PrimaryEmailSwitchTestDataSeeder
{
    public static function seedLookups(): array
    {
        self::createPermission();

        $customers = self::createCustomers();
        $randomCustomers = Arr::random($customers, 2);
        $quotes = self::createQuotes([array_first($customers), ...$customers, ...$randomCustomers]);
        $customerAdditionalContacts = self::createQuoteCustomerAdditionalContacts($quotes);

        return [
            'customers' => $customers,
            'quotes' => $quotes,
            'customerAdditionalContacts' => $customerAdditionalContacts,
        ];
    }

    private static function createPermission()
    {
        $db = \Illuminate\Support\Facades\DB::connection('sqlite');
        $db->table('permissions')->insert([
            'name' => PermissionsEnum::DELETE_ADDITIONAL_CONTACT,
            'guard_name' => 'web'
        ]);
    }

    private static function createCustomers(): array
    {
        $emailTypes = [
            Email::TEST,
            Email::EXAMPLE,
            Email::YOPMAIL
        ];

        $customers = [];

        foreach ($emailTypes as $emailType) {
            $overrides['email'] = getEmail($emailType);
            $customerData = PrimaryEmailSwitchTestDataBuilder::buildCustomerData($overrides);

            $db = \Illuminate\Support\Facades\DB::connection('sqlite');

            // Create Customer
            $customerId = $db->table('customer')->insertGetId([
                'emirates_id_number' => $customerData['emirates_id_number'],
                'dob' => $customerData['dob'],
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $overrides['email'],
                'mobile_no' => $customerData['mobile_no'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $customers[] = ['email' => $overrides['email'], 'customer_id' => $customerId];
        }
        return $customers;
    }

    private static function createQuotes(array $customers)
    {
        $quotes = [];

        foreach ($customers as $customer) {
            $overrides = Arr::only($customer, ['email', 'customer_id']);

            $quoteData = PrimaryEmailSwitchTestDataBuilder::buildQuoteData($overrides, QUOTE_TYPE);

            // Create Car Quote
            $db = \Illuminate\Support\Facades\DB::connection('sqlite');
            $quoteId = $db->table('car_quote_request')->insertGetId($quoteData);
            $db->table('personal_quotes')->insertGetId([...$quoteData, 'quote_type_id' => QUOTE_TYPE->id()]);

            $quotes[] = ['email' => $customer['email'], 'customer_id' => $customer['customer_id'], 'quote_id' => $quoteId];
        }

        return $quotes;
    }

    private static function createQuoteCustomerAdditionalContacts(array $quotes)
    {
        $customerAdditionalContacts = [];
        foreach ($quotes as $key => $quote) {
    
            $otherQuotes = $quotes;
    
            if ($key > 0)
                $otherQuotes = array_filter($otherQuotes, fn($q) => !($q['customer_id'] == $quote['customer_id'] && $q['email'] == $quote['email']));
    
            foreach ($otherQuotes as $contactQuote) {
    
                $customerAdditionalContact = getCustomerEmailAdditionalContacts($contactQuote['email'], $quote['customer_id']);
                if ($customerAdditionalContact->count() > 0) {
                    continue;
                }
    
                $additionalContactData = PrimaryEmailSwitchTestDataBuilder::buildCustomerAdditionalContactData($quote['customer_id'], $contactQuote['email']);
    
                $db = \Illuminate\Support\Facades\DB::connection('sqlite');
                $additionalContactId = $db->table('customer_additional_contact')->insertGetId($additionalContactData);
    
                $customerAdditionalContacts[] = [
                    'email' => $contactQuote['email'],
                    'customer_id' => $quote['customer_id'],
                    'quote_id' => $quote['quote_id'],
                    'additional_contact_id' => $additionalContactId
                ];
            };
        }
        return $customerAdditionalContacts;
    }
}