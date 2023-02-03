<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

trait FilterCriteria
{
    public function scopeFilter($query, $paginate = true) {


        $filters = request()->all();

        if(sizeof($filters) && sizeof($this->filterable))
        {
            foreach ($this->filterable as $key => $operator) {

                if(isset(request()->{$key}) || $operator == 'dateBetween') {

                    $value = request()->{$key};

                    switch ($operator) {
                        case 'exact':
                            $query->where($key, $value);
                        break;
                        case 'free':
                            $query->where($key, 'like', '%' . $value . '%');
                        break;
                        case 'date':
                            $date = Carbon::parse($value)->format('Y-m-d');
                            $query->whereDate($key, $date);
                        break;
                        case 'dateBetween':
                            if(isset(request()->{$key.'_start'}) && isset(request()->{$key.'_end'})) {
                                $startDate = Carbon::parse(request()->{$key.'_start'})->format('Y-m-d');
                                $endDate = Carbon::parse(request()->{$key.'_end'})->format('Y-m-d');
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
