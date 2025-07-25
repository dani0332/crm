<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;

class ManualCommissionUpdateService extends BaseService
{
    private function calculateVatOnCommission($commissionVatApplicable, $vatRate)
    {
        if ($commissionVatApplicable > 0) {
            return roundNumber($commissionVatApplicable * $vatRate);
        }

        return 0;
    }

    private function calculateCommissionPercentage($totalCommissionWithoutVat, $totalPriceWithoutVat, $brokerCommission = null)
    {
        if ($totalCommissionWithoutVat <= 0) {
            return 0;
        }

        $totalCommissionInPercentage = ($totalCommissionWithoutVat / $totalPriceWithoutVat) * 100;

        $commissionPercentageMin = 0;
        $commissionPercentageMax = 0;
        $commissionPercentageExceedsLimit = false;

        if ($brokerCommission && $brokerCommission->fixed_commission) {
            $commissionPercentageMin = max(($brokerCommission->fixed_commission - 2.5), 0);
            $commissionPercentageMax = $brokerCommission->fixed_commission + 2.5;

            if ($totalCommissionInPercentage < $commissionPercentageMin ||
                $totalCommissionInPercentage > $commissionPercentageMax) {
                $commissionPercentageExceedsLimit = true;
            }
        }

        return [
            'min_percentage' => $commissionPercentageMin,
            'max_percentage' => $commissionPercentageMax,
            'percentage' => roundNumber($totalCommissionInPercentage),
            'exceeds_limit' => $commissionPercentageExceedsLimit,
        ];
    }

    private function calculateCommissionDetails($carQuoteDetails, $payment, $brokerCommission)
    {
        $vatRate = ApplicationStorageEnums::VAT;
        $totalPriceWithoutVat =
            ($carQuoteDetails->price_vat_applicable ?? 0) +
            ($carQuoteDetails->price_vat_not_applicable ?? 0);

        $totalCommissionWithoutVat =
            ($payment->commission_vat_not_applicable ?? 0) +
            ($payment->commission_vat_applicable ?? 0);

        $result = [
            'total_price_without_vat' => $totalPriceWithoutVat,
            'total_commission_without_vat' => $totalCommissionWithoutVat,
            'vat_on_commission' => 0,
            'total_commission' => 0,
            'min_percentage' => 0,
            'max_percentage' => 0,
            'commission_percentage' => 0,
            'commission_percentage_exceeds_limit' => false,
            'error' => null,
        ];

        if ($totalCommissionWithoutVat > 0) {
            $result['vat_on_commission'] = $this->calculateVatOnCommission($payment->commission_vat_applicable ?? 0, $vatRate);
            $result['total_commission'] = $totalCommissionWithoutVat + $result['vat_on_commission'];

            if ($totalPriceWithoutVat > 0) {
                $percentageResult = $this->calculateCommissionPercentage(
                    $totalCommissionWithoutVat,
                    $totalPriceWithoutVat,
                    $brokerCommission
                );
                $result['min_percentage'] = $percentageResult['min_percentage'] ?? 0;
                $result['max_percentage'] = $percentageResult['max_percentage'] ?? 0;
                $result['commission_percentage'] = $percentageResult['percentage'] ?? 0;
                $result['commission_percentage_exceeds_limit'] = $percentageResult['exceeds_limit'] ?? false;
            } else {
                $result['error'] = 'Total price is zero for this policy';
            }
        }

        return $result;
    }

    public function updateCommissionForLeads()
    {
        $results = [];
        $carQuoteRefIds = [
            'CAR-2FQ3U3GN',
            'CAR-2GFXHRXR',
            'CAR-2GZTJW7H',
            'CAR-2H7JFU3N',
            'CAR-2MM353XV',
            'CAR-2Q4RU3T8',
            'CAR-2QJ2HC7T',
            'CAR-2QPRZHGB',
            'CAR-2T5SREKN',
            'CAR-2TPFH78U',
            'CAR-2YT4T4D8',
            'CAR-38SN44FE',
            'CAR-3G68VKQH',
            'CAR-3RBFJMUW',
            'CAR-3SWUPSYC',
            'CAR-46FWXABF',
            'CAR-49HGBRWJ',
            'CAR-4E3K2KEQ',
            'CAR-4HU6RDTF',
            'CAR-4JGSE9NB',
            'CAR-4N7ACMEB',
            'CAR-4Q67JHQN',
            'CAR-54UC7BAV',
            'CAR-5B3WJRTS',
            'CAR-5HBZMPS6',
            'CAR-5L9DEBMU',
            'CAR-5LC5ZWF8',
            'CAR-5RBLYZYJ',
            'CAR-6EQ56WH9',
            'CAR-6K3YNDBY',
            'CAR-6KLPWUQ2',
            'CAR-6ZSPBKZC',
            'CAR-7KBKSQUX',
            'CAR-7LCYLGCA',
            'CAR-7MXWXFN2',
            'CAR-7WAQ6HJB',
            'CAR-7ZGZXYPQ',
            'CAR-88PXEUHG',
            'CAR-8E2ZQ53U',
            'CAR-8EWPDM5Z',
            'CAR-8F64BBHL',
            'CAR-8JPGDV4W',
            'CAR-8K9ZUDTX',
            'CAR-8NY5NXQL',
            'CAR-8QHR3ZL4',
            'CAR-8Z5A6SJJ',
            'CAR-9C858TXK',
            'CAR-9PTAXUWU',
            'CAR-ABE83Y5A',
            'CAR-ADKYBS5B',
            'CAR-AF6JQE3M',
            'CAR-B5AX5LNP',
            'CAR-BEDNX36X',
            'CAR-BH5SJBFS',
            'CAR-BHDB2D4Q',
            'CAR-BT68HZGE',
            'CAR-BZ5Y5GWB',
            'CAR-CGPX4C5Z',
            'CAR-CHGXFD7R',
            'CAR-CHL4ZM3Q',
            'CAR-CP582T3N',
            'CAR-CWSSWL7Y',
            'CAR-D3AREJFH',
            'CAR-D432DYKG',
            'CAR-DC3KPXXE',
            'CAR-DKQQZQ6X',
            'CAR-DP8YJZPG',
            'CAR-DTLTBUR3',
            'CAR-E3HHFL37',
            'CAR-E6Y9PTE4',
            'CAR-E7Z2VHT5',
            'CAR-E8AX6AP6',
            'CAR-EJACL6EB',
            'CAR-EKMSJ6TV',
            'CAR-ENM86AQ7',
            'CAR-ERJDZMJN',
            'CAR-F5QTHVB5',
            'CAR-FFV4QFJP',
            'CAR-FJBKX7LG',
            'CAR-FYDFR7JH',
            'CAR-G8HHCNV3',
            'CAR-G9UR4ZMC',
            'CAR-GF4WBM6H',
            'CAR-GS4YWG7N',
            'CAR-GS7CHQ5T',
            'CAR-GUKC7FTN',
            'CAR-HCKYAR2Z',
            'CAR-HDGKZ49C',
            'CAR-HMRQC3VU',
            'CAR-HWX28CMM',
            'CAR-J324E5KK',
            'CAR-J4YNT4LC',
            'CAR-J7WTRDFS',
            'CAR-J8SKPNVY',
            'CAR-J9EV4DSB',
            'CAR-JKPATDDH',
            'CAR-JP3B7XNL',
            'CAR-JPHXAQ8U',
            'CAR-JQ7VWA24',
            'CAR-JX8P4G8U',
            'CAR-K5WLYK9B',
            'CAR-K6WLE6W9',
            'CAR-K79EULWJ',
            'CAR-KG52R52B',
            'CAR-KGYZZP69',
            'CAR-KHUCQQU8',
            'CAR-KMJWL8ED',
            'CAR-L3HNBTK8',
            'CAR-L8GT5DPF',
            'CAR-L8KMPRQT',
            'CAR-LDJC9JWJ',
            'CAR-LHX6LJ6K',
            'CAR-LJA4C63R',
            'CAR-LR2RDPBE',
            'CAR-LR84K3UD',
            'CAR-MB5X6ZNZ',
            'CAR-MDWV3FQ4',
            'CAR-MLB83A9U',
            'CAR-N422QH76',
            'CAR-N5M763GR',
            'CAR-NCVRAYZU',
            'CAR-NN9ZYGFG',
            'CAR-NRKN2VSA',
            'CAR-NVEVDKZ2',
            'CAR-NY9GGPJC',
            'CAR-P2CXBS4Z',
            'CAR-P5JUNL2G',
            'CAR-P6M4G3VQ',
            'CAR-PFWRFGPA',
            'CAR-PMAJG2MQ',
            'CAR-PMZDZ2T5',
            'CAR-Q4LBDTQF',
            'CAR-QBJ64NSD',
            'CAR-QE2QENPF',
            'CAR-QL3ZQXCL',
            'CAR-QMEGPUZV',
            'CAR-QPMTX76L',
            'CAR-QRVKEFPP',
            'CAR-QTCAFT9Y',
            'CAR-QYUJEREF',
            'CAR-RB42N4TH',
            'CAR-RBAQMTJ8',
            'CAR-RNWD3942',
            'CAR-RU522N2P',
            'CAR-RUSPYFEZ',
            'CAR-RVH88XSP',
            'CAR-RZK5KMA7',
            'CAR-S3SVPX6E',
            'CAR-S8VZNNSV',
            'CAR-SP5DELZ4',
            'CAR-TH5B3EDK',
            'CAR-TVNXM7TS',
            'CAR-U9D8PG2D',
            'CAR-UAJX2DKP',
            'CAR-UJHKCDTS',
            'CAR-UQEXWSTA',
            'CAR-UWERDGGZ',
            'CAR-V943NKJF',
            'CAR-VEH53THT',
            'CAR-VRRDSDDJ',
            'CAR-VRYWVWFQ',
            'CAR-VSLS7U8C',
            'CAR-VTH68HFR',
            'CAR-W6ZJ4287',
            'CAR-WXZEJSLD',
            'CAR-X7GEWKJL',
            'CAR-XANYV7UJ',
            'CAR-XBXTRZTN',
            'CAR-XCNRZJ98',
            'CAR-XEGLL934',
            'CAR-XENNWVTU',
            'CAR-XJZV4R9Q',
            'CAR-XLQ36AWA',
            'CAR-XLYKSZPG',
            'CAR-XRA52CZH',
            'CAR-XT2R9T2Q',
            'CAR-XVE3N6E8',
            'CAR-YJY27ARF',
            'CAR-YMQ6WR84',
            'CAR-Z4RCPFHK',
            'CAR-Z7P7TTRM',
            'CAR-ZFC538Z9',
            'CAR-ZK6EYHZE',
            'CAR-ZNXQY35P',
            'CAR-ZSYR37KC',
        ];

        foreach ($carQuoteRefIds as $refId) {
            try {
                $carQuoteDetails = CarQuote::where('code', $refId)->first();
                if ($carQuoteDetails->status !== QuoteStatusEnum::PolicyBooked) {
                    $results[$refId] = [
                        'success' => false,
                        'error' => 'Policy is not booked yet',
                    ];

                    continue;
                }

                $payment = $carQuoteDetails->payment;
                $insuranceProvider = getInsuranceProvider($payment, QuoteTypes::CAR->value, $carQuoteDetails);
                $insuranceProviderId = $insuranceProvider ? $insuranceProvider->id : null;

                [$isCreditCardEnabled, $brokerCommission, $commissionInPayments] = app(BrokerCommissionService::class)
                    ->fetchBrokerCommission(QuoteTypes::CAR->id(), $insuranceProviderId, null, $carQuoteDetails->plan_id ?? null, $carQuoteDetails);

                if ($payment) {
                    $invoiceDescription = app(PaymentRepository::class)->generateInvoiceDescription($payment, QuoteTypes::CAR->value, $carQuoteDetails);
                    app(PaymentRepository::class)->generateAndStoreBrokerInvoiceNumber($carQuoteDetails, $payment, QuoteTypes::CAR->value);
                    $commissionDetails = $this->calculateCommissionDetails($carQuoteDetails, $payment, $brokerCommission);

                    if ($commissionDetails['commission_percentage_exceeds_limit']) {
                        $results[$refId] = [
                            'success' => false,
                            'error' => 'Commission percentage exceeds limit',
                            'commission_details' => $commissionDetails,
                        ];

                        continue;
                    }

                    $payment->update([
                        'invoice_description' => $invoiceDescription,
                        'commmission_percentage' => $commissionDetails['commission_percentage'],
                        'commission_vat' => $commissionDetails['vat_on_commission'],
                        'commission' => $commissionDetails['total_commission'],
                    ]);

                    $results[$refId] = ['success' => true, 'commission_details' => $commissionDetails];

                }
            } catch (\Exception $e) {
                LoggerService::info('__class: '.self::class.' fn: '.__FUNCTION__.' Error while processing commission for ref_id: '.$refId, extra: ['error' => $e->getMessage()]);
                $results[$refId] = ['success' => false, 'error' => 'Error processing commission: '.$e->getMessage()];
            }
        }

        $successfullRefIds = array_filter($results, fn ($r) => $r['success']);
        $successfullRefIds = array_keys($successfullRefIds);

        $failedRefIds = array_filter($results, fn ($r) => ! $r['success']);
        $failedRefIds = array_keys($failedRefIds);

        LoggerService::info('__class: '.self::class.' fn: '.__FUNCTION__.' Commission processing completed', context: [
            'total_processed' => count($carQuoteRefIds),
            'successful' => count($successfullRefIds),
            'failed' => count($failedRefIds),
            'successfull_ref_ids' => $successfullRefIds,
            'failed_ref_ids' => $failedRefIds,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commission processing completed',
            'total_processed' => count($carQuoteRefIds),
            'successfull_ref_ids' => $successfullRefIds,
            'failed_ref_ids' => $failedRefIds,
            'results' => $results,
        ]);
    }
}
