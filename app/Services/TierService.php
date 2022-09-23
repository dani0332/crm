<?php

namespace App\Services;

use App\Models\Tier;
use DB;
use Illuminate\Http\Request;

class TierService extends BaseService
{
    protected $query;
    protected $searchPrefix = 't.';
    public function __construct()
    {
        $this->query = DB::table('tiers as t')
            ->select(
                't.id',
                't.name',
                't.min_price',
                't.max_price',
                't.cost_per_lead',
                't.is_tpl',
                't.is_auto_assignment_enabled',
                't.is_active',
                't.updated_at',
                't.created_at',
                DB::raw('group_concat(u.name) AS tier_users'),
            )
            ->leftJoin('tier_users as tu', 'tu.tier_id', 't.id')
            ->leftJoin('users as u', 'u.id', 'tu.user_id')
            ->groupBy('t.id', 't.name');
    }

    public function getEntity($id)
    {
        return $this->query->where($this->searchPrefix . 'id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $this->query = addSearchClauses($model, $request, $this->query, $this->searchPrefix);
        $this->query = addOrderByClauses($request,$this->query, $this->searchPrefix);
        return $this->query;
    }

    public function saveTier(Request $request)
    {
        $tier = Tier::create([
            'name' => $request->name,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'cost_per_lead' => $request->cost_per_lead,
            'is_tpl' => $request->has('is_tpl') && $request->is_tpl == 'on' ? 1 : 0,
            'is_auto_assignment_enabled' => $request->has('is_auto_assignment_enabled') && $request->is_auto_assignment_enabled == 'on' ? 1 : 0,
            'is_active' => $request->has('is_active') && $request->is_active == 'on' ? 1 : 0,
        ]);

        if (isset($request->tier_users)) {
            DB::table('tier_users')->where('tier_id', $tier->id)->delete();
            $userIds = $request->tier_users;
            foreach ($userIds as $userId) {
                DB::table('tier_users')->insert([
                    'tier_id' => $tier->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $tier;
    }

    public function updateTier(Request $request, $id)
    {
        $tier = Tier::where('id', $id)->first();
        $tier->name = $request->name;
        $tier->min_price = $request->min_price;
        $tier->max_price = $request->max_price;
        $tier->cost_per_lead = $request->cost_per_lead;
        $tier->is_tpl = $request->has('is_tpl') && $request->is_tpl == 'on' ? 1 : 0;
        $tier->is_auto_assignment_enabled = $request->has('is_auto_assignment_enabled') && $request->is_auto_assignment_enabled == 'on' ? 1 : 0;
        $tier->is_active = $request->has('is_active') && $request->is_active == 'on' ? 1 : 0;
        $tier->save();
        if (isset($request->tier_users)) {
            DB::table('tier_users')->where('tier_id', $tier->id)->delete();
            $userIds = $request->tier_users;
            foreach ($userIds as $userId) {
                DB::table('tier_users')->insert([
                    'tier_id' => $tier->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $tier;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'created_at' => 'input|title|date|range|dateRange',
            'name' => 'input|title|required|likeSearch',
            'min_price' => 'input|number|title|required|equalSearch',
            'max_price' => 'input|number|title|required|equalSearch',
            'cost_per_lead' => 'input|number|title|equalSearch',
            'tier_users' => 'select|multiple|multiSearch',
            'is_tpl' => 'input|checkbox|title',
            'is_auto_assignment_enabled' => 'input|checkbox|title',
            'is_active' => 'input|checkbox|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'name':
                $title = 'Tier Name';
                break;
            case 'min_price':
                $title = 'Min. Price';
                break;
            case 'max_price':
                $title = 'Max. Price';
                break;
            case 'cost_per_lead':
                $title = 'Cost Per Lead';
                break;
            case 'is_tpl':
                $title = 'Is TPL ?';
                break;
            case 'is_auto_assignment_enabled':
                $title = 'Auto Assign ?';
                break;
            case 'is_active':
                $title = 'Is Active ?';
                break;
            case 'created_at':
                $title = 'Created Date';
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
            'list' => 'tier_users',
            'update' => 'id,created_at,updated_at',
            'show' => 'created_at,updated_at,tier_users',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['name', 'min_price', 'max_price', 'created_at'];
    }
}
