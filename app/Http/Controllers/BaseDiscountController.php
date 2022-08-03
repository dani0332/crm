<?php

namespace App\Http\Controllers;

use App\Http\Requests\BaseDiscountRequest;
use App\Http\Resources\BaseDiscountResource;
use App\Http\Traits\VehicleTypeTrait;
use App\Models\BaseDiscount;
use DataTables;
use Illuminate\Http\Request;

class BaseDiscountController extends Controller
{
    use VehicleTypeTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, BaseDiscount $base)
    {
        $vehicleTypes = $this->getVehicleTypes();
        if ($request->ajax()) {
            $data = $base::select('discount_engine_base.*', 'vehicle_type.text as vehicle_type_text')
                ->leftjoin('vehicle_type', 'vehicle_type.id', 'discount_engine_base.vehicle_type_id')
                ->where('discount_engine_base.vehicle_type_id', '!=', null)
                ->orderBy('discount_engine_base.created_at', 'desc');
            if (! empty($request->vehicle_type)) {
                $data->where('discount_engine_base.vehicle_type_id', $request->vehicle_type);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);

            return view('basediscount.view', compact('vehicleTypes'));
        }

        return view('basediscount.view', compact('vehicleTypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $vehicleTypes = $this->getVehicleTypes();

        return view('basediscount.add', compact('vehicleTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BaseDiscountRequest $request, BaseDiscount $base)
    {
        $existingBaseDiscount = $base::where([['value_start', $request->start_value], ['value_end', $request->end_value], ['vehicle_type_id', $request->vehicle_type]])->get()->first();
        if ($existingBaseDiscount != '') {
            return redirect()->back()->with('message', 'Discount with vehicle type and values already exists.')->withInput();
        }

        $id = $base->create($request->validated())->id;

        return redirect('discount/base/'.$id)->with('success', 'Base Discount has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(BaseDiscount $base)
    {
        $basediscount = new BaseDiscountResource($base);

        return view('basediscount.show', compact('basediscount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(BaseDiscount $base)
    {
        $vehicleTypes = $this->getVehicleTypes();
        $basediscount = new BaseDiscountResource($base);

        return view('basediscount.edit', compact('basediscount', 'vehicleTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BaseDiscountRequest $request, BaseDiscount $base)
    {
        $base->update($request->validated());
        if (isset($request->return_to_view)) {
            return redirect('discount/base/'.$base->id)->with('success', 'Base Discount has been updated');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(BaseDiscount $base)
    {
        $base->delete();

        return redirect()->route('basediscount.index')->with('message', 'Base discount has been deleted');
    }
}
