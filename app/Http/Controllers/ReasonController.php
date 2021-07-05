<?php

namespace App\Http\Controllers;

use App\Models\Reason;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use DB;

class ReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:reason-list|reason-create|reason-edit|reason-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:reason-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:reason-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:reason-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Reason::select('*')->orderBy('created_at','desc');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('reason.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('reason.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('reason.add');
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
            'name' => 'required|max:150',
        ]);

        $reason = new Reason;
        $reason->name = $request->name;
        $reason->is_active = $request->is_active == 'on' ? 1 : 0;
        $reason->created_by = Auth::user()->email;
        $reason->updated_by = Auth::user()->email;
        $reason->save();

        if(isset($request->return_to_view)) {
            return redirect("transapp/reason/".$reason->id)->with('success', 'Reason Description has been stored');
        }
        return redirect()->back()->with('success', 'Reason Description has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function show(Reason $reason)
    {
        return view('reason.show', compact('reason'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function edit(Reason $reason)
    {
        return view('reason.edit', compact('reason'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,Reason $reason)
    {
        $this->validate($request, [
            'name' => 'required|max:150',
        ]);
        $reason->name = $request->name;
        $reason->is_active = $request->is_active == 'on' ? 1 : 0;
        $reason->updated_by = Auth::user()->email;
        $reason->save();
        if(isset($request->return_to_view)) {
            return redirect("transapp/reason/".$reason->id)->with('success', 'Reason Description has been updated');
        }
        return redirect()->back()->with('success', 'Reason Description has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Reason  $reason
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reason $reason)
    {
        $reason->delete();
        return redirect()->route('reason.index')->with('message','Reason Description has been deleted');
    }
}
