<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Auth;
use DataTables;

class StatusController extends Controller
{
        /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:status-list|status-create|status-edit|status-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:status-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:status-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:status-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Status::select('*')->orderBy('created_at','desc');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('status.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('status.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        return view('status.add');
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

            'name' => 'required',
        ]);

        $status = new Status;
        $status->name = $request->name;
        $status->is_active = $request->is_active == 'on' ? 1 : 0;
        $status->created_by = Auth::user()->email;
        $status->save();
        if(isset($request->return_to_view))
            return redirect("transapp/status");

        return redirect()->back()
        ->with('success', 'Status created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  Status  $status
     * @return \Illuminate\Http\Response
     */

    public function show(Status $status)
    {
        return view('status.show', compact('status'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Status  $status
     * @return \Illuminate\Http\Response
     */

    public function edit(Status $status)
    {
        return view('status.edit', compact('status'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Status  $status
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,Status $status)
    {
        $this->validate($request, [

            'name' => 'required',
        ]);
        // return $request->is_active;
        $status->name = $request->name;
        $status->is_active = $request->is_active == 'on' ? 1 : 0;
        $status->updated_by = Auth::user()->email;
        $status->save();
        if(isset($request->return_to_view))
        return redirect("transapp/status");

        return redirect()->back()
            ->with('success', 'Status updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Status  $status
     * @return \Illuminate\Http\Response
     */

    public function destroy(Status $status)
    {

        $status->delete();
        return redirect()->route('status.index')
            ->with('success', 'Status deleted successfully');
    }
}
