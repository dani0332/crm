<?php

namespace App\Http\Controllers\V2;

use App\Enums\BuyLeadSegment;
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
        $this->middleware('role:'.Arr::join([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['show']]);
    }

    public function show()
    {
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value]))->values()->toArray();
        $data['segments'] = BuyLeadSegment::withLabels();
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
        $data['config'] = BuyLeadConfiguration::where([
            'quote_type_id' => $request->getQuoteTypeId(),
            'department_id' => $request->department_id,
        ])->first();

        return response()->json($data);
    }

    public function upsert(BuyLeadConfigUpsertRequest $request)
    {
        $data = $request->validated();

        BuyLeadConfiguration::updateOrCreate(
            [
                'quote_type_id' => $request->getQuoteTypeId(),
                'department_id' => $data['department_id'],
            ],
            $data
        );

        return to_route('admin.buy-leads.config.show');
    }
}
