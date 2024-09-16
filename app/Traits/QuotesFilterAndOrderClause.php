<?php

namespace App\Traits;

use App\Enums\IMCRMSearchTypesEnum;

trait QuotesFilterAndOrderClauseTrait
{
    public function addSearchClauses($model, $request, $query)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $searchProperties = $model->searchProperties;
        foreach ($searchProperties as $searchProperty) {
            //            if (ucwords($model->modelType) == 'Car' && in_array($searchProperty, ['created_at', 'policy_expiry_date', 'advisor_assigned_date'])) {
            //            }
            if (isset($request->$searchProperty)) {
                $prefix = $this->{ 'get'.ucwords($model->modelType).'QuoteQueryPrefix'}($searchProperty);
                $propertyMetaData = $model->properties[$searchProperty];
                switch ($propertyMetaData) {
                    case str_contains($propertyMetaData, IMCRMSearchTypesEnum::LIKE_SEARCH):
                        $query = $query->where($prefix.$searchProperty, 'like', '%'.$request->$searchProperty.'%');
                        break;
                    case str_contains($propertyMetaData, IMCRMSearchTypesEnum::EQUAL_SEARCH):
                        $query = $query->where($prefix.$searchProperty, $request->$searchProperty);
                        break;
                    case str_contains($propertyMetaData, IMCRMSearchTypesEnum::DATE_RANGE):
                        $dateFrom = Carbon::createFromFormat($dateFormat, $request[$searchProperty])->startOfDay()->toDateTimeString();
                        $dateTo = Carbon::createFromFormat($dateFormat, $request[$searchProperty.'_end'])->endOfDay()->toDateTimeString();
                        $query = $query->whereBetween($prefix.$searchProperty, [$dateFrom, $dateTo]);
                        break;
                    case str_contains($propertyMetaData, IMCRMSearchTypesEnum::MULTI_SEARCH):
                        $query = $query->whereIn($prefix.$searchProperty, $request->$searchProperty);
                        break;
                    default:
                        break;
                }
            }
        }

        return $query;
    }

    private function getCarQuoteQueryPrefix($item)
    {
        switch ($item) {
            case 'advisor_assigned_date':
                return 'cqrd.advisor_assigned_date';
                break;
            case 'created_at':
                return 'cqr.created_at';
                break;
            case 'updated_at':
                return 'cqr.updated_at';
                break;
            case 'next_followup_date':
                return 'cqrd.next_followup_date';
                break;
            case 'uae_license_held_for':
                return 'ulhf';
                break;
            case 'car_make':
                return 'cmake';
                break;
            case 'payment_status':
                return 'ps';
                break;
            case 'car_model':
                return 'cmodel';
                break;
            case 'nationality':
                return 'n';
                break;
            case 'emirates':
                return 'e';
                break;
            case 'insurance_provider':
                return 'ip';
                break;
            case 'claim_history':
                return 'ch';
                break;
            case 'car_type_insurance':
                return 'cti';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'plan':
                return 'cp';
                break;
            case 'car_plan_provider':
                return 'cpip';
                break;
            default:
                return 'cqr';
                break;
        }
    }
}
