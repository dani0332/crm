<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\RolesEnum;
use Illuminate\Support\Str;

class GroupMedicalAllocation extends BaseAllocation
{


    // Employee Ranges
    const EMPLOYEE_RANGE_0_5 = '0-5';
    const EMPLOYEE_RANGE_6_50 = '6-50';
    const EMPLOYEE_RANGE_51_100 = '51-100';
    const EMPLOYEE_RANGE_101_PLUS = '101+';

    // Teams
    const TEAM_MICRO = 'micro';
    const TEAM_NON_MICRO = 'non_micro';

    protected function fetchAdvisor(int $onlineStatus)
    {
        $planType = HealthPlanTypeEnum::typeName($this->lead->health_plan_type_id)?->label();
        $team = $this->getTeamByCriteria($planType, $this->lead->number_of_employees);
        info(self::class . " - group medical team: $team | plan type: $planType | number of employees: {$this->lead->number_of_employees} | online status: $onlineStatus |
         quote Ref-ID: {$this->lead->uuid} | time: " . now());
        $emails = $team === self::TEAM_MICRO ? $this->getMicroAdvisors() : $this->getNonMicroAdvisors();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::GMAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    private function getMicroAdvisors()
    {
        return cache()->remember('group_medical_micro_advisors', now()->addHour(), function () {
            return explode(',', getAppStorageValueByKey(ApplicationStorageEnums::GROUP_MEDICAL_MICRO_ADVISORS));
        });
    }

    private function getNonMicroAdvisors()
    {
        return cache()->remember('group_medical_non_micro_advisors', now()->addHour(), function () {
            return explode(',', getAppStorageValueByKey(ApplicationStorageEnums::GROUP_MEDICAL_NON_MICRO_ADVISORS));
        });
    }

    public function getTeamByCriteria(string $planType, int $numberOfEmployees)
    {
        $employeeRange = $this->getEmployeeRange($numberOfEmployees);

        switch ($planType) {
            case HealthPlanTypeEnum::ENTRY_LEVEL:
                if ($employeeRange === self::EMPLOYEE_RANGE_0_5) {
                    return ''; // N/A (default to Non-Micro)
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_6_50) {
                    return self::TEAM_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_51_100) {
                    return self::TEAM_MICRO;
                } else {
                    return self::TEAM_NON_MICRO;
                }
                break;

            case HealthPlanTypeEnum::GOOD:
                if ($employeeRange === self::EMPLOYEE_RANGE_0_5) {
                    return self::TEAM_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_6_50) {
                    return self::TEAM_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_51_100) {
                    return self::TEAM_MICRO;
                } else {
                    return self::TEAM_NON_MICRO;
                }
                break;

            case HealthPlanTypeEnum::BEST:
                if ($employeeRange === self::EMPLOYEE_RANGE_0_5) {
                    return self::TEAM_NON_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_6_50) {
                    return self::TEAM_NON_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_51_100) {
                    return self::TEAM_NON_MICRO;
                } else {
                    return self::TEAM_NON_MICRO;
                }
                break;

            case HealthPlanTypeEnum::MULTI_CATEGORIES:
                if ($employeeRange === self::EMPLOYEE_RANGE_0_5) {
                    return self::TEAM_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_6_50) {
                    return self::TEAM_MICRO;
                } elseif ($employeeRange === self::EMPLOYEE_RANGE_51_100) {
                    return self::TEAM_MICRO;
                } else {
                    return self::TEAM_NON_MICRO;
                }
                break;
            default:
                    return "";
        }
    }

    /**
     * Get the employee range based on the number of employees.
     *
     * @param int $numberOfEmployees
     * @return string
     */
    protected function getEmployeeRange(int $numberOfEmployees): string
    {
        return match (true) {
            $numberOfEmployees >= 0 && $numberOfEmployees <= 5 => self::EMPLOYEE_RANGE_0_5,
            $numberOfEmployees >= 6 && $numberOfEmployees <= 50 => self::EMPLOYEE_RANGE_6_50,
            $numberOfEmployees >= 51 && $numberOfEmployees <= 100 => self::EMPLOYEE_RANGE_51_100,
        };
    }
}
