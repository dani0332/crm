<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\CommercialKeyword;

class CommercialKeywordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * get resource grid view function
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $gridData = CommercialKeyword::query();

        if ($request->ajax()) {
            if (isset($request->name) && ! empty($request->name)) {
                $name = $request->name;
                $gridData = $gridData->where(function ($query) use ($name) {
                    $query->whereRaw('LOWER(name) LIKE ?', [strtolower("%{$name}%")]);
                });
            }

            return DataTables::of($gridData->orderByDesc('id')->get())
                ->addIndexColumn()
                ->make(true);
        }

        return view('commercialkeywords.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('commercialkeywords.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validateArray = [
            'name' => 'required|max:255',
        ];

        $this->validate($request, $validateArray);

        $latestRecord = CommercialKeyword::select('id')->orderByDesc('id')->first();

        $keyword = new CommercialKeyword();
        $keyword->id = ($latestRecord->id + 1);
        $keyword->name = $request->get('name');
        $keyword->key  = strtoupper(str_replace(' ', '_', $request->get('name')));
        $keyword->save();

        return redirect()->back()->with('success', 'Commercial Keyword has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(CommercialKeyword $commercialKeyword)
    {
        return view('commercialkeywords.show', compact('commercialKeyword'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(CommercialKeyword $commercialKeyword)
    {
        return view('commercialkeywords.edit', compact('commercialKeyword'));
    }

    /**
     * update resource function
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $validateArray = [
            'name' => 'required',
        ];
        $this->validate($request, $validateArray);

        $keyword = CommercialKeyword::find($id);
        if($keyword){
            $keyword->name = $request->get('name');
            $keyword->key  = strtoupper(str_replace(' ', '_', $request->get('name')));
            $keyword->update();
        } else {
            return redirect()->route('admin.commercial.keywords')->with('message', 'Record not found');
        }

        return redirect()->route('admin.commercial.keywords')->with('success', 'Commercial Keyword has been updated');
    }

}
