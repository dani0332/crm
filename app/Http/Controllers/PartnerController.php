<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use DataTables;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests\PartnerAddRequest;
use App\Http\Requests\PartnerEditRequest;
use App\Http\Resources\PartnerResource;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:partners-list|partners-create|partners-edit|partners-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:partners-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:partners-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:partners-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Partner::select('*')->orderBy('created_at','desc');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('partner.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('partner.view');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('partner.add');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PartnerAddRequest $request, Partner $partner)
    {  
        $validated = $request->validated();
        if ($request->file()) {
            $fileName = time() . '_' . $request->logo_image->getClientOriginalName();
            $filePath = $request->file('logo_image')->storeAs('/', $fileName, 'azure');
           $validated['logo_image'] = $fileName;
        }
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $id = $partner->create($validated)->id;

        if(isset($request->return_to_view)) {
            return redirect("rewards/partner/".$id)->with('success', 'Partner has been stored');
        }
        return redirect()->back()->with('success', 'Partner has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function show(Partner $partner)
    {
        $partner = new PartnerResource($partner);
        return view('partner.show', compact('partner'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function edit(Partner $partner)
    {
        $partner = new PartnerResource($partner);
        return view('partner.edit', compact('partner'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function update(PartnerEditRequest $request, Partner $partner)
    {
        $validated = $request->validated();
        if ($request->file()) {
            $fileName = time() . '_' . $request->logo_image->getClientOriginalName();
            $filePath = $request->file('logo_image')->storeAs('/', $fileName, 'azure');
            $validated['logo_image'] = $fileName;
        }
        $validated['is_active'] = $validated['is_active'] ?? 0;
        $partner->update($validated);
        if(isset($request->return_to_view)) {
            return redirect("rewards/partner/".$partner->id)->with('success', 'Partner has been updated');
        }
        return redirect()->back()->with('success', 'Partner has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Partner $partner)
    {
        if($partner->rewards()->count()) {
            return redirect()->route('partner.index')->with('message','Partner is linked with Reward and cannot be deleted');
        }
        else {
            $partner->delete();
            return redirect()->route('partner.index')->with('message','Partner has been deleted');
        }
    }
}
