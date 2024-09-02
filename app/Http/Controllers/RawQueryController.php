<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\JetskiQuoteRepository;
use App\Repositories\LifeQuoteRepository;
use App\Repositories\PetQuoteRepository;
use App\Repositories\YachtQuoteRepository;
use App\Services\BusinessQuoteService;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\HealthQuoteService;
use App\Services\HomeQuoteService;
use App\Services\PetQuoteService;
use App\Services\TravelQuoteService;
use Illuminate\Http\Request;

class RawQueryController extends Controller
{
    public function executeQuery(Request $request)
    {
        $entity = [];
        $members = [];
        $payments = [];
        $customerAdditionalContacts = [];
        if (in_array($request->modelType, [QuoteTypes::HEALTH->value, QuoteTypes::HOME->value, QuoteTypes::TRAVEL->value, QuoteTypes::CAR->value, QuoteTypes::BUSINESS->value])) {
            $entity = app(CRUDService::class)->getEntity($request->modelType, $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $serviceClass = $this->getServiceClass($request->modelType);
            $paymentEntityModel = $serviceClass->getEntityPlain($entity->id);
            $payments = $paymentEntityModel->payments;
            $customerAdditionalContacts = app(CustomerService::class)->getAdditionalContacts($entity->customer_id, $entity->mobile_no);
        } elseif ($request->modelType === QuoteTypes::PET->value) {
            $entity = PetQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        } elseif ($request->modelType === QuoteTypes::CYCLE->value) {
            $entity = CycleQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        } elseif ($request->modelType === QuoteTypes::YACHT->value) {
            $entity = YachtQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        } elseif ($request->modelType === QuoteTypes::LIFE->value) {
            $entity = LifeQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        } elseif ($request->modelType === QuoteTypes::BIKE->value) {
            $entity = BikeQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        } elseif ($request->modelType === QuoteTypes::JETSKI->value) {
            $entity = JetskiQuoteRepository::getBy('uuid', $request->code);
            $members = CustomerMembersRepository::getBy($entity->id, $request->modelType);
            $payments = $entity->payments;
            $customerAdditionalContacts = $entity->customer->additionalContactInfo;
        }

        return response()->json(['record' => $entity, 'members' => $members, 'payments' => $payments, 'customerAdditionalContacts' => $customerAdditionalContacts]);

    }

    public function getServiceClass($modelType)
    {
        switch ($modelType) {
            case QuoteTypes::HEALTH->value:
                return app(HealthQuoteService::class);
            case QuoteTypes::CAR->value:
                return app(CarQuoteService::class);
            case QuoteTypes::HOME->value:
                return app(HomeQuoteService::class);
            case QuoteTypes::PET->value:
                return app(PetQuoteService::class);
            case QuoteTypes::TRAVEL->value:
                return app(TravelQuoteService::class);
            case QuoteTypes::BUSINESS->value:
                return app(BusinessQuoteService::class);
            default:
                return null;
        }
    }
}
