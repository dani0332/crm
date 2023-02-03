<?php

namespace App\Traits;

use App\Enums\FilterTypes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

trait FilterCriteria
{
    public function scopeFilter($query, $paginate = true) {

        $filters = request()->all();

        if(sizeof($filters) && sizeof($this->filterables))
        {
            foreach ($this->filterables as $key => $operator) {

                if(isset(request()->{$key}) || $operator == FilterTypes::DATE_BETWEEN) {

                    $value = request()->{$key};

                    switch ($operator) {
                        case FilterTypes::EXACT:
                            $query->where($key, $value);
                        break;
                        case FilterTypes::FREE:
                            $query->where($key, 'like', '%' . $value . '%');
                        break;
                        case FilterTypes::DATE:
                            $date = Carbon::parse($value)->format('Y-m-d');
                            $query->whereDate($key, $date);
                        break;
                        case FilterTypes::DATE_BETWEEN:
                            if(isset(request()->{$key.'_start'}) && isset(request()->{$key.'_end'})) {
                                $startDate = Carbon::parse(request()->{$key.'_start'})->format(config('constants.DB_DATE_FORMAT_MATCH'));
                                $endDate = Carbon::parse(request()->{$key.'_end'})->format(config('constants.DB_DATE_FORMAT_MATCH'));
                                $query->whereBetween(DB::raw('date('.$key.')'), [$startDate, $endDate]);
                            }
                        break;
                    }
                }
            }
        }

        if($paginate) $query->simplePaginate();

        return $query;
    }
}
