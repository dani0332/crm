<?php

namespace App\Http\Controllers;

use App\Models\TmLeadStatus;
use Illuminate\Http\Request;
use DataTables;

class TmLeadStatusController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:tm-lead-status-list|tm-lead-status-create|tm-lead-status-edit|tm-lead-status-delete', ['only' => ['index','store']]);
         $this->middleware('permission:tm-lead-status-create', ['only' => ['create','store']]);
         $this->middleware('permission:tm-lead-status-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:tm-lead-status-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TmLeadStatus::select('*')->where('is_deleted', 0)->orderBy('sort_order','asc');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('tmleadstatus.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('tmleadstatus.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tmleadstatus.add');
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
            'code' => 'required|max:100',
            'text' => 'required|max:100',
            'text_ar' => 'required|max:100',
            'sort_order' => 'required|max:5',
        ]);

        $TmLeadStatus = new TmLeadStatus();
        $TmLeadStatus->code = $request->code;
        $TmLeadStatus->text = $request->text;
        $TmLeadStatus->text_ar = $request->text_ar;
        $TmLeadStatus->is_active = $request->is_active == 'on' ? 1 : 0;
        $TmLeadStatus->sort_order = $request->sort_order;
        $TmLeadStatus->save();

        if(isset($request->return_to_view)) {
            return redirect("telemarketing/tmleadstatus/".$TmLeadStatus->id)->with('success', 'TM Lead Status has been stored');
        }
        return redirect()->back()->with('success', 'TM Lead Status has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TmLeadStatus  $tmleadstatus
     * @return \Illuminate\Http\Response
     */
    public function show(TmLeadStatus $tmleadstatus)
    {
        return view('tmleadstatus.show',compact('tmleadstatus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TmLeadStatus  $tmleadstatus
     * @return \Illuminate\Http\Response
     */
    public function edit(TmLeadStatus $tmleadstatus)
    {
        return view('tmleadstatus.edit',compact('tmleadstatus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TmLeadStatus  $tmleadstatus
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TmLeadStatus $tmleadstatus)
    {
        $this->validate($request, [
            'code' => 'required|max:100',
            'text' => 'required|max:100',
            'text_ar' => 'required|max:100',
            'sort_order' => 'required|max:5',
        ]);

        $tmleadstatus->code = $request->code;
        $tmleadstatus->text = $request->text;
        $tmleadstatus->text_ar = $request->text_ar;
        $tmleadstatus->is_active = $request->is_active == 'on' ? 1 : 0;
        $tmleadstatus->sort_order = $request->sort_order;
        $tmleadstatus->save();

        if(isset($request->return_to_view)) {
            return redirect("telemarketing/tmleadstatus/".$tmleadstatus->id)->with('success', 'TM Lead Status has been updated');
        }
        return redirect()->back()->with('success', 'TM Lead Status has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TmLeadStatus  $tmleadstatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(TmLeadStatus $tmleadstatus)
    {
        $tmleadstatus->is_deleted = 1;
        $tmleadstatus->save();
        return redirect()->route('tmleadstatus.index')->with('message','TM Lead Status has been deleted');
    }
}
