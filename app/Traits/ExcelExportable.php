<?php

namespace App\Traits;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExcelExportable
{
    abstract public function collection();
    abstract public function headings();
    abstract public function map($quote);

    public function download($fileName)
    {
        $fileName = $fileName.'-'.Carbon::now()->format('Y-m-d');

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());
            $data = $this->collection();
            foreach ($data as $quote) {
                fputcsv($handle, $this->map($quote));
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'.csv"',
        ]);
    }
}
