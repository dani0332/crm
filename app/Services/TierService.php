<?php

namespace App\Services;

use App\Models\Tier;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class TierService extends BaseService
{
    protected $query;

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
            );
    }

    public function getEntity($id)
    {
        return $this->query->where('t.id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $columnName = $request->get('columns')[$column]['name'];
            return $this->query->orderBy($this->getSortingColumnNameWithPrefix($columnName), $direction);
        } else {
            return $this->query->orderBy('t.created_at', 'DESC');
        }
    }
    private function getSortingColumnNameWithPrefix($columnName)
    {
        switch ($columnName) {
            case 'created_at':
                return 't.created_at';
                break;
            case 'updated_at':
                return 't.updated_at';
                break;
            case 'next_followup_date':
                return 'cqrd.next_followup_date';
                break;
            default:
                break;
        }
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
        return $tier;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|title|required',
            'min_price' => 'input|number|title|required',
            'max_price' => 'input|number|title|required',
            'cost_per_lead' => 'input|number|title',
            'is_tpl' => 'input|checkbox|title',
            'is_auto_assignment_enabled' => 'input|checkbox|title',
            'is_active' => 'input|checkbox|title'
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
                $title = 'Auto Assign Enabled ?';
                break;
            case 'is_active':
                $title = 'Is Active ?';
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
        return ['name', 'min_price', 'max_price'];
    }
}
