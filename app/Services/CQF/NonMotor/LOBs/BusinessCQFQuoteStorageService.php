<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Services\CQF\NonMotor\BaseCQFQuoteStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;

class BusinessCQFQuoteStorageService extends BaseCQFQuoteStorageService
{
    public function __construct(
        BusinessCQFQuoteMappingService $mappingService,
        EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct($mappingService, $embeddedProductRepository);
    }

    protected function getLobName(): string
    {
        return 'business';
    }

    protected function getQuoteTypeId(): int
    {
        return QuoteTypeId::Business;
    }

    protected function copyLobQuoteDetail(PersonalQuote $newQuote, Model $oldQuote): void
    {
        if (! $oldQuote instanceof PersonalQuote) {
            return;
        }
        $this->copyBusinessQuoteDetail($newQuote, $oldQuote);
    }

    protected function copyBusinessQuoteDetail(PersonalQuote $newQuote, PersonalQuote $oldQuote): void
    {
        $oldBusinessQuote = $oldQuote->businessQuote;

        if ($oldBusinessQuote === null) {
            LoggerService::info(self::class.' - No business quote detail found for old quote');

            return;
        }

        $data = $this->copyableAttributesForBusinessQuote($oldBusinessQuote->getAttributes(), $newQuote->uuid, $newQuote->code);
        $data['personal_quote_id'] = $newQuote->id;
        $data = $this->alignCopiedLobRowWithRenewalPersonalQuote($data, $newQuote, $oldBusinessQuote);

        $businessQuote = BusinessQuote::create($data);
        $newQuote->businessQuote()->associate($businessQuote);
        $newQuote->save();

        if ($oldBusinessQuote->businessQuoteRequestDetail) {
            BusinessQuoteRequestDetail::create(['business_quote_request_id' => $businessQuote->id]);
        }

        LoggerService::info(self::class.' - Business quote detail copied for renewal quote');
    }

    /**
     * BusinessQuote uses uuid/code (no personal_quote_id). Different signature from base copyableAttributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function copyableAttributesForBusinessQuote(array $attributes, string $newQuoteUuid, string $newQuoteCode): array
    {
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['uuid'], $attributes['code']);
        $attributes['uuid'] = $newQuoteUuid;
        $attributes['code'] = $newQuoteCode;

        return $attributes;
    }
}
