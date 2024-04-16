<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuadrantRequest;
use App\Repositories\QuadrantRepository;
use App\Repositories\TierRepository;
use App\Repositories\UserRepository;

class QuadrantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $quadrants = QuadrantRepository::getData();
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
        $quadTiers = TierRepository::select('id', 'name')->where('is_active', true)->get();

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

        $response = QuadrantRepository::create($data);

        $response->users()->attach($request->quad_users);
        $response->tiers()->attach($request->quad_tiers);

        return redirect(route('quadrants.show', $response->id))->with('success', 'Quadrant added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $quadrant = QuadrantRepository::where(['id' => $id])->first();
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
        $quadrant = QuadrantRepository::find($id);
        $quadUsers = UserRepository::select('id', 'name')->where('is_active', true)->get();
        $quadTiers = TierRepository::select('id', 'name')->where('is_active', true)->get();

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

        $quadrant = QuadrantRepository::find($id);
        $quadrant->update($data);
        $quadrant->users()->sync($request->quad_users);
        $quadrant->tiers()->sync($request->quad_tiers);

        return redirect(route('quadrants.show', $id))->with('success', 'Quadrant updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = QuadrantRepository::deleteQuad($id);
        if ($deleted) {
            return redirect()->route('quadrants.index')->with('success', 'Quadrant deleted successfully');
        }
    }
}
