<?php

namespace App\Services;
use App\Models\Customer;
use App\Models\Reward;
use Illuminate\Support\Facades\DB;

class LeadsService extends BaseService
{

    public function getLeadListWithFilter($filters = []){


        DB::enableQueryLog();
        $response = DB::table('reward')
        ->where(function($query) use($filters) {
            foreach($filters as $key => $value) {
                $query->where($key, $value);
            }
        })
        ->get();

       dd(DB::getQueryLog());exit;
        //dd($response);exit;
        //$response = Reward::get();
        return $response;
    }

}
