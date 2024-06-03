<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RuleRequest;
use App\Models\Rule;
use App\Models\RuleType;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        ]);

        return inertia('Admin/AllocationConfig/Rules/Index', [
            'rules' => $rules,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/AllocationConfig/Rules/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'rulesTypeList' => RuleType::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RuleRequest $request)
    {
        $rule = Rule::create($request->except(['rule_users']));

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
        $rule = Rule::with('ruleType')->with('ruleUsers')->findOrFail($id);

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
            'rule' => $rule->load([
                'ruleUsers',
                'ruleType',
                'leadSource',
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rule = Rule::findOrFail($id);
        $rule->update($request->except('rule_users'));

        // Sync users
        $response = $rule->users()->sync($request->rule_users);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('rule.show', $id))->with('message', 'Rule is updated successfully.');
    }

}
