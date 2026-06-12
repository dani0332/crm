<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\CustomerAddress;
use App\Models\CustomerMembers;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerService extends BaseService
{
    public static function getCustomerByEmail($email)
    {
        return Customer::where('email', strtolower(trim($email)))->first();
    }

    public static function getCustomerByName($first_name, $last_name)
    {
        return Customer::where('first_name', strtolower(trim($first_name)))
            ->where('last_name', strtolower(trim($last_name)))
            ->first();
    }

    public static function getUniqueCustomerByMobileNo($mobileNo)
    {
        return Customer::where('mobile_no', $mobileNo)->first();
    }

    public static function updatePolicyExpiry($email, $expiry_date)
    {
        $customer = Customer::where('email', strtolower(trim($email)))->first();
        $parsedPolicyExpiry = date('Y-m-d', strtotime(str_replace('.', '-', $expiry_date)));
        $parsedCustomerExpiry = date('Y-m-d', strtotime($customer->myalfred_expiry_date));
        if ($parsedCustomerExpiry < $parsedPolicyExpiry) {
            $customer->myalfred_expiry_date = $parsedPolicyExpiry;
            $customer->save();
        }
    }

    public static function getCustomerById($customerId)
    {
        return Customer::where('id', $customerId)->first();
    }

    public static function getCustomerIdAndCreateIfNotExists($firstName, $lastName, $email)
    {
        $customer = self::getCustomerByEmail($email);
        if ($customer) {
            return $customer->id;
        } else {
            return self::createCustomerAndGetId($firstName, $lastName, $email);
        }
    }

    public static function createCustomerAndGetId($firstName, $lastName, $email)
    {
        $existingCustomer = Customer::where('email', strtolower(trim($email)))->first();
        if (! $existingCustomer) {
            $customer = Customer::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower(trim($email)),
                'lang' => 'EN',
            ]);

            return $customer->id;
        } else {
            return false;
        }
    }

    public static function setCustomerAccess($customerId)
    {
        $customer = self::getCustomerById($customerId);
        $customer->has_alfred_access = true;
        $customer->has_reward_access = true;
        $customer->save();
    }

    public static function getAllCustomers($from, $to)
    {
        $from = date($from);
        $to = date($to);

        return Customer::whereBetween('created_at', [$from, $to])
            ->where(['has_alfred_access' => 1, 'has_reward_access' => 1])
            ->get();
    }

    public static function getValidEmailFromString($emailStr)
    {
        $customer_email = $emailStr;
        if (strpos($emailStr, ',')) {
            $strArray = explode(',', $emailStr);
            $customer_email = $strArray[0];
        }
        if (strpos($emailStr, ';')) {
            $strArray = explode(';', $emailStr);
            $customer_email = $strArray[0];
        }

        return $customer_email;
    }

    public function getAdditionalContacts($customerId, $quoteMobileNo)
    {
        $customer = $this->getCustomerById($customerId);
        $additionalContacts = CustomerAdditionalContact::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();

        if (isset($customer) && $quoteMobileNo != $customer->mobile_no && $additionalContacts->where('key', 'mobile_no')->where('value', $customer->mobile_no)->isEmpty()) {
            $customerMobileNo = (object) [
                'key' => 'mobile_no',
                'value' => isset($customer->mobile_no) ? $customer->mobile_no : '',
                'created_at' => isset($customer->created_at) ? $customer->created_at : '',
            ];
            $additionalContacts->push($customerMobileNo);
        }

        return $additionalContacts;
    }

    public function checkAdditionalEmailExist($lead, $newAdditionalEmail)
    {
        $newAdditionalEmail = strtolower($newAdditionalEmail);
        $customer = $this->getCustomerByEmail($newAdditionalEmail);
        $additionalEmail = CustomerAdditionalContact::where(['key' => GenericRequestEnum::EMAIL, 'value' => $newAdditionalEmail])
            ->first();

        if (
            $newAdditionalEmail == strtolower($lead->email)
            || $customer && $newAdditionalEmail == strtolower($customer->email)
            || $additionalEmail && $newAdditionalEmail == strtolower($additionalEmail->value)
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function checkAdditionalMobileNoExist($lead, $newAdditionalMobileNo)
    {
        $customer = $this->getUniqueCustomerByMobileNo($newAdditionalMobileNo);
        $additionalMobileNo = CustomerAdditionalContact::where(['key' => GenericRequestEnum::MOBILE_NO, 'value' => $newAdditionalMobileNo])
            ->first();

        if (
            $newAdditionalMobileNo == $lead->mobile_no
            || $customer && $newAdditionalMobileNo == $customer->mobile_no
            || $additionalMobileNo && $newAdditionalMobileNo == $additionalMobileNo->value
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function getAdditionalContactByKey($customerId, $key)
    {
        return CustomerAdditionalContact::where(['customer_id' => $customerId, 'key' => $key])->get();
    }

    public static function getCustomerByUuid($uuid)
    {
        return Customer::where('uuid', $uuid)->first();
    }

    public function makeAdditionalContactPrimary($lead, $key, $value, bool $keepExistingPrimaryEmail = true)
    {
        DB::transaction(function () use ($lead, $key, $value, $keepExistingPrimaryEmail) {
            $this->executeMakeAdditionalContactPrimary($lead, $key, $value, $keepExistingPrimaryEmail);
        });
    }

    /**
     * Performs the database writes for making an additional contact primary.
     * Called inside a transaction by makeAdditionalContactPrimary().
     */
    private function executeMakeAdditionalContactPrimary($lead, $key, $value, bool $keepExistingPrimaryEmail): void
    {
        if ($key == GenericRequestEnum::EMAIL) {
            $customer = null;
            $previousEmail = trim($lead->email);
            if ($lead->customer && ! $this->getCustomerByEmail($value)) {
                LoggerService::info('Customer additional contact primary email updated. Previous Email: '.$lead->email.' New Email: '.$value);
                $customerArray = [
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'mobile_no' => $lead->mobile_no,
                    'email' => $value,
                    'dob' => $lead->dob,
                ];
                $customer = Customer::create($customerArray);
                $customer->update(['code' => 'IND-'.$customer->id]);
                $email = trim($lead->email);

                if (str_ends_with($email, '@insurancemarket.ae') || str_ends_with($email, '@afia.ae')) {
                    $this->getFirstAdditionalContactByEmail($value, $lead->customer_id)?->delete();

                } else {

                    $getCustomerAdditionalContact = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                        ->get();
                    foreach ($getCustomerAdditionalContact as $contact) {
                        CustomerAdditionalContact::create([
                            'customer_id' => $customer->id,
                            'key' => $contact->key,
                            'value' => $contact->value,
                        ]);
                    }

                    $isExist = CustomerAdditionalContact::where('key', 'email')
                        ->where('customer_id', $lead->customer_id)
                        ->where('value', $email)
                        ->exists();

                    if ($keepExistingPrimaryEmail && ! $isExist) {
                        CustomerAdditionalContact::create([
                            'customer_id' => $customer->id,
                            'key' => GenericRequestEnum::EMAIL,
                            'value' => $email,
                        ]);
                    }
                }

                $lead->update(['customer_id' => $customer->id, 'email' => $value]);

                // REMOVE EMAIL TO MAKE PRIMARY IN ADDITIONAL CONTACT
                $this->getFirstAdditionalContactByEmail($lead->email, $lead->customer_id)?->delete();

                // ADD PRIMARY EMAIL IN ADDITIONAL CONTACT
                $removeAdvisorEmail = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->where('key', 'email')
                    ->where(function ($query) {
                        $query->where('value', 'like', '%@insurancemarket.ae')
                            ->orWhere('value', 'like', '%@afia.ae');
                    })
                    ->first();
                if (isset($removeAdvisorEmail->id)) {
                    $removeAdvisorEmail->delete();
                }
            } else {
                $customer = $this->getCustomerByEmail($value);
                if (! $customer) {
                    LoggerService::warning("Customer not found against this email: {$value}");
                    vAbort("Customer not found against this email: {$value}");
                }

                // REMOVE EMAIL TO MAKE PRIMARY IN ADDITIONAL CONTACT
                $this->getFirstAdditionalContactByEmail($value, $lead->customer_id)?->delete();

                $email = trim($lead->email);
                if (! str_ends_with($email, '@insurancemarket.ae') && ! str_ends_with($email, '@afia.ae')) {

                    // Convert lead email from primary to secondary, if user wants to keep
                    if ($keepExistingPrimaryEmail) {
                        CustomerAdditionalContact::firstOrCreate([
                            'customer_id' => $customer->id,
                            'key' => GenericRequestEnum::EMAIL,
                            'value' => trim($lead->email),
                        ]);
                    }

                    $getCustomerAdditionalContact = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                        ->get();
                    foreach ($getCustomerAdditionalContact as $contact) {
                        CustomerAdditionalContact::firstOrCreate([
                            'customer_id' => $customer->id,
                            'key' => $contact->key,
                            'value' => trim($contact->value),
                        ]);
                    }
                }
                $lead->update(['customer_id' => $customer->id, 'email' => $value]);
                // Remove @insurancemarket.ae and @afia.ae Domain Email From Additional Contact
                $removeAdvisorEmail = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                    ->where('key', 'email')
                    ->where(function ($query) {
                        $query->where('value', 'like', '%@insurancemarket.ae')
                            ->orWhere('value', 'like', '%@afia.ae');
                    })
                    ->first();
                if (isset($removeAdvisorEmail->id)) {
                    $removeAdvisorEmail->delete();
                }
            }

            if (! $keepExistingPrimaryEmail) {
                $this->getFirstAdditionalContactByEmail($previousEmail, $lead->customer_id)?->delete();
            }

        } elseif ($key == GenericRequestEnum::MOBILE_NO) {
            // REMOVE Mobile Number TO MAKE PRIMARY IN ADDITIONAL CONTACT
            $removeMobileNumber = CustomerAdditionalContact::where('customer_id', $lead->customer_id)
                ->where('value', $value)
                ->where('key', 'mobile_no')
                ->first();
            if (isset($removeMobileNumber->id)) {
                $removeMobileNumber->delete();
            }
            $isExist = CustomerAdditionalContact::where('key', 'mobile_no')
                ->where('customer_id', $lead->customer_id)
                ->where('value', $lead->mobile_no)
                ->exists();

            if (! $isExist) {
                CustomerAdditionalContact::create([
                    'customer_id' => $lead->customer_id,
                    'key' => 'mobile_no',
                    'value' => $lead->mobile_no,
                ]);
            }
            $lead->update(['mobile_no' => $value]);
            if ($lead->customer) {
                LoggerService::info('Customer additional contact primary mobile_no updated. Previous Mobile_No: '.$lead->mobile_no.' New Mobile_No: '.$value);
                $lead->customer->update(['mobile_no' => $value]);
            }
        }
    }

    /**
     * Delete customer additional contact(s) by ID.
     * Deletes all contacts matching the same key, value, and customer_id.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteCustomerAdditionalContacts(int $additionalContactId): array
    {
        $additionalContact = CustomerAdditionalContact::find($additionalContactId);
        if (! $additionalContact) {
            LoggerService::warning(__CLASS__.' fn:deleteCustomerAdditionalContacts - Additional contact not found.', [
                'additionalContactId' => $additionalContactId,
            ]);

            return ['success' => false, 'message' => 'Additional contact not found.'];
        }

        $filters = $additionalContact->only(['key', 'value', 'customer_id']);
        $matchingContacts = CustomerAdditionalContact::where($filters)->get();
        $deleteCount = $matchingContacts->count();

        DB::transaction(function () use ($matchingContacts) {
            foreach ($matchingContacts as $contact) {
                $contact->delete();
            }
        });

        @[$success, $message] = $deleteCount > 0
            ? [true, "{$deleteCount} additional contact deleted."]
            : [false, 'No additional contacts were deleted.'];

        LoggerService::info(__CLASS__.' fn:deleteCustomerAdditionalContacts', [
            ...$filters,
            'message' => $message,
        ]);

        return ['success' => $success, 'message' => $message];
    }

    private function getFirstAdditionalContactByEmail($email, $customerId)
    {
        $additionalContacts = CustomerAdditionalContact::where('customer_id', $customerId)
            ->where('value', $email)
            ->where('key', GenericRequestEnum::EMAIL)
            ->first();

        if (! $additionalContacts) {
            LoggerService::info('No additional email contact found to delete.', [
                'email' => $email,
                'customerId' => $customerId,
            ]);
        }

        return $additionalContacts;
    }

    public function getCustomerCampaignFollowups($id)
    {
        return Customer::select('id', 'email', 'campaign_followups', 'last_followup_sent_at')->where('id', $id)->first();
    }

    public function getCustomerIdByEmail(?string $email): ?int
    {
        if (empty($email)) {
            LoggerService::warning('Empty or null email provided to getCustomerIdByEmail.');

            return null;
        }

        $customer = Customer::where('email', $email)->first();

        if (! $customer) {
            LoggerService::info('Customer with the provided email not found.', ['email' => $email]);

            return null;
        }

        return $customer->id;
    }

    public function getCustomerAddressData($data)
    {
        $customerId = $data->customer_id ?? null;
        $quoteUuid = $data->uuid ?? null;
        if (! $customerId || ! $quoteUuid) {
            LoggerService::warning('Missing required data: customerId or quote UUID is not provided.', [
                'customerId' => $customerId,
                'quote_uuid' => $quoteUuid,
            ]);

            return null;
        }

        // Retrieve customer address based on customerId and quoteUuid
        $customerAddress = CustomerAddress::where('customer_id', $customerId)
            ->where('quote_uuid', $quoteUuid)
            ->first();

        if (! $customerAddress) {
            LoggerService::info('Customer address not found.', [
                'customerId' => $customerId,
                'quote_uuid' => $quoteUuid,
            ]);

            return null;
        }

        return $customerAddress;
    }

    public function createCustomerIfNotExists($customerData)
    {
        $existingCustomer = CustomerService::getCustomerByEmail($customerData['email']);

        if (! $existingCustomer) {
            $customer = Customer::create(Arr::only($customerData, ['first_name', 'last_name', 'email', 'mobile_no']));
        }

        return $customer ?? $existingCustomer;
    }

    public function addAdditionalContactsIfNotExists($customer, $additionalContacts)
    {
        // add additional emails
        if (isset($additionalContacts['additional_emails']) && count($additionalContacts['additional_emails'])) {
            foreach ($additionalContacts['additional_emails'] as $additionalEmail) {
                $isExistEmail = CustomerAdditionalContact::where('customer_id', $customer->id)
                    ->where('value', $additionalEmail)->where('key', GenericRequestEnum::EMAIL)->first();
                if (! $isExistEmail) {
                    $customer->additionalContactInfo()->create(['key' => 'email', 'value' => $additionalEmail]);
                }
            }
        }

        // add additional mobile numbers
        if (isset($additionalContacts['additional_mobiles']) && count($additionalContacts['additional_mobiles'])) {
            foreach ($additionalContacts['additional_mobiles'] as $additionalMobile) {
                $isExistMobile = CustomerAdditionalContact::where('customer_id', $customer->id)
                    ->where('value', $additionalMobile)->where('key', GenericRequestEnum::MOBILE_NO)->first();
                if (! $isExistMobile) {
                    $customer->additionalContactInfo()->create(['key' => 'mobile_no', 'value' => $additionalMobile]);
                }
            }
        }
    }

    public function getPrimaryCustomerById($id, $memberId = null)
    {
        $customer = CustomerMembers::find($id);

        return $customer && $customer->id == $memberId;
    }
}
