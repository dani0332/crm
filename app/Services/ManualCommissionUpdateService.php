<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
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

        $fixedCommission = 0.0;
        if ($brokerCommission !== null && isset($brokerCommission->fixed_commission)) {
            $fixedCommission = (float) $brokerCommission->fixed_commission;
        }

        $commissionPercentageMin = max(($fixedCommission - 2.5), 0.0);
        $commissionPercentageMax = $fixedCommission > 0.0 ? $fixedCommission + 2.5 : 0.0;

        if ($commissionPercentageMin != 0.0 && $commissionPercentageMax != 0.0) {
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

    public function updateCommissionForLeads(array $carQuoteRefIds = [])
    {
        $results = [];
        $carQuoteRefIds = $carQuoteRefIds ?? [];

        foreach ($carQuoteRefIds as $refId) {
            try {
                $carQuoteDetails = CarQuote::where('code', $refId)->first();
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
                        // $results[$refId] = [
                        //     'status' => false,
                        //     'success' => false,
                        //     'error' => 'Commission percentage exceeds limit',
                        //     'message' => 'Commission percentage exceeds limit',
                        //     'commission_details' => $commissionDetails,
                        // ];

                        // TODO:: this is temp code to update commission and always work on single lead
                        $results = [
                            'status' => false,
                            'success' => false,
                            'error' => 'Commission percentage exceeds limit',
                            'message' => 'Commission percentage exceeds limit',
                            'commission_details' => $commissionDetails,
                        ];

                        return $results;

                        // continue;
                    }

                    $payment->update([
                        'invoice_description' => $invoiceDescription,
                        'commmission_percentage' => $commissionDetails['commission_percentage'],
                        'commission_vat' => $commissionDetails['vat_on_commission'],
                        'commission' => $commissionDetails['total_commission'],
                    ]);

                    $results = ['status' => true, 'success' => true, 'commission_details' => $commissionDetails];

                }
            } catch (\Exception $e) {
                LoggerService::info('__class: '.self::class.' fn: '.__FUNCTION__.' Error while processing commission for ref_id: '.$refId, extra: ['error' => $e->getMessage()]);
                $results = ['status' => false, 'success' => false, 'error' => 'Error processing commission: '.$e->getMessage()];
            }
        }

        // $successfullRefIds = array_filter($results, fn ($r) => $r['success']);
        // $successfullRefIds = array_keys($successfullRefIds);

        // $failedRefIds = array_filter($results, fn ($r) => ! $r['success']);
        // $failedRefIds = array_keys($failedRefIds);

        // LoggerService::info('__class: '.self::class.' fn: '.__FUNCTION__.' Commission processing completed', context: [
        //     'total_processed' => count($carQuoteRefIds),
        //     'successful' => count($successfullRefIds),
        //     'failed' => count($failedRefIds),
        //     'successfull_ref_ids' => $successfullRefIds,
        //     'failed_ref_ids' => $failedRefIds,
        // ]);

        return $results;

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Commission processing completed',
        //     'total_processed' => count($carQuoteRefIds),
        //     'successfull_ref_ids' => $successfullRefIds,
        //     'failed_ref_ids' => $failedRefIds,
        //     'results' => $results,
        // ]);
    }
}
