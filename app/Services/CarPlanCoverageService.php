<?php

namespace App\Services;

use App\Models\CarPlanCoverage;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class CarPlanCoverageService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('car_plan_coverage as cpv')
            ->select(
                'cpv.id',
                'cpv.code',
                'cpv.text',
                'cpv.text_ar',
                'cpv.value',
                'cpv.type',
                'cpv.value_ar',
                'cpv.plan_id',
                'cp.text as planName',
                'cpv.updated_at',
                'cpv.created_at'
            )
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cpv.plan_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('cpv.id', $id)->first();
    }

    public function getPlanCoverage($id)
    {
        return $this->query->where('cpv.plan_id', $id)->get();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('cpv.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('text_ar', $searchProperties) && isset($request->text_ar) && $request->text_ar != '') {
                $this->query->Where('cpv.text_ar', 'like', '%'.$request->text_ar.'%');
            }
            if (in_array('text', $searchProperties) && isset($request->text) && $request->text != '') {
                $this->query->Where('cpv.text', 'like', '%'.$request->text.'%');
            }
            if (in_array('value', $searchProperties) && isset($request->value) && $request->value != '') {
                $this->query->Where('cpv.value', 'like', '%'.$request->value.'%');
            }
            if (in_array('code', $searchProperties) && isset($request->code) && $request->code != '') {
                $this->query->Where('cpv.code', 'like', '%'.$request->code.'%');
            }
            if (in_array('type', $searchProperties) && isset($request->type) && $request->type != '') {
                $this->query->Where('cpv.type', 'like', '%'.$request->type.'%');
            }
            if (in_array('value_ar', $searchProperties) && isset($request->value_ar) && $request->value_ar != '') {
                $this->query->Where('cpv.value_ar', 'like', '%'.$request->value_ar.'%');
            }
            foreach ($searchProperties as $item) {
                if (! empty($request[$item]) && $item != 'created_at') {
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
                $column = 'cpv.code';
            }
            if ($column == 2) {
                $column = 'cpv.text';
            }
            if ($column == 3) {
                $column = 'cpv.text_ar';
            }
            if ($column == 4) {
                $column = 'cpv.value';
            }
            if ($column == 5) {
                $column = 'cpv.value_ar';
            }
            if ($column == 6) {
                $column = 'cpv.type';
            }
            if ($column == 7) {
                $column = 'cpv.created_at';
            }
            if ($column == 8) {
                $column = 'cpv.updated_at';
            }
            if ($column == 9) {
                $column = 'cpv.plan_id';
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('cpv.created_at', 'DESC');
        }
    }

    public function saveCarPlanCoverage(Request $request)
    {
        $data = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'value' => $request->value,
            'value_ar' => $request->value_ar,
            'type' => $request->type,
            'plan_id' => $request->plan_id,
        ];

        return CarPlanCoverage::create($data);
    }

    public function updateCarPlanCoverage(Request $request, $id)
    {
        $carPlanCoverage = CarPlanCoverage::where('id', $id)->first();
        $carPlanCoverage->text = $request->text;
        $carPlanCoverage->text_ar = $request->text_ar;
        $carPlanCoverage->code = $request->code;
        $carPlanCoverage->value = $request->value;
        $carPlanCoverage->value_ar = $request->value_ar;
        $carPlanCoverage->type = $request->type;
        $carPlanCoverage->plan_id = $request->plan_id;
        $carPlanCoverage->save();

        return true;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'select|title|required',
            'text' => 'input|text|title|required',
            'text_ar' => 'input|text|title',
            'value' => 'input|text|title|required',
            'value_ar' => 'input|text|title',
            'created_at' => 'input|title|date|range',
            'updated_at' => 'input|title|date',
            'type' => 'input|title',
            'plan_id' => 'select|title',
            'planName' => 'readonly|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'code':
                $title = 'Code';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'text':
                $title = 'Plan Coverage Name';
                break;
            case 'text_ar':
                $title = 'Plan Coverage Name (Arabic)';
                break;
            case 'type':
                $title = 'Type';
                break;
            case 'value':
                $title = 'Value';
                break;
            case 'value_ar':
                $title = 'Value (Arabic)';
                break;
            case 'plan_id':
                $title = 'Plan';
                break;
            case 'planName':
                $title = 'Plan Name';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'created_at,updated_at,planName',
            'list' => '',
            'update' => 'id,created_at,updated_at,planName',
            'show' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'text', 'text_ar', 'created_at', 'value', 'value_ar', 'type'];
    }
}
