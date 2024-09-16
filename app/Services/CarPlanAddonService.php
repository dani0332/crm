<?php

namespace App\Services;

use App\Models\CarAddOn;
use App\Models\CarPlanAddonBridge;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class CarPlanAddonService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('car_plan_addon as cpa')
            ->select(
                'cpa.plan_id',
                'cpa.addon_id',
                'ca.id',
                'ca.code',
                'ca.text',
                'ca.text_ar',
                'cp.text as planName',
                'ca.type',
                'ca.created_at',
                'ca.updated_at'
            )
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cpa.plan_id')
            ->leftJoin('car_addon as ca', 'ca.id', '=', 'cpa.addon_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('ca.id', $id)->first();
    }

    public function getPlanAddon($id)
    {
        return $this->query->where('cpa.plan_id', $id)->get();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('cpa.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('text_ar', $searchProperties) && isset($request->text_ar) && $request->text_ar != '') {
                $this->query->Where('cpa.text_ar', 'like', '%'.$request->text_ar.'%');
            }
            if (in_array('text', $searchProperties) && isset($request->text) && $request->text != '') {
                $this->query->Where('cpa.text', 'like', '%'.$request->text.'%');
            }
            if (in_array('code', $searchProperties) && isset($request->code) && $request->code != '') {
                $this->query->Where('cpa.code', 'like', '%'.$request->code.'%');
            }
            if (in_array('type', $searchProperties) && isset($request->type) && $request->type != '') {
                $this->query->Where('cpa.type', 'like', '%'.$request->type.'%');
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
                $column = 'cpa.code';
            }
            if ($column == 2) {
                $column = 'cpa.text';
            }
            if ($column == 3) {
                $column = 'cpa.text_ar';
            }
            if ($column == 4) {
                $column = 'cpa.type';
            }
            if ($column == 5) {
                $column = 'cpa.created_at';
            }
            if ($column == 6) {
                $column = 'cpa.updated_at';
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('cpa.created_at', 'DESC');
        }
    }

    public function saveCarPlanAddon(Request $request)
    {
        $data = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'type' => $request->type,
        ];
        $record = CarAddOn::create($data);
        $addonData = [
            'plan_id' => $request->plan_id,
            'addon_id' => $record->id,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        $carPlanAddonBridge = new CarPlanAddonBridge;
        $carPlanAddonBridge->create($addonData);

        return $record;
    }

    public function updateCarPlanAddon(Request $request, $id)
    {
        $carAddon = CarAddOn::where('id', $id)->first();
        $carAddon->text = $request->text;
        $carAddon->text_ar = $request->text_ar;
        $carAddon->code = $request->code;
        $carAddon->type = $request->type;
        $carAddon->save();

        return $carAddon;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => '|static|required|title|driverCover,passengerCover,breakdownCover,carHire,waiverOfDepreciation,omanCover,myAlfred,gccCover,fastTrackClaim',
            'text' => 'input|text|title|required',
            'text_ar' => 'input|text|title',
            'created_at' => 'input|title|date|range',
            'updated_at' => 'input|title|date',
            'type' => '|static|required|title|Select,Checkbox',
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
                $title = 'Plan Addon Name';
                break;
            case 'text_ar':
                $title = 'Plan Addon Name (Arabic)';
                break;
            case 'type':
                $title = 'Type';
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
        return ['code', 'text', 'text_ar', 'created_at', 'type'];
    }
}
