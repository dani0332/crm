<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\PermissionsEnum;
use App\Enums\RuleTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\RuleRequest;
use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\Rule;
use App\Models\RuleType;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RulesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:'.PermissionsEnum::RULE_CONFIG_LIST)->only('index');
        $this->middleware('can:'.PermissionsEnum::RULE_CONFIG_LIST)->only('show');
        $this->middleware('can:'.PermissionsEnum::RULE_CONFIG_CREATE)->only(['create', 'store']);
        $this->middleware('can:'.PermissionsEnum::RULE_CONFIG_UPDATE)->only(['edit', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Rule::orderBy('created_at', 'desc');

        $data->when(request()->name, function ($query, $name) {
            return $query->where('name', 'LIKE', '%'.$name.'%');
        })
            ->when(request()->cost_per_lead, function ($query, $costPerLead) {
                return $query->where('cost_per_lead', $costPerLead);
            })
            ->when(request()->created_at && request()->created_at_end, function ($query) {
                $dateFrom = Carbon::createFromFormat('Y-m-d', request()->created_at)->startOfDay();
                $dateTo = Carbon::createFromFormat('Y-m-d', request()->created_at)->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        $rules = $data->simplePaginate(10)->withQueryString();

        $rules->load([
            'ruleUsers',
            'ruleType',
            'leadSource',
            'quoteType',
        ]);

        return inertia('Admin/AllocationConfig/Rules/Index', [
            'rules' => $rules,
        ]);
    }

    private function getLeadSourcesList()
    {
        return LeadSource::select('id', 'name')
            ->withActive()
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/AllocationConfig/Rules/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'rulesTypeList' => RuleType::select('id', 'name')->get(),
            'quoteTypes' => QuoteType::select('id', 'code as name')->get(),
            'leadSourcesList' => $this->getLeadSourcesList(),
            'ruleTypeEnumLeadSource' => RuleTypeEnum::LEAD_SOURCE,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RuleRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $rule = Rule::create($request->except(['rule_users', 'lead_source_id', 'utm_source', 'utm_campaign', 'utm_medium']));

            // Create rule detail if lead_source_id is provided
            if ($request->filled('lead_source_id')) {
                $leadSource = LeadSource::find($request->lead_source_id);

                if ($leadSource && ! $leadSource->is_applicable_for_rules) {
                    $leadSource->update(['is_applicable_for_rules' => true]);
                }

                $rule->ruleDetail()->create([
                    'lead_source_id' => $leadSource->id,
                    'utm_source' => $request->utm_source,
                    'utm_campaign' => $request->utm_campaign,
                    'utm_medium' => $request->utm_medium,
                ]);

                if ($request->filled('rule_users')) {
                    $rule->leadSources()->createMany(
                        collect($request->rule_users)->map(fn ($userId) => [
                            'lead_source_id' => $request->lead_source_id,
                            'user_id' => $userId,
                        ])->toArray()
                    );
                }
            }

            $response = $rule->users()->attach($request->rule_users);

            if (! empty($response->errors) || ! empty($response->msg)) {
                vAbort($response->msg);
            }

            return redirect(route('rule.show', $rule->id))->with('message', 'Rule is created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $rule = Rule::with(['ruleType', 'ruleUsers', 'quoteType', 'ruleDetail.leadSource'])->findOrFail($id);

        return inertia('Admin/AllocationConfig/Rules/Show', [
            'rule' => $rule,
            'ruleTypeEnumLeadSource' => RuleTypeEnum::LEAD_SOURCE,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rule = Rule::find($id);

        return inertia('Admin/AllocationConfig/Rules/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'rulesTypeList' => RuleType::select('id', 'name')->get(),
            'quoteTypes' => QuoteType::select('id', 'code as name')->get(),
            'leadSourcesList' => $this->getLeadSourcesList(),
            'ruleTypeEnumLeadSource' => RuleTypeEnum::LEAD_SOURCE,
            'rule' => $rule->load([
                'ruleUsers',
                'ruleType',
                'ruleDetail',
                'leadSource',
                'quoteType',
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RuleRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $rule = Rule::findOrFail($id);

            $rule->update($request->except(['rule_users', 'lead_source_id', 'utm_source', 'utm_campaign', 'utm_medium']));

            if ($request->filled('lead_source_id') && $request->get('rule_type') == RuleTypeEnum::LEAD_SOURCE) {
                $leadSource = LeadSource::find($request->lead_source_id);
                if ($leadSource && ! $leadSource->is_applicable_for_rules) {
                    $leadSource->update(['is_applicable_for_rules' => true]);
                }

                $rule->ruleDetail()->updateOrCreate(
                    ['rule_id' => $rule->id],
                    [
                        'lead_source_id' => $leadSource->id,
                        'utm_source' => $request->utm_source,
                        'utm_campaign' => $request->utm_campaign,
                        'utm_medium' => $request->utm_medium,
                    ]
                );

                if ($request->filled('rule_users')) {
                    $rule->leadSources()->delete();
                    $rule->leadSources()->createMany(
                        collect($request->rule_users)->map(fn ($userId) => [
                            'lead_source_id' => $request->lead_source_id,
                            'user_id' => $userId,
                        ])->toArray()
                    );
                }
            } else {
                $rule->ruleDetail()->update([
                    'lead_source_id' => null,
                    'utm_source' => null,
                    'utm_campaign' => null,
                    'utm_medium' => null,
                ]);
                $rule->leadSources()->delete();
            }

            $response = $rule->users()->sync($request->rule_users);

            if (! empty($response->errors) || ! empty($response->msg)) {
                vAbort($response->msg);
            }

            return redirect(route('rule.show', $id))->with('message', 'Rule is updated successfully.');
        });
    }

}
