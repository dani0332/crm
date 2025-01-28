<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Http\Requests\RenewalBatchRequest;
use App\Models\RenewalBatch;
use App\Models\Slab;
use App\Models\Team;
use App\Services\CRUDService;
use Illuminate\Http\Request;

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
        $gridData = RenewalBatch::orderByDesc('id');

        if ($request->filled('quote_type_id') && $request->quote_type_id == QuoteTypeId::Car) {
            $gridData->where('quote_type_id', QuoteTypeId::Car);
        } elseif ($request->filled('quote_type_id') && $request->quote_type_id != QuoteTypeId::Car) {
            $gridData->where('quote_type_id', '<>', QuoteTypeId::Car)->orWhereNull('quote_type_id');
        }

        if ($request->filled('name')) {
            $gridData->whereIn('name', $request->name);
        }

        return inertia('Admin/RenewalBatches/Index', [
            'batches' => $gridData->paginate(15),
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lastExistingBatch = RenewalBatch::where('quote_type_id', QuoteTypeId::Car)->orderByDesc('id')->first();
        $params = $this->getProcessedBatchData($lastExistingBatch);

        $teams = $params['teams'];
        $volumeSegmentAdvisorsId = $params['volumeSegmentAdvisorsId'];
        $valueSegmentAdvisorsId = $params['valueSegmentAdvisorsId'];
        $lastBatchSlabs = $params['lastBatchSlabs'];
        $carAdvisors = $params['carAdvisors'];
        $slabs = $params['slabs'];

        return inertia('Admin/RenewalBatches/Form', [
            'teams' => $teams,
            'volumeSegmentAdvisorsId' => $volumeSegmentAdvisorsId,
            'valueSegmentAdvisorsId' => $valueSegmentAdvisorsId,
            'lastBatchSlabs' => $lastBatchSlabs,
            'carAdvisors' => $carAdvisors,
            'slabs' => $slabs,
            'quoteStatus' => QuoteStatusEnum::asArray(),
        ]);

    }

    /**
     * store renewal batch function
     *
     * @return void
     */
    public function store(RenewalBatchRequest $request)
    {

        $attributes = $request->validated();
        $attributes['quote_type_id'] = QuoteTypeId::Car;
        RenewalBatch::create($attributes);

        return redirect()->route('renewal-batches-list')->with('message', 'Renewal Batch Successfully created');
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
        $carSoldDeadline = $params['carSoldDeadline'];
        $uncontactableDeadline = $params['uncontactableDeadline'];

        return inertia('Admin/RenewalBatches/Form', [
            'teams' => $teams,
            'volumeSegmentAdvisorsId' => $volumeSegmentAdvisorsId,
            'valueSegmentAdvisorsId' => $valueSegmentAdvisorsId,
            'lastBatchSlabs' => $lastBatchSlabs,
            'carAdvisors' => $carAdvisors,
            'slabs' => $slabs,
            'carSoldDeadline' => $carSoldDeadline,
            'uncontactableDeadline' => $uncontactableDeadline,
            'renewalBatch' => $renewalBatch,
            'quoteStatus' => QuoteStatusEnum::asArray(),
        ]);

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

        return redirect()->route('renewal-batches-list')->with('message', 'Renewal Batch Successfully updated');
    }

    /**
     * get required batch data with preprocessing function
     */
    public function getProcessedBatchData(?RenewalBatch $renewalBatch = null): array
    {
        $volumeSegmentAdvisorsId = [];
        $valueSegmentAdvisorsId = [];
        $lastBatchSlabs = [];

        if (! empty($renewalBatch) && ! empty($renewalBatch->segmentAdvisors() && ! empty($renewalBatch->slabs()))) {
            $volumeSegmentAdvisorsId = $renewalBatch->segmentAdvisors()
                ->where('segment_type', RenewalBatch::SEGMENT_TYPE_VOLUME)
                ->pluck('users.id')
                ->toArray();

            $valueSegmentAdvisorsId = $renewalBatch->segmentAdvisors()
                ->where('segment_type', RenewalBatch::SEGMENT_TYPE_VALUE)
                ->pluck('users.id')
                ->toArray();

            $lastBatchSlabs = $renewalBatch->slabs()->orderBy('id')->get()->groupBy('pivot.slab_id');

            $lastBatchSlabs = $lastBatchSlabs->map(function ($lastBatchSlab) {
                return $lastBatchSlab->keyBy('pivot.team_id');
            });
        }

        if (! empty($renewalBatch->deadlines)) {
            $carSoldDeadline = $renewalBatch->deadlines()
                ->where('quote_status_id', QuoteStatusEnum::CarSold)
                ->pluck('deadline_date')
                ->first();

            $uncontactableDeadline = $renewalBatch->deadlines()
                ->where('quote_status_id', QuoteStatusEnum::Uncontactable)
                ->pluck('deadline_date')
                ->first();
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
            'slabs' => $slabs,
            'carSoldDeadline' => isset($carSoldDeadline) ? $carSoldDeadline : null,
            'uncontactableDeadline' => isset($uncontactableDeadline) ? $uncontactableDeadline : null,
        ];
    }
}
