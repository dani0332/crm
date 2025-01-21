<?php

namespace App\Http\Controllers;

use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use Illuminate\Http\Request;

class RawQueryController extends Controller
{
    public function show(Request $request)
    {
        $nameSpace = 'App\\Models\\';
        $modelType = (checkPersonalQuotes(ucwords($request->modelType)))
         ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($request->modelType).'Quote';

        $fieldsMap = [
            HealthQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'plan_id', 'nationality_id', 'source', 'lead_allocation_failed_at', 'sic_flow_enabled', 'sic_advisor_requested', 'created_at'],
            HomeQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'pa_id', 'nationality_id'],
            TravelQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'plan_id', 'nationality_id', 'source', 'lead_allocation_failed_at', 'sic_flow_enabled', 'sic_advisor_requested', 'created_at'],
            PersonalQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'plan_id', 'nationality_id'],
            LifeQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'pa_id', 'nationality_id', 'source', 'lead_allocation_failed_at', 'created_at'],
            CarQuote::class => ['id', 'uuid', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'plan_id', 'nationality_id', 'source', 'is_renewal_tier_email_sent', 'lead_allocation_failed_at', 'sic_flow_enabled', 'sic_advisor_requested', 'created_at'],
            BusinessQuote::class => ['id', 'customer_id', 'quote_status_id', 'payment_status_id', 'advisor_id', 'pa_id', 'nationality_id'],
        ];

        if (isset($fieldsMap[$modelType])) {
            $entity = $modelType::select($fieldsMap[$modelType])
                ->where('uuid', $request->code)
                ->first();

            return response()->json(['record' => $entity]);
        }

        // Optionally handle cases where $modelType is not in the map
        return response()->json(['error' => 'Invalid model type'], 400);

    }
}
