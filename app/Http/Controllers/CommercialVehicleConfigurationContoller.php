<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\CommercialVehicleConfigurationRequest;
use App\Services\CommercialVehicleConfigurationService;
use Illuminate\Http\Request;

class CommercialVehicleConfigurationContoller extends Controller
{
    private $commercialVehicleConfigurationService;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(CommercialVehicleConfigurationService $commercialVehicleConfigurationService)
    {
        $this->middleware(
            ['permission:'.PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES],
            ['only' => ['index', 'create', 'store', 'edit', 'update', 'show']]
        );
        $this->commercialVehicleConfigurationService = $commercialVehicleConfigurationService;
    }

    /**
     * get resource grid view function.
     *
     * @return void
     */
    public function index(Request $request)
    {
        $gridData = $this->commercialVehicleConfigurationService->getGridData();

        if (isset($request->text) && ! empty($request->text)) {
            $text = $request->text;
            $gridData = $gridData->where(function ($query) use ($text) {
                $query->whereRaw('LOWER(text) LIKE ?', [strtolower("%{$text}%")]);
            });
        }

        $gridData = $gridData->orderBy('text')->paginate();

        return inertia('Admin/AllocationConfig/CommericalVehicles/Index', [
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
        $carsMake = $this->commercialVehicleConfigurationService->getActiveCarMakes();

        return inertia('Admin/AllocationConfig/CommericalVehicles/Form', [
            'carMakes' => $carsMake,
            'commercialModels' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CommercialVehicleConfigurationRequest $request)
    {
        $attributes = $request->validated();

        return $this->commercialVehicleConfigurationService->store($attributes);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $carMake = $this->commercialVehicleConfigurationService->getDetails($id);

        return inertia('Admin/AllocationConfig/CommericalVehicles/Show', [
            'carMake' => $carMake,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->commercialVehicleConfigurationService->edit($id);

        $carMake = $data['car_make'];
        $commercialModels = $data['commercial_models'];

        return inertia('Admin/AllocationConfig/CommericalVehicles/Form', [
            'carMakes' => $carMake,
            'commercialModels' => $commercialModels,
        ]);
    }

    /**
     * update resource function.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return void
     */
    public function update(CommercialVehicleConfigurationRequest $request)
    {
        $attributes = $request->validated();

        return $this->commercialVehicleConfigurationService->update($attributes);
    }
}
