<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgeDiscountRequest;
use App\Http\Resources\AgeDiscountResource;
use App\Models\AgeDiscount;
use DataTables;
use Illuminate\Http\Request;

class AgeDiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, AgeDiscount $age)
    {
        if ($request->ajax()) {
            $data = $age::orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
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
    public function store(AgeDiscountRequest $request, AgeDiscount $age)
    {
        $existingAgeDiscount = $age::where([['age_start', $request->age_start], ['age_end', $request->age_end]])->get()->first();
        if ($existingAgeDiscount != '') {
            return redirect()->back()->with('message', 'Discount with specified age already exists.')->withInput();
        }
        $id = $age->create($request->validated())->id;

        return redirect('discount/age/'.$id)->with('success', 'Age Discount has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(AgeDiscount $age)
    {
        $agediscount = new AgeDiscountResource($age);

        return view('agediscount.show', compact('agediscount'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(AgeDiscount $age)
    {
        $agediscount = new AgeDiscountResource($age);

        return view('agediscount.edit', compact('agediscount'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AgeDiscountRequest $request, AgeDiscount $age)
    {
        $age->update($request->validated());
        if (isset($request->return_to_view)) {
            return redirect('discount/age/'.$age->id)->with('success', 'Age Discount has been updated');
        }
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
