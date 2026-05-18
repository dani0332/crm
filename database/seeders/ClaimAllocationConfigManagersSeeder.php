<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\ClaimsLeadAllocationConfig;
use App\Models\Permission;
use App\Models\QuoteType;
use App\Models\Role;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class ClaimAllocationConfigManagersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    private array $genericManagersEmails = [
        'ashli.fernandez@insurancemarket.ae',
        'shaista.khan@insurancemarket.ae',
        'waseem.suduri@insurancemarket.ae',
        'vanshika.choudhary@insurancemarket.ae',
        'ali.farzan@insurancemarket.ae',
        'mini.narayanan@insurancemarket.ae',
        'rabishankar.roy@insurancemarket.ae',
        'fehmida.shaikh@insurancemarket.ae',
    ];
    public function run(): void
    {
        try {
            $this->claimILADashboardManagers();

            $roles = $this->createClaimRoles();

            $this->claimAllocationConfigManagersForCar();
            $this->claimAllocationConfigManagersForHealth();
            $this->claimAllocationConfigManagersForGM();
            $this->claimAllocationConfigManagersForLife();
            $this->claimAllocationConfigManagersForTravel();
            $this->claimAllocationConfigManagersForHome();
            $this->claimAllocationConfigManagersForPet();
            $this->claimAllocationConfigManagersForYacht();
            $this->claimAllocationConfigManagersForCycle();
            $this->claimAllocationConfigManagersForJetski();
            $this->claimAllocationConfigManagersForCorpline();

        } catch (\Exception $e) {
            LoggerService::error('Error creating claim ILADashboard managers: '.$e->getMessage());
        }
    }
    private function claimILADashboardManagers()
    {

        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::CLAIM_ALLOCATION_DASHBOARD,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::ClaimsManager])->get();
        foreach ($roles as $role) {
            LoggerService::info('Claim allocation dashboard manager role: '.$role->name);
            if (! $role->hasPermissionTo(PermissionsEnum::CLAIM_ALLOCATION_DASHBOARD)) {
                LoggerService::info('Claim allocation dashboard manager role does not have permission: '.$role->name);
                $role->givePermissionTo(PermissionsEnum::CLAIM_ALLOCATION_DASHBOARD);
                LoggerService::info('Claim allocation dashboard manager role given permission: '.$role->name);
            } else {
                LoggerService::info('Claim allocation dashboard manager role already has permission: '.$role->name);
            }
        }
    }
    private function createClaimRoles()
    {
        $roles = [
            RolesEnum::ClaimsManager,
            RolesEnum::CarClaimManager,
            RolesEnum::HealthClaimManager,
            RolesEnum::GMClaimManager,
            RolesEnum::LifeClaimManager,
            RolesEnum::TravelClaimManager,
            RolesEnum::HomeClaimManager,
            RolesEnum::PetClaimManager,
            RolesEnum::YachtClaimManager,
            RolesEnum::CycleClaimManager,
            RolesEnum::JetskiClaimManager,
            RolesEnum::CorplineClaimManager,
        ];
        foreach ($roles as $role) {

            $isExists = Role::where('name', $role)->first();
            if (! $isExists && $role) {
                $role = Role::create(['name' => $role, 'guard_name' => 'web']);
                LoggerService::info('Claim role created: '.$role->name);
            } else {
                $roles[] = $isExists;
                LoggerService::info('Claim role already exists: '.$isExists->name);
            }
        }

        return $roles ?? [];
    }
    private function claimAllocationConfigManagersForCar()
    {
        $managersEmails = [
            'amjad.umar@insurancemarket.ae',
            'rabishankar.roy@insurancemarket.ae',
            'mini.narayanan@insurancemarket.ae',
            'yogesh.rawat@insurancemarket.ae',
            'vanshika.choudhary@insurancemarket.ae',
            'shaista.khan@insurancemarket.ae',
            'ashli.fernandez@insurancemarket.ae',
            'sarvjeet.singh@insurancemarket.ae',
            'ali.farzan@insurancemarket.ae',

        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::CAR->id(), RolesEnum::CarClaimManager);
    }
    private function claimAllocationConfigManagersForHealth()
    {
        $managersEmails = [
            'rae.rodrigo@insurancemarket.ae',
            'fathima.azmy@insurancemarket.ae',
            'nifraz.nizar@insurancemarket.ae',
            'poorva.soota@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::HEALTH->id(), RolesEnum::HealthClaimManager);
    }
    private function claimAllocationConfigManagersForGM()
    {
        $managersEmails = [
            'sita.laxmi@insurancemarket.ae',
            'vivek.jadhav@insurancemarket.ae',
            'poorva.soota@insurancemarket.ae',
            // Life Claims Manager:
            'komal.rajput@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::BUSINESS->id(), RolesEnum::GMClaimManager);

    }
    private function claimAllocationConfigManagersForLife()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::LIFE->id(), RolesEnum::LifeClaimManager);
    }
    private function claimAllocationConfigManagersForTravel()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::TRAVEL->id(), RolesEnum::TravelClaimManager);
    }
    private function claimAllocationConfigManagersForHome()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::HOME->id(), RolesEnum::HomeClaimManager);
    }
    private function claimAllocationConfigManagersForPet()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::PET->id(), RolesEnum::PetClaimManager);
    }
    private function claimAllocationConfigManagersForYacht()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::YACHT->id(), RolesEnum::YachtClaimManager);
    }
    private function claimAllocationConfigManagersForCycle()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::CYCLE->id(), RolesEnum::CycleClaimManager);
    }
    private function claimAllocationConfigManagersForJetski()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::JETSKI->id(), RolesEnum::JetskiClaimManager);
    }
    private function claimAllocationConfigManagersForCorpline()
    {
        $managersEmails = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];
        $managersEmails = array_merge($managersEmails, $this->genericManagersEmails);
        $this->seedClaimAllocationConfigManagers($managersEmails, QuoteTypes::BUSINESS->id(), RolesEnum::CorplineClaimManager);
    }
    private function seedClaimAllocationConfigManagers($managersEmails = [], $quoteTypeId = null, $role = null): void
    {
        $users = User::select('id', 'email')->whereIn('email', $managersEmails)->get();
        foreach ($users as $user) {
            if ($quoteTypeId === null || $quoteTypeId === '') {
                LoggerService::warning('Claim allocation config skipped: quote type id is empty');

                continue;
            }

            if (! QuoteType::query()->whereKey($quoteTypeId)->exists()) {
                LoggerService::warning(
                    "Claim allocation config skipped: quote_type id {$quoteTypeId} does not exist in quote_type table"
                );

                continue;
            }

            $isExists = ClaimsLeadAllocationConfig::where('user_id', $user->id)->where('quote_type_id', $quoteTypeId)->first();
            // Assign the Claim Manager role to the user if not already assigned
            if (method_exists($user, 'assignRole')) {
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                    LoggerService::info('Assigned '.$role.' role to user: '.$user->email);
                }
            }
            if ($isExists) {
                LoggerService::info('Claim allocation config manager already exists for user: '.$user->email.' and quote type: '.$quoteTypeId);

                continue;
            }

            ClaimsLeadAllocationConfig::create([
                'user_id' => $user->id,
                'quote_type_id' => $quoteTypeId,
                'max_capacity' => 100,
                'allocation_count' => 0,
                'auto_assignment_count' => 0,
                'manual_assignment_count' => 0,
                'last_allocated' => null,
                'reset_cap' => 1,
            ]);
            LoggerService::info('Claim allocation config manager seeded for user: '.$user->email.' and quote type: '.$quoteTypeId);
        }
    }
}
