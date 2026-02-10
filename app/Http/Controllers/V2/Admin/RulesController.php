<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RuleRequest;
use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\Rule;
use App\Models\RuleType;
use App\Repositories\UserRepository;
use Carbon\Carbon;

class RulesController extends Controller
{
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
            // ->applicableForRules()
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RuleRequest $request)
    {
        $rule = Rule::create($request->except(['rule_users', 'lead_source_id', 'utm_source', 'utm_campaign', 'utm_medium']));

        // Create rule detail if lead_source_id is provided
        if ($request->filled('lead_source_id')) {
            $rule->ruleDetail()->create([
                'lead_source_id' => $request->lead_source_id,
                'utm_source' => $request->utm_source,
                'utm_campaign' => $request->utm_campaign,
                'utm_medium' => $request->utm_medium,
            ]);

            // Create rule_lead_sources records for each user using batch insert
            if ($request->filled('rule_users')) {
                $rule->leadSources()->createMany(
                    collect($request->rule_users)->map(fn ($userId) => [
                        'lead_source_id' => $request->lead_source_id,
                        'user_id' => $userId,
                    ])->toArray()
                );
            }
        }

        // Attaching users
        $response = $rule->users()->attach($request->rule_users);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('rule.show', $rule->id))->with('message', 'Rule is created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $rule = Rule::with(['ruleType', 'ruleUsers', 'quoteType', 'ruleDetail.leadSource'])->findOrFail($id);

        return inertia('Admin/AllocationConfig/Rules/Show', [
            'rule' => $rule,
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
        $rule = Rule::findOrFail($id);

        $rule->update($request->except(['rule_users', 'lead_source_id', 'utm_source', 'utm_campaign', 'utm_medium']));

        // Update or create rule detail if lead_source_id is provided
        if ($request->filled('lead_source_id')) {
            $rule->ruleDetail()->updateOrCreate(
                ['rule_id' => $rule->id],
                [
                    'lead_source_id' => $request->lead_source_id,
                    'utm_source' => $request->utm_source,
                    'utm_campaign' => $request->utm_campaign,
                    'utm_medium' => $request->utm_medium,
                ]
            );

            // Sync rule_lead_sources records using relationship
            if ($request->filled('rule_users')) {
                // Delete existing rule_lead_sources for this rule
                $rule->leadSources()->delete();

                // Batch create new rule_lead_sources records
                $rule->leadSources()->createMany(
                    collect($request->rule_users)->map(fn ($userId) => [
                        'lead_source_id' => $request->lead_source_id,
                        'user_id' => $userId,
                    ])->toArray()
                );
            }
        } else {
            // If changing from lead source to another type, clean up related data
            // If lead_source_id is not provided, remove all rule_lead_sources using relationship
            $rule->ruleDetail()->delete();
            $rule->leadSources()->delete();
        }

        // Sync users
        $response = $rule->users()->sync($request->rule_users);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('rule.show', $id))->with('message', 'Rule is updated successfully.');
    }

}
