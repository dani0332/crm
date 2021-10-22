<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;
use App\Transformers\LeadsTransformer;
use Auth;

class FormController extends ApiController
{

    public function index(Request $request)
    {
        try {

            //sleep(1);
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $modelInstance->isGetList = true;
            $collection = collect($modelInstance->processGetDSL($filters, $request ));
            return $this->respondData($collection);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
           // sleep(1);
            $role = strtolower(Auth::user()->usersroles[0]->name);
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $modelInstance->APIController = $this;
            // $updatePermission = $modelInstance->access["update"];
            // $collection = collect($updatePermission);
            // if(!$collection->contains($role))
            //     return  $this->respondError('Access denied', 400);
            return $modelInstance->saveForm($request, true);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {

            $role = strtolower(Auth::user()->usersroles[0]->name);
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $parentFormModel = $parentFormModelColl->get('model');
            $form_id = $request->form_id;
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            if(!array_key_exists('delete',$modelInstance->access)) {
                return $this->respondError("Unable to delete");
            }

            $deletePermission = $modelInstance->access["delete"];
            $collection = collect($deletePermission);
            if(!$collection->contains($role))
                return  $this->respondError('Access denied');
            return $this->respondData($modelInstance->deleteForm($request));
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function save(Request $request)
    {
        try {
           // sleep(1);
            $role = strtolower(Auth::user()->usersroles[0]->name);
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $modelInstance->APIController = $this;
            $writePermission = $modelInstance->access["write"];
            $collection = collect($writePermission);
            if(!$collection->contains($role))
                return  $this->respondError('Access denied', 400);
            return $modelInstance->saveForm($request, false);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }


    public function getFormDetail(Request $request)
    {
        try {

            //sleep(1);
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $form_id = $request->form_id;
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $modelInstance->isGetList = false;
            $collection = collect($modelInstance->processGetDSL(["id" => $form_id], $request ))->first();
            return $this->respondData($collection);

        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
