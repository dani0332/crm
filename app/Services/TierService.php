<?php

namespace App\Services;

use App\Enums\TiersEnum;
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
        if (isset($request->min_price) && isset($request->max_price) && ($request->min_price > $request->max_price)) {
            $errorResponse = new stdClass;
            $errorResponse->message = 'Error: Min price should be less then Max price';

            return $errorResponse;
        }

        if (isset($request->min_price) && isset($request->max_price) && ($request->max_price < $request->min_price)) {
            $errorResponse = new stdClass;
            $errorResponse->message = 'Error: Max price should be greater then Min price';

            return $errorResponse;
        }

        if (! isset($request->min_price) && Tier::whereNull('min_price')->get() != null) {
            $errorResponse = new stdClass;
            $errorResponse->message = 'Error: Only one tier can have null as minimum price';

            return $errorResponse;
        }
        if (! isset($request->min_price) && Tier::whereNull('max_price')->get() != null) {
            $errorResponse = new stdClass;
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
        $tier = Tier::where('id', $id)->first();
        $tier->min_price = $request->min_price;
        $tier->max_price = $request->max_price;
        $tier->cost_per_lead = floatval($request->cost_per_lead);
        $tier->can_handle_null_value = $request->has('can_handle_null_value') && $request->can_handle_null_value == 'on' ? 1 : 0;
        $tier->can_handle_ecommerce = $request->has('can_handle_ecommerce') && $request->can_handle_ecommerce == 'on' ? 1 : 0;
        $tier->can_handle_tpl = $request->has('can_handle_tpl') && $request->can_handle_tpl == 'on' ? 1 : 0;
        $tier->is_tpl_renewals = $request->has('is_tpl_renewals') && $request->is_tpl_renewals == 'on' ? 1 : 0;
        $tier->is_active = $request->has('is_active') && $request->is_active == 'on' ? 1 : 0;
        $tier->save();
        DB::table('tier_users')->where('tier_id', $tier->id)->delete();
        if (isset($request->tier_users)) {
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
        info('------ Tier update is successfully done by user : '.auth()->user()->id.' for tier : '.$tier->name.' ------');

        return $tier;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'created_at' => 'input|title|date|range|dateRange',
            'name' => 'input|title|required|likeSearch|disabled',
            'min_price' => 'input|number|title|equalSearch|min:1',
            'max_price' => 'input|number|title|equalSearch|max:999999999',
            'cost_per_lead' => 'input|number|title|equalSearch|min:1',
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
                $title = 'Is Ecommerce ?';
                break;
            case 'can_handle_tpl':
                $title = 'Is TPL ?';
                break;
            case 'can_handle_null_value':
                $title = 'Null Value ?';
                break;
            case 'is_active':
                $title = 'Is Active ?';
                break;
            case 'is_tpl_renewals':
                $title = 'Renewal (TPL_RENEWALS)?';
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

    public function fillSortingProperties()
    {
        return ['id', 'name', 'min_price', 'max_price'];
    }

    public function getTPLTiers()
    {
        return Tier::where('can_handle_tpl', 1)->where('is_active', 1)->orderBy('name')->get();
    }

    public function getCompTiers()
    {
        return Tier::where('can_handle_tpl', 1)->where('is_active', 1)->orderBy('name')->get();
    }

    public function getTiersExceptTierR()
    {
        return Tier::where('name', '!=', TiersEnum::TIER_R)->where('is_active', 1)->orderBy('name')->get();
    }

    public function isTierRAssigned($leadAssignedTierId)
    {
        $assignedTier = Tier::where('id', $leadAssignedTierId)->where('is_active', 1)->orderBy('name')->first();

        if ($assignedTier != null) {
            return str_contains($assignedTier->name, TiersEnum::TIER_R);
        }

        return false;
    }
}
