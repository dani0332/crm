<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarRepairCoverageRequest;
use App\Http\Resources\CarRepairCoverageResource;
use App\Models\CarRepairCoverage;
use DataTables;
use Illuminate\Http\Request;

class CarRepairCoverageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:car-repair-coverage-list|car-repair-coverage-create|car-repair-coverage-edit|car-repair-coverage-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:car-repair-coverage-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:car-repair-coverage-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:car-repair-coverage-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CarRepairCoverage::select('*')->orderBy('sort_order', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('carrepaircoverage.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('carrepaircoverage.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carrepaircoverage.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CarRepairCoverageRequest $request, CarRepairCoverage $carrepaircoverage)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->is_active ?? 0;
        $id = $carrepaircoverage->create($validated)->id;
        if (isset($request->return_to_view)) {
            return redirect('claim/carrepaircoverage/'.$id)->with('success', 'Car Repair Coverage has been stored');
        }

        return redirect()->back()->with('success', 'Car Repair Coverage has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function show(CarRepairCoverage $carrepaircoverage)
    {
        $carrepaircoverage = new CarRepairCoverageResource($carrepaircoverage);

        return view('carrepaircoverage.show', compact('carrepaircoverage'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function edit(CarRepairCoverage $carrepaircoverage)
    {
        $carrepaircoverage = new CarRepairCoverageResource($carrepaircoverage);

        return view('carrepaircoverage.edit', compact('carrepaircoverage'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function update(CarRepairCoverageRequest $request, CarRepairCoverage $carrepaircoverage)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $carrepaircoverage->update($validated);
        if (isset($request->return_to_view)) {
            return redirect('claim/carrepaircoverage/'.$carrepaircoverage->id)->with('success', 'Car Repair Coverage has been updated');
        }

        return redirect()->back()->with('success', 'Car Repair Coverage has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CarRepairCoverage  $carRepairCoverage
     * @return \Illuminate\Http\Response
     */
    public function destroy(CarRepairCoverage $carrepaircoverage)
    {
        $carrepaircoverage->delete();

        return redirect()->route('carrepaircoverage.index')->with('message', 'Car Repair Coverage has been deleted');
    }
}
