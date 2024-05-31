<?php

namespace App\Services;

use App\Models\CarMake;
use App\Models\CarModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

class CommercialVehicleConfigurationService extends BaseService
{
    /**
     * get grid date function
     */
    public function getGridData(): Builder
    {
        return CarMake::whereHas('carModels', function ($qry) {
            $qry->where('is_commercial', 1)
                ->select(['id', 'car_make_code', 'text']);
        })->select('id', 'code', 'text')
            ->where('is_commercial', true)
            ->with(['carModels' => function ($qry) {
                $qry->where('is_commercial', 1)
                    ->select(['id', 'car_make_code', 'text']);
            }]);
    }

    /**
     * get active car makes function
     */
    public function getActiveCarMakes(): Collection
    {
        return CarMake::select('id', 'code', 'text')
            ->where('is_active', true)
            ->get();
    }

    /**
     * store new configuration function
     */
    public function store(array $attributes): RedirectResponse
    {
        $carMake = CarMake::find($attributes['car_make_id']);

        if ($carMake) {
            $carMake->is_commercial = true;
            $carMake->save();

            if ($attributes['car_make_id'] && count($attributes['car_model_id']) > 0) {
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

        return redirect()->route('admin.configure.commerical.vehicles.show', $carMake->id)->with('success', 'Commercial status assigned to the seleced vehicles');
    }

    /**
     * get details of a configuration function
     *
     * @param  int  $id
     */
    public function getDetails($id): CarMake
    {
        return CarMake::where('id', $id)->select('id', 'code', 'text', 'created_at', 'updated_at')
            ->where('is_commercial', true)
            ->with(['carModels' => function ($qry) {
                $qry->where('is_commercial', 1)
                    ->select(['id', 'car_make_code', 'text']);
            }])->first();
    }

    /**
     * get edit information function
     *
     * @param  int  $id
     */
    public function edit($id): array
    {
        $carMake = CarMake::where('id', $id)->select('id', 'code', 'text')
            ->with(['carModels' => function ($qry) {
                $qry->select(['id', 'car_make_code', 'text']);
            }])->first();

        $commercialModels = CarModel::where('car_make_code', $carMake->code)
            ->where('is_commercial', true)
            ->pluck('id')
            ->toArray();

        return $data = [
            'car_make' => $carMake,
            'commercial_models' => $commercialModels,
        ];
    }

    /**
     * update a configuration function
     */
    public function update(array $attributes): RedirectResponse
    {
        $carMake = CarMake::find($attributes['car_make_id']);

        if ($carMake) {

            $commercialModels = CarModel::where('car_make_code', $carMake->code)
                ->where('is_commercial', true)
                ->get();
            foreach ($commercialModels as $model) {
                $model->is_commercial = false;
                $model->save();
            }

            if ($attributes['car_make_id'] && count($attributes['car_model_id']) > 0) {
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

        return redirect()->route('admin.configure.commerical.vehicles.show', ($carMake->id))->with('success', 'Commercial status assigned to the seleced vehicles');
    }
}
