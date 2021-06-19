<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use DataTables;
use Illuminate\Http\Request;
use DB;
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
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'name_ar' => 'required|max:120',
            'logo_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $partner = new Partner();
        $partner->name = $request->name;
        $partner->name_ar = $request->name_ar;
        $partner->is_active = $request->is_active == 'on' ? 1 : 0;
        if ($request->file()) {
            $fileName = time() . '_' . $request->logo_image->getClientOriginalName();
            $filePath = $request->file('logo_image')->storeAs('/', $fileName, 'azure');
            $partner->logo_image = $fileName;
        }

        $partner->save();
        if(isset($request->return_to_view))
            return redirect("rewards/partner/".$partner->id)->with('success', 'Partner has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function show(Partner $partner)
    {
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
        return view('partner.edit', compact('partner'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Partner $partner)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'name_ar' => 'required|max:120',
            'logo_image' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);
        $partner->name = $request->name;
        $partner->name_ar = $request->name_ar;
        $partner->is_active = $request->is_active == 'on' ? 1 : 0;
        if ($request->file()) {
            $fileName = time() . '_' . $request->logo_image->getClientOriginalName();
            $filePath = $request->file('logo_image')->storeAs('/', $fileName, 'azure');
            $partner->logo_image = $fileName;
        }

        $partner->save();
        if(isset($request->return_to_view))
            return redirect("rewards/partner/".$partner->id)->with('success', 'Partner has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Partner  $partner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect('rewards/partner')->with('message','Partner has been deleted');
    }
}
