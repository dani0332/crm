<?php

namespace App\View\Components;

use App\Models\CarQuote;
use App\Models\InsurerRequestResponse;
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
            $apilogs = InsurerRequestResponse::with('insuranceProvider')
                ->select('*')
                ->where('insurer_request_response.quote_uuid', $uuid)
                ->orderByDesc('insurer_request_response.created_at')
                ->get();

            return view('components.apilogs', compact('apilogs'));
        }
    }
}
