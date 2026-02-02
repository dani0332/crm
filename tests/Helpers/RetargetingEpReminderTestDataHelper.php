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
use Tests\Helpers\Payments\PaymentTestDataHelper;

/**
 * Test data helper for Retargeting EP Reminder API feature tests.
 * Creates minimal schema and car quote data required for get-retargeting-ep-reminder
 * and retargeting-ep-reminder-callback endpoints.
 */
class RetargetingEpReminderTestDataHelper
{
    /**
     * Set up test data for RetargetingEpReminderApiTest.
     * Creates schema, insurance provider, car plan, car quote, and embedded products (MDX, ECB).
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
            ->draft()
            ->create(['code' => "MDX-{$carQuote->code}"]);

        return [
            'insuranceProvider' => $insuranceProvider,
            'carPlan' => $carPlan,
            'carQuote' => $carQuote,
            'quoteUuid' => $quoteUuid,
            'quoteId' => $carQuote->id,
            'quoteCode' => $quoteCode,
            'epMDXTransaction' => $epMDXTransaction
        ];
    }
}
