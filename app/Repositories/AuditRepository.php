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
                $q->where('auditable_id', request()->auditable_id)->where('auditable_type', $auditables['auditable_type']);
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

        return $query->orderBy('created_at', 'desc')->get();
    }
}
