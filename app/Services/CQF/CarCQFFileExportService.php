<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Exports\RenewalFailedValidationExport;
use App\Models\RenewalsUploadLeads;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Logger\LoggerService;

class CarCQFFileExportService
{
    public function downloadValidationFailedFile(int $id)
    {
        $renewalUploadLead = RenewalsUploadLeads::where('id', $id)->first();
        if (!$renewalUploadLead) {
            LoggerService::error(self::class.' - Renewal upload lead not found');
            return null;
        }
        return Excel::download(
            new RenewalFailedValidationExport($renewalUploadLead), 
            'failed_'.$renewalUploadLead->file_name
        );
    }
}
