<?php

namespace App\View\Components;

use App\Models\CarQuote;
use App\Models\CarQuotePlanDetail;
use App\Models\CarQuoteRequestDetail;
use DB;
use Illuminate\View\Component;

class Auditable extends Component
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

        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->where('auditable_id', $this->auditableId)
            ->where('auditable_type', $this->auditableType);

        if ($this->auditableType == CarQuote::class) {

            $query->addSelect('car_quote_plan_details.plan_name', 'car_quote_plan_details.provider_name')
                ->leftJoin('car_quote_plan_details', function ($join) {
                    $join->on('audits.auditable_id', '=', 'car_quote_plan_details.id')
                        ->where('audits.auditable_type', CarQuotePlanDetail::class);
                });

            $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $auditableId)->with('carQuote')->first();

            $query->orWhere(function ($q) use ($carQuoteDetail) {
                $q->where('auditable_type', CarQuoteRequestDetail::class)->where('auditable_id', $carQuoteDetail->id);
            });

            $query->orWhere(function ($q) use ($carQuoteDetail) {
                $carQuotePlanDetailIds = CarQuotePlanDetail::where('quote_uuid', $carQuoteDetail->carQuote->uuid)->get()->pluck('id')->toArray();
                $q->where('auditable_type', CarQuotePlanDetail::class)->whereIn('auditable_id', $carQuotePlanDetailIds);
            });
        }

        $audits = $query->get();

        return view('components.auditable', compact('audits'));
    }
}
