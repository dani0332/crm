<?php

declare(strict_types=1);

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CensusListExcelExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(
        private Collection $members
    ) {}

    public function collection(): Collection
    {
        return $this->members;
    }

    public function headings(): array
    {
        return [
            'Full name',
            'Date of birth',
            'Gender',
            'Marital status',
            'Relation',
            'Nationality',
            'Emirate of visa',
            'Salary',
            'Category',
        ];
    }

    /**
     * @param  array<string, mixed>|object  $member
     * @return array<int, string>
     */
    public function map($member): array
    {
        $row = is_array($member) ? $member : (array) $member;

        return [
            (string) ($row['full_name'] ?? ''),
            $row['date_of_birth'] ? Carbon::parse($row['date_of_birth'])->format('d-m-Y') : '',
            (string) ($row['gender'] ?? ''),
            (string) ($row['marital_status'] ?? ''),
            (string) ($row['relation'] ?? ''),
            (string) ($row['nationality'] ?? ''),
            (string) ($row['emirates_of_visa'] ?? ''),
            (string) ($row['salary'] ?? ''),
            (string) ($row['category'] ?? ''),
        ];
    }
}
