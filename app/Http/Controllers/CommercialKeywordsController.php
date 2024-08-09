<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\CommercialKeywordRequest;
use App\Models\CommercialKeyword;
use App\Services\CommercialKeywordsService;
use Illuminate\Http\Request;

class CommercialKeywordsController extends Controller
{
    private $commercialKeywordsService;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(CommercialKeywordsService $commercialKeywordsService)
    {
        $this->middleware(
            ['permission:'.PermissionsEnum::COMMERCIAL_KEYWORDS],
            ['only' => ['index', 'create', 'store', 'edit', 'update', 'show']]
        );
        $this->commercialKeywordsService = $commercialKeywordsService;
    }

    /**
     * get resource grid view function.
     *
     * @return void
     */
    public function index(Request $request)
    {
        $gridData = CommercialKeyword::query();

        if (isset($request->name) && ! empty($request->name)) {
            $name = $request->name;
            $gridData = $gridData->where(function ($query) use ($name) {
                $query->whereRaw('LOWER(name) LIKE ?', [strtolower("%{$name}%")]);
            });
        }

        $gridData = $gridData->orderByDesc('id')->paginate();

        return inertia('Admin/AllocationConfig/CommercialKeywords/Index', [
            'data' => $gridData,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Admin/AllocationConfig/CommercialKeywords/Form');
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
        return inertia('Admin/AllocationConfig/CommercialKeywords/Show', ['keyword' => $commercialKeyword]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(CommercialKeyword $commercialKeyword)
    {

        return inertia('Admin/AllocationConfig/CommercialKeywords/Form', ['keyword' => $commercialKeyword]);
    }

    /**
     * update resource function.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return void
     */
    public function update(CommercialKeywordRequest $request, $id)
    {
        $attributes = $request->validated();

        return $this->commercialKeywordsService->update($id, $attributes);
    }
}
