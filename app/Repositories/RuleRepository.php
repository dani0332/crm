<?php

namespace App\Repositories;

use App\Models\RuleType;
use Carbon\Carbon;

class RuleRepository extends BaseRepository
{
    public function model()
    {
        return \App\Models\Rule::class;
    }

    public function fetchGetData()
    {

        $data = $this->orderBy('created_at', 'desc');

        $data->when(request()->name, function ($query, $name) {
            return $query->where('name', 'LIKE', '%'.$name.'%');
        })
            ->when(request()->cost_per_lead, function ($query, $costPerLead) {
                return $query->where('cost_per_lead', $costPerLead);
            })
            ->when(request()->created_at && request()->created_at_end, function ($query) {
                $dateFrom = Carbon::createFromFormat('Y-m-d', request()->created_at)->startOfDay();
                $dateTo = Carbon::createFromFormat('Y-m-d', request()->created_at)->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            });

        return $data->simplePaginate(10)->withQueryString();
    }

    public static function getRuleTypes()
    {
        return RuleType::select('id', 'name')->get();
    }

    public static function deleteRule($id)
    {
        $rule = self::findOrFail($id);
        $rule->users()->detach();
        $rule->delete();

        return true;
    }
}
