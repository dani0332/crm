<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImcrmUsersRolesCleaner extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->deActivateUsers();
        $this->renameRoles();
        $this->removeAndMergeRoles();
        $this->deleteRoles();
    }

    private function deleteRoles()
    {
        $roles = [
            'CAR_DEPUTY_MANAGER',
            'HOME_NEW_BUSINESS_ADVISOR',
            'HOME_NEW_BUSINESS_MANAGER',
            'TEST_CORPLINE_MANAGER',
            'TRAVEL_RENEWAL_MANAGER',
            'TRAVEL_RENEWAL_ADVISOR',
            'CAR_OE',
            'HEALTH_WCU_ADVISOR',
            'QA',
            'TM_AUDIT',
            'TRAVEL_NEW_BUSINESS_ADVISOR',
            'TRAVEL_NEW_BUSINESS_MANAGER',
            'HEALTH_NEW_BUSINESS_MANAGER',
            'HEALTH_NEW_BUSINESS_ADVISOR',
            'LIFE_NEW_BUSINESS_ADVISOR',
            'LIFE_NEW_BUSINESS_MANAGER',
            'GM_NEW_BUSINESS_ADVISOR',
            'GM_NEW_BUSINESS_MANAGER',
            'CORPLINE_NEW_BUSINESS_ADVISOR',
            'CORPLINE_NEW_BUSINESS_MANAGER',
            'PET_NEW_BUSINESS_ADVISOR',
        ];

        $this->command->info("\nDeleting Roles...\n");
        $progressBar = $this->command->getOutput()->createProgressBar(count($roles));
        $progressBar->start();

        foreach ($roles as $role) {
            $role = Role::where('name', $role)->first();
            if ($role) {
                $role->delete();
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nRoles deleted successfully.");
    }

    private function removeAndMergeRoles()
    {
        $roles = [
            'MARKETTING_ADMIN' => 'MARKETING_OPERATIONS',
            'ROLE_SM_DASHBOARD' => 'SENIOR_MANAGEMENT',
            'LEAD_ALLOCATION' => 'LEAD_POOL',
            'SMT' => 'SENIOR_MANAGEMENT',
            'Senior Management' => 'SENIOR_MANAGEMENT',
        ];

        $this->command->info("\nMerging and Removing Roles...\n");
        $progressBar = $this->command->getOutput()->createProgressBar(count($roles));
        $progressBar->start();

        foreach ($roles as $oldRoleKey => $newRoleKey) {
            $oldRole = Role::where('name', $oldRoleKey)->first();
            $newRole = Role::where('name', $newRoleKey)->first();

            if ($oldRole === null || $newRole === null) {
                $progressBar->advance();

                continue;
            }

            foreach ($oldRole->permissions as $permission) {
                if (! $newRole->hasPermissionTo($permission)) {
                    $newRole->givePermissionTo($permission);
                }
            }

            foreach ($oldRole->users as $user) {
                if (! $user->hasRole($newRole)) {
                    $user->assignRole($newRole);
                }
            }

            $oldRole->delete();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nRoles merged successfully.");
    }

    private function renameRoles()
    {
        $roles = [
            'CUSTOMER_UPLOAD' => 'GM_MYALFRED_INVITES',
            'RENEWALS' => 'RENEWALS_MANAGEMENT',
            'VEHCILE_VALUATION' => 'VALUATION_MANAGER',
            'Call Desk' => 'HAPEX',
        ];

        $this->command->info("\nRenaming Roles...\n");
        $progressBar = $this->command->getOutput()->createProgressBar(count($roles));
        $progressBar->start();

        foreach ($roles as $oldRole => $newRole) {
            Role::where('name', $oldRole)->update(['name' => $newRole]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nRoles renamed successfully.");
    }

    private function deActivateUsers()
    {
        $emails = [
            'irfan.ahmed@afia.ae',
            'shehroz.khan@afia.ae',
            'abitha.beagum@afia.ae',
            'karishma.rastogi@afia.ae',
            'alyanna.uy@afia.ae',
            'anjo.singuillo@afia.ae',
            'karishma.rastogi@afia.ae',
            'anne.lumauig@afia.ae',
            'cecilia.acuzar@afia.ae',
            'pyarijan.shaik@afia.ae',
            'danie.aconge@afia.ae',
            'elianna.ronquillo@afia.ae',
            'robby.semilla@afia.ae',
            'gleselle.arrofo@afia.ae',
            'shahid.sayyed@insurancemarket.ae',
            'hira.yamin@afia.ae',
            'james.ronquillo@afia.ae',
            'john.polino@afia.ae',
            'judith.glindro@afia.ae',
            'julius.jabonillo@afia.ae',
            'kim.anonuevo@afia.ae',
            'leonese.villanueva@afia.ae',
            'marve.ochia@afia.ae',
            'monaliza.eduria@afia.ae',
            'neslyn.quimio@afia.ae',
            'praveen.nair@afia.ae',
            'ravish.deshmukh@afia.ae',
            'reynaldo.ugaldejr@afia.ae',
            'rubie.mabini@afia.ae',
            'sayril.delarosa@afia.ae',
            'sowjanya.balleda@afia.ae',
            'trupali.devaliya@afia.ae',
            'vishakh.ak@afia.ae',
            'viswesh.bhatt@afia.ae',
            'irfan.ahmed@insurancemarket.ae',
            'shehroz.khan@insurancemarket.ae',
            'abitha.beagum@insurancemarket.ae',
            'karishma.rastogi@insurancemarket.ae',
            'alyanna.uy@insurancemarket.ae',
            'anjo.singuillo@insurancemarket.ae',
            'karishma.rastogi@insurancemarket.ae',
            'anne.lumauig@insurancemarket.ae',
            'cecilia.acuzar@insurancemarket.ae',
            'charles.peria@insurancemarket.ae',
            'pyarijan.shaik@insurancemarket.ae',
            'danie.aconge@insurancemarket.ae',
            'elianna.ronquillo@insurancemarket.ae',
            'robby.semilla@insurancemarket.ae',
            'gleselle.arrofo@insurancemarket.ae',
            'shahid.sayyed@myalfred.com',
            'hira.yamin@insurancemarket.ae',
            'james.ronquillo@insurancemarket.ae',
            'john.polino@insurancemarket.ae',
            'judith.glindro@insurancemarket.ae',
            'julius.jabonillo@insurancemarket.ae',
            'kim.anonuev@insurancemarket.ae',
            'leonese.villanueva@insurancemarket.ae',
            'marve.ochia@insurancemarket.ae',
            'monaliza.eduria@insurancemarket.ae',
            'neslyn.quimio@insurancemarket.ae',
            'praveen.nair@insurancemarket.ae',
            'ravish.deshmukh@insurancemarket.ae',
            'reynaldo.ugaldejr@insurancemarket.ae',
            'rubie.mabini@insurancemarket.ae',
            'sayril.delarosa@insurancemarket.ae',
            'sowjanya.balleda@insurancemarket.ae',
            'trupali.devaliya@insurancemarket.ae',
            'vishakh.ak@insurancemarket.ae',
            'viswesh.bhatt@insurancemarket.ae',
        ];

        $this->command->info("De Activating Users...\n");
        $users = User::where('is_active', true)->whereIn('email', $emails)->get();
        $progressBar = $this->command->getOutput()->createProgressBar($users->count());
        $progressBar->start();

        $users->each(function ($user) use ($progressBar) {
            $user->is_active = false;
            $user->save();
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->command->info("\nUsers deactivated successfully.");
    }
}
