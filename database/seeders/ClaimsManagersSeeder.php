<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LeadAllocation;
use App\Models\QuoteType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClaimsManagersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        $claimManagerRole = Role::firstOrCreate([
            'name' => RolesEnum::ClaimsManager,
            'guard_name' => 'web',
        ]);

        $claimLeadRole = Role::firstOrCreate([
            'name' => RolesEnum::CLAIM_LEAD,
            'guard_name' => 'web',
        ]);

        // Car Claims Manager (for CAR and BIKE)
        $carClaimsManagers = [
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

        // Non Motor Claims Manager
        $nonMotorClaimsManagers = [
            'waseem.suduri@insurancemarket.ae',
            'gloria.hurboda@insurancemarket.ae',
        ];

        // Individual Health Claims Manager
        $individualHealthClaimsManagers = [
            'rae.rodrigo@insurancemarket.ae',
            'fathima.azmy@insurancemarket.ae',
            'nifraz.nizar@insurancemarket.ae',
            'poorva.soota@insurancemarket.ae',
        ];

        // Group Health Claims Manager
        $groupHealthClaimsManagers = [
            'sita.laxmi@insurancemarket.ae',
            'vivek.jadhav@insurancemarket.ae',
            'poorva.soota@insurancemarket.ae',
        ];

        // Life Claims Manager
        $lifeClaimsManagers = [
            'komal.rajput@insurancemarket.ae',
        ];

        // Claims Lead
        $claimsLeads = [
            'ashmy.arackal@insurancemarket.ae',
            'surabhi.singh@insurancemarket.ae',
            'santhosh.ganesan@insurancemarket.ae',
        ];

        // Assign quote types to Car Claims Managers (CAR and BIKE)
        foreach ($carClaimsManagers as $email) {
            $user = $this->createOrUpdateUser($email, $claimManagerRole);
            $this->assignQuoteTypes($user, [QuoteTypes::CAR, QuoteTypes::BIKE]);
        }

        // Assign quote types to Non Motor Claims Managers
        // CORPLINE (except Group Medical), Home, Travel, Bike, Pet, Cycle, Yacht, Savings (except Health, Group Health, Life)
        foreach ($nonMotorClaimsManagers as $email) {
            $user = $this->createOrUpdateUser($email, $claimManagerRole);
            $this->assignQuoteTypes($user, [
                QuoteTypes::CORPLINE,
                QuoteTypes::HOME,
                QuoteTypes::TRAVEL,
                QuoteTypes::BIKE,
                QuoteTypes::PET,
                QuoteTypes::CYCLE,
                QuoteTypes::YACHT,
                QuoteTypes::SAVINGS,
            ]);
        }

        // Assign quote types to Individual Health Claims Managers (Health)
        foreach ($individualHealthClaimsManagers as $email) {
            $user = $this->createOrUpdateUser($email, $claimManagerRole);
            $this->assignQuoteTypes($user, [QuoteTypes::HEALTH]);
        }

        // Assign quote types to Group Health Claims Managers (Group Medical)
        foreach ($groupHealthClaimsManagers as $email) {
            $user = $this->createOrUpdateUser($email, $claimManagerRole);
            $this->assignQuoteTypes($user, [QuoteTypes::GROUP_MEDICAL]);
        }

        // Assign quote types to Life Claims Manager (Life)
        foreach ($lifeClaimsManagers as $email) {
            $user = $this->createOrUpdateUser($email, $claimManagerRole);
            $this->assignQuoteTypes($user, [QuoteTypes::LIFE]);
        }

        // Assign all quote types to Claims Leads
        foreach ($claimsLeads as $email) {
            $user = $this->createOrUpdateUser($email, $claimLeadRole);
            $this->assignQuoteTypes($user, [
                QuoteTypes::CAR,
                QuoteTypes::BIKE,
                QuoteTypes::HOME,
                QuoteTypes::HEALTH,
                QuoteTypes::LIFE,
                QuoteTypes::TRAVEL,
                QuoteTypes::PET,
                QuoteTypes::CYCLE,
                QuoteTypes::YACHT,
                QuoteTypes::CORPLINE,
                QuoteTypes::GROUP_MEDICAL,
                QuoteTypes::SAVINGS,
            ]);
        }
    }

    /**
     * Create or update user and assign role
     */
    private function createOrUpdateUser(string $email, Role $role): User
    {
        $name = $this->extractNameFromEmail($email);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('CLAIM@afia123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]
        );

        // Assign role if user doesn't have it
        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
            $this->command->info("Assigned {$role->name} role to {$email}");
        } else {
            $this->command->info("User {$email} already has {$role->name} role");
        }

        return $user;
    }

    /**
     * Assign quote types to user via LeadAllocation
     */
    private function assignQuoteTypes(User $user, array $quoteTypes): void
    {
        foreach ($quoteTypes as $quoteType) {
            $quoteTypeId = $quoteType->id();
            if (! $quoteTypeId) {
                $this->command->warn("Quote type {$quoteType->value} does not have an ID, skipping...");

                continue;
            }

            // Verify quote type exists in quote_type table
            $quoteTypeExists = QuoteType::where('id', $quoteTypeId)->exists();
            if (! $quoteTypeExists) {
                $this->command->warn("Quote type ID {$quoteTypeId} ({$quoteType->value}) does not exist in quote_type table, skipping...");

                continue;
            }

            $existing = LeadAllocation::where('user_id', $user->id)
                ->where('quote_type_id', $quoteTypeId)
                ->first();

            if (! $existing) {
                $leadAllocation = new LeadAllocation;
                $leadAllocation->user_id = $user->id;
                $leadAllocation->quote_type_id = $quoteTypeId;
                $leadAllocation->allocation_count = 0;
                $leadAllocation->last_allocated = now()->timestamp;
                $leadAllocation->max_capacity = 0;
                $leadAllocation->is_available = false;
                $leadAllocation->save();
                $this->command->info("Assigned quote type {$quoteType->value} to {$user->email}");
            } else {
                $this->command->info("User {$user->email} already has quote type {$quoteType->value}");
            }
        }
    }

    /**
     * Extract name from email address
     */
    private function extractNameFromEmail(string $email): string
    {
        $name = explode('@', $email)[0];
        $name = str_replace(['.', '_', '-'], ' ', $name);
        $name = ucwords($name);

        return $name;
    }
}
