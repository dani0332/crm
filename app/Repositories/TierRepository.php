<?php

namespace App\Repositories;

use Carbon\Carbon;

class TierRepository extends BaseRepository
{
    public function model()
    {
        return \App\Models\Tier::class;
    }

    public function fetchGetData()
    {

        $data = $this->orderBy('created_at', 'desc');

        $data->when(request()->name, function ($query, $name) {
            return $query->where('name', 'LIKE', '%'.$name.'%');
        })
            ->when(request()->min_price, function ($query, $minPrice) {
                return $query->where('min_price', $minPrice);
            })
            ->when(request()->max_price, function ($query, $maxPrice) {
                return $query->where('max_price', $maxPrice);
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

    public static function deleteTier($id)
    {
        $tier = self::findOrFail($id);
        $tier->users()->detach();
        $tier->delete();

        return true;
    }
}
