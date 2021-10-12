<?php

namespace App\Http\Controllers;

use App\Models\BaseDiscount;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Auth;
use DataTables;
use Illuminate\Support\Facades\Log;

class BaseDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vehicleTypes = VehicleType::where('is_active', '=', 1)->whereRaw('text = category')->orderBy('created_at', 'desc')->get();
        if ($request->ajax()) {

            $data = BaseDiscount::select('discount_engine_base.*', 'vehicle_type.text as vehicle_type_text')
                ->leftjoin('vehicle_type', 'vehicle_type.id', 'discount_engine_base.vehicle_type_id')
                ->where('discount_engine_base.vehicle_type_id', '!=', null)
                ->orderBy('discount_engine_base.created_at', 'desc');
            if (!empty($request->vehicle_type)) {
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
        $vehicleTypes = VehicleType::where('is_active', '=', 1)->whereRaw('text = category')->orderBy('created_at', 'desc')->get();
        return view('basediscount.add', compact('vehicleTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'start_value' => 'required|numeric|min:0',
            'vehicle_type' => 'required',
            'non_agency' => 'required|numeric|min:0',
            'agency' => 'required|numeric|min:0',
        ]);

        $existingBaseDiscount = BaseDiscount::where([['value_start', $request->start_value], ['value_end', $request->end_value], ['vehicle_type_id', $request->vehicle_type]])->get()->first();
        if ($existingBaseDiscount != '') {
            return redirect()->back()->with('message', 'Discount with vehicle type and values already exists.')->withInput();
        }
        $baseDiscount = new BaseDiscount();
        $baseDiscount->value_start = $request->start_value;
        $baseDiscount->value_end = $request->end_value;
        $baseDiscount->vehicle_type_id = $request->vehicle_type;
        $baseDiscount->comprehensive_discount = $request->non_agency;
        $baseDiscount->agency_discount = $request->agency;
        $baseDiscount->save();
        return redirect()->back()->with('success', 'Discount has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $basediscount = BaseDiscount::find($id);
        return view('basediscount.show', compact('basediscount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vehicleTypes = VehicleType::where('is_active', '=', 1)->whereRaw('text = category')->orderBy('created_at', 'desc')->get();
        $basediscount = BaseDiscount::find($id);
        return view('basediscount.edit', compact('basediscount', 'vehicleTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'value_start' => 'required|numeric|min:0',
            'vehicle_type_id' => 'required',
            'comprehensive_discount' => 'required|numeric|min:0',
            'agency_discount' => 'required|numeric|min:0',
        ]);
        $baseDiscount = BaseDiscount::find($id);
        $baseDiscount->value_start = $request->value_start;
        $baseDiscount->value_end = $request->value_end;
        $baseDiscount->vehicle_type_id = $request->vehicle_type_id;
        $baseDiscount->comprehensive_discount = $request->comprehensive_discount;
        $baseDiscount->agency_discount = $request->agency_discount;
        $baseDiscount->save();
        if (isset($request->return_to_view))
            return redirect("discount/base/" . $baseDiscount->id)->with('success', 'Base Discount has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table("discount_engine_base")->where('id', $id)->delete();
        return redirect()->route('basediscount.index')->with('message', 'Base discount has been deleted');
    }
}
