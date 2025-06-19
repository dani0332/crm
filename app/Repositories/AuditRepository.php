<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class AuditRepository extends BaseRepository
{
    public function model()
    {
        return Audit::class;
    }

    public function fetchGetQuoteAudits()
    {

        $lobs = [
            quoteTypeCode::Health,
            quoteTypeCode::Car,
            quoteTypeCode::Travel,
            quoteTypeCode::Home,
            quoteTypeCode::Life,
            quoteTypeCode::Pet,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::Business,
            quoteTypeCode::Cycle,
            quoteTypeCode::Bike,
            quoteTypeCode::Yacht,
            quoteTypeCode::Jetski,
        ];
        $quoteObject = (in_array(ucfirst(strtolower(request()->quote_type)), $lobs)) ? app('\\App\\Models\\'.ucfirst(strtolower(request()->quote_type)).'Quote') : app('\\App\\Models\\'.request()->quote_type);

        $auditables = $quoteObject->getAuditables();
        $code = isset(request()->code) ? request()->code : '';
        $auditableTypes = ['App\Models\Payment', 'App\Models\PaymentSplits'];

        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->where(function ($q) use ($auditables) {
                if (request()->has('auditable_id') && request()->auditable_id) {
                    $q->where('auditable_id', request()->auditable_id)->where('auditable_type', $auditables['auditable_type']);
                } else {
                    $q->where('auditable_type', $auditables['auditable_type']);
                }
            });
        /*if ($code != '') {
            $query->orWhere(function ($query) use ($code, $auditableTypes) {
                $query->where('old_values', 'like', '%"code":"'.$code.'"%')
                    ->whereIn('auditable_type', $auditableTypes);
            });
        }*/
        if (! empty($auditables['relations'])) {
            foreach ($auditables['relations'] as $relation) {
                $model = $relation['auditable_type'];
                if ($childRecord = $model::where($relation['key'], request()->auditable_id)->first()) {
                    $query->orWhere(function ($q) use ($relation, $childRecord) {
                        $q->where('auditable_type', $relation['auditable_type'])->where('auditable_id', $childRecord->id);
                    });
                }
            }
        }
        $results = $query->orderBy('created_at', 'desc')->get();

        $results->transform(function ($audit) {
            $newValues = json_decode($audit->new_values, true) ?? [];
            $oldValues = json_decode($audit->old_values, true) ?? [];

            $fieldMap = [
                'pcp_tag' => 'Private Client',
                'pc_qualified' => 'PC-Qualified',
            ];

            $transformValue = function ($value) {
                if ($value === 1 || $value === true) {
                    return 'Yes';
                } elseif ($value === 0 || $value === false) {
                    return 'Ex-PC';
                } elseif (is_null($value)) {
                    return 'No';
                }

                return $value;
            };

            $transformedNew = [];
            foreach ($newValues as $key => $value) {
                if (isset($fieldMap[$key])) {
                    $transformedNew[$fieldMap[$key]] = $transformValue($value);
                } else {
                    $transformedNew[$key] = $value;
                }
            }

            $transformedOld = [];
            foreach ($oldValues as $key => $value) {
                if (isset($fieldMap[$key])) {
                    $transformedOld[$fieldMap[$key]] = $transformValue($value);
                } else {
                    $transformedOld[$key] = $value;
                }
            }

            $audit->new_values = json_encode($transformedNew);
            $audit->old_values = json_encode($transformedOld);

            return $audit;
        });

        return $results;
    }
}
