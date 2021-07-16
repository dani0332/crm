<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;
use App\Transformers\LeadsTransformer;

class FormController extends ApiController
{

    public function index(Request $request)
    {
        try {
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $collection = collect($modelInstance->processGetDSL($filters, $request ));
            return $this->respondData($collection);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function getFormDetail(Request $request)
    {
        try {

            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $form_id = $request->form_id;
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $collection = collect($modelInstance->processGetDSL(["id" => $form_id], $request ))->first();
            $relation = $parentFormModelColl->get('relation');

            foreach ($relation as $key => $value) {

                if(property_exists($collection, $value['where'][1])) {
                    $subModel = '\\App\\Models\\'.$value['model'];
                    $subModelInstance = new $subModel;
                    $filter = [$value['where'][0] => $collection->{$value['where'][1]}];
                    $resp = $subModelInstance->processGetDSL($filter, $request );
                    $collection->$key = $resp->toArray();
                }
            }

            return $this->respondData($collection);

        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
