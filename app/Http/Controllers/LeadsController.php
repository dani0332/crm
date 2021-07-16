<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LeadsService;
use App\Transformers\LeadsTransformer;
use App\Models\Status;
use Illuminate\Support\Collection;

class LeadsController extends ApiController
{

    /**
     * @var LeadService
     */
    private $service;

    public function __construct(LeadsService $service)
    {
        $this->service = $service;
        //$this->transformer = ;
    }

    public function index(Request $request)
    {
        try {

            $form = $request->form;
            $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
           // dd($request->query('filter'));exit;
            //['coupon_code' => 'fsd', 'is_active' => 1, 'discount' => 0];
            $response = $this->service->getLeadListWithFilter($filters,$form);
            return $this->respondData($response);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function getFormDetail(Request $request)
    {
        try {


            $parentFormModel = config('form-models')[$request->form];
            $Model = '\\App\\Models\\'.$parentFormModel;
            $modelInstance = new $Model;
            $collection = collect($modelInstance->processGetDSL())->first();
            $collection->$parentFormModel = ['hello' => 'good'];
           // dd($collection);exit;
        //     $form = $request->form;
        //     $form_id = $request->form_id;

        //     $model_name = '\\App\\Models\\Status';
        //     $parentForm = '';
        //     echo $form. '--'.$form_id;
        //     exit;
        //     $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
        //    // dd($request->query('filter'));exit;
        //     //['coupon_code' => 'fsd', 'is_active' => 1, 'discount' => 0];
        //     $response = $this->service->getLeadListWithFilter($filters,$form);
                return $this->respondData($collection);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }

    public function save(Request $request)
    {
        try {

            $form = $request->form;
            $data = $request->all();

            $model_name = '\\App\\Models\\Status';
            $status = new $model_name;
            // $status->name = 'sdfasfssssssd';
            // $status->is_active =1;
            // $status->created_by = 'ssss@yssahoo.com';
            //$status->fill(['name' => 'Amsterdam to Frankfurt', 'is_active' => 1, 'created_by' => 'ssss@sssyssahoo.com']);
            return $this->respondData($status->saveDSL($data));
            //dd($data);exit;
        //     $filters = $request->query('filter') ? json_decode($request->query('filter'), true) : [];
        //    // dd($request->query('filter'));exit;
        //     //['coupon_code' => 'fsd', 'is_active' => 1, 'discount' => 0];
        //     $response = $this->service->getLeadListWithFilter($filters,$form);
        //     return $this->respondData($response);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage());
        }
    }
}
