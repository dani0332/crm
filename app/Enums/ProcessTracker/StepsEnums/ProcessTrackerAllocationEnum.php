<?php

namespace App\Enums\ProcessTracker\StepsEnums;

enum ProcessTrackerAllocationEnum: string implements Step
{
    case REQUEST_DETAILS = 'request_details';
    case LEAD_FOUND = 'lead_found';
    case ALIANCE_PLAN_FOUND = 'alliance_plan_found';
    case LEAD_NOT_FOUND = 'lead_not_found';
    case ADVISOR_FOUND = 'advisor_found';
    case ADVISOR_NOT_FOUND = 'advisor_not_found';
    case LEAD_ASSIGNED = 'lead_assigned';
    case EXCEPTION_RAISED = 'exception_raised';
    case AUTOMATION_NOT_COMPLETED = 'automation_not_completed';
    case POLICY_ISSUANCE_FAILED = 'policy_issuance_failed';
    case AUTOMATION_COMPLETED = 'automation_completed';
    case BOOKING_FAILED = 'booking_failed';

    public function data(): object
    {
        /*
            NOTE: In Schema, @ means if we need to replace the data in description and also want to have that data as params for dev
            NOTE: In Schema, : means if we need to replace the data in description but don't want to have that data as params for dev
        */

        return (object) match ($this) {
            self::REQUEST_DETAILS => [
                'description' => 'Request Details',
                'schema' => ['teamId', 'requestParams'],
                'devOnly' => true,
                'dataDevOnly' => true,
            ],
            self::LEAD_FOUND => [
                'description' => 'Lead found',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::ALIANCE_PLAN_FOUND => [
                'description' => 'The lead is for a Single Trip, includes adult members, and is marked as Paid. An Alliance Plan has been found; therefore, the Booking Details need to be checked.',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::AUTOMATION_NOT_COMPLETED => [
                'description' => 'Automation not completed yet',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::AUTOMATION_COMPLETED => [
                'description' => 'Automation is completed so proceed with allocation to assign CHS Advisor',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::BOOKING_FAILED => [
                'description' => 'Booking is failed so proceed with allocation to assign CHS Advisor',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::POLICY_ISSUANCE_FAILED => [
                'description' => 'Policy issuance failed but policy issuance failed so proceed with allocation to assign SIC Advisor',
                'schema' => [],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::LEAD_NOT_FOUND => [
                'description' => 'Lead not found. Possible reasons include: the Lead UUID might be invalid, the Lead may have one of the following statuses: @statuses, or the SIC flow might be enabled, it was not requested for an advisor, or the Lead is not marked as Paid.',
                'schema' => ['@statuses'],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::ADVISOR_FOUND => [
                'description' => 'Advisor found',
                'schema' => ['userId', '@name', '@email', '@status'],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::ADVISOR_NOT_FOUND => [
                'description' => 'Advisor not found. The possible reason: No @status Active Advisor found having available capacity against team :teamName for role @roleName',
                'schema' => ['@status', ':teamName', 'teamId', '@roleName'],
                'devOnly' => false,
                'dataDevOnly' => false,
            ],
            self::LEAD_ASSIGNED => [
                'description' => 'Lead assigned',
                'schema' => ['leadId', 'leadUuid', 'advisorId', 'advisorName', 'advisorEmail', 'quoteBatchId', 'quoteBatchName', 'previousAssignmentType', 'previousUserId', 'previousAdvisorAssignedDate'],
                'devOnly' => false,
                'dataDevOnly' => true,
            ],
            self::EXCEPTION_RAISED => [
                'description' => 'Exception occurred during allocation',
                'schema' => [],
                'devOnly' => true,
                'dataDevOnly' => true,
            ],
            default => [
                'description' => 'Unknown step',
                'schema' => [],
                'devOnly' => true,
                'dataDevOnly' => true,
            ],
        };
    }
}
