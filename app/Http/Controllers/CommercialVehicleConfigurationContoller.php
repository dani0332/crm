<?php

namespace App\Http\Controllers;

use App\Models\CarMake;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Requests\CommercialVehicleConfigurationRequest;

class CommercialVehicleConfigurationContoller extends Controller
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
        $gridData = CarMake::whereHas('carModels', function ($qry) {
            $qry->where('is_commercial', 1)
                ->select(['id', 'car_make_code', 'text']);
            })->select('id', 'code', 'text')
             ->where('is_commercial', true)
             ->with(['carModels' => function ($qry) {
                $qry->where('is_commercial', 1)
                ->select(['id', 'car_make_code', 'text']);
            }]);

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
        $carsMake = CarMake::select('id', 'code', 'text')
            ->where('is_active', true)
            ->get();

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

        $carMake = CarMake::find($attributes['car_make_id']);

        if ($carMake) {
            $carMake->is_commercial = true;
            $carMake->save();

            if ($attributes['car_make_id'] && count ($attributes['car_model_id']) > 0)
            {
                foreach ($attributes['car_model_id'] as $carModelId) {
                    $carModel = CarModel::where('id', $carModelId)
                        ->where('car_make_code', $carMake->code)
                        ->first();

                    if ($carModel) {
                        $carModel->is_commercial = true;
                        $carModel->save();
                    } else {
                        return redirect()->back()->with('message', 'Car Model record not found');
                    }
                }
            } else {
                return redirect()->back()->with('message', 'Car Model Ids missing');
            }
        } else {
            return redirect()->back()->with('message', 'Car Make record not found');
        }
        return redirect()->back()->with('success', 'Commercial status assigned to the seleced vehicles');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $carMake = CarMake::where('id', $id)->select('id', 'code', 'text')
            ->where('is_commercial', true)
            ->with(['carModels' => function ($qry) {
                $qry->where('is_commercial', 1)
                    ->select(['id', 'car_make_code', 'text']);
            }])->first();

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
        $carMake = CarMake::where('id', $id)->select('id', 'code', 'text')
            ->with(['carModels' => function ($qry) {
                $qry->select(['id', 'car_make_code', 'text']);
            }])->first();

        $commercialModels = CarModel::where('car_make_code', $carMake->code)
            ->where('is_commercial', true)
            ->pluck('id')
            ->toArray();

        return view('commercialcarmakemodel.edit', compact('carMake', 'commercialModels'));
    }

    /**
     * update resource function
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(CommercialVehicleConfigurationRequest $request, $id)
    {
       $attributes = $request->validated();

        $keyword = CarMake::find($id);
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
