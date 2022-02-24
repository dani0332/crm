<?php

namespace App\Services;

use App\Models\CarPlan;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Config;

class CarPlanService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('car_plan as cp')
            ->select(
                'cp.id',
                'cp.code',
                'cp.text',
                'cp.repair_type',
                'cp.insurance_type',
                'cp.provider_id as pid',
                'ip.text as provider_id',
                'cp.updated_at',
                'cp.created_at',
                'cp.text_ar',
                'cp.is_active'
            )
            ->leftJoin('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id');
    }

    public function getEntity($id)
    {
        return $this->query->where('cp.id', $id)->first();
    }
    public function getProvderPlans($id)
    {
        return $this->query->where('cp.provider_id', $id)->get();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('cp.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('text_ar', $searchProperties) && isset($request->text_ar) && $request->text_ar != "") {
                $this->query->Where('cp.text_ar', 'like', '%' . $request->text_ar . '%');
            }
            if (in_array('text', $searchProperties) && isset($request->text) && $request->text != "") {
                $this->query->Where('cp.text', 'like', '%' . $request->text . '%');
            }
            if (in_array('code', $searchProperties) && isset($request->code) && $request->code != "") {
                $this->query->Where('cp.code', 'like', '%' . $request->code . '%');
            }
            if (in_array('repair_type', $searchProperties) && isset($request->repair_type) && $request->repair_type != "") {
                $this->query->Where('cp.repair_type', 'like', '%' . $request->repair_type . '%');
            }
            if (in_array('insurance_type', $searchProperties) && isset($request->insurance_type) && $request->insurance_type != "") {
                $this->query->Where('cp.insurance_type', 'like', '%' . $request->insurance_type . '%');
            }
            if (in_array('provider_id', $searchProperties) && isset($request->provider_id) && $request->provider_id != "") {
                $this->query->Where('cp.provider_id', 'like', '%' . $request->provider_id . '%');
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    }
                }
            }
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
                if ($column == 1) {
                    $column = "cp.code";
                }
                if ($column == 2) {
                    $column = "cp.text";
                }
                if ($column == 3) {
                    $column = "cp.text_ar";
                } 
                if ($column == 4) {
                    $column = "cp.repair_type";
                } 
                if ($column == 5) {
                    $column = "cp.insurance_type";
                }     
                if ($column == 6) {
                    $column = "cp.created_at";
                }
                if ($column == 7) {
                    $column = "cp.updated_at";
                }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('cp.created_at', 'DESC');
        }
    }

    public function saveCarPlan(Request $request)
    {
        $data = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'repair_type' => $request->repair_type,
            'insurance_type' => $request->insurance_type,
            'provider_id' => $request->provider_id,
            'is_active' => $request->has("is_active") ? 1 : 0 
        ];
        return CarPlan::create($data);
    }

    public function updateCarPlan(Request $request, $id)
    {
        $updateArray = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'repair_type' => $request->repair_type,
            'insurance_type' => $request->insurance_type,
            'provider_id' => $request->provider_id,
            'is_active' => $request->has("is_active") ? $request->is_active : 0
        ];
        CarPlan::where('id', $id)->update($updateArray);
        return true;
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "code" => "input|title|required",
            "text" => "input|text|title|required",
            "text_ar" => "input|text|title|required",
            "repair_type" => "input|text|title|required",
            "insurance_type" => "input|text|title|required",
            "provider_id" => "select|title",
            "created_at" => "input|title|date|range",
            "updated_at" => "input|title|date",
            "is_active" => "input|checkbox"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'code':
                $title = "Code";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'text':
                $title = "Plan Name";
                break;
            case 'text_ar':
                $title = "Plan Name (Arabic)";
                break;
            case 'repair_type':
                $title = "Repair Type";
                break;
            case 'insurance_type':
                $title = "Insurance Type";
                break;
            case 'provider_id':
                $title = "Provder Name";
                break;
            case 'is_active':
                $title = "Is Active";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "created_at,updated_at",
            "list" => "",
            "update" => "id,created_at,updated_at",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'text', 'text_ar','created_at','repair_type','insurance_type','provider_id'];
    }
}
