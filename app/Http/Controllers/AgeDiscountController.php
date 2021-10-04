<?php

namespace App\Http\Controllers;

use App\Models\AgeDiscount;
use Illuminate\Http\Request;
use DataTables;

class AgeDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = AgeDiscount::orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
            return view('agediscount.view');
        }
        return view('agediscount.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('agediscount.add');
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
            'age_start' => 'required|numeric|min:0',
            'age_end' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
        ]);

        $existingAgeDiscount = AgeDiscount::where([['age_start', $request->age_start], ['age_end', $request->age_end]])->get()->first();
        if ($existingAgeDiscount != '') {
            return redirect()->back()->with('message', 'Discount with specified age already exists.')->withInput();
        }
        $baseDiscount = new AgeDiscount();
        $baseDiscount->age_start = $request->age_start;
        $baseDiscount->age_end = $request->age_end;
        $baseDiscount->discount = $request->discount;
        $baseDiscount->save();
        return redirect()->back()->with('success', 'Age Discount has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $agediscount = AgeDiscount::find($id);
        return view('agediscount.show', compact('agediscount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $agediscount = AgeDiscount::find($id);
        return view('agediscount.edit', compact('agediscount'));
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
            'age_start' => 'required|numeric|min:0',
            'age_end' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
        ]);
        $agediscount = AgeDiscount::find($id);
        $agediscount->age_start = $request->age_start;
        $agediscount->age_end = $request->age_end;
        $agediscount->discount = $request->discount;
        $agediscount->save();
        if (isset($request->return_to_view))
            return redirect("discount/age/" . $agediscount->id)->with('success', 'Age Discount has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
