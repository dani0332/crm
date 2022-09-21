<?php

namespace App\Services;

use App\Models\Quadrants;
use App\Models\Tier;
use DB;
use Illuminate\Http\Request;

class QuadrantService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('quadrants as q')
            ->select(
                'q.id',
                'q.name',
                'q.is_active',
                'q.updated_at',
                'q.created_at',
                DB::raw('group_concat(t.name) as quad_tiers'),
            )
            ->leftJoin('tiers as t', 't.quad_id', 'q.id')
            ->groupBy('q.id');
    }

    public function getEntity($id)
    {
        return $this->query->where('q.id', $id)->first();
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
                return 'q.created_at';
                break;
            case 'updated_at':
                return 'q.updated_at';
                break;
            default:
                break;
        }
    }

    public function saveQuadrant(Request $request)
    {
        $quad = Quadrants::create([
            'name' => $request->name,
            'is_active' => $request->has('is_active') && $request->is_active == 'on' ? 1 : 0,
        ]);
        if (isset($request->quad_tiers)) {
            $tierIds = $request->quad_tiers;
            foreach ($tierIds as $tierId) {
                $tier = Tier::where('id', $tierId)->first();
                $tier->quad_id = $quad->id;
                $tier->save();
            }
        }

        return $quad;
    }

    public function updateQuadrant(Request $request, $id)
    {
        $quad = Quadrants::where('id', $id)->first();
        $quad->name = $request->name;
        $quad->is_active = $request->has('is_active') && $request->is_active == 'on' ? 1 : 0;
        $quad->save();
        $tiers = Tier::where('quad_id', $quad->id)->get();
        foreach ($tiers as $tier) {
            $tier->quad_id = null;
            $tier->save();
        }
        if (isset($request->quad_tiers)) {
            $tierIds = $request->quad_tiers;
            foreach ($tierIds as $tierId) {
                $tier = Tier::where('id', $tierId)->first();
                $tier->quad_id = $quad->id;
                $tier->save();
            }
        }

        return $quad;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|title|required',
            'is_active' => 'input|checkbox|title',
            'updated_at' => 'input|date',
            'quad_tiers' => 'select|title||multiple',
            'created_at' => 'input|date',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'name':
                $title = 'Quad Name';
                break;
            case 'is_active':
                $title = 'Is Active ?';
                break;
            case 'quad_tiers':
                $title = 'Tier Name';
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
            'list' => 'quad_tiers',
            'update' => 'id,created_at,updated_at',
            'show' => 'updated_at,quad_tiers',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['name'];
    }
}
