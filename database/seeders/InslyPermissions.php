<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class InslyPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission1 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_ENDO_FIN_ADD,
            'guard_name' => 'web',
        ]);

        $permission2 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_TO_CUSTOMER_BUTTON,
            'guard_name' => 'web',
        ]);

        $group1 = [RolesEnum::Admin, RolesEnum::Production, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::ServiceExecutive,
            RolesEnum::SeniorManagement, RolesEnum::CarManager, RolesEnum::CarAdvisor, RolesEnum::HealthManager, RolesEnum::RMAdvisor,
            RolesEnum::TravelManager, RolesEnum::TravelAdvisor, RolesEnum::LifeManager, RolesEnum::HomeManager, RolesEnum::PetManager,
            RolesEnum::BikeManager, RolesEnum::BikeAdvisor, RolesEnum::CycleManager, RolesEnum::CycleAdvisor, RolesEnum::YachtManager,
            RolesEnum::YachtAdvisor, RolesEnum::GMManager, RolesEnum::GMAdvisor, RolesEnum::CorplineManager, RolesEnum::CorpLineAdvisor];

        $permission1->assignRole($group1);
        $permission2->assignRole($group1);

        $permission3 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_ENDO_NON_FIN_ADD,
            'guard_name' => 'web',
        ]);

        $permission4 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_UPLOAD_ADD,
            'guard_name' => 'web',
        ]);

        $group2 = [RolesEnum::Admin, RolesEnum::ServiceExecutive, RolesEnum::SeniorManagement, RolesEnum::CarManager, RolesEnum::CarAdvisor,
            RolesEnum::HealthManager, RolesEnum::RMAdvisor, RolesEnum::TravelManager, RolesEnum::TravelAdvisor, RolesEnum::LifeManager,
            RolesEnum::HomeManager, RolesEnum::PetManager, RolesEnum::BikeManager, RolesEnum::BikeAdvisor, RolesEnum::CycleManager, RolesEnum::CycleAdvisor,
            RolesEnum::YachtManager, RolesEnum::YachtAdvisor, RolesEnum::GMManager, RolesEnum::GMAdvisor, RolesEnum::CorplineManager,
            RolesEnum::CorpLineAdvisor];

        $permission3->assignRole($group2);
        $permission4->assignRole($group2);

        $permission5 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD,
            'guard_name' => 'web',
        ]);

        $permission6 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD,
            'guard_name' => 'web',
        ]);

        $group3 = [RolesEnum::Admin, RolesEnum::Production, RolesEnum::NRA, RolesEnum::OperationExecutive,
            RolesEnum::SeniorManagement, RolesEnum::CarManager, RolesEnum::CarAdvisor, RolesEnum::HealthManager, RolesEnum::RMAdvisor,
            RolesEnum::TravelManager, RolesEnum::TravelAdvisor, RolesEnum::LifeManager, RolesEnum::HomeManager, RolesEnum::PetManager,
            RolesEnum::BikeManager, RolesEnum::BikeAdvisor, RolesEnum::CycleManager, RolesEnum::CycleAdvisor, RolesEnum::YachtManager,
            RolesEnum::YachtAdvisor, RolesEnum::GMManager, RolesEnum::GMAdvisor, RolesEnum::CorplineManager, RolesEnum::CorpLineAdvisor];

        $permission5->assignRole($group3);
        $permission6->assignRole($group3);

        $permission7 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_CORRECT_POLICY_DETAILS_ADD,
            'guard_name' => 'web',
        ]);

        $permission7->assignRole([RolesEnum::Admin, RolesEnum::Production, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement]);

        $permission8 = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_AND_BOOK_UPDATE_BUTTON,
            'guard_name' => 'web',
        ]);

        $permission9 = Permission::firstOrCreate([
            'name' => PermissionsEnum::BOOK_UPDATE_BUTTON,
            'guard_name' => 'web',
        ]);

        $group4 = [RolesEnum::Admin, RolesEnum::Production, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement];

        $permission8->assignRole($group4);
        $permission9->assignRole($group4);

        // Create new Permission for Endorsment Financial subtypes.
        $addBookingSUPermission = Permission::firstOrCreate([
            'name' => PermissionsEnum::SEND_UPDATE_ADD_BOOKING,
            'guard_name' => 'web',
        ]);
        $addBookingSUPermission->assignRole([RolesEnum::Admin, RolesEnum::BetaUser, RolesEnum::Engineering]);

    }
}
