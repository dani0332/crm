<?php

namespace App\Traits;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait GetUserTreeTrait
{
    use TeamHierarchyTrait;

    public function walkTree($userId)
    {
        $childUserIds = [$userId];
        $carTeam = $this->getProductByName(quoteTypeCode::Car);
        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::LeadPool])) {
            $userAllTeams = DB::table('teams')
                ->join('user_team', 'user_team.team_id', 'teams.id')
                ->where('user_id', $userId)
                ->where('teams.parent_team_id', $carTeam->id)->select('teams.id');
            $teamMates = DB::table('user_team')->whereIn('team_id', $userAllTeams)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                array_push($childUserIds, $teamMateId);
            }
        } else {
            $carUserIds = $this->getUsersByTeamId($carTeam->id)->pluck('id');
            $teamMates = DB::table('user_manager')->where('manager_id', $userId)->whereIn('user_id', $carUserIds)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                $carUserIds = $this->getUsersByTeamId($carTeam->id)->pluck('id');
                $nextChild = DB::table('user_manager')->where('manager_id', $teamMateId)->whereIn('user_id', $carUserIds)->pluck('user_id');
                if (count($nextChild) > 0) {
                    $this->walkTree($teamMateId);
                }
                array_push($childUserIds, $teamMateId);
            }
        }

        return array_unique($childUserIds);
    }

    /**
     * to be used in retention report for deputy manager role
     * for getting the deputy manager's manager team
     *
     * @param [type] $userId
     * @return void
     */
    public function deputyWalkTreeMirrorForManager($managerId)
    {
        $childUserIds = [$managerId];
        $carTeam = $this->getProductByName(quoteTypeCode::Car);
        if (auth()->user()->hasAnyRole([RolesEnum::CarDeputyManager])) {
            $userAllTeams = DB::table('teams')
                ->join('user_team', 'user_team.team_id', 'teams.id')
                ->where('user_id', $managerId)
                ->where('teams.parent_team_id', $carTeam->id)->select('teams.id');
            $teamMates = DB::table('user_team')->whereIn('team_id', $userAllTeams)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                array_push($childUserIds, $teamMateId);
            }
        }

        return array_unique($childUserIds);
    }

    public function deputyManagerWalkTree($userId)
    {
        $childUserIds = [$userId];
        $car = $this->getProductByName(quoteTypeCode::Car);
        $carTeamIds = $this->getTeamsByProductId($car->id)->pluck('id');
        $carUserIds = $this->getUsersByTeamIds($carTeamIds)->pluck('id');
        $teamMates = DB::table('user_manager')->where('manager_id', $userId)->whereIn('user_id', $carUserIds)->pluck('user_id');
        foreach ($teamMates as $teamMateId) {
            $nextChild = DB::table('user_manager')->where('manager_id', $teamMateId)->whereIn('user_id', $carUserIds)->pluck('user_id');
            if (count($nextChild) > 0) {
                $this->walkTree($teamMateId);
            }
            array_push($childUserIds, $teamMateId);
        }

        return array_unique($childUserIds);
    }

    public static function StaticWalkTree($userId)
    {
        $childUserIds = [];
        $childs = User::where('manager_id', $userId)->pluck('id');
        foreach ($childs as $child) {
            $nextChilds = User::where('manager_id', $child)->pluck('id');
            if (count($nextChilds) > 0) {
                walkTree($child);
            }
            array_push($childUserIds, $child);
        }
        array_push($childUserIds, $userId);

        return $childUserIds;
    }
}
