<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\TeamTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuadrantRequest;
use App\Models\Quadrant;
use App\Models\Tier;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

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

        $quadrants = $data->with([
            'users' => function ($users) {
                $users->select('id', 'name');
            },
            'tiers' => function ($tiers) {
                $tiers->select('id', 'name');
            },
        ])->selectRaw('quadrants.*, GROUP_CONCAT(DISTINCT teams.name ORDER BY teams.id SEPARATOR ",") AS line_of_business')
            ->leftJoin('quad_users as qu', 'qu.quad_id', '=', 'quadrants.id')
            ->leftJoin('users as u', 'u.id', '=', 'qu.user_id')
            ->leftJoin('user_products as up', 'up.user_id', '=', 'u.id')
            ->leftJoin('teams as teams', function ($join) {
                $join->on('teams.id', '=', 'up.product_id')
                    ->where('teams.type', TeamTypeEnum::PRODUCT)
                    ->where('teams.is_active', 1);
            })
            ->groupBy('quadrants.id')
            ->simplePaginate(10)
            ->withQueryString();

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

        $data['code'] = Str::replace(' ', '_', Str::squish(Str::upper($data['name'])));

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
