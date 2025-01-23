<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BaseModel extends Model implements AuditableContract
{
    use Auditable , HasFactory;
    use SoftDeletes;

    public $isGetList = false;
    public $APIController = null;

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::user()->email;
                $model->updated_by = Auth::user()->email;
            }
        });
        static::updating(function ($model) {
            if (Auth::check()) {
                if ($model->getConnection()
                    ->getSchemaBuilder()
                    ->hasColumn($model->getTable(), 'updated_by')) {
                    $model->updated_by = Auth::user()->email;
                }
            }
        });
    }

    public function deleteForm($request)
    {
        $form_id = $request->form_id;
        $model = self::find($form_id);

        return $model->delete($request);
    }

    public function saveForm($request, $update)
    {
        $role = strtolower(Auth::user()->usersroles[0]->name);
        $input = $request->all();
        $collection = collect($this->access);
        $access = collect($collection->get('access')[$role]);

        if ($update == true) {
            if ($request->input('subform')) {
                $car_quote_id = $request->input('car_quote_id');
                $model = VehicleDetailCarQuote::where('car_quote_id', $car_quote_id)->first();
                $data = $request->input('subform');
                if ($model) {
                    $collection = collect($model->access);
                    $access = collect($collection->get('access')[$role]);
                    foreach ($access as $key) {
                        if ($data[$key]) {
                            $model->$key = $data[$key];
                        }
                    }

                    return $model->save();
                } else {
                    $model = new VehicleDetailCarQuote;
                    $collection = collect($model->access);
                    $access = collect($collection->get('access')[$role]);
                    foreach ($access as $key) {
                        if ($data[$key]) {
                            $model->$key = $data[$key];
                        }
                    }

                    return $model->save();
                }
            }

            $form_id = $request->form_id;
            $model = self::find($form_id);

            foreach ($access as $key) {
                if ($request->has($key)) {
                    $model->$key = $request->input($key);
                }
            }

            return $model->save();
        } else {
            foreach ($access as $key) {
                if ($request->has($key)) {
                    $this->$key = $request->input($key);
                }
            }

            return $this->save();
        }
    }

    private function table($filters)
    {
        $role = strtolower(Auth::user()->usersroles[0]->name); // 'advisor';
        $collection = collect($this->access);
        $access = collect($collection->get('list'));
        if (! $access->has($role)) {
            $access = collect($collection->get('list'));
        } else {
            $access = collect($collection->get('list')[$role]);
        }

        $response = DB::table($this->table)
            ->select($access->toArray())
            ->where(function ($query) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (is_array($value)) {
                        switch ($value['op']) {
                            case 'in':
                                $query->whereIn($key, $value['val']);
                                break;
                            default:
                                $query->where($key, $value['op'], $value['val']);
                        }
                    } else {
                        $query->where($key, $value);
                    }
                }
            })
            ->get();

        return $response;
    }

    public function scopeRelationWhere($query, $isGetList, $filters)
    {
        // $query->whereHas('insurance_coverage', function($q) {
        //     // Query the name field in status table
        //     $q->where('car_quote_id', '=', 1377); // '=' is optional
        // });
    }

    private function relation($filters)
    {
        $role = strtolower(Auth::user()->usersroles[0]->name); // 'advisor';
        $collection = collect($this->access);
        $access = collect($collection->get('list'));
        if (! $access->has($role)) {
            $access = collect($collection->get('list'));
        } else {
            $access = collect($collection->get('list')[$role]);
        }

        if (! $this->isGetList && $collection->get('detail')) {
            $access = collect($collection->get('detail')[$role]);
        }

        //  DB::enableQueryLog();
        $response = self::with($this->relations())
            ->select($access->toArray())
            ->where(function ($query) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (is_array($value)) {
                        switch ($value['op']) {
                            case 'in':
                                $query->whereIn($key, $value['val']);
                                break;
                            default:

                                if ($value['op'] == '<>' && $value['val'] == 'null') {
                                    $query->whereNotNull($key);
                                } else {
                                    $query->where($key, $value['op'], $value['val']);
                                }
                        }
                    } else {
                        $query->where($key, $value);
                    }
                }
            })
            ->relationWhere($this->isGetList, $filters)
            ->get();

        // dd($response);exit;
        // $query = DB::getQueryLog();
        // dd($query);exit;
        return $response;
    }
}
