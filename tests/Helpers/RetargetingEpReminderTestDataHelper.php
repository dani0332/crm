<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\QuoteStatusEnum;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\InsuranceProvider;

/**
 * Universal test data helper for Retargeting EP Reminder tests.
 * Use setupTestData() for API/observer tests; use setupRepositoryTestData() for repository tests.
 */
class RetargetingEpReminderTestDataHelper
{
    /**
     * Set up test data for repository tests (getDraftEpTransactions, fetchFindEmbededTransactionWithDetails).
     * Creates provider, plan, two quotes (policy booked / policy issued), MDX/ECB/COU products and transactions.
     *
     * @return object{carQuotePolicyBooked: CarQuote, carQuotePolicyIssued: CarQuote, validTransaction: EmbeddedTransaction, notBookedTransaction: EmbeddedTransaction, epECBTransaction: EmbeddedTransaction, notAllowedEpTransaction: EmbeddedTransaction, notDraftTransaction: EmbeddedTransaction}
     */
    public static function setupRepositoryTestData(): object
    {
        $insuranceProvider = InsuranceProvider::forceCreate(InsuranceProvider::factory()->definition());
        $carPlan = CarPlan::forceCreate(array_merge(
            CarPlan::factory()->definition(),
            ['provider_id' => $insuranceProvider->id]
        ));

        $carQuotePolicyBooked = CarQuote::forceCreate(array_merge(CarQuote::factory()->definition(), [
            'quote_status_id' => QuoteStatusEnum::PolicyBooked,
            'plan_id' => $carPlan->id,
            'insurance_provider_id' => $insuranceProvider->id,
        ]));
        $carQuotePolicyIssued = CarQuote::forceCreate(array_merge(CarQuote::factory()->definition(), [
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'plan_id' => null,
        ]));

        $epMDX = EmbeddedProduct::factory()->mdx()->forInsuranceProvider($insuranceProvider->id)->create();
        $epECB = EmbeddedProduct::factory()->ecb()->forInsuranceProvider($insuranceProvider->id)->create();
        $epCOU = EmbeddedProduct::factory()->cou()->forInsuranceProvider($insuranceProvider->id)->create();
        $optionMDX = EmbeddedProductOption::factory()->forEmbeddedProduct($epMDX->id)->create();
        $optionECB = EmbeddedProductOption::factory()->forEmbeddedProduct($epECB->id)->create();
        $optionCOU = EmbeddedProductOption::factory()->forEmbeddedProduct($epCOU->id)->create();

        $validTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuotePolicyBooked)
            ->forProduct($optionMDX->id)
            ->create(['code' => 'ET-VALID']);
        $notBookedTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuotePolicyIssued)
            ->forProduct($optionMDX->id)
            ->create(['code' => 'ET-NOT-BOOKED']);
        $epECBTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuotePolicyBooked)
            ->forProduct($optionECB->id)
            ->create(['code' => 'ET-ECB-DRAFT']);
        $notAllowedEpTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuotePolicyBooked)
            ->forProduct($optionCOU->id)
            ->create(['code' => 'ET-NOT-ALLOWED-EP']);
        $notDraftTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuotePolicyBooked)
            ->forProduct($optionMDX->id)
            ->nonDraft()
            ->create(['code' => 'ET-AUTHORISED']);

        return (object) [
            'carQuotePolicyBooked' => $carQuotePolicyBooked,
            'carQuotePolicyIssued' => $carQuotePolicyIssued,
            'validTransaction' => $validTransaction,
            'notBookedTransaction' => $notBookedTransaction,
            'epECBTransaction' => $epECBTransaction,
            'notAllowedEpTransaction' => $notAllowedEpTransaction,
            'notDraftTransaction' => $notDraftTransaction,
        ];
    }

    /**
     * Set up test data for API and observer tests (get-ep-workflow-data, CarQuoteObserver).
     * Creates insurance provider, car plan, car quote (PolicyIssued), and one MDX embedded transaction.
     *
     * @return array{
     *     carQuote: CarQuote,
     *     quoteUuid: string,
     *     quoteId: int,
     *     quoteCode: string,
     *     insuranceProvider: InsuranceProvider,
     *     carPlan: CarPlan,
     *     epMDXTransaction: EmbeddedTransaction
     * }
     */
    public static function setupTestData(
        string $quoteCode = 'CAR-RETARGET001',
        string $quoteUuid = 'RETARGET001'
    ): array {
        // Create InsuranceProvider using model-based creation
        $providerAttributes = InsuranceProvider::factory()->definition();
        $insuranceProvider = InsuranceProvider::forceCreate($providerAttributes);

        // Create CarPlan linked to the InsuranceProvider using model-based creation
        $planFactory = CarPlan::factory();
        $planAttributes = $planFactory->definition();
        // Replace factory relationship with actual provider ID
        $planAttributes['provider_id'] = $insuranceProvider->id;
        $carPlan = CarPlan::forceCreate($planAttributes);

        // Create CarQuote using factory
        $quoteAttributes = CarQuote::factory()->definition();
        $quoteAttributes['uuid'] = $quoteUuid;
        $quoteAttributes['code'] = $quoteCode;
        $quoteAttributes['insurance_provider_id'] = $insuranceProvider->id;
        $quoteAttributes['plan_id'] = $carPlan->id;
        $quoteAttributes['quote_status_id'] = QuoteStatusEnum::PolicyIssued;
        $carQuote = CarQuote::forceCreate($quoteAttributes);

        $epMDX = EmbeddedProduct::factory()
            ->mdx()
            ->forInsuranceProvider($insuranceProvider->id)
            ->create();
        $epMDXOption = EmbeddedProductOption::factory()
            ->forEmbeddedProduct($epMDX->id)
            ->create();
        $epMDXTransaction = EmbeddedTransaction::factory()
            ->forCarQuote($carQuote)
            ->forProduct($epMDXOption->id)
            ->create(['code' => "MDX-{$carQuote->code}"]);

        return [
            'insuranceProvider' => $insuranceProvider,
            'carPlan' => $carPlan,
            'carQuote' => $carQuote,
            'quoteUuid' => $quoteUuid,
            'quoteId' => $carQuote->id,
            'quoteCode' => $quoteCode,
            'epMDXTransaction' => $epMDXTransaction,
            'epMDX' => $epMDX,
        ];
    }
}
