<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TierRequest;
use App\Models\Tier;
use App\Repositories\UserRepository;
use Carbon\Carbon;

class TierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $data = Tier::orderBy('created_at', 'desc');

        $data->when(request()->name, function ($query, $name) {
            return $query->where('name', 'LIKE', '%'.$name.'%');
        })
            ->when(request()->min_price, function ($query, $minPrice) {
                return $query->where('min_price', $minPrice);
            })
            ->when(request()->max_price, function ($query, $maxPrice) {
                return $query->where('max_price', $maxPrice);
            })
            ->when(request()->cost_per_lead, function ($query, $costPerLead) {
                return $query->where('cost_per_lead', $costPerLead);
            })
            ->when(request()->created_at && request()->created_at_end, function ($query) {
                $dateFrom = Carbon::createFromFormat('Y-m-d', request()->created_at)->startOfDay();
                $dateTo = Carbon::createFromFormat('Y-m-d', request()->created_at)->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        $tiers = $data->simplePaginate(10)->withQueryString();

        return inertia('Admin/AllocationConfig/Tiers/Index', [
            'tiers' => $tiers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/AllocationConfig/Tiers/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TierRequest $request)
    {

        $tier = Tier::create($request->except('tier_user'));

        $response = $tier->users()->attach($request->tier_user);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('tiers.show', $tier->id))->with('message', 'Tier is created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tier = Tier::find($id);

        return inertia('Admin/AllocationConfig/Tiers/Show', [
            'tier' => $tier,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tier = Tier::find($id);

        return inertia('Admin/AllocationConfig/Tiers/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'tier' => $tier->load('users'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TierRequest $request, string $id)
    {
        $tier = Tier::findOrFail($id);
        $tier->update($request->except('tier_user'));

        $response = $tier->users()->sync($request->tier_user);

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('tiers.show', $id))->with('message', 'Tier is updated successfully.');
    }

}
