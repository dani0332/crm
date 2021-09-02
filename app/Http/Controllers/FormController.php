<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;
use App\Transformers\LeadsTransformer;
use App\Jobs\MailServiceJob;

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
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            return $this->respondData($modelInstance->saveForm($request, true));
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {

            // $request->to = 'muhammad.amjad@afia.ae';
            // $request->subject = 'Car Quote Request Policy Information';
            // $request->templateName = 'demo';

            $params = [
                'to' => 'muhammad.amjad@afia.ae',
                'subject' => 'Hello Testing',
                'templateName' => 'demo',
                'templateParams' => ['first_name' => "Amjad", 'last_name' => "LastName"],
            ];
            //$request->templateParams = $request->all()['data'];
            $json = json_encode($params);
            dispatch(new MailServiceJob($json));

            return $this->respondData(["data" => true]);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function save(Request $request)
    {
        try {
           // sleep(1);
            $role = 'advisor';
            $parentFormModelColl = collect(config('form-models')[$request->form]);
            $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
            $parentFormModel = $parentFormModelColl->get('model');
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $writePermission = $modelInstance->access["write"];
            $collection = collect($writePermission);
            if(!$collection->contains($role))
                return  $this->respondError('Access denied');
            return $this->respondData($modelInstance->saveForm($request));
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
