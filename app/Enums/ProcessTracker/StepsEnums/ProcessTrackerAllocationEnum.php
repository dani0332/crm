<?php

namespace App\Enums\ProcessTracker\StepsEnums;

enum ProcessTrackerAllocationEnum: string implements Step
{
    case REQUEST_DETAILS = 'request_details';
    case LEAD_FOUND = 'lead_found';
    case LEAD_NOT_FOUND = 'lead_not_found';
    case ADVISOR_FOUND = 'advisor_found';
    case ADVISOR_NOT_FOUND = 'advisor_not_found';
    case LEAD_ASSIGNED = 'lead_assigned';
    case EXCEPTION_RAISED = 'exception_raised';

    public function data(): object
    {
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
            self::LEAD_NOT_FOUND => [
                'description' => 'Lead not found. The possible reason: Lead uuid might be invalid or having one of the following statuses: @statuses',
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
