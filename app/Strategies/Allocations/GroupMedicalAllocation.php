<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use Illuminate\Support\Str;

class GroupMedicalAllocation extends BaseAllocation
{
     // Plan Types
     const PLAN_TYPE_ENTRY_LEVEL = 'entry_level';
     const PLAN_TYPE_GOOD = 'good';
     const PLAN_TYPE_BEST = 'best';
     const PLAN_TYPE_MULTIPLE_CATEGORIES = 'multiple_categories';

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
        $emails = match (true) {
            $this->isMicroLead() => $this->getMicroAdvisors(),
            $this->isNonMicroLead() => $this->getNonMicroAdvisors(),
            default => [],
        };

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::GMAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    private function isMicroLead(){
        dd($this->lead);
        return true;
    }

    private function isNonMicroLead(){
        return true;
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

    public function getTeamByCriteria(string $planType, int $numberOfEmployees): string
    {
        $employeeRange = $this->getEmployeeRange($numberOfEmployees);

        switch ($planType) {
            case self::PLAN_TYPE_ENTRY_LEVEL:
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

            case self::PLAN_TYPE_GOOD:
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

            case self::PLAN_TYPE_BEST:
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

            case self::PLAN_TYPE_MULTIPLE_CATEGORIES:
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
                return self::TEAM_NON_MICRO; // Default to Non-Micro for unknown plan types
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
            if ($numberOfEmployees >= 0 && $numberOfEmployees <= 5) {
                return self::EMPLOYEE_RANGE_0_5;
            } elseif ($numberOfEmployees >= 6 && $numberOfEmployees <= 50) {
                return self::EMPLOYEE_RANGE_6_50;
            } elseif ($numberOfEmployees >= 51 && $numberOfEmployees <= 100) {
                return self::EMPLOYEE_RANGE_51_100;
            } else {
                return self::EMPLOYEE_RANGE_101_PLUS;
            }
        }




}
