<?php

namespace App\Services;

use App\Models\CarAddOnOption;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class CarPlanAddOnOptionService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('car_addon_option as cao')
            ->select(
                'cao.value',
                'cao.value_ar',
                'cao.addon_id',
                'cao.id',
                'cao.price',
                'ca.text',
                'cao.sort_order',
                'cao.created_at',
                'cao.updated_at'
            )
            ->leftJoin('car_addon as ca', 'ca.id', '=', 'cao.addon_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('cao.id', $id)->first();
    }

    public function getPlanAddonOption($id)
    {
        return $this->query->where('cao.addon_id', $id)->get();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('cao.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('value_ar', $searchProperties) && isset($request->value_ar) && $request->value_ar != '') {
                $this->query->Where('cao.value_ar', 'like', '%'.$request->value_ar.'%');
            }
            if (in_array('value', $searchProperties) && isset($request->value) && $request->value != '') {
                $this->query->Where('cao.value', 'like', '%'.$request->value.'%');
            }
            if (in_array('price', $searchProperties) && isset($request->price) && $request->price != '') {
                $this->query->Where('cao.price', 'like', '%'.$request->price.'%');
            }
            if (in_array('addon_id', $searchProperties) && isset($request->addon_id) && $request->addon_id != '') {
                $this->query->Where('cao.addon_id', 'like', '%'.$request->addon_id.'%');
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
                $column = 'cao.value';
            }
            if ($column == 2) {
                $column = 'cao.value_ar';
            }
            if ($column == 3) {
                $column = 'cao.price';
            }
            if ($column == 4) {
                $column = 'cao.addon_id';
            }
            if ($column == 5) {
                $column = 'cao.created_at';
            }
            if ($column == 6) {
                $column = 'cao.updated_at';
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('cao.created_at', 'DESC');
        }
    }

    public function saveCarPlanAddonOption(Request $request)
    {
        $data = [
            'addon_id' => $request->addon_id,
            'price' => $request->price,
            'value' => $request->value,
            'value_ar' => $request->value_ar,
            'sort_order' => $request->sort_order,
        ];

        return CarAddOnOption::create($data);
    }

    public function updateCarPlanAddOnOption(Request $request, $id)
    {
        $carAdddonOption = CarAddOnOption::where('id', $id)->first();
        $carAdddonOption->price = $request->price;
        $carAdddonOption->value = $request->value;
        $carAdddonOption->value_ar = $request->value_ar;
        $carAdddonOption->sort_order = $request->sort_order;
        $carAdddonOption->save();

        return true;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'value' => 'input|title|required',
            'value_ar' => 'input|text|title',
            'price' => 'input|number|title|required',
            'created_at' => 'input|title|date|range',
            'updated_at' => 'input|title|date',
            'addon_id' => 'readonly|title',
            'text' => 'readonly|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'value':
                $title = 'Value';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'value_ar':
                $title = 'Value (Arabic)';
                break;
            case 'price':
                $title = 'Price';
                break;
            case 'addon_id':
                $title = 'Addon';
                break;
            case 'text':
                $title = 'Addon Name';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'created_at,updated_at,text',
            'list' => '',
            'update' => 'id,created_at,updated_at,text',
            'show' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['price', 'value', 'value_ar', 'created_at', 'type'];
    }
}
