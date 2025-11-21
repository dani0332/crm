<?php

namespace App\Http\Controllers\V2;

use App\Enums\BuyLeadSegment;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadConfigUpsertRequest;
use App\Http\Requests\BuyLeads\BuyLeadsConfigFetchRequest;
use App\Http\Requests\BuyLeads\BuyLeadsNationalitiesFetchRequest;
use App\Models\BuyLeadConfiguration;
use App\Models\BuyLeadConfigurationNationality;
use App\Models\Department;
use App\Models\Nationality;
use Illuminate\Support\Arr;

class BuyLeadConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['show']]);
    }

    public function show()
    {
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::CAR_CAT_A->value]))->values()->toArray();
        $data['nationalities'] = Nationality::withActive()->options();
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
        $isCarRevival = $request->quote_type === QuoteTypes::CAR_CAT_A->value;
        $quoteTypeId = $request->getQuoteTypeId();
        $baseQuery = BuyLeadConfiguration::query()
            ->where('department_id', $request->department_id);

        if ($isCarRevival) {
            $baseQuery->where('source', LeadSourceEnum::REVIVAL);
            $quoteTypeId = QuoteTypes::CAR->id();
        }
        $config = $baseQuery->where('quote_type_id', $quoteTypeId)->first();

        if ($isCarRevival) {
            $config?->setAttribute('nationalities',
                BuyLeadConfigurationNationality::forQuoteType($request->quote_type)
                    ->with('nationality')
                    ->get()
                    ->map(fn ($crn) => ['id' => $crn->nationality_id, 'text' => $crn->nationality->text])
            );
        }

        return response()->json(['config' => $config]);
    }

    public function fetchNationalities(BuyLeadsNationalitiesFetchRequest $request)
    {
        $nationalities = BuyLeadConfigurationNationality::forQuoteType($request->quote_type)
            ->with('nationality')
            ->get()
            ->map(fn ($crn) => ['id' => $crn->nationality_id, 'text' => $crn->nationality->text])
            ->values();

        return response()->json(['nationalities' => $nationalities]);
    }

    public function upsert(BuyLeadConfigUpsertRequest $request)
    {
        $data = $request->validated();
        $isCarRevival = $request->quote_type === QuoteTypes::CAR_CAT_A->value;

        if ($isCarRevival) {
            $data['quote_type_id'] = QuoteTypes::CAR->id();
            $data['source'] = LeadSourceEnum::REVIVAL;

            // Create/update buy lead configuration (pricing per department)
            BuyLeadConfiguration::updateOrCreate(
                [
                    'quote_type_id' => QuoteTypes::CAR->id(),
                    'department_id' => $data['department_id'],
                    'source' => LeadSourceEnum::REVIVAL,
                ],
                [
                    'value' => $data['value'],
                    'volume' => $data['volume'],
                    'segment' => $data['segment'],
                ]
            );

            // Sync nationalities per quote type (LOB-specific, independent of department)
            if (isset($data['nationalities']) && is_array($data['nationalities'])) {
                BuyLeadConfigurationNationality::syncNationalities($request->quote_type, $data['nationalities']);
            }
        } else {
            BuyLeadConfiguration::updateOrCreate(
                [
                    'quote_type_id' => $request->getQuoteTypeId(),
                    'department_id' => $data['department_id'],
                ],
                $data
            );
        }

        return to_route('admin.buy-leads.config.show');
    }
}
