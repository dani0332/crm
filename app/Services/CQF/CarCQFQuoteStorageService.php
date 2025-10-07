<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\RenewalsUploadLeads;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CapiRequestService;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Carbon;

class CarCQFQuoteStorageService
{
    public function storeCarCQFRenewalQuote(
        CarQuote $quote,
        RenewalsUploadLeads $renewalsUploadLeads,
        int $renewalDaysThreshold,
        array &$epCodes
    ): ?CarQuote {
        LoggerService::info(self::class.' - Storing car cqf renewal quote');

        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);

        // Calculate the policy expiry date based on the start date + 120 days
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class.' - Policy Details', [
            'policyExpiryDate' => $policyExpiryDate,
            'policyStartDate' => $policyStartDate,
            'newPolicyExpiryDate' => $newPolicyExpiryDate,
        ]);

        $quoteMappingService = app(CarCQFQuoteMappingService::class);
        $quoteUuid = $this->generateUUID();
        $quoteData = $quoteMappingService->mapCarCQFRenewalQuote($quote, $renewalsUploadLeads, $quoteUuid);

        $newQuote = CarQuote::create($quoteData);

        if ($newQuote) {
            $entityService = app(CarCQFEntityService::class);
            $entityService->getCustomerEntity($newQuote, $quote);
            $entityService->storeCarDetails($newQuote, $quote);

            app(EmbeddedProductRepository::class)->saveEmbeddedTransaction($newQuote, QuoteTypeId::Car);

            if (! empty($quote->embeddedTransactions)) {
                foreach ($quote->embeddedTransactions as $embeddedTransaction) {
                    
                    $isSelected = $embeddedTransaction->is_selected == 1;
                    $isPaymentCaptured = $embeddedTransaction->payment_status_id === PaymentStatusEnum::CAPTURED;
                    $isMdxProduct = str_contains($embeddedTransaction->code, EmbeddedProductEnum::MDX);
                    
                    $shouldCreateEP = $isSelected && $isPaymentCaptured && $isMdxProduct;
                    
                    if ($shouldCreateEP) {
                        $epCodes[] = EmbeddedProductEnum::MDX.'-'.$newQuote->code;
                        LoggerService::info(self::class.' - Embedded Transaction found for quote', [
                            'embeddedTransaction' => [
                                'code' => $embeddedTransaction->code,
                                'is_selected' => $embeddedTransaction->is_selected,
                                'payment_status_id' => $embeddedTransaction->payment_status_id,
                                'product_id' => $embeddedTransaction->product_id ?? null,
                                'previous_quote_uuid' => $quote->uuid,
                                'new_quote_uuid' => $newQuote->uuid,
                                'previous_quote_id' => $quote->id,
                                'new_quote_id' => $newQuote->id,
                            ],
                        ]);
                    }

                }
            }

            LoggerService::info(sprintf('%s - Car CQF Renewal Quote created successfully', self::class), [
                'previous_quote_uuid' => $quote->uuid,
                'new_quote_uuid' => $newQuote->uuid,
                'previous_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
            ]);
        }

        return $newQuote;
    }

    public function generateUUID(): ?string
    {
        if (checkPersonalQuotes(QuoteTypes::CAR)) {
            $response = app(CapiRequestService::class)->getPersonalQuoteUUID(QuoteTypes::CAR->id());
        } else {
            $response = app(CapiRequestService::class)->getUUID(QuoteTypes::CAR->id());
        }

        if ($response) {
            return $response->uuid;
        }

        return null;
    }
}
