<?php

namespace App\Traits;

use App\Enums\TeamTypeEnum;
use App\Models\Team;
use App\Models\User;
use DB;

trait TeamHierarchyHelpers
{
    public function getAllProducts()
    {
        return Team::where('type', TeamTypeEnum::Product)->get();
    }

    public function getProductByName($productName)
    {
        return Team::where('type', TeamTypeEnum::Product)->where('name', $productName)->first();
    }

    public function getAllTeams()
    {
        return Team::where('type', TeamTypeEnum::Team)->get();
    }

    public function getTeamsByProductId($productId)
    {
        return Team::where('type', TeamTypeEnum::Team)->where('parent_team_id', $productId)->get();
    }

    public function getTeamsByProductName($productName)
    {
        $product = Team::where('type', TeamTypeEnum::Product)->where('name', $productName)->first();

        return Team::where('type', TeamTypeEnum::Team)->where('parent_team_id', $product->id)->get();
    }

    public function getAllSubTeams()
    {
        return Team::where('type', TeamTypeEnum::SubTeam)->get();
    }

    public function getSubTeamsByTeamId($teamId)
    {
        return Team::where('type', TeamTypeEnum::SubTeam)->where('parent_team_id', $teamId)->get();
    }

    public function getUsersByTeamId($teamId)
    {
        return User::whereIn('id', DB::table('user_team')->where('team_id', $teamId)->pluck('user_id'))->get();
    }

    public function getUsersByTeamIds($teamIds)
    {
        return User::whereIn('id', DB::table('user_team')->whereIn('team_id', $teamIds)->pluck('user_id'))->get();
    }

    public function getUsersByProductName($productName)
    {
        $product = Team::where('type', TeamTypeEnum::Product)->where('name', $productName)->first();
        $productTeams = Team::where('type', TeamTypeEnum::Team)->where('parent_team_id', $product->id)->get();

        return User::whereIn('id', DB::table('user_team')->whereIn('team_id', $productTeams->pluck('id'))->pluck('user_id'))->get();
    }
}
