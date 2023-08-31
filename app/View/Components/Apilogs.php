<?php

namespace App\View\Components;

use App\Models\CarQuote;
use App\Models\CarQuotePlanDetail;
use App\Models\CarQuoteRequestDetail;
use DB;
use Illuminate\View\Component;

class Apilogs extends Component
{
    public $auditableId;
    public $auditableType;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($auditableId, $auditableType)
    {
        $this->auditableId = $auditableId;
        $this->auditableType = $auditableType;
    }

    /**
     * Get the view / contents that represent the component.
     *
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    { 
        $auditableId = $this->auditableId;
        if ($this->auditableType == CarQuote::class) {


            /*
            $uuid = CarQuote::where('id', $auditableId)->value('uuid');            
            $insurerQuery = DB::table('insurer_request_response')
            ->select('insurer_request_response.*', 'car_quote_plan_details.provider_name', DB::raw("'Insurer' as source_table"))
            ->leftJoin('car_quote_plan_details', 'insurer_request_response.provider_id', 'car_quote_plan_details.id')
            ->where('insurer_request_response.quote_uuid', $uuid);
            $axaQuery = DB::table('axa_request_response')
                ->select('*', DB::raw("'Axa' as source_table"), DB::raw("'Not Available' as provider_name"))
                ->where('car_quote_uuid', $uuid);
            $query = $qataQuery->union($aloQuery)->union($mostQuery)->orderByDesc('created_at')->get();
            */
           
            //die($uuid);

                        
            $uuid = CarQuote::where('id', $auditableId)->value('uuid');
            $query = DB::table('insurer_request_response')
            ->select('insurer_request_response.*', 'car_quote_plan_details.provider_name')
            ->leftJoin('car_quote_plan_details', 'insurer_request_response.provider_id', 'car_quote_plan_details.id')
            ->where('insurer_request_response.quote_uuid', $uuid)->orderby('insurer_request_response.created_at','desc');

            $apilogs = $query->get();
            return view('components.apilogs', compact('apilogs'));

        }        
        
    }
}
