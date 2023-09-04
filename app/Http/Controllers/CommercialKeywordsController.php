<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\CommercialKeyword;
use App\Http\Requests\CommercialKeywordRequest;
use App\Services\CommercialKeywordsService;

class CommercialKeywordsController extends Controller
{
    protected $commercialKeywordsService;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(CommercialKeywordsService $commercialKeywordsService)
    {
        $this->middleware('auth');
        $this->commercialKeywordsService = $commercialKeywordsService;
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
    public function store(CommercialKeywordRequest $request)
    {
        $attributes = $request->validated();

        return $this->commercialKeywordsService->store($attributes);
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
    public function update(CommercialKeywordRequest $request, $id)
    {
       $attributes = $request->validated();

        return $this->commercialKeywordsService->update($id, $attributes);
    }

}
