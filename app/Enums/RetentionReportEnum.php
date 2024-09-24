<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RetentionReportEnum extends Enum
{
    const RETENTION = 'RETENTION';
    const MONTHLY = 'MONTHLY';
    const BATCH = 'BATCH';
    const LOST = 'LOST';
    const INVALID = 'INVALID';
    const SALES = 'SALES';
    const TOTAL = 'TOTAL';
    const MONTH_HEADING = 'The policies expiring in the selected month.';
    const BATCH_HEADING = 'This will show all the policies that are expiring in the selected batch.';
    const START_DATE_HEADING = 'This date will display the start date of the Batch. If you\'re using a "Monthly" filter, this date will represent the start of the selected month for data display.';
    const END_DATE_HEADING = 'This date will display the end date of the Batch. If you\'re using a "Monthly" filter, this date will represent the end of the selected month for data display';
    const ADVISOR_NAME_HEADING = 'This displays the policies handled by the selected advisor';
    const TOTAL_HEADING = 'The total number of leads in a specific month assigned to you within the selected time range.';
    const LOST_HEADING = 'The number of leads that have been marked as "Lost"';
    const INVALID_HEADING = 'The number of leads marked as "Fake" or "Duplicate."';
    const POLICIES_BOOKED_HEADING = 'The number of leads that you\'ve won. Great job turning these into successes!';
    const VOLUME_NET_RETENTION_HEADING = 'The ratio of won leads to the total leads, excluding invalid leads. VOLUME NET RETENTION= (SALES)/(TOTAL-INVALID)';
    const VOLUME_GROSS_RETENTION_HEADING = 'The ratio of won leads to the total leads, including all leads. VOLUME GROSS RETENTION= (SALES)/(TOTAL)';
    const RELATIVE_RETENTION_HEADING = 'The difference between the average Net Retention of the renewals and the Net Retention of an advisor.';
    const TOTAL_COLUMN = 'Sum of selected leads.';
    const LOST_COLUMN = 'Sum of selected leads classified as "Lost".';
    const INVALID_COLUMN = 'Sum of selected leads classified as "Invalid".';
    const SALES_COLUMN = 'Sum of selected leads that resulted in sales.';
    const VOLUME_NET_RETENTION_COLUMN = 'Total net retention volume of the selected leads. [ Formula ➝ (Total Won leads)/(Sum of Total Leads - Total Invalid leads) ]';
    const VOLUME_GROSS_RETENTION_COLUMN = 'Total gross retention volume of the selected leads. [ Formula ➝ (Total Won leads)/(Sum of Total Leads) ]';
    const RELATIVE_RETENTION_COLUMN = 'The percentage of a particular advisor relative to other advisors of a particular team.';
}
