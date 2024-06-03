<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuadrantRequest;
use App\Repositories\UserRepository;
use App\Models\Quadrant;
use App\Models\Tier;
use DB;

class QuadrantController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Quadrant::orderBy('id');
        if (request()->name) {
            $data->where('name', 'LIKE', '%'.request()->name.'%');
        }

        $quadrants =  $data->simplePaginate(10)->withQueryString();
        $quadrants->load([
            'users' => function ($users) {
                return $users->select('id', 'name');
            },
            'tiers' => function ($tier) {
                return $tier->select('id', 'name');
            },
        ]);

        return inertia('Admin/AllocationConfig/Quadrants/Index', [
            'quadrants' => $quadrants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $quadUsers = UserRepository::select('id', 'name')->where('is_active', true)->get();
        $quadTiers = Tier::select('id', 'name')->where('is_active', true)->get();

        return inertia('Admin/AllocationConfig/Quadrants/Form', [
            'quad_users' => $quadUsers,
            'quad_tiers' => $quadTiers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuadrantRequest $request)
    {
        $data = $request->except('quad_users', 'quad_tiers');

        $response = Quadrant::create($data);

        $response->users()->attach($request->quad_users);
        $response->tiers()->attach($request->quad_tiers);

        return redirect(route('quadrants.show', $response->id))->with('success', 'Quadrant added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $quadrant = Quadrant::where(['id' => $id])->first();
        $quadrant->load([
            'users' => function ($users) {
                return $users->select('id', 'name');
            },
            'tiers' => function ($tier) {
                return $tier->select('id', 'name');
            },
        ]);

        return inertia('Admin/AllocationConfig/Quadrants/Show', [
            'quadrant' => $quadrant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $quadrant = Quadrant::find($id);
        $quadUsers = UserRepository::select('id', 'name')->where('is_active', true)->get();
        $quadTiers = Tier::select('id', 'name')->where('is_active', true)->get();

        $quadrant = $quadrant->load([
            'users' => function ($users) {
                return $users->select('id', 'name');
            },
            'tiers' => function ($tier) {
                return $tier->select('id', 'name');
            },
        ]);

        return inertia('Admin/AllocationConfig/Quadrants/Form', [
            'quadrant' => $quadrant,
            'quad_users' => $quadUsers,
            'quad_tiers' => $quadTiers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuadrantRequest $request, string $id)
    {
        $data = $request->except('quad_users', 'quad_tiers');

        $quadrant = Quadrant::find($id);
        $quadrant->update($data);
        $quadrant->users()->sync($request->quad_users);
        $quadrant->tiers()->sync($request->quad_tiers);

        return redirect(route('quadrants.show', $id))->with('success', 'Quadrant updated successfully');
    }

}
