<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\InsuranceProvider;

/**
 * Universal test data helper for Retargeting EP Reminder tests.
 *
 * Create only the data each test/group needs; share data within a describe group via
 * group-level beforeEach (create once when null, assign to $this->data). Use setupTestData()
 * for PolicyIssued quote + ET (validation/404); setupTestDataForApiSuccess() for 200 flow;
 * setupRepositoryTestData() for repository tests.
 */
class RetargetingEpReminderTestDataHelper
{
    /**
     * Set up test data for repository tests (fetchFilterEpTransactions, fetchFindEmbededTransactionWithDetails).
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

        $epMDX = self::createMinimalProduct($insuranceProvider->id, EmbeddedProductEnum::MDX);
        $epECB = self::createMinimalProduct($insuranceProvider->id, EmbeddedProductEnum::ECB);
        $epCOU = self::createMinimalProduct($insuranceProvider->id, EmbeddedProductEnum::COURIER);
        $optionMDX = self::createMinimalProductOption($epMDX->id);
        $optionECB = self::createMinimalProductOption($epECB->id);
        $optionCOU = self::createMinimalProductOption($epCOU->id);

        $validTransaction = self::createMinimalEmbeddedTransaction($carQuotePolicyBooked->id, $optionMDX->id, 'ET-VALID');
        $notBookedTransaction = self::createMinimalEmbeddedTransaction($carQuotePolicyIssued->id, $optionMDX->id, 'ET-NOT-BOOKED');
        $epECBTransaction = self::createMinimalEmbeddedTransaction($carQuotePolicyBooked->id, $optionECB->id, 'ET-ECB-DRAFT');
        $notAllowedEpTransaction = self::createMinimalEmbeddedTransaction($carQuotePolicyBooked->id, $optionCOU->id, 'ET-NOT-ALLOWED-EP');
        $notDraftTransaction = EmbeddedTransaction::forceCreate([
            'code' => 'ET-AUTHORISED',
            'quote_request_id' => $carQuotePolicyBooked->id,
            'quote_request_type' => CarQuote::class,
            'quote_type_id' => QuoteTypeId::Car,
            'product_id' => $optionMDX->id,
            'is_selected' => false,
            'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        $epMDX = self::createMinimalMdxProduct($insuranceProvider->id);
        $epMDXOption = self::createMinimalProductOption($epMDX->id);
        $epMDXTransaction = self::createMinimalEmbeddedTransaction($carQuote->id, $epMDXOption->id, "MDX-{$carQuote->code}");

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

    /**
     * Set up test data for API success case (GET get-ep-workflow-data returns 200).
     * Creates PolicyBooked quote with car make/model, plan, and MDX embedded transaction
     * so the real service/repository flow returns retargeting reminder data.
     *
     * @return array{
     *     carQuote: CarQuote,
     *     quoteUuid: string,
     *     quoteId: int,
     *     quoteCode: string,
     *     insuranceProvider: InsuranceProvider,
     *     carPlan: CarPlan,
     *     epMDXTransaction: EmbeddedTransaction,
     *     epMDX: EmbeddedProduct,
     *     carMake: CarMake,
     *     carModel: CarModel
     * }
     */
    public static function setupTestDataForApiSuccess(
        string $quoteCode = 'CAR-RETARGET002',
        string $quoteUuid = 'RETARGET002'
    ): array {
        $insuranceProvider = InsuranceProvider::forceCreate(InsuranceProvider::factory()->definition());
        $carPlan = CarPlan::forceCreate(array_merge(
            CarPlan::factory()->definition(),
            ['provider_id' => $insuranceProvider->id]
        ));

        $carMake = CarMake::forceCreate(['text' => 'Toyota', 'code' => 'TOY', 'created_at' => now(), 'updated_at' => now()]);
        $carModel = CarModel::forceCreate(['text' => 'Camry', 'created_at' => now(), 'updated_at' => now()]);

        $quoteAttributes = CarQuote::factory()->definition();
        $quoteAttributes['uuid'] = $quoteUuid;
        $quoteAttributes['code'] = $quoteCode;
        $quoteAttributes['insurance_provider_id'] = $insuranceProvider->id;
        $quoteAttributes['plan_id'] = $carPlan->id;
        $quoteAttributes['quote_status_id'] = QuoteStatusEnum::PolicyBooked;
        $quoteAttributes['policy_booking_date'] = now()->toDateString();
        $quoteAttributes['car_make_id'] = $carMake->id;
        $quoteAttributes['car_model_id'] = $carModel->id;
        $carQuote = CarQuote::forceCreate($quoteAttributes);

        $epMDX = self::createMinimalMdxProduct($insuranceProvider->id);
        $epMDXOption = self::createMinimalProductOption($epMDX->id);
        $epMDXTransaction = self::createMinimalEmbeddedTransaction($carQuote->id, $epMDXOption->id, "MDX-{$carQuote->code}");

        return [
            'insuranceProvider' => $insuranceProvider,
            'carPlan' => $carPlan,
            'carQuote' => $carQuote,
            'quoteUuid' => $quoteUuid,
            'quoteId' => $carQuote->id,
            'quoteCode' => $quoteCode,
            'epMDXTransaction' => $epMDXTransaction,
            'epMDX' => $epMDX,
            'carMake' => $carMake,
            'carModel' => $carModel,
        ];
    }

    /**
     * Create minimal embedded product by short code (no factory create() overhead).
     */
    private static function createMinimalProduct(int $insuranceProviderId, string $shortCode): EmbeddedProduct
    {
        return EmbeddedProduct::forceCreate([
            'insurance_provider_id' => $insuranceProviderId,
            'short_code' => $shortCode,
            'product_name' => $shortCode,
            'display_name' => $shortCode,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create minimal MDX embedded product (no factory create() overhead).
     */
    private static function createMinimalMdxProduct(int $insuranceProviderId): EmbeddedProduct
    {
        return self::createMinimalProduct($insuranceProviderId, EmbeddedProductEnum::MDX);
    }

    /**
     * Create minimal embedded product option (no factory create() overhead).
     */
    private static function createMinimalProductOption(int $embeddedProductId): EmbeddedProductOption
    {
        return EmbeddedProductOption::forceCreate([
            'embedded_product_id' => $embeddedProductId,
            'price' => 0,
            'variant' => 'default',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create minimal embedded transaction (no factory create() overhead).
     */
    private static function createMinimalEmbeddedTransaction(int $quoteId, int $productId, string $code): EmbeddedTransaction
    {
        return EmbeddedTransaction::forceCreate([
            'code' => $code,
            'quote_request_id' => $quoteId,
            'quote_request_type' => CarQuote::class,
            'quote_type_id' => QuoteTypeId::Car,
            'product_id' => $productId,
            'is_selected' => false,
            'payment_status_id' => PaymentStatusEnum::DRAFT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
