<?php

namespace App\Traits;

use App\Enums\DatabaseColumnsString;
use App\Enums\FilterTypes;
use Carbon\Carbon;

trait FilterCriteria
{
    use ContextAwareFiltering;
    public function scopeFilter($query, $paginate = true, $forTotalLeadsCount = false, array $requestParams = [])
    {
        $tableName = $this->getTable();
        $filters = $forTotalLeadsCount ? request()->merge([
            'created_at_start' => date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')),
            'created_at_end' => now()->format(config('constants.DATE_FORMAT_ONLY')),
        ])->all() : (! empty($requestParams) ? $requestParams : request()->all());

        // Handle parameter name variations for lead status
        $filters = $this->mapParameterVariations($filters);

        if (count($filters) && isset($this->filterables) && count($this->filterables)) {
            foreach ($this->filterables as $key => $operator) {
                if ($this->hasFilterValue($key, $filters) || $operator == FilterTypes::DATE_BETWEEN) {                 
                    $value = $this->getFilterValue($key, $filters);
                  
                    switch ($operator) {
                        case FilterTypes::EXACT:
                            if ($key == DatabaseColumnsString::PREVIOUS_QUOTE_POLICY_NUMBER_TEXT) {
                                $key = DatabaseColumnsString::PREVIOUS_QUOTE_POLICY_NUMBER;
                            }
                            if ($key == 'policy_number' || $key == 'previous_quote_policy_number') {
                                $query->where(function ($query) use ($value) {
                                    $query->where('policy_number', $value)
                                        ->orWhere('previous_quote_policy_number', $value);
                                });
                            } else {
                                $query->where($key, $value);
                            }
                            break;
                        case FilterTypes::FREE:
                            $query->where($key, 'like', '%'.$value.'%');
                            break;
                        case FilterTypes::DATE:
                            $date = Carbon::parse($value)->format('Y-m-d');
                            $query->whereDate($key, $date);
                            break;
                        case FilterTypes::IN:
                            if (is_array($value) && count($value)) {
                                $query->whereIn($key, $value);
                            }
                            break;
                        case FilterTypes::NULL_CHECK:
                            if ($value == 1) {
                                $query->whereNull($key);
                            } elseif ($value == 0) {
                                $query->whereNotNull($key);
                            }
                            break;
                        case FilterTypes::DATE_BETWEEN:
                            if ($this->hasFilterValue($key.'_start', $filters) && $this->hasFilterValue($key.'_end', $filters)) {
                                $startDate = Carbon::parse($this->getFilterValue($key.'_start', $filters))->startOfDay();
                                $endDate = Carbon::parse($this->getFilterValue($key.'_end', $filters))->endOfDay();
                                $query->whereBetween($tableName.'.'.$key, [$startDate, $endDate]);
                            } elseif ($this->hasFilterValue($key.'_time_start', $filters) && $this->hasFilterValue($key.'_time_end', $filters)) {
                                $startDate = Carbon::parse($this->getFilterValue($key.'_time_start', $filters));
                                $endDate = Carbon::parse($this->getFilterValue($key.'_time_end', $filters));
                                $query->whereBetween($key, [$startDate, $endDate]);
                            } elseif ($this->hasFilterValue('policy_expiry_date', $filters) && $this->hasFilterValue('policy_expiry_date_end', $filters)) {
                                $startDate = Carbon::parse($this->getFilterValue('policy_expiry_date', $filters))->format('Y-m-d');
                                $endDate = Carbon::parse($this->getFilterValue('policy_expiry_date_end', $filters))->format('Y-m-d');
                                $query->whereBetween('previous_policy_expiry_date', [$startDate, $endDate]);
                            } elseif ($this->hasFilterValue('last_modified_date', $filters) && $this->getFilterValue('last_modified_date', $filters) != '') {
                                $dateArray = $this->getFilterValue('last_modified_date', $filters);
                                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                                $query->whereBetween('updated_at', [$dateFrom, $dateTo]);
                            }
                            elseif($this->hasFilterValue('previous_policy_expiry_date_start', $filters) && $this->hasFilterValue('previous_policy_expiry_date_end', $filters)) {
                                $startDate = Carbon::parse($this->getFilterValue('previous_policy_expiry_date_start', $filters))->format('Y-m-d');
                                $endDate = Carbon::parse($this->getFilterValue('previous_policy_expiry_date_end', $filters))->format('Y-m-d');
                                $query->whereBetween('previous_policy_expiry_date', [$startDate, $endDate]);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        if ($paginate) {
            $query->simplePaginate();
        }

        return $query;
    }

}
