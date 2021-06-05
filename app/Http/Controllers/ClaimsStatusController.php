<?php

namespace App\Http\Controllers;

use App\Models\ClaimsStatus;
use Illuminate\Http\Request;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class ClaimsStatusController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:claims-status-list|claims-status-create|claims-status-edit|claims-status-delete', ['only' => ['index','store']]);
         $this->middleware('permission:claims-status-create', ['only' => ['create','store']]);
         $this->middleware('permission:claims-status-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:claims-status-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ClaimsStatus::select('*');
            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('claimsstatus.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('claimsstatus.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('claimsstatus.add');
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

        $claimsstatus = new ClaimsStatus();
        $claimsstatus->text=  $request->text;
        $claimsstatus->text_ar=  $request->text_ar;
        $claimsstatus->is_active =  $request->is_active == 'on' ? 1 : 0;
        $claimsstatus->sort_order =  $request->sort_order;
        $claimsstatus->save();
        if(isset($request->return_to_view))
            return redirect("claim/claimsstatus");
        return back()->with('success','Claims Status has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function show(ClaimsStatus $claimsstatus)
    {
        return view('claimsstatus.show',compact('claimsstatus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(ClaimsStatus $claimsstatus)
    {
        return view('claimsstatus.edit',compact('claimsstatus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ClaimsStatus $claimsstatus)
    {
        $this->validate($request,[
            'text' => 'required|max:120',
            'text_ar' => 'required|max:120',
        ]);
        $claimsstatus->text=  $request->text;
        $claimsstatus->text_ar=  $request->text_ar;
        $claimsstatus->is_active =  $request->is_active == 'on' ? 1 : 0; 
        $claimsstatus->sort_order =  $request->sort_order;    
        $claimsstatus->save();
        if(isset($request->return_to_view))
            return redirect("claim/claimsstatus");
        return back()->with('success','Claims Status has been Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClaimsStatus $claimsstatus)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $claimsstatus->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return redirect()->route('claimsstatus.index');
    }
}
