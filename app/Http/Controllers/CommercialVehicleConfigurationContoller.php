<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Requests\CommercialVehicleConfigurationRequest;
use App\Services\CommercialVehicleConfigurationService;

class CommercialVehicleConfigurationContoller extends Controller
{
    protected $commercialVehicleConfigurationService;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(CommercialVehicleConfigurationService $commercialVehicleConfigurationService)
    {
        $this->middleware('auth');
        $this->commercialVehicleConfigurationService = $commercialVehicleConfigurationService;
    }

    /**
     * get resource grid view function
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $gridData = $this->commercialVehicleConfigurationService->getGridData();

        if ($request->ajax()) {
            if (isset($request->text) && ! empty($request->text)) {
                $text = $request->text;
                $gridData = $gridData->where(function ($query) use ($text) {
                    $query->whereRaw('LOWER(text) LIKE ?', [strtolower("%{$text}%")]);
                });
            }

            return DataTables::of($gridData->get()->sortBy('text'))
                ->addIndexColumn()
                ->addColumn('car_models', function (CarMake $carMake) {
                    if (!empty($carMake->carModels)) {
                        return implode(', ', $carMake->carModels->pluck('text')->toArray());
                    }
                })
                ->rawColumns(['car_models'])
                ->make(true);
        }

        return view('commercialcarmakemodel.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $carsMake = $this->commercialVehicleConfigurationService->getActiveCarMakes();

        return view('commercialcarmakemodel.add', compact('carsMake'));
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

        return view('commercialcarmakemodel.show', compact('carMake'));
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

        return view('commercialcarmakemodel.edit', compact('carMake', 'commercialModels'));
    }

    /**
     * update resource function
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(CommercialVehicleConfigurationRequest $request)
    {
       $attributes = $request->validated();

       return $this->commercialVehicleConfigurationService->update($attributes);

    }
}
