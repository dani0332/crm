<?php

namespace App\Traits;

use App\Enums\TeamTypeEnum;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Models\UserProducts;
use App\Models\UserTeams;
use Illuminate\Support\Facades\DB;

trait TeamHierarchyTrait
{
    public function getAllProducts()
    {
        return Team::where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->orderBy('name', 'asc')->get();
    }

    public function getProductByName($productName)
    {
        return Team::where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->where('name', $productName)->first();
    }

    public function getAllTeams()
    {
        return Team::where('type', TeamTypeEnum::TEAM)->where('is_active', 1)->orderBy('name', 'asc')->get();
    }

    public function getTeamsByProductId($productId)
    {
        return Team::where('type', TeamTypeEnum::TEAM)->where('is_active', 1)->where('parent_team_id', $productId)->get();
    }

    public function getTeamsSubTeamsByProductId($productId)
    {
        $result = [];
        $teams = Team::where('type', TeamTypeEnum::TEAM)->where('is_active', 1)->where('parent_team_id', $productId)->get();
        $subTeams = [];
        foreach ($teams as $team) {
            array_push($result, $team);
            $subTeams = Team::where('type', TeamTypeEnum::SUB_TEAM)->where('is_active', 1)->where('parent_team_id', $team->id)->get();
            foreach ($subTeams as $subTeam) {
                array_push($result, $subTeam);
            }
        }

        return $result;
    }

    public function getTeamsByProductIds($productIds)
    {
        return Team::where('type', TeamTypeEnum::TEAM)->whereIn('parent_team_id', $productIds)->where('is_active', 1)->orderBy('name', 'asc')->get();
    }

    public function getTeamsByProductName($productName)
    {
        $product = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', $productName)->where('is_active', 1)->first();

        return ! $product ? null : Team::where('type', TeamTypeEnum::TEAM)->where('parent_team_id', $product->id)->where('is_active', 1)->get();
    }

    public function getAllSubTeams()
    {
        return Team::where('type', TeamTypeEnum::SUB_TEAM)->orderBy('name', 'asc')->where('is_active', 1)->get();
    }

    public function getSubTeamsByTeamId($teamId)
    {
        return Team::where('type', TeamTypeEnum::SUB_TEAM)->where('parent_team_id', $teamId)->where('is_active', 1)->get();
    }

    public function getSubTeamsByTeamIds($teamIds)
    {
        $subTeams = Team::with('parent')->where('type', TeamTypeEnum::SUB_TEAM)->whereIn('parent_team_id', $teamIds)->where('is_active', 1)->select('id', 'name', 'parent_team_id')->orderBy('name', 'asc')->get();

        $subTeams->map(function ($subTeam) {
            if ($subTeam->parent) {
                $subTeam->name = "$subTeam->name ({$subTeam->parent?->name})";
            }

            return $subTeam;
        });

        return $subTeams;
    }

    public function getUsersByTeamId($teamId)
    {
        $userIds = User::where(function ($query) use ($teamId) {
            $query->whereIn('id', function ($subQuery) use ($teamId) {
                $subQuery->select('user_id')
                    ->from('user_team')
                    ->whereIn('team_id', (array) $teamId);
            })->orWhereIn('sub_team_id', (array) $teamId);
        })->where('is_active', 1)
            ->pluck('id')
            ->toArray();

        $users = User::whereIn('id', $userIds)
            ->select('id', 'name')
            ->get();

        return $users;
    }

    public function getUsersByTeamIds($teamIds)
    {
        $teamUserIds = DB::table('user_team')->whereIn('team_id', $teamIds)->pluck('user_id');

        return User::whereIn('id', $teamUserIds)->where('is_active', 1)->get();
    }

    public function getUsersBySubTeamIds($teamIds)
    {
        return User::whereIn('sub_team_id', $teamIds)->where('is_active', 1)->get();
    }

    public function getUsersByProductName($productName)
    {
        $product = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', $productName)->where('is_active', 1)->first();
        $productTeams = Team::where('type', TeamTypeEnum::TEAM)->where('parent_team_id', $product->id)->where('is_active', 1)->get();

        $teamUserIds = UserTeams::whereIn('team_id', $productTeams->pluck('id'))->pluck('user_id')->toArray();
        $productUserIds = UserProducts::select('user_id')->where('product_id', $product->id)->whereNotIn('user_id', $teamUserIds)->pluck('user_id')->toArray();

        return array_values(array_unique(
            array_merge($teamUserIds, $productUserIds)
        ));
    }

    public function getUserManagers($userId)
    {
        $managerIds = DB::table('user_manager')->where('user_id', $userId)->get()->pluck('manager_id');

        return User::whereIn('id', $managerIds)->where('is_active', 1)->get();
    }

    public function getUserManagersByTeamId($userId, $teamId)
    {
        $teamUserIds = DB::table('user_team')->where('team_id', $teamId)->get()->pluck('user_id');
        $managerIds = DB::table('user_manager')->whereIn('user_id', $teamUserIds)->get()->pluck('manager_id');

        return User::whereIn('id', $managerIds)->where('is_active', 1)->get();
    }

    public function getUserTeams($userId)
    {
        $teamIds = DB::table('user_team')->where('user_id', $userId)->get()->pluck('team_id');

        return Team::whereIn('id', $teamIds)->where('type', TeamTypeEnum::TEAM)->where('is_active', 1)->get();
    }

    public function getUserProducts($userId)
    {
        $productIds = DB::table('user_products')->where('user_id', $userId)->get()->pluck('product_id');

        return Team::whereIn('id', $productIds)->where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->get();
    }

    public function getAllUserIdsByProductName($productName)
    {
        $teamId = $this->getTeamsByProductName($productName)->first()->id;

        return DB::table('user_team')->where('team_id', $teamId)->get()->pluck('user_id');
    }

    public function getCurrentUserTeamsAndSubTeams($userId)
    {
        return collect(DB::select("
        WITH RECURSIVE team_hierarchy
                AS (
                    SELECT id,
                        name,
                        parent_team_id
                    FROM teams
                    WHERE name IN (
                            SELECT teams.name
                            FROM user_team
                            INNER JOIN teams ON teams.id = user_team.team_id
                            WHERE user_id = '{$userId}'
                            ) -- Replace with the list of team names

                    UNION ALL

                    SELECT t.id,
                        t.name,
                        t.parent_team_id
                    FROM teams t
                    JOIN team_hierarchy th ON th.id = t.parent_team_id
                    )
                SELECT id,
                    name,
                    parent_team_id
                FROM team_hierarchy;"));
    }

    public function getAdvisorsByRole($role)
    {
        return User::join('model_has_roles as mr', 'mr.model_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'ut.team_id as u_team_id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->activeUser()
            ->where('r.name', $role)
            ->get();
    }

    public function userHaveProduct($userId, $productId)
    {
        $user = User::with('products')->where('id', $userId)->first();

        return $user && $user->products->contains('product_id', $productId);
    }

    public function getDepartmentsByTeamIds($ids)
    {
        return Department::whereHas('teams', function ($query) use ($ids) {
            $query->whereIn('team_id', $ids);
        })->get();
    }

    public function getUserDepartments($userId)
    {
        return DB::table('user_departments')->where('user_id', $userId)->get();
    }

    public function usersByTeamProduct()
    {
        $product = Team::where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->first();
        if (! $product) {
            return [];
        }

        $productTeams = Team::where('type', TeamTypeEnum::TEAM)
            ->where('parent_team_id', $product->id)
            ->where('is_active', 1)
            ->pluck('id')
            ->toArray();

        $teamUserIds = UserTeams::whereIn('team_id', $productTeams)
            ->pluck('user_id')
            ->toArray();

        $productUserIds = UserProducts::where('product_id', $product->id)
            ->whereNotIn('user_id', $teamUserIds)
            ->pluck('user_id')
            ->toArray();

        return array_unique(array_merge($teamUserIds, $productUserIds));
    }

    public function getAdvisorsByManagers(): array
    {
        return DB::table('user_manager')->where('manager_id', auth()->id())->pluck('user_id')->toArray() ?? [];
    }
}
