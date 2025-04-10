<?php

namespace App\Traits;

use App\Enums\DatabaseColumnsString;
use App\Enums\FilterTypes;
use Carbon\Carbon;

trait FilterCriteria
{
    public function scopeFilter($query, $paginate = true, $forTotalLeadsCount = false, $requestParams = [])
    {
        $tableName = $this->getTable();

        if(empty($requestParams)){
            $requestParams = request()->all();
        }
        $requestParams = collect($requestParams);

        $filters = $forTotalLeadsCount ?
            $requestParams->merge([
                'created_at_start' => date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')),
                'created_at_end' => now()->format(config('constants.DATE_FORMAT_ONLY')),
            ]) : $requestParams;

        if (count($filters) && isset($this->filterables) && count($this->filterables)) {
            foreach ($this->filterables as $key => $operator) {
                if (! empty($filters->get($key)) || $operator == FilterTypes::DATE_BETWEEN) {
                    $value = $filters->get($key);

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
                            if (! empty($filters->get($key.'_start')) && ! empty($filters->get($key.'_end'))) {
                                $startDate = Carbon::parse($filters->get($key.'_start'))->startOfDay();
                                $endDate = Carbon::parse($filters->get($key.'_end'))->endOfDay();
                                $query->whereBetween($tableName.'.'.$key, [$startDate, $endDate]);
                            } elseif (! empty($filters->get($key.'_time_start')) && ! empty($filters->get($key.'_time_end'))) {
                                $startDate = date('Y-m-d H:i:s', strtotime($filters->get($key.'_time_start')));
                                $endDate = date('Y-m-d H:i:s', strtotime($filters->get($key.'_time_end')));
                                $query->whereBetween($key, [$startDate, $endDate]);
                            } elseif (! empty($filters->get('policy_expiry_date')) && ! empty($filters->get('policy_expiry_date_end'))) {
                                $startDate = Carbon::parse($filters->get('policy_expiry_date'))->format('Y-m-d');
                                $endDate = Carbon::parse($filters->get('policy_expiry_date_end'))->format('Y-m-d');
                                $query->whereBetween('previous_policy_expiry_date', [$startDate, $endDate]);
                            } elseif (! empty($filters->get('last_modified_date')) && $filters->get('last_modified_date') != '') {
                                $dateArray = $filters->get('last_modified_date');
                                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                                $query->whereBetween('updated_at', [$dateFrom, $dateTo]);
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
