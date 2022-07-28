<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimStatusRequest;
use App\Http\Resources\ClaimStatusResource;
use App\Models\ClaimsStatus;
use DataTables;
use Illuminate\Http\Request;

class ClaimsStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:claims-status-list|claims-status-create|claims-status-edit|claims-status-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:claims-status-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:claims-status-edit', ['only' => ['edit', 'update']]);
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
            $data = ClaimsStatus::select('*')->orderBy('sort_order', 'asc');

            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
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
    public function store(ClaimStatusRequest $request, ClaimsStatus $claimsstatus)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $id = $claimsstatus->create($validated)->id;
        if (isset($request->return_to_view)) {
            return redirect('claim/claimsstatus/'.$id)->with('success', 'Claim Status has been stored');
        }

        return redirect()->back()->with('success', 'Claim Status has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function show(ClaimsStatus $claimsstatus)
    {
        $claimsstatus = new ClaimStatusResource($claimsstatus);

        return view('claimsstatus.show', compact('claimsstatus'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(ClaimsStatus $claimsstatus)
    {
        $claimsstatus = new ClaimStatusResource($claimsstatus);

        return view('claimsstatus.edit', compact('claimsstatus'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function update(ClaimStatusRequest $request, ClaimsStatus $claimsstatus)
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $claimsstatus->update($validated);
        if (isset($request->return_to_view)) {
            return redirect('claim/claimsstatus/'.$claimsstatus->id)->with('success', 'Claim Status has been updated');
        }

        return redirect()->back()->with('success', 'Claim Status has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaimsStatus  $claimsStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClaimsStatus $claimsstatus)
    {
        $claimsstatus->delete();

        return redirect()->route('claimsstatus.index')->with('message', 'Claim Status has been deleted');
    }
}
