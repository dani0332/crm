<?php

namespace App\Http\Controllers;

use App\Models\Handler;
use Auth;
use DataTables;
use DB;
use Illuminate\Http\Request;

class HandlerController extends Controller
{
    /**
     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:handler-list|handler-create|handler-edit|handler-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:handler-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:handler-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:handler-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Handler::select('*')->orderBy('created_at', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('handler.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('handler.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('handler.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [

            'name' => 'required',

        ]);

        $handler = new Handler;
        $handler->name = $request->name;
        $handler->is_active = $request->is_active == 'on' ? 1 : 0;
        $handler->created_by = Auth::user()->email;
        $handler->save();
        if (isset($request->return_to_view)) {
            return redirect('transapp/handler');
        }

        return redirect()->back()
            ->with('success', 'Handler created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Handler $handler)
    {
        return view('handler.show', compact('handler'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Handler $handler)
    {
        return view('handler.edit', compact('handler'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Handler $handler)
    {
        $this->validate($request, [

            'name' => 'required',
        ]);
        // return $request->is_active;
        $handler->name = $request->name;
        $handler->is_active = $request->is_active == 'on' ? 1 : 0;
        $handler->updated_by = Auth::user()->email;
        $handler->save();
        if (isset($request->return_to_view)) {
            return redirect('transapp/handler');
        }

        return redirect()->back()
            ->with('success', 'Handler updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Handler $handler)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $handler->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return redirect()->route('handler.index')
            ->with('success', 'Handler deleted successfully');
    }
}
