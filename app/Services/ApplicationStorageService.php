<?php

namespace App\Services;

use App\Models\ApplicationStorage;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class ApplicationStorageService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('application_storage as as')
            ->select(
                'as.id',
                'as.key_name',
                'as.value',
                'as.updated_at',
                'as.created_at',
                'as.is_active'
            );
    }

    public function getEntity($id)
    {
        return $this->query->where('as.id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('as.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('key_name', $searchProperties) && isset($request->key_name) && $request->key_name != '') {
                $this->query->Where('key_name', 'like', '%'.$request->key_name.'%');
            }
            if (in_array('value', $searchProperties) && isset($request->value) && $request->value != '') {
                $this->query->Where('value', 'like', '%'.$request->value.'%');
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
            if ($column == 0) {
                $column = 'as.id';
            }
            if ($column == 1) {
                $column = 'as.key_name';
            }
            if ($column == 2) {
                $column = 'as.value';
            }
            if ($column == 3) {
                $column = 'as.created_at';
            }
            if ($column == 4) {
                $column = 'as.updated_at';
            }
            if ($column == 5) {
                $column = 'as.is_active';
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('as.created_at', 'DESC');
        }
    }

    public function saveApplicationStorage(Request $request)
    {
        $applicationStorage = new ApplicationStorage;
        $applicationStorage->key_name = $request->key_name;
        $applicationStorage->value = $request->value;
        $applicationStorage->is_active = 1;
        $applicationStorage->save();

        return $applicationStorage;
    }

    public function updateApplicationStorage(Request $request, $id)
    {
        $applicationStorage = ApplicationStorage::where('id', $id)->first();
        $applicationStorage->key_name = $request->key_name;
        $applicationStorage->value = $request->value;
        $applicationStorage->is_active = $request->is_active == 'on' ? 1 : 0;
        $applicationStorage->save();

        return true;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'key_name' => 'input|title|required',
            'value' => 'input|text|title|required',
            'created_at' => 'input|title|date|range',
            'updated_at' => 'input|title|date',
            'is_active' => 'input|checkbox',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'key_name':
                $title = 'Key Name';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'value':
                $title = 'Value';
                break;
            case 'is_active':
                $title = 'Is Active';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'created_at,updated_at',
            'list' => '',
            'update' => 'id,created_at,updated_at',
            'show' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['key_name', 'value', 'created_at'];
    }

    public function getIsActiveByKey($keyName)
    {
        $query = ApplicationStorage::select('is_active')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->is_active;
    }

    public function getValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public static function getValueByKeyName($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function updateLeadAllocationJobStatus()
    {
        $applicationStorage = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_JOB_SWITCH')->first();
        $applicationStorage->value = $applicationStorage->value == 1 ? 0 : 1;
        $applicationStorage->save();

        return true;
    }

    public function updateCarLeadAllocationJobStatus()
    {
        $applicationStorage = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_MASTER_SWITCH')->first();
        $applicationStorage->value = $applicationStorage->value == 1 ? 0 : 1;
        $applicationStorage->save();

        return true;
    }

    public function updateRenewalCarLeadAllocationStatus()
    {
        $applicationStorage = ApplicationStorage::where('key_name', 'CAR_RENEWAL_LEAD_ALLOCATION')->first();
        $applicationStorage->value = $applicationStorage->value == 1 ? 0 : 1;
        $applicationStorage->save();

        return true;
    }
    public function updateCarLeadFetchSequence()
    {
        $applicationStorage = ApplicationStorage::where('key_name', 'CAR_LEAD_PICKUP_FIFO')->first();
        $applicationStorage->value = $applicationStorage->value == 1 ? 0 : 1;
        $applicationStorage->save();

        return true;
    }
}
