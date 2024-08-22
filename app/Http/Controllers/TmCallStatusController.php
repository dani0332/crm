<?php

namespace App\Http\Controllers;

use App\Models\TmCallStatus;
use DataTables;
use Illuminate\Http\Request;

class TmCallStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tm-call-status-list|tm-call-status-create|tm-call-status-edit|tm-call-status-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:tm-call-status-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tm-call-status-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tm-call-status-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TmCallStatus::select('*')->where('is_deleted', 0)->orderBy('created_at', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('tmcallstatus.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('tmcallstatus.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tmcallstatus.add');
    }

    /**
     * Store a newly created resource in storage.
     *
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

        $tmCallStatus = new TmCallStatus;
        $tmCallStatus->code = $request->code;
        $tmCallStatus->text = $request->text;
        $tmCallStatus->text_ar = $request->text_ar;
        $tmCallStatus->is_active = $request->is_active == 'on' ? 1 : 0;
        $tmCallStatus->sort_order = $request->sort_order;
        $tmCallStatus->save();

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tmcallstatus/'.$tmCallStatus->id)->with('success', 'TM Call Status has been stored');
        }

        return redirect()->back()->with('success', 'TM Call Status has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(TmCallStatus $tmcallstatus)
    {
        return view('tmcallstatus.show', compact('tmcallstatus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(TmCallStatus $tmcallstatus)
    {
        return view('tmcallstatus.edit', compact('tmcallstatus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TmCallStatus $tmcallstatus)
    {
        $this->validate($request, [
            'code' => 'required|max:100',
            'text' => 'required|max:100',
            'text_ar' => 'required|max:100',
            'sort_order' => 'required|max:5',
        ]);

        $tmcallstatus->code = $request->code;
        $tmcallstatus->text = $request->text;
        $tmcallstatus->text_ar = $request->text_ar;
        $tmcallstatus->is_active = $request->is_active == 'on' ? 1 : 0;
        $tmcallstatus->sort_order = $request->sort_order;
        $tmcallstatus->save();

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tmcallstatus/'.$tmcallstatus->id)->with('success', 'TM Call Status has been updated');
        }

        return redirect()->back()->with('success', 'TM Call Status has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(TmCallStatus $tmcallstatus)
    {
        $tmcallstatus->is_deleted = 1;
        $tmcallstatus->save();

        return redirect()->route('tmcallstatus.index')->with('message', 'TM Call Status has been deleted');
    }
}
