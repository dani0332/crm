<?php

namespace App\Exports;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Services\CarQuoteService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PUAUpdatesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    private const HEADING_NEW_BUSINESS = 'Source: New Business';
    private const HEADING_RENEWALS = 'Source: Renewals';
    private const HEADING_TOTAL = 'Total:';
    private const HEADING_TPC_TOTAL = 'TPC Grand Total:';
    private const EMPTY_ROW = '';
    private const NA_VALUE = 'N/A';
    private const DATE_RANGE = '-1 day';

    private array $boldHeadings;
    protected $paymentStatusCounts;
    protected $totalPremiumCaptured;
    protected $totalLeads;
    protected $formatDate;
    protected $newBusinessCounts;
    protected $renewalsCounts;
    protected $newBusinessTPC;
    protected $renewalsTPC;
    protected $requestParams;
    protected $quotesData;

    public function __construct($requestParams = [])
    {
        $this->requestParams = $requestParams;
        $this->initializeBoldHeadings();
        $this->processData();
    }

    private function initializeBoldHeadings(): void
    {
        $this->boldHeadings = [
            self::HEADING_NEW_BUSINESS,
            self::HEADING_RENEWALS,
            self::HEADING_TOTAL,
            self::HEADING_TPC_TOTAL,
        ];
    }

    private function processData(): void
    {
        $currentDate = new \DateTime($this->requestParams['captured_date']);
        $pastDate = $currentDate->modify(self::DATE_RANGE);
        $this->formatDate = $pastDate->format('j M Y');

        $results = $this->fetchData();
        $this->initializeCounters();
        $this->calculateMetrics($results);
    }

    private function fetchData()
    {
        return app(CarQuoteService::class)
            ->exportPUAUpdates($this->requestParams)
            ->select('cqr.source', 'cqr.payment_status_id', 'cqr.premium_captured')
            ->get();
    }

    private function initializeCounters(): void
    {
        $this->newBusinessCounts = $this->renewalsCounts = [
            PaymentStatusEnum::CAPTURED => 0,
            PaymentStatusEnum::PARTIAL_CAPTURED => 0,
        ];
        $this->newBusinessTPC = $this->renewalsTPC = 0;
    }

    private function calculateMetrics($results): void
    {
        foreach ($results as $result) {
            $this->processRecord($result);
        }

        $this->totalPremiumCaptured = $this->newBusinessTPC + $this->renewalsTPC;
        $this->totalLeads = $results->count();
    }

    private function processRecord($result): void
    {
        $isRenewal = $result->source === LeadSourceEnum::RENEWAL_UPLOAD;
        $counts = $isRenewal ? 'renewalsCounts' : 'newBusinessCounts';
        $tpc = $isRenewal ? 'renewalsTPC' : 'newBusinessTPC';

        if ($this->isValidPaymentStatus($result->payment_status_id)) {
            $this->{$counts}[$result->payment_status_id]++;
        }
        $this->{$tpc} += $result->premium_captured;
    }

    private function isValidPaymentStatus(int $status): bool
    {
        return in_array($status, [
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::PARTIAL_CAPTURED,
        ]);
    }

    public function collection()
    {
        $quotes = $this->getQuotesData();

        return $quotes->merge($this->prepareSummary());
    }

    private function getQuotesData()
    {
        return app(CarQuoteService::class)->exportPUAUpdates($this->requestParams)->select(
            'cqr.code as RefId',
            'cqr.source as source',
            'cmk.text as CarMake',
            'cmd.text as CarModel',
            'n.text as Nationality',
            'qs.text as LeadStatus',
            'ps.text as PaymentStatus',
            'cqr.payment_status_id',
            'vt.text as VehicleType',
            'cp.text as PlanName',
            'cp.repair_type as PlanType',
            'ip.text as Insurer',
            'cqr.premium as PremiumAuth',
            'cqr.premium_captured as PremiumCaptured',
            'cqr.dob as dob',
            'cqr.car_value as carValue',
            'cqr.paid_at as paidAt',
            'cqp.pua_type as PUAType',
            'cqr.created_at as createdAt',
        )->get();
    }

    private function prepareSummary(): Collection
    {
        return collect([
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::HEADING_NEW_BUSINESS),
            $this->createSummaryRow('CAPTURED:', $this->formatCount($this->newBusinessCounts[PaymentStatusEnum::CAPTURED])),
            $this->createSummaryRow('PARTIAL CAPTURED:', $this->formatCount($this->newBusinessCounts[PaymentStatusEnum::PARTIAL_CAPTURED])),
            $this->createSummaryRow('TPC:', $this->formatAmount($this->newBusinessTPC)),
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::HEADING_RENEWALS),
            $this->createSummaryRow('CAPTURED:', $this->formatCount($this->renewalsCounts[PaymentStatusEnum::CAPTURED])),
            $this->createSummaryRow('PARTIAL CAPTURED:', $this->formatCount($this->renewalsCounts[PaymentStatusEnum::PARTIAL_CAPTURED])),
            $this->createSummaryRow('TPC:', $this->formatAmount($this->renewalsTPC)),
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::EMPTY_ROW),
            $this->createSummaryRow(self::HEADING_TOTAL, $this->formatCount($this->totalLeads)),
            $this->createSummaryRow(self::HEADING_TPC_TOTAL, $this->formatAmount($this->totalPremiumCaptured)),
        ]);
    }

    private function createSummaryRow(string $status, $count = ''): object
    {
        return (object) ['PaymentStatus' => $status, 'Count' => $count];
    }

    private function formatCount($value): string
    {
        return $value ?? '0';
    }

    private function formatAmount($value): string
    {
        return number_format($value ?? 0, 2);
    }

    public function headings(): array
    {
        return [[
            "CAR PUA (Payment Status Date : $this->formatDate)",
        ], [
            'Ref-ID',
            'Car Make',
            'Car Model',
            'Nationality',
            'Lead Status',
            'Payment Status',
            'Payment Status ID',
            'Vehicle Type',
            'Plan Name',
            'Plan Type',
            'Insurer',
            'Premium Auth',
            'Premium Captured',
            'DOB',
            'Car Value',
            'Paid At',
            'PUA Type',
            'Created At',
            'Source',
            'Transaction Type',
        ]];
    }

    public function map($row): array
    {
        if (! isset($row->RefId) && isset($row->PaymentStatus)) {
            return $this->mapSummaryRow($row);
        }

        return $this->mapDataRow($row);
    }

    private function mapSummaryRow($row): array
    {
        return array_merge(
            ['Payment Status' => $row->PaymentStatus, 'Count' => $row->Count],
            array_fill(0, 14, '') // Fill remaining columns with empty strings
        );
    }

    private function mapDataRow($row): array
    {
        return [
            $row->RefId ?? self::NA_VALUE,
            $row->CarMake ?? self::NA_VALUE,
            $row->CarModel ?? self::NA_VALUE,
            $row->Nationality ?? self::NA_VALUE,
            $row->LeadStatus ?? self::NA_VALUE,
            $row->PaymentStatus ?? self::NA_VALUE,
            $row->payment_status_id ?? self::NA_VALUE,
            $row->VehicleType ?? self::NA_VALUE,
            $row->PlanName ?? self::NA_VALUE,
            $row->PlanType ?? self::NA_VALUE,
            $row->Insurer ?? self::NA_VALUE,
            $row->PremiumAuth ?? self::NA_VALUE,
            $row->PremiumCaptured ?? self::NA_VALUE,
            $this->formatDate($row->dob),
            $row->carValue ?? self::NA_VALUE,
            $this->formatDate($row->paidAt),
            $row->PUAType ?? self::NA_VALUE,
            $this->formatDate($row->createdAt),
            $row->source ?? self::NA_VALUE,
            $row->source == LeadSourceEnum::RENEWAL_UPLOAD ? 'Renewals' : 'New Business',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyHeaderStyles($sheet);
        $this->applyBoldStyles($sheet);

        return [];
    }

    private function applyHeaderStyles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1')->getFont()->setBold(true);
    }

    private function applyBoldStyles(Worksheet $sheet): void
    {
        $rowCount = $sheet->getHighestRow();

        for ($row = 1; $row <= $rowCount; $row++) {
            $value = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            if (in_array($value, $this->boldHeadings)) {
                $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
            }
        }
    }

    private function formatDate($date)
    {
        return $date ? date(config('constants.datetime_format'), strtotime($date)) : self::NA_VALUE;
    }
}
