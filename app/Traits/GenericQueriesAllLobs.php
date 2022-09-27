<?php

namespace App\Traits;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Services\CapiRequestService;

trait GenericQueriesAllLobs
{
    public function getQuoteCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType.'Quote';
        $result = $modelType::whereId($id)->value('code');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * get quote object by quote type
     *
     * @param $quoteType e.g car, health etc
     * @param $id can be id or uuid
     * @return false|mixed
     */
    public function getQuoteObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $model = $nameSpace.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        $quote = (intval($id)) ? $model::find($id) : $model::where('uuid', $id)->first();

        return (isset($quote->id)) ? $quote : false;
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
        switch (strtolower($lob)) {
            case strtolower(quoteTypeCode::Business):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-business-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Health):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Home):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Car):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-car-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Travel):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Pet):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-pet-quote', $dataArr);
                break;
            case strtolower(quoteTypeCode::Life):
                $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $dataArr);
                break;
            default:
                false;
        }
        if (isset($response->message) && str_contains($response->message, 'Error')) {
            return false;
        } elseif (isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
            $record = $model::where('uuid', $response->quoteUID)->first();
            if ($record) {
                $record->parent_duplicate_quote_id = $parentRecord->code;
                $record->save();
            }
        }
    }
}
