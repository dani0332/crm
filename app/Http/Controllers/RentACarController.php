<?php

namespace App\Http\Controllers;

use App\Models\RentACar;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class RentACarController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:rent-a-car-list|rent-a-car-create|rent-a-car-edit|rent-a-car-delete', ['only' => ['index','store']]);
         $this->middleware('permission:rent-a-car-create', ['only' => ['create','store']]);
         $this->middleware('permission:rent-a-car-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:rent-a-car-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = RentACar::select('*');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('rentacar.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        
        return view('rentacar.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('rentacar.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);

        $rentacar = new RentACar();
        $rentacar->text=  $request->text;
        $rentacar->text_ar=  $request->text_ar;
        $rentacar->is_active =  $request->is_active == 'on' ? 1 : 0;
        $rentacar->sort_order =  $request->sort_order;
        $rentacar->save();
        return back()
            ->with('success','Rent a Car has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RentACar  $rentACar
     * @return \Illuminate\Http\Response
     */
    public function show(RentACar $rentacar)
    {
        return view('rentacar.show',compact('rentacar'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RentACar  $rentACar
     * @return \Illuminate\Http\Response
     */
    public function edit(RentACar $rentacar)
    {
        return view('rentacar.edit',compact('rentacar'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RentACar  $rentACar
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RentACar $rentacar)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $rentacar->text=  $request->text;
        $rentacar->text_ar=  $request->text_ar;
        $rentacar->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $rentacar->sort_order =  $request->sort_order;    
        $rentacar->save();
        return back()
            ->with('success','Rent a Car has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RentACar  $rentACar
     * @return \Illuminate\Http\Response
     */
    public function destroy(RentACar $rentacar)
    {
        $rentacar->delete();
        return back()
            ->with('success','Rent a Car has been Deleted');
    }
}
