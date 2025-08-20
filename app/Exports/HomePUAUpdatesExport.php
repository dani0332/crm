<?php

namespace App\Exports;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Repositories\HomeQuoteRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HomePUAUpdatesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        $currentDate = new \DateTime;
        $pastDate = $currentDate->modify(self::DATE_RANGE);
        $this->formatDate = $pastDate->format('j M Y');

        $results = $this->fetchData();
        $this->initializeCounters();
        $this->calculateMetrics($results);
    }

    private function fetchData()
    {
        return app(HomeQuoteRepository::class)
            ->exportPUAUpdates($this->requestParams)
            ->get()
            ->map(function ($quote) {
                return (object) [
                    'source' => $quote->source,
                    'payment_status_id' => $quote->payment_status_id,
                    'premium' => $quote->premiumcaptured,
                ];
            });
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
        $this->{$tpc} += $result->premium ?? 0;
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

        return collect($quotes)->merge($this->prepareSummary());
    }

    private function getQuotesData()
    {
        $results = app(HomeQuoteRepository::class)->exportPUAUpdates($this->requestParams)->get();

        return $results->map(function ($quote) {
            return (object) [
                'RefId' => $quote->RefID,
                'source' => $quote->source,
                'PropertyType' => 'Home Insurance',
                'PropertyCategory' => 'Property',
                'nationality' => $quote->nationality->text ?? 'N/A',
                'LeadStatus' => $quote->quoteStatus->text ?? 'N/A',
                'PaymentStatus' => $quote->paymentStatus->text ?? 'N/A',
                'payment_status_id' => $quote->payment_status_id,
                'InsuranceType' => 'Home Insurance',
                'PlanName' => 'Home Property Plan',
                'PlanType' => 'Comprehensive',
                'Insurer' => $quote->insuranceProvider->text ?? 'N/A',
                'PremiumAuth' => $quote->premiumauthorized,
                'PremiumCaptured' => $quote->premiumcaptured,
                'dob' => $quote->dob,
                'paidAt' => $quote->paymentauthdate,
                'PUAType' => 'Premium Update',
                'createdAt' => $quote->created_at,
            ];
        });
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
            "HOME PUA (Payment Status Date : $this->formatDate)",
        ], [
            'Ref-ID',
            'Property Type',
            'Property Category',
            'Nationality',
            'Lead Status',
            'Payment Status',
            'Payment Status ID',
            'Insurance Type',
            'Plan Name',
            'Plan Type',
            'Insurer',
            'Premium Auth',
            'Premium Captured',
            'DOB',
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
            array_fill(0, 17, '') // Fill remaining columns with empty strings
        );
    }

    private function mapDataRow($row): array
    {
        return [
            $row->RefId ?? self::NA_VALUE,
            $row->PropertyType ?? self::NA_VALUE,
            $row->PropertyCategory ?? self::NA_VALUE,
            $row->nationality ?? self::NA_VALUE,
            $row->LeadStatus ?? self::NA_VALUE,
            $row->PaymentStatus ?? self::NA_VALUE,
            $row->payment_status_id ?? self::NA_VALUE,
            $row->InsuranceType ?? self::NA_VALUE,
            $row->PlanName ?? self::NA_VALUE,
            $row->PlanType ?? self::NA_VALUE,
            $row->Insurer ?? self::NA_VALUE,
            $row->PremiumAuth ?? self::NA_VALUE,
            $row->PremiumCaptured ?? self::NA_VALUE,
            $this->formatDate($row->dob),
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
