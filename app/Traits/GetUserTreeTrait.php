<?php

namespace App\Traits;

use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use Illuminate\Support\Facades\DB;

trait GetUserTreeTrait
{
    use TeamHierarchyTrait;

    /**
     * NOTE: For future reference, this is how you use this trait:
     * Product Type should be passed for the relevant LOB type, default is set to car
     * And Update the required roles in the if condition
     *
     * @param [type] $userId
     * @param [type] $productType
     * @return void
     */
    public function associateAdvisorsWithManager($userId, $productType = null)
    {
        if (! empty($userId)) {
            return DB::table('user_manager')->where('manager_id', $userId)->get()->pluck('user_id');
        }

        return [];
    }
    public function walkTree($userId, $productType = null, $allowedPermissions = []) // product
    {
        $childUserIds = [$userId];
        $productTeam = $this->getProductByName($productType ?? quoteTypeCode::Car);
        $rolesArray = [
            RolesEnum::CarManager,
            RolesEnum::BikeManager,
            RolesEnum::HealthManager,
            RolesEnum::TravelManager,
            RolesEnum::PetManager,
            RolesEnum::CycleManager,
            RolesEnum::YachtManager,
            RolesEnum::LifeManager,
            RolesEnum::HomeManager,
            RolesEnum::CorplineManager,
            RolesEnum::GMManager,
            RolesEnum::LeadPool,
        ];
        if (auth()->user()->hasAnyRole($rolesArray) || auth()->user()->hasAnyPermission($allowedPermissions)) {
            $userAllTeams = DB::table('teams')
                ->join('user_team', 'user_team.team_id', 'teams.id')
                ->where('user_id', $userId)
                ->where('teams.parent_team_id', $productTeam->id)->select('teams.id');
            $teamMates = DB::table('user_team')->whereIn('team_id', $userAllTeams)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                array_push($childUserIds, $teamMateId);
            }
        } else {
            $carUserIds = $this->getUsersByTeamId($productTeam->id)->pluck('id');
            $teamMates = DB::table('user_manager')->where('manager_id', $userId)->whereIn('user_id', $carUserIds)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                $carUserIds = $this->getUsersByTeamId($productTeam->id)->pluck('id');
                $nextChild = DB::table('user_manager')->where('manager_id', $teamMateId)->whereIn('user_id', $carUserIds)->pluck('user_id');
                if (count($nextChild) > 0) {
                    $this->walkTree($teamMateId, $productType);
                }
                array_push($childUserIds, $teamMateId);
            }
        }

        return array_unique($childUserIds);
    }
}
