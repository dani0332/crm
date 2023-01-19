<?php

namespace App\Services;

use App\Models\Quadrant;
use DB;
use Illuminate\Http\Request;

class QuadrantService extends BaseService
{
    protected $query;
    protected $searchPrefix = 'q.';
    public function __construct()
    {
        $this->query = DB::table('quadrants as q')
            ->select(
                'q.id',
                'q.name',
                'q.is_active',
                'q.updated_at',
                'q.created_at',
                DB::raw('GROUP_CONCAT(DISTINCT t.name ORDER BY t.id SEPARATOR ",") AS quad_tiers'),
                DB::raw('GROUP_CONCAT(DISTINCT u.name ORDER BY u.id SEPARATOR ",") AS quad_users'),
            )
            ->leftJoin('quad_tiers as qt', 'qt.quad_id', 'q.id')
            ->leftJoin('tiers as t', 't.id', 'qt.tier_id')
            ->leftJoin('quad_users as qu', 'qu.quad_id', 'q.id')
            ->leftJoin('users as u', 'u.id', 'qu.user_id')
            ->groupBy('q.id', 'q.name');
    }

    public function getEntity($id)
    {
        return $this->query->where('q.id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $this->query = addSearchClauses($model, $request, $this->query, $this->searchPrefix);
        $this->query = addOrderByClauses($request, $this->query, $this->searchPrefix);

        return $this->query;
    }

    public function saveQuadrant(Request $request)
    {
        $quad = Quadrant::create([
            'name' => $request->name,
            'is_active' => $request->has('is_active') && $request->is_active == 'on' ? 1 : 0,
        ]);
        if (isset($request->quad_tiers)) {
            $this->addTiersAgainstQuad($request->quad_tiers, $quad->id);
        }
        if (isset($request->quad_users)) {
            $this->addUserAgainstQuadAndTiers($request->quad_users, $quad->id);
        }

        return $quad;
    }

    private function addTiersAgainstQuad($tierIds, $quadId)
    {
        foreach ($tierIds as $tierId) {
            DB::table('quad_tiers')->insert([
                'quad_id' => $quadId,
                'tier_id' => $tierId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addUserAgainstQuadAndTiers($userIds, $quadId)
    {
        foreach ($userIds as $userId) {
            $quadUsers = DB::table('quad_users')->where('quad_id', $quadId)->where('user_id', $userId)->get();
            if (count($quadUsers) == 0) {
                DB::table('quad_users')->insert([
                    'quad_id' => $quadId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $quadTierIds = DB::table('quad_tiers')->where('quad_id', $quadId)->pluck('tier_id');
            foreach ($quadTierIds as $quadTierId) {
                $tierUserId = DB::table('tier_users')->where('tier_id', $quadTierId)->where('user_id', $userId)->get();
                if (count($tierUserId) == 0) {
                    DB::table('tier_users')->insert([
                        'tier_id' => $quadTierId,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function updateQuadrant(Request $request, $id)
    {
        $quad = Quadrant::where('id', $id)->first();
        $quad->name = $request->name;
        $quad->is_active = $request->has('is_active') && $request->is_active == 'on' ? 1 : 0;
        $quad->save();
        DB::table('quad_tiers')->where('quad_id', $quad->id)->delete();
        if (isset($request->quad_tiers)) {
            $this->addTiersAgainstQuad($request->quad_tiers, $quad->id);
        }
        DB::table('quad_users')->where('quad_id', $quad->id)->delete();
        if (isset($request->quad_users)) {
            $this->addUserAgainstQuadAndTiers($request->quad_users, $quad->id);
        }
        info('------ Quadrant update is successfully done by user : '.auth()->user()->id.' for Quad : '.$quad->name.' ------');

        return $quad;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|title|required|likeSearch',
            'is_active' => 'input|checkbox|title',
            'updated_at' => 'input|date',
            'quad_tiers' => 'select|title||multiple',
            'quad_users' => 'select|multiple',
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
            'list' => '',
            'update' => 'id,created_at,updated_at',
            'show' => 'updated_at,quad_tiers,quad_users',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['name'];
    }

    public function fillSortingProperties()
    {
        return ['id', 'name'];
    }
}
