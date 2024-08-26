<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivePoliciesReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ACTIVE_POLICIES;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ACTIVE_POLICIES;
        if ($request['createdAt'] && ! empty($request['createdAt'])) {
            $this->reportDateRange = Carbon::parse($request['createdAt'])->toDateString();
        }

        $query = PersonalQuote::query()
            ->select(
                DB::raw('COUNT(personal_quotes.id) as active_policy_count'),
                DB::raw('FORMAT(SUM(personal_quotes.price_vat_applicable), 2) as price_with_vat'),
                DB::raw('FORMAT(SUM(price_vat_not_applicable), 2) as price_without_vat'),
                'ip.text as insurer',
                'quote_type.code as line_of_business',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('users as u', 'personal_quotes.advisor_id', '=', 'u.id')
            ->groupBy('ip.text', 'personal_quotes.quote_type_id');

        $this->applyFilters($query, $request, isSSR: true);

        if ($request->export == 1) {
            $data = $query->get();

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 1];

            return $this->download(
                'Active Policies Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes
            );
        } else {
            return $query->simplePaginate(100)->withQueryString();
        }
    }

    public function headings(): array
    {
        return [
            'Insurer',
            'Line of Business',
            'Active Policy Count',
            'Price (VAT applicable)',
            'Price (VAT not applicable)',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->active_policy_count ?? 0,
            $quote->price_with_vat ?? '0.00',
            $quote->price_without_vat ?? '0.00',
        ];
    }

    public function getDefaultFilters()
    {
        return [
            'reportCategory' => ManagementReportCategoriesEnum::ACTIVE_POLICIES,
        ];
    }
}
