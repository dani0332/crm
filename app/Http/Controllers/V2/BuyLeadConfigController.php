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
use App\Repositories\NationalityRepository;
use Illuminate\Support\Arr;

class BuyLeadConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.Arr::join([RolesEnum::LeadPool, RolesEnum::SeniorManagement, RolesEnum::Engineering], '|'), ['only' => ['show']]);
    }

    public function show()
    {
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::CAR_REVIVAL->value]))->values()->toArray();
        $data['nationalities'] = NationalityRepository::withActive()->get()->map(function ($nationality) {
            return [
                'value' => $nationality->id,
                'label' => $nationality->text,
            ];
        })->toArray();
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
        $isCarRevival = false;
        if ($request->quote_type == QuoteTypes::CAR_REVIVAL->value) {
            $request->merge(['quote_type' => QuoteTypes::CAR->value]);
            $isCarRevival = true;
        }
        if ($isCarRevival) {
            $config = BuyLeadConfiguration::with('nationalities')
                ->where([
                    'quote_type_id' => $request->getQuoteTypeId(),
                    'department_id' => $request->department_id,
                ])
                ->whereHas('nationalities')
                ->first();
        } else {
            $config = BuyLeadConfiguration::where([
                'quote_type_id' => $request->getQuoteTypeId(),
                'department_id' => $request->department_id,
            ])->first();
        }

        return response()->json($config);
    }

    public function upsert(BuyLeadConfigUpsertRequest $request)
    {
        $data = $request->validated();
        $isCarRevival = false;
        if ($data['quote_type'] == QuoteTypes::CAR_REVIVAL->value) {
            $data['quote_type'] = QuoteTypes::CAR->value;
            $request->merge(['quote_type' => QuoteTypes::CAR->value]);
            $isCarRevival = true;
        }
        if (! $isCarRevival) {
            $buyLeadConfiguration = BuyLeadConfiguration::updateOrCreate(
                [
                    'quote_type_id' => $request->getQuoteTypeId(),
                    'department_id' => $data['department_id'],
                ],
                $data
            );
        } else {

            // If there is an existing BuyLeadConfiguration with any nationalities, update it; otherwise, create new
            $buyLeadConfiguration = BuyLeadConfiguration::with('nationalities')
                ->where([
                    'quote_type_id' => $request->getQuoteTypeId(),
                    'department_id' => $data['department_id'],
                ])
                ->whereHas('nationalities')
                ->first();

            if ($buyLeadConfiguration) {
                $buyLeadConfiguration->fill($data);
                $buyLeadConfiguration->save();
            } else {
                $buyLeadConfiguration = BuyLeadConfiguration::updateOrCreate(
                    [
                        'quote_type_id' => $request->getQuoteTypeId(),
                        'department_id' => $data['department_id'],
                    ],
                    $data
                );
            }
            if (isset($data['nationalities']) && count($data['nationalities']) > 0) {
                $this->syncNationalities($buyLeadConfiguration, $data['nationalities']);
            }
            dd('jj');

        }

        return to_route('admin.buy-leads.config.show');
    }

    private function syncNationalities($buyLeadConfiguration, $nationalities)
    {
        $buyLeadConfiguration->nationalities()->syncWithoutDetaching($nationalities);
    }
}
