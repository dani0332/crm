<?php

namespace App\Exports;

use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HealthQuotesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct($code, $fname, $lname, $email, $mobile, $from, $to, $sub_team, $lead_status, $advisors, $is_ecom, $is_renewal)
    {
        $this->code = $code;
        $this->fname = $fname;
        $this->lname = $lname;
        $this->email = $email;
        $this->mobile = $mobile;
        $this->from = $from;
        $this->to = $to;
        $this->sub_team = $sub_team;
        $this->lead_status = $lead_status;
        $this->advisors = $advisors;
        $this->is_ecom = $is_ecom;
        $this->is_renewal = $is_renewal;
    }

    public function query()
    {
        return HealthQuote::query()
            ->whereBetween('created_at', [$this->from, $this->to])
            ->when($this->code, function ($query, $code) {
                return $query->where('code', $code);
            })
            ->when($this->fname, function ($query, $fname) {
                return $query->where('first_name', $fname);
            })
            ->when($this->lname, function ($query, $lname) {
                return $query->where('last_name', $lname);
            })
            ->when($this->email, function ($query, $email) {
                return $query->where('email', $email);
            })
            ->when(! $this->email, function ($query) {
                return $query->where('quote_status_id', '!=', QuoteStatusEnum::Fake);
            })
            ->when($this->mobile, function ($query, $mobile) {
                return $query->where('mobile_no', $mobile);
            })
            ->when($this->sub_team, function ($query, $sub_team) {
                return $query->where('sub_team', $sub_team);
            })
            ->when($this->lead_status, function ($query, $lead_status) {
                return $query->whereIn('quote_status_id', $lead_status);
            })
            ->when($this->advisors, function ($query, $advisors) {
                return $query->whereIn('advisor_id', $advisors)->orWhereIn('wcu_id', $advisors);
            })
            ->when($this->is_ecom, function ($query, $is_ecom) {
                return $query->where('is_ecommerce', $is_ecom);
            })
            ->when($this->is_renewal, function ($query, $is_renewal) {
                if ($is_renewal == 'Yes') {
                    return $query->whereNotNull('previous_quote_policy_number');
                } else {
                    return $query->whereNull('previous_quote_policy_number');
                }
            })
            ->latest();
    }

    public function headings(): array
    {
        return [
            'CDB ID',
            'FIRST NAME',
            'LAST NAME',
            'LEAD STATUS',
            'ADVISOR',
            'WC ADVISOR',
            'CREATED DATE',
            'LAST MODIFIED DATE',
            'HEALTH TEAM TYPE',
            'TRANSAPP CODE',
            'LOST REASON',
            'PREMIUM',
            'POLICY NUMBER',
            'SOURCE',
            'LEAD TYPE',
            'SALARY BAND',
            'MEMBER CATEGORY',
            'CURRENTLY INSURED WITH',
            'IS ECOMMERCE',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code,
            $quote->first_name,
            $quote->last_name,
            $quote->quoteStatus->text ?? null,
            $quote->advisor->name ?? null,
            $quote->wcAdvisor->name ?? null,
            date('d-m-Y H:i:s', strtotime($quote->created_at)),
            date('d-m-Y H:i:s', strtotime($quote->updated_at)),
            $quote->health_team_type,
            $quote->transapp_code,
            $quote->lost_reason,
            $quote->premium,
            $quote->policy_number,
            $quote->source,
            $quote->healthLeadType->text ?? null,
            $quote->salaryBand->text ?? null,
            $quote->memberCategory->text ?? null,
            $quote->currentProvider->text ?? null,
            $quote->is_ecommerce ? 'Yes' : 'No',
        ];
    }
}
