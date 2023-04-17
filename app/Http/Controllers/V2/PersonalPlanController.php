<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteTypeRepository;

class PersonalPlanController extends Controller
{
    public function getList()
    {
        $query = PersonalPlanRepository::filter();

        if (! empty(request()->quote_type) && ($quoteType = QuoteTypeRepository::where('code', request()->quote_type)->first())) {
            $query->where('quote_type_id', $quoteType->id);
        }

        $plans = $query->get();

        return response()->json($plans);
    }
}
