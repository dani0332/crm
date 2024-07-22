<?php

namespace App\Traits;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Services\CapiRequestService;
use App\Services\CustomerService;
use Illuminate\Support\Arr;

trait GenericQueriesAllLobs
{
    public function getQuoteCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($modelType)) {
            return false;
        }

        $result = $modelType::whereId($id)->value('code');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function getModelObject($quoteType)
    {
        $nameSpace = '\\App\\Models\\';
        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        return $model;
    }

    /**
     * get quote object by quote type.
     *
     * @param    $quoteType  e.g car, health etc
     * @param    $id  can be id or uuid
     * @return false|mixed
     */
    public function getQuoteObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';

        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = (is_numeric($id)) ? $model::find($id) : $model::where('uuid', $id)->first();

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * @return false|mixed
     */
    public function getQuoteObjectBy($quoteType, $id, $column = 'id')
    {
        $nameSpace = '\\App\\Models\\';

        $model = (in_array(ucwords($quoteType), newUi()) && checkPersonalQuotes(ucwords($quoteType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = $model::where($column, $id)->first();

        return (isset($quote->id)) ? $quote : false;
    }

    /**
     * get Quote Request Member Detail by Quote Type e.g health, travel etc
     *
     * @return false|mixed
     */
    public function getMemberDetailObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($quoteType).'QuoteMemberDetail';

        if (! class_exists($model)) {
            return false;
        }

        return $model::find($id);
    }

    public function getRepositoryObject($quoteType)
    {
        $repository = '\\App\\Repositories\\'.ucwords($quoteType).'QuoteRepository';

        if (! class_exists($repository)) {
            return false;
        }

        return $repository;
    }

    public function createDuplicateRecord($lob, $parentRecord)
    {
        if (! ($lob) || ! isset($parentRecord->enquiryType) || ! isset($parentRecord->id)) {
            return false;
        }
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($lob).'Quote';
        if (! class_exists($model)) {
            return false;
        }
        $dataArr = [
            'firstName' => $parentRecord->first_name,
            'lastName' => $parentRecord->last_name,
            'email' => $parentRecord->email,
            'mobileNo' => $parentRecord->mobile_no,
            'referenceUrl' => config('constants.APP_URL'),
            'source' => config('constants.SOURCE_NAME'),
        ];
        if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
            $dataArr['business_type_of_insurance_id'] = 5;
        }
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-'.strtolower($lob).'-quote', $dataArr);
        if (isset($response->message) && str_contains($response->message, 'Error')) {
            return false;
        } elseif (isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
            $record = $model::where('uuid', $response->quoteUID)->first();
            if ($record) {
                $record->parent_duplicate_quote_id = $parentRecord->code;
                $record->advisor_id = auth()->user()->id;
                if (strtolower($lob) == strtolower(quoteTypeCode::Health)) {
                    $subTeam = null;
                    if (auth()->user()->subTeam) {
                        $subTeam = auth()->user()->subTeam->name;
                    }
                    $record->health_team_type = $subTeam;
                }
                $record->save();
            }
        }
    }

    public function getCustomer($customerData)
    {
        $customer = CustomerService::getCustomerByEmail($customerData['email']);

        //create new customer if not exists
        if (! isset($customer->id)) {
            $customer = Customer::create(Arr::only($customerData, ['first_name', 'last_name', 'email', 'mobile_no']));

            // create additional emails
            if (isset($customerData['additional_emails']) && count($customerData['additional_emails'])) {
                foreach ($customerData['additional_emails'] as $additionalEmail) {
                    $customer->additionalContactInfo()->create(['key' => 'email', 'value' => $additionalEmail]);
                }
            }

            // create additional mobile nos
            if (isset($customerData['additional_mobiles']) && count($customerData['additional_mobiles'])) {
                foreach ($customerData['additional_mobiles'] as $additionalMobile) {
                    $customer->additionalContactInfo()->create(['key' => 'mobile_no', 'value' => $additionalMobile]);
                }
            }
        }

        return $customer;
    }

    public function inslyInsurances()
    {
        return [
            QuoteTypes::BIKE->value => ['Bike insurance'],
            QuoteTypes::BUSINESS->value => [
                'business interruption insurance', 'contractors all risks', 'Cyber liability', 'directors and officers liability insurance',
                'Engineering and plant insurance', 'fidelity guarantee', 'group life', 'group medical insurance', 'holiday homes',
                'livestock insurance', 'machinery breakdown insurance', 'marine cargo (individual shipment) insurance',
                'marine hull insurance', 'medical malpractice insurance', 'money insurance', 'motor fleet',
                'open cover - marine cargo insurance', 'professional indemnity insurance,property insurance',
                'public liability insurance', 'road transit (international)', 'road transit (UAE only)',
                'sme packaged insurance', 'trade credit insurance', 'workmens compensation insurance',
            ],
            QuoteTypes::CAR->value => ['casco', 'motor insurance - Comprehensive', 'motor insurance - TPL'],
            QuoteTypes::LIFE->value => ['Critical illness', 'Individual life insurance'],
            QuoteTypes::HOME->value => ['Home insurance', 'personal accident', 'home insurance'],
            QuoteTypes::TRAVEL->value => ['Inbound travel insurance', 'Outbound travel insurance'],
            QuoteTypes::HEALTH->value => ['Individual or family medical'],
            QuoteTypes::CYCLE->value => ['Pedal cycle insurance'],
            QuoteTypes::PET->value => ['Pet insurance'],
            QuoteTypes::YACHT->value => ['Yacht insurance'],
        ];
    }

    public function getQuoteCodeType($lead)
    {
        $leadCodeArray = explode('-', $lead->code);
        if (count($leadCodeArray) == 0) {
            return false;
        }

        return $leadCodeArray[0];
    }
}
