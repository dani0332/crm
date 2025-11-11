<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LeadAllocation;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\CyberAllocation;
use Illuminate\Database\Seeder;

class CyberLeadAllocationSeeder extends Seeder
{
    private const CYBER_ADVISOR_MAX_CAPACITY = 200;

    public function run(): void
    {
        LoggerService::info('CyberLeadAllocationSeeder: Starting Cyber lead allocation setup');

        $cyberAdvisors = $this->getCyberAdvisors();

        if ($cyberAdvisors->isEmpty()) {
            LoggerService::info('CyberLeadAllocationSeeder: No Cyber advisors found in the system');

            return;
        }

        LoggerService::info('CyberLeadAllocationSeeder: Found Cyber advisors', extra: [
            'count' => $cyberAdvisors->count(),
            'advisorEmails' => $cyberAdvisors->pluck('email')->toArray(),
        ]);

        $this->setupLeadAllocationRecords($cyberAdvisors);

        $this->setupSystemUserAllocation();

        LoggerService::info('CyberLeadAllocationSeeder: Cyber lead allocation setup completed successfully');
    }

    private function getCyberAdvisors()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', RolesEnum::CyberAdvisor);
        })
            ->where('is_active', 1)
            ->get();
    }

    private function setupLeadAllocationRecords($cyberAdvisors): void
    {
        $cyberQuoteTypeId = QuoteTypes::getId(QuoteTypes::CYBER);

        foreach ($cyberAdvisors as $advisor) {
            $leadAllocation = LeadAllocation::updateOrCreate(
                [
                    'user_id' => $advisor->id,
                    'quote_type_id' => $cyberQuoteTypeId,
                ],
                [
                    'allocation_count' => 0,
                    'auto_assignment_count' => 0,
                    'manual_assignment_count' => 0,
                    'max_capacity' => self::CYBER_ADVISOR_MAX_CAPACITY,
                    'buy_lead_allocation_count' => 0,
                    'buy_lead_max_capacity' => 0,
                    'buy_lead_status' => 0,
                    'buy_lead_reset_capacity' => 0,
                    'normal_allocation_enabled' => 1,
                    'last_allocated' => now()->timestamp,
                    'reset_cap' => 0,
                ]
            );

            LoggerService::info('CyberLeadAllocationSeeder: Lead allocation record created/updated', extra: [
                'advisorId' => $advisor->id,
                'advisorEmail' => $advisor->email,
                'advisorName' => $advisor->name,
                'maxCapacity' => self::CYBER_ADVISOR_MAX_CAPACITY,
                'quoteTypeId' => $cyberQuoteTypeId,
                'resetCap' => 0,
                'wasRecentlyCreated' => $leadAllocation->wasRecentlyCreated,
            ]);
        }
    }

    private function setupSystemUserAllocation(): void
    {
        $systemUser = User::where('email', CyberAllocation::HAPPINESS_SUPPORT_USER_EMAIL)->first();

        if (! $systemUser) {
            LoggerService::warning('CyberLeadAllocationSeeder: Customer Happiness Centre system user not found');

            return;
        }

        $cyberQuoteTypeId = QuoteTypes::getId(QuoteTypes::CYBER);

        $leadAllocation = LeadAllocation::updateOrCreate(
            [
                'user_id' => $systemUser->id,
                'quote_type_id' => $cyberQuoteTypeId,
            ],
            [
                'allocation_count' => 0,
                'auto_assignment_count' => 0,
                'manual_assignment_count' => 0,
                'max_capacity' => -1,
                'buy_lead_allocation_count' => 0,
                'buy_lead_max_capacity' => 0,
                'buy_lead_status' => 0,
                'buy_lead_reset_capacity' => 0,
                'normal_allocation_enabled' => 1,
                'last_allocated' => now()->timestamp,
                'reset_cap' => 0,
            ]
        );

        LoggerService::info('CyberLeadAllocationSeeder: System user (Customer Happiness Centre) allocation record created/updated', extra: [
            'userId' => $systemUser->id,
            'userEmail' => $systemUser->email,
            'userName' => $systemUser->name,
            'maxCapacity' => -1,
            'quoteTypeId' => $cyberQuoteTypeId,
            'resetCap' => 0,
            'wasRecentlyCreated' => $leadAllocation->wasRecentlyCreated,
        ]);
    }
}

