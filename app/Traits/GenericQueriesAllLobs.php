<?php

namespace App\Traits;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Services\CapiRequestService;
use App\Services\QuoteDocumentService;
use League\CommonMark\Extension\SmartPunct\Quote;

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
     * @param  $quoteType  e.g car, health etc
     * @param  $id  can be id or uuid
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

    public function inslyInsurances()
    {
        return [
            QuoteTypes::BIKE->value => ['Bike insurance'],
            QuoteTypes::BUSINESS->value => [
                'Business interruption insurance', 'Contractors all risks', 'Cyber liability', 'Directors and officers liability insurance',
                'Engineering and plant insurance', 'Fidelity guarantee', 'Group life', 'Group medical insurance', 'Holiday homes',
                'Livestock insurance', 'Machinery breakdown insurance', 'Marine cargo (individual shipment) insurance',
                'Marine hull insurance', 'Medical malpractice insurance', 'Money insurance', 'Motor fleet',
                'Open cover - marine cargo insurance', 'Professional indemnity insurance,Property insurance',
                'Public liability insurance', 'Road transit (international)', 'Road transit (UAE only)',
                'SME packaged insurance', 'Trade credit insurance', 'Workmens compensation insurance',
            ],
            QuoteTypes::CAR->value => ['Casco', 'Motor insurance - Comprehensive', 'Motor insurance - TPL'],
            QuoteTypes::LIFE->value => ['Critical illness', 'Individual life insurance'],
            QuoteTypes::HOME->value => ['Home insurance', 'Personal accident'],
            QuoteTypes::TRAVEL->value => ['Inbound travel insurance', 'Outbound travel insurance'],
            QuoteTypes::HEALTH->value => ['Individual or family medical'],
            QuoteTypes::CYCLE->value => ['Pedal cycle insurance'],
            QuoteTypes::PET->value => ['Pet insurance'],
            QuoteTypes::YACHT->value => ['Yacht insurance'],
        ];
    }

    public function bookPolicyPayload($record, $quoteType, $payments, $quoteDocuments)
    {
        $insuranceProviderLeadCount = $insuranceProviderCode = '';
        if ($payments->first()) {
            $insurance_provider_id = $payments[0]['insurance_provider_id'];
            $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
            $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();
        }
        $bPDetails['brokerInvoiceNo'] = $insuranceProviderCode.$insuranceProviderLeadCount;
        $bPDetails['invoiceDescription'] = $insuranceProviderCode.'-'.$quoteType.'-'.$record->policy_number;
        $bPDetails['sendButton'] = false;
        $bPDetails['editButton'] = false;
        $bPDetails['sendPolicyType'] = null;
        $bPDetails['text'] = '';
        // check if policy details are filled & all required documents are uploaded then show send policy button to customer & show edit button &  send policy to sage
        if ($this->isFilledPolicyDetails($quoteType, $record)) {
            if (! empty($quoteDocuments)) {
                if ($this->isAllRequiredDocumentAreUploaded($quoteDocuments, $quoteType)) {
                    $bPDetails['sendButton'] = true;
                    $bPDetails['text'] = 'Send Policy To Customer';
                    $bPDetails['sendPolicyType'] = 'customer';
                }
                if ($bPDetails['sendButton']) {
                    $taxDocuments = app(DocumentTypeRepository::class)->getTaxDocumentsCode($quoteType);
                    $taxDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $taxDocuments)->groupBy('document_type_code')->count();
                    if ($taxDocumentsCount == count($taxDocuments)) {
                        $bPDetails['text'] = 'Send Policy';
                        $bPDetails['editButton'] = true;
                        $bPDetails['sendPolicyType'] = 'sage';
                    }
                }
            }
        }

        return $bPDetails;
    }

    public function getQuoteCodeType($lead)
    {
        $leadCodeArray = explode('-', $lead->code);
        if (count($leadCodeArray) == 0) {
            return false;
        }

        return $leadCodeArray[0];
    }

    public function updateQuoteStatus($type, $id)
    {
        if ($type == 'send-update') {
            return true;
        }
        if (request()->has('quote_type')) {
            $type = request()->quote_type;
        }
        $quote = $this->getQuoteObject($type, $id);
        if ($quote->quote_status_id != QuoteStatusEnum::PolicySentToCustomer) {
            if ($this->isFilledPolicyDetails($type, $quote)) {
                $quoteDocuments = (new QuoteDocumentService())->getQuoteDocuments($type, $id);
                if ($this->isAllRequiredDocumentAreUploaded($quoteDocuments, $type)) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                        'policy_issuance_status_id' => null,
                        'policy_issuance_status_other' => '',
                    ]);
                }
            }
        }
    }

    private function isFilledPolicyDetails($type, $quote)
    {
        if (! empty($quote->policy_number) && ! empty($quote->policy_issuance_date) && ! empty($quote->policy_start_date) && ! empty($quote->renewal_expiry_date) && $quote->price_with_vat > 0) {
            if (in_array(ucfirst($type), [QuoteTypes::CAR->value, QuoteTypes::BIKE->value])) {
                if (! empty($quote->insurer_quote_number)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function isAllRequiredDocumentAreUploaded($quoteDocuments, $quoteType)
    {
        $documentTypeCodes = app(DocumentTypeRepository::class)->getSendPolicyDocumentCodes($quoteType);
        $quoteDocumentsCount = collect($quoteDocuments)->whereIn('document_type_code', $documentTypeCodes)->groupBy('document_type_code')->count();

        return $quoteDocumentsCount == count($documentTypeCodes);
    }
}
