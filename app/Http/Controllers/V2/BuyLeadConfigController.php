<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadConfigUpsertRequest;
use App\Http\Requests\BuyLeads\BuyLeadsConfigFetchRequest;
use App\Models\BuyLeadConfiguration;
use App\Models\Department;
use Illuminate\Support\Arr;

class BuyLeadConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['upsert']]);
    }

    public function show()
    {
        $data['lobs'] = QuoteTypes::withLabels();
        $data['departments'] = Department::where('is_active', true)->get()
            ->map(function ($department) {
                return [
                    'value' => $department->id,
                    'label' => $department->name,
                ];
            })->toArray();

        return inertia('Admin/BuyLeads/Config/Show', $data);
    }

    public function fetch(BuyLeadsConfigFetchRequest $request)
    {
        $config = BuyLeadConfiguration::where([
            'quote_type_id' => $request->getQuoteTypeId(),
            'department_id' => $request->department_id,
        ])->first();

        return response()->json(['config' => $config]);
    }

    public function upsert(BuyLeadConfigUpsertRequest $request)
    {
        $data = $request->validated();

        $config = BuyLeadConfiguration::updateOrCreate(
            [
                'quote_type_id' => $request->getQuoteTypeId(),
                'department_id' => $data['department_id'],
            ],
            $data
        );

        return response()->json(['config' => $config]);
    }
}
