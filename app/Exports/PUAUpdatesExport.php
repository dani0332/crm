<?php

namespace App\Exports;

use App\Enums\PaymentStatusEnum;
use App\Services\CarQuoteService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PUAUpdatesExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected $paymentStatusCounts;
    protected $totalPremiumCaptured;
    protected $totalLeads;
    protected $formatDate;

    public function __construct()
    {
        $currentDate = new \DateTime;
        $pastDate = $currentDate->modify('-1 day');
        $this->formatDate = $pastDate->format('j M Y');

        $this->paymentStatusCounts = app(CarQuoteService::class)
            ->exportPUAUpdates()
            ->select('cqr.payment_status_id')
            ->whereIn('cqr.payment_status_id', [
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PARTIAL_CAPTURED,
            ])
            ->groupBy('cqr.payment_status_id')
            ->selectRaw('cqr.payment_status_id, COUNT(*) as total_count')
            ->pluck('total_count', 'cqr.payment_status_id');

        $this->totalPremiumCaptured = app(CarQuoteService::class)
            ->exportPUAUpdates()
            ->sum('cqr.premium_captured');
        $this->totalLeads = app(CarQuoteService::class)
            ->exportPUAUpdates()
            ->selectRaw('COUNT(*) as lead_count')
            ->pluck('lead_count')
            ->first();
    }

    public function collection()
    {
        $quotes = app(CarQuoteService::class)->exportPUAUpdates()->select(
            'cqr.code as RefId',
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

        $summary = collect([
            (object) ['PaymentStatus' => ' ', 'Count' => ' '],
            (object) ['PaymentStatus' => ' ', 'Count' => ' '],
            (object) ['PaymentStatus' => ' ', 'Count' => ' '],
            (object) ['PaymentStatus' => 'CAPTURED:', 'Count' => $this->paymentStatusCounts[PaymentStatusEnum::CAPTURED] ?? ''],
            (object) ['PaymentStatus' => 'PARTIAL CAPTURED:', 'Count' => $this->paymentStatusCounts[PaymentStatusEnum::PARTIAL_CAPTURED] ?? ''],
            (object) ['PaymentStatus' => 'TPC:', 'Count' => number_format($this->totalPremiumCaptured, 2)],
            (object) ['PaymentStatus' => 'Total:', 'Count' => $this->totalLeads],
        ]);

        return $quotes->merge($summary);
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
        ],
        ];
    }

    public function map($row): array
    {
        if (! isset($row->RefId) && isset($row->PaymentStatus)) {
            info($row->PaymentStatus);
            info($row->Count);

            return [
                'Payment Status' => $row->PaymentStatus,
                'Count' => $row->Count ? $row->Count : number_format(0),
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            ];
        }

        return [
            $row->RefId ?? 'N/A',
            $row->CarMake ?? 'N/A',
            $row->CarModel ?? 'N/A',
            $row->Nationality ?? 'N/A',
            $row->LeadStatus ?? 'N/A',
            $row->PaymentStatus ?? 'N/A',
            $row->payment_status_id ?? 'N/A',
            $row->VehicleType ?? 'N/A',
            $row->PlanName ?? 'N/A',
            $row->PlanType ?? 'N/A',
            $row->Insurer ?? 'N/A',
            $row->PremiumAuth ?? 'N/A',
            $row->PremiumCaptured ?? 'N/A',
            $this->formatDate($row->dob),
            $row->carValue ?? 'N/A',
            $this->formatDate($row->paidAt),
            $row->PUAType ?? 'N/A',
            $this->formatDate($row->createdAt),
        ];
    }

    private function formatDate($date)
    {
        return $date ? date(config('constants.datetime_format'), strtotime($date)) : 'N/A';
    }
}
