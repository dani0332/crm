<?php

namespace App\Http\Controllers;

use App\Http\Requests\RuleRequest;
use App\Repositories\RuleRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class RulesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rules = RuleRepository::getData();
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
            'rulesTypeList' => RuleRepository::getRuleTypes(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RuleRequest $request)
    {
        $rule = RuleRepository::create($request->except(['rule_users']));

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
        $rule = RuleRepository::with('ruleType')->with('ruleUsers')->findOrFail($id);

        return inertia('Admin/AllocationConfig/Rules/Show', [
            'rule' => $rule,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rule = RuleRepository::find($id);

        return inertia('Admin/AllocationConfig/Rules/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'rulesTypeList' => RuleRepository::getRuleTypes(),
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
        $rule = RuleRepository::findOrFail($id);
        $rule->update($request->except('rule_users'));

        // Sync users
        $response = $rule->users()->sync($request->rule_users);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('rule.show', $id))->with('message', 'Rule is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rule = RuleRepository::deleteRule($id);
        if ($rule) {
            return back()->with('message', 'Rule has been deleted.');
        } else {
            return back()->with('message', 'Something went wrong.');
        }
    }
}
