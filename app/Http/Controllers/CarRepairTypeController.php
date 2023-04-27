<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarRepairTypeRequest;
use App\Http\Resources\CarRepairTypeResource;
use App\Models\CarRepairType;
use DataTables;
use Illuminate\Http\Request;

class CarRepairTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:car-repair-type-list|car-repair-type-create|car-repair-type-edit|car-repair-type-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:car-repair-type-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:car-repair-type-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:car-repair-type-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CarRepairType::select('*')->orderBy('sort_order', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('carrepairtype.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('carrepairtype.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carrepairtype.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CarRepairTypeRequest $request, CarRepairType $carrepairtype)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $id = $carrepairtype->create($validated)->id;
        if (isset($request->return_to_view)) {
            return redirect('claim/carrepairtype/'.$id)->with('success', 'Car Repair Type has been stored');
        }

        return redirect()->back()->with('success', 'Car Repair Type has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CarRepairType  $carRepairType
     * @return \Illuminate\Http\Response
     */
    public function show(CarRepairType $carrepairtype)
    {
        $carrepairtype = new CarRepairTypeResource($carrepairtype);

        return view('carrepairtype.show', compact('carrepairtype'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CarRepairType  $carRepairType
     * @return \Illuminate\Http\Response
     */
    public function edit(CarRepairType $carrepairtype)
    {
        $carrepairtype = new CarRepairTypeResource($carrepairtype);

        return view('carrepairtype.edit', compact('carrepairtype'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CarRepairType  $carRepairType
     * @return \Illuminate\Http\Response
     */
    public function update(CarRepairTypeRequest $request, CarRepairType $carrepairtype)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $carrepairtype->update($validated);
        if (isset($request->return_to_view)) {
            return redirect('claim/carrepairtype/'.$carrepairtype->id)->with('success', 'Car Repair Type has been updated');
        }

        return redirect()->back()->with('success', 'Car Repair Type has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CarRepairType  $carRepairType
     * @return \Illuminate\Http\Response
     */
    public function destroy(CarRepairType $carrepairtype)
    {
        $carrepairtype->delete();

        return redirect()->route('carrepairtype.index')->with('message', 'Car Repair Type has been deleted');
    }
}
