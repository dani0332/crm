<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\Entity;
use App\Models\QuoteRequestEntityMapping;
use App\Services\Logger\LoggerService;

class CarCQFEntityService
{
    public function getCustomerEntity(CarQuote $newQuote, CarQuote $oldQuote): void
    {
        $entityMapping = QuoteRequestEntityMapping::with('entity')
        ->where('quote_type_id', QuoteTypeId::Car)
        ->where('quote_request_id', $newQuote->id)
        ->first();

        if (isset($oldQuote->registration_type) && $oldQuote->registration_type == CarRegistrationType::COMPANY) {

            if (! $entityMapping) {
                $entity = Entity::create([
                    'company_name' => $oldQuote->first_name.' '.$oldQuote->last_name ?? null,
                ]);
                $entityId = $entity->id;
                $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);
                QuoteRequestEntityMapping::updateOrCreate([
                    'quote_type_id' => QuoteTypeId::Car,
                    'quote_request_id' => $newQuote->id,
                ], ['entity_id' => $entityId]);
            }

        } else {
            if ($entityMapping) {
                $entityMappingCount = $entityMapping->entity->quoteRequestEntityMapping->count();
                $entityRecord = $entityMapping->entity;
                $entityMapping->delete();
                if ($entityMappingCount == 1) {
                    $entityRecord->delete();
                }
            }
        }
    }

    public function storeCarDetails(CarQuote $newQuote, CarQuote $oldQuote): ?CarQuoteRequestDetail
    {
        if ($oldQuote->carQuoteRequestDetail) {
            LoggerService::info(self::class.' - Car Quote Request Detail found for quote');
            return CarQuoteRequestDetail::create([
                'car_quote_request_id' => $newQuote->id,
                'chassis_number' => $oldQuote->carQuoteRequestDetail->chassis_number,
            ]);
        }

        LoggerService::info(self::class.' - Car Quote Request Detail not found for quote');
        return null;
    }
}
