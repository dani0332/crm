<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\TierRequest;
use App\Repositories\TierRepository;
use App\Repositories\UserRepository;

class TiersController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiers = TierRepository::getData();
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

        $tier = TierRepository::create($request->except('tier_user'));

        // Attaching users
        $response = $tier->users()->attach($request->tier_user);

        if (!empty($response->errors) || !empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('tiers.show', $tier->id))->with('message', 'Tier is created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(String $id)
    {
        $tier = TierRepository::find($id);
        return inertia('Admin/AllocationConfig/Tiers/Show', [
            'tier' => $tier
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(String $id)
    {
        $tier = TierRepository::find($id);
        return inertia('Admin/AllocationConfig/Tiers/Form', [
            'usersList' => UserRepository::select('id', 'name')->where('is_active', true)->get(),
            'tier' => $tier->load('users')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TierRequest $request, string $id)
    {
        $tier = TierRepository::findOrFail($id);
        $tier->update($request->except('tier_user'));

        // Sync users
        $response =  $tier->users()->sync($request->tier_user);

        if (!empty($response->errors) || !empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('tiers.show', $id))->with('message', 'Tier is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tier = TierRepository::deleteTier($id);
        if ($tier) {
            return back()->with('message', 'Tier has been deleted.');
        } else {
            return back()->with('message', 'Something went wrong.');
        }
    }
}
