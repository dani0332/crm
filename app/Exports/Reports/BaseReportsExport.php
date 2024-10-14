<?php

namespace App\Exports\Reports;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

abstract class BaseReportsExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithPreCalculateFormulas
{
    use Exportable, RegistersEventListeners;

    abstract public function headings(): array;

    public function __construct(public Collection $data) {}

    public function collection()
    {
        return $this->data;
    }

    public function columnFormats(): array
    {
        return $this->generateExcelColumns()->mapWithKeys(fn ($column) => [$column => NumberFormat::FORMAT_TEXT])->all();
    }

    protected function generateExcelColumns(): Collection
    {
        $columns = collect();

        $getColumnName = function ($num) {
            $name = '';
            while ($num > 0) {
                $num--;
                $name = chr($num % 26 + 65).$name;
                $num = intval($num / 26);
            }

            return $name;
        };

        foreach (range(1, count($this->headings())) as $i) {
            $columns->push($getColumnName($i));
        }

        return $columns;
    }

    protected function resolveNumberFormat($value)
    {
        if (is_string($value) && strpos($value, ',') !== false) {
            return floatval(str_replace(',', '', $value));
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return $value;
    }

    public static function performSum(AfterSheet $event, array $numberColumns)
    {
        $sheet = $event->sheet->getDelegate();

        $lastRow = $sheet->getHighestRow();

        // if sheet has no row, then no need to show totals row
        if ($lastRow < 2) {
            return;
        }

        $totalsRow = $lastRow + 1;

        $sheet->setCellValue("A{$totalsRow}", 'Total:');

        foreach ($numberColumns as $column) {
            $sheet->setCellValue("{$column}{$totalsRow}", "=SUM({$column}2:{$column}{$lastRow})");
        }

        $lastColumn = Arr::last($numberColumns);
        $sheet->getStyle("A{$totalsRow}:{$lastColumn}{$totalsRow}")->getFont()->setBold(true);
    }
}
