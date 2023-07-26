<?php

namespace App\Http\Controllers;

use App\Models\Slab;
use App\Models\Team;
use App\Enums\quoteTypeCode;
use App\Models\RenewalBatch;
use Illuminate\Http\Request;
use App\Services\CRUDService;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\RenewalBatchRequest;

class RenewalBatchController extends Controller
{
    protected $crudService;

    public function __construct(CRUDService $crudService)
    {
        $this->crudService = $crudService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $gridData = RenewalBatch::orderByDesc('id')->get();

        if ($request->ajax()) {

            return DataTables::of($gridData)
                ->addIndexColumn()
                ->addColumn('action', function ($gridData) {
                    // $btn =   '<a href="#" data-id="'.$gridData->id.'" class="btn edit btn-secondary">Edit</a>';
                    // return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('renewalBatch.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lastExistingBatch = RenewalBatch::orderByDesc('id')->first();
        $params = $this->getProcessedBatchData($lastExistingBatch);

        $teams = $params['teams'];
        $volumeSegmentAdvisorsId = $params['volumeSegmentAdvisorsId'];
        $valueSegmentAdvisorsId = $params['valueSegmentAdvisorsId'];
        $lastBatchSlabs = $params['lastBatchSlabs'];
        $carAdvisors = $params['carAdvisors'];
        $slabs = $params['slabs'];

        return view('renewalbatch.config-views.create',
            compact(
                'teams',
                'volumeSegmentAdvisorsId',
                'valueSegmentAdvisorsId',
                'lastBatchSlabs',
                'carAdvisors',
                'slabs'
            ));

    }

    /**
     * store renewal batch function
     *
     * @param RenewalBatchRequest $request
     * @return void
     */
    public function store(RenewalBatchRequest $request)
    {
        $attributes = $request->validated();

        RenewalBatch::create($attributes);

        return redirect()->route('renewal-batch.index')->with('message', "Renewal Batch Successfully created");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(RenewalBatch $renewalBatch)
    {
        $params = $this->getProcessedBatchData($renewalBatch);

        $teams = $params['teams'];
        $renewalBatch = $params['renewalBatch'];
        $volumeSegmentAdvisorsId = $params['volumeSegmentAdvisorsId'];
        $valueSegmentAdvisorsId = $params['valueSegmentAdvisorsId'];
        $lastBatchSlabs = $params['lastBatchSlabs'];
        $carAdvisors = $params['carAdvisors'];
        $slabs = $params['slabs'];

        return view('renewalbatch.config-views.edit',
            compact(
                'teams',
                'renewalBatch',
                'volumeSegmentAdvisorsId',
                'valueSegmentAdvisorsId',
                'lastBatchSlabs',
                'carAdvisors',
                'slabs'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RenewalBatchRequest $request, RenewalBatch $renewalBatch)
    {
        $attributes = $request->validated();

        $renewalBatch->update($attributes);

        return redirect()->route('renewal-batch.index')->with('message', "Renewal Batch Successfully updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * get required batch data with preprocessing function
     *
     * @param RenewalBatch|null $renewalBatch
     * @return Array
     */
    public function getProcessedBatchData(RenewalBatch $renewalBatch=null):Array
    {
        $volumeSegmentAdvisorsId    = [];
        $valueSegmentAdvisorsId     = [];
        $lastBatchSlabs             = [];

        if (!empty($renewalBatch) && !empty($renewalBatch->segmentAdvisors() && !empty($renewalBatch->slabs())))
        {
            $volumeSegmentAdvisorsId = $renewalBatch->segmentAdvisors()
                ->where('segment_type', RenewalBatch::SEGMENT_TYPE_VOLUME)
                ->pluck('users.id')
                ->toArray();

            $valueSegmentAdvisorsId = $renewalBatch->segmentAdvisors()
                ->where('segment_type', RenewalBatch::SEGMENT_TYPE_VALUE)
                ->pluck('users.id')
                ->toArray();

            $lastBatchSlabs = $renewalBatch->slabs()->orderBy('id')->get()->groupBy('pivot.slab_id');

            $lastBatchSlabs = $lastBatchSlabs->map(function($lastBatchSlab){
                return $lastBatchSlab->keyBy('pivot.team_id');
            });
        }

        $carAdvisors = $this->crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Car));

        $teams = Team::select(['id', 'name', 'slabs_count'])
            ->where('is_active', true)
            ->whereIn('name', RenewalBatch::RENEWAL_BATCH_TEAMS_LIST)
            ->get();

        $slabs = Slab::select(['id', 'title'])->orderBy('id')->get();

        return
        [
            'teams' => $teams,
            'renewalBatch' => $renewalBatch,
            'volumeSegmentAdvisorsId' => $volumeSegmentAdvisorsId,
            'valueSegmentAdvisorsId' => $valueSegmentAdvisorsId,
            'lastBatchSlabs' => $lastBatchSlabs,
            'carAdvisors' => $carAdvisors,
            'slabs' => $slabs
        ];
    }
}
