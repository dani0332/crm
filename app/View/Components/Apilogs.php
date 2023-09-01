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
            
            $uuid = CarQuote::where('id', $auditableId)->value('uuid');            
            
            $insurerQuery = DB::table('insurer_request_response')
                ->select('insurer_request_response.id','insurer_request_response.created_at',
                        'car_quote_plan_details.provider_name', DB::raw("'Insurer' as source_table"),
                        'insurer_request_response.quote_uuid as quote_uuid','insurer_request_response.call_type as call_type',
                        'insurer_request_response.status','insurer_request_response.updated_at',
                        'insurer_request_response.request','insurer_request_response.response'
                        )
                ->leftJoin('car_quote_plan_details', 'insurer_request_response.provider_id', 'car_quote_plan_details.id')
                ->where('insurer_request_response.quote_uuid', $uuid);
            
            $axaQuery = DB::table('axa_request_response')
                ->select('id','axa_request_response.created_at', DB::raw("'N/A' as provider_name"), 
                          DB::raw("'Axa' as source_table"), 'axa_request_response.car_quote_uuid as quote_uuid',
                          DB::raw("'N/A' as call_type"),'axa_request_response.status',
                          'axa_request_response.updated_at','axa_request_response.request','axa_request_response.response'
                        )->where('car_quote_uuid', $uuid);
            
            $omanQuery = DB::table('oman_request_response')
                ->select('id','oman_request_response.created_at', DB::raw("'N/A' as provider_name"), 
                        DB::raw("'Oman' as source_table"), 'oman_request_response.car_quote_uuid as quote_uuid',
                        DB::raw("'N/A' as call_type"),DB::raw("'N/A' as status"),
                        'oman_request_response.updated_at','oman_request_response.request','oman_request_response.response'
                    )->where('car_quote_uuid', $uuid);

            $qatarQuery = DB::table('qatar_request_response')
                ->select('id','qatar_request_response.created_at', DB::raw("'N/A' as provider_name"), 
                        DB::raw("'Qatar' as source_table"), 'qatar_request_response.car_quote_uuid as quote_uuid',
                        DB::raw("'N/A' as call_type"),DB::raw("'N/A' as status"),
                        'qatar_request_response.updated_at','qatar_request_response.request','qatar_request_response.response'
                    )->where('car_quote_uuid', $uuid);

            $rsaQuery = DB::table('rsa_request_response')
                ->select('id','rsa_request_response.created_at', DB::raw("'N/A' as provider_name"), 
                        DB::raw("'RSA' as source_table"), 'rsa_request_response.car_quote_uuid as quote_uuid',
                        DB::raw("'N/A' as call_type"),DB::raw("'N/A' as status"),
                        'rsa_request_response.updated_at','rsa_request_response.request','rsa_request_response.response'
                    )->where('car_quote_uuid', $uuid);

            $tokioQuery = DB::table('tokio_request_response')
                ->select('id','tokio_request_response.created_at', DB::raw("'N/A' as provider_name"), 
                        DB::raw("'Tokio' as source_table"), 'tokio_request_response.car_quote_uuid as quote_uuid',
                        DB::raw("'N/A' as call_type"),DB::raw("'N/A' as status"),
                        'tokio_request_response.updated_at','tokio_request_response.request','tokio_request_response.response'
                    )->where('car_quote_uuid', $uuid);   
            
            
            $query  =   $insurerQuery->union($axaQuery)->union($omanQuery)
                        ->union($qatarQuery)->union($rsaQuery)
                        ->union($tokioQuery)->orderByDesc('created_at');
            $apilogs = $query->get();
            return view('components.apilogs', compact('apilogs'));

        }
    }
}
