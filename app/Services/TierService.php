<?php

namespace App\Services;

use App\Models\Tier;
use DB;
use Illuminate\Http\Request;
use stdClass;

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
                't.can_handle_null_value',
                't.can_handle_ecommerce',
                't.is_active',
                't.updated_at',
                't.created_at',
                't.can_handle_tpl',
                't.is_tpl_renewals',
                DB::raw('group_concat(u.name) AS tier_users'),
            )
            ->leftJoin('tier_users as tu', 'tu.tier_id', 't.id')
            ->leftJoin('users as u', 'u.id', 'tu.user_id')
            ->groupBy('t.id', 't.name');
    }

    public function getEntity($id)
    {
        return $this->query->where($this->searchPrefix.'id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $this->query = addSearchClauses($model, $request, $this->query, $this->searchPrefix);
        $this->query = addOrderByClauses($request, $this->query, $this->searchPrefix);

        return $this->query;
    }

    public function saveTier(Request $request)
    {
        if (! isset($request->min_price) && Tier::whereNull('min_price')->get() != null) {
            $errorResponse = new stdClass();
            $errorResponse->message = 'Error: Only one tier can have null as minimum price';

            return $errorResponse;
        }
        if (! isset($request->min_price) && Tier::whereNull('max_price')->get() != null) {
            $errorResponse = new stdClass();
            $errorResponse->message = 'Error: Only one tier can have null as maximum price';

            return $errorResponse;
        }
        $tier = Tier::create([
            'name' => $request->name,
            'min_price' => isset($request->min_price) ? $request->min_price : null,
            'max_price' => isset($request->max_price) ? $request->max_price : null,
            'cost_per_lead' => $request->cost_per_lead,
            'can_handle_null_value' => $request->has('can_handle_null_value') && $request->can_handle_null_value == 'on' ? 1 : 0,
            'can_handle_ecommerce' => $request->has('can_handle_ecommerce') && $request->can_handle_ecommerce == 'on' ? 1 : 0,
            'can_handle_tpl' => $request->has('can_handle_tpl') && $request->can_handle_tpl == 'on' ? 1 : 0,
            'is_tpl_renewals' => $request->has('is_tpl_renewals') && $request->is_tpl_renewals == 'on' ? 1 : 0,
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
        if (! isset($request->min_price) && Tier::whereNull('min_price')->where('id', '!=', $id)->get() != null) {
            $errorResponse = new stdClass();
            $errorResponse->message = 'Error: Only one tier can have null as minimum price';

            return $errorResponse;
        }
        if (! isset($request->min_price) && Tier::whereNull('max_price')->where('id', '!=', $id)->get() != null) {
            $errorResponse = new stdClass();
            $errorResponse->message = 'Error: Only one tier can have null as maximum price';

            return $errorResponse;
        }
        $tier = Tier::where('id', $id)->first();
        $tier->name = $request->name;
        $tier->min_price = $request->min_price;
        $tier->max_price = $request->max_price;
        $tier->cost_per_lead = $request->cost_per_lead;
        $tier->can_handle_null_value = $request->has('can_handle_null_value') && $request->can_handle_null_value == 'on' ? 1 : 0;
        $tier->can_handle_ecommerce = $request->has('can_handle_ecommerce') && $request->can_handle_ecommerce == 'on' ? 1 : 0;
        $tier->can_handle_tpl = $request->has('can_handle_tpl') && $request->can_handle_tpl == 'on' ? 1 : 0;
        $tier->is_tpl_renewals = $request->has('is_tpl_renewals') && $request->is_tpl_renewals == 'on' ? 1 : 0;
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
            'min_price' => 'input|number|title|equalSearch',
            'max_price' => 'input|number|title|equalSearch',
            'cost_per_lead' => 'input|number|title|equalSearch',
            'tier_users' => 'select|multiple|multiSearch',
            'can_handle_ecommerce' => 'input|checkbox|title',
            'can_handle_null_value' => 'input|checkbox|title',
            'can_handle_tpl' => 'input|checkbox|title',
            'is_tpl_renewals' => 'input|checkbox|title',
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
            case 'can_handle_ecommerce':
                $title = 'Handle Ecommerce ?';
                break;
            case 'can_handle_tpl':
                $title = 'IsTPL ?';
                break;
            case 'can_handle_null_value':
                $title = 'Handle Null Value ?';
                break;
            case 'is_active':
                $title = 'Is Active ?';
                break;
            case 'is_tpl_renewals':
                $title = 'Handle Renewal Leads ?';
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
            'update' => 'created_at,updated_at',
            'show' => 'created_at,updated_at,tier_users',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['name', 'min_price', 'max_price', 'created_at'];
    }
}
