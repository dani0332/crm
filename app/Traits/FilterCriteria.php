<?php

namespace App\Traits;

use App\Enums\DatabaseColumnsString;
use App\Enums\FilterTypes;
use Carbon\Carbon;

trait FilterCriteria
{
    public function scopeFilter($query, $paginate = true, $forTotalLeadsCount = false)
    {
        $tableName = $this->getTable();
        $filters = $forTotalLeadsCount ? request()->merge([
            'created_at_start' => date(config('constants.DATE_FORMAT_ONLY'), strtotime('-30 days')),
            'created_at_end' => now()->format(config('constants.DATE_FORMAT_ONLY')),
        ])->all() : request()->all();

        if (count($filters) && isset($this->filterables) && count($this->filterables)) {
            foreach ($this->filterables as $key => $operator) {
                if (isset(request()->{$key}) || $operator == FilterTypes::DATE_BETWEEN) {
                    $value = request()->{$key};

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
                            if (isset(request()->{$key.'_start'}) && isset(request()->{$key.'_end'})) {
                                $startDate = date('Y-m-d 00:00:00', strtotime(request()->{$key.'_start'}));
                                $endDate = date('Y-m-d 23:59:59', strtotime(request()->{$key.'_end'}));
                                $query->whereBetween($tableName.'.'.$key, [$startDate, $endDate]);
                            } elseif (isset(request()->{$key.'_time_start'}) && isset(request()->{$key.'_time_end'})) {
                                $startDate = date('Y-m-d H:i:s', strtotime(request()->{$key.'_time_start'}));
                                $endDate = date('Y-m-d H:i:s', strtotime(request()->{$key.'_time_end'}));
                                $query->whereBetween($key, [$startDate, $endDate]);
                            } elseif (isset(request()->{'policy_expiry_date'}) && isset(request()->{'policy_expiry_date_end'})) {
                                $startDate = date('Y-m-d H:i:s', strtotime(request()->{'policy_expiry_date'}));
                                $endDate = date('Y-m-d H:i:s', strtotime(request()->{'policy_expiry_date_end'}));
                                $query->whereBetween('previous_policy_expiry_date', [$startDate, $endDate]);
                            } elseif (isset(request()->last_modified_date) && request()->last_modified_date != '') {
                                $dateArray = request()->{'last_modified_date'};
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
