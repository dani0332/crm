<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\HealthTeamType;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\HealthPlanType;
use App\Models\Nationality;
use App\Models\User;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\HealthRevivalQuoteRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\QuoteNoteRepository;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\CustomerService;
use App\Services\DropdownSourceService;
use App\Services\HealthQuoteService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;

class HealthRevivalQuoteController extends Controller
{
    //

    public function index()
    {

        $formOptions = HealthRevivalQuoteRepository::getFormOptions();
        $quotes = HealthRevivalQuoteRepository::getData();

        return inertia('HealthRevivalQuote/Index', [
            'quotes' => $quotes,
            'formOptions' => $formOptions,
        ]);
    }

    public function show($id)
    {

        $quoteType = quoteTypeCode::Health;

        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId($quoteType);

        $record = app(CRUDService::class)->getEntity($quoteType, $id);

        $ecomDetails = app(HealthQuoteService::class)->getEcomDetails($record);

        $membersDetails = CustomerMembersRepository::getBy($record->id, QuoteTypes::HEALTH->name);

        $memberCategories = app(LookupService::class)->getMemberCategories();
        $formOptions = HealthRevivalQuoteRepository::getFormOptions();
        $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();

        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();

        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', $quoteTypeId);

        $leadStatuses = app(HealthQuoteService::class)->statusesToDisplay($leadStatuses, $record);
        $customerAdditionalContacts = app(CustomerService::class)->getAdditionalContacts($record->customer_id, $record->mobile_no);

        $paymentEntityModel = app(HealthQuoteService::class)->getEntityPlain($record->id);
        $payments = $paymentEntityModel->payments;
        $payments->load(['paymentStatus', 'healthPlan.insuranceProvider', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);

        $documentTypes = app(QuoteDocumentService::class)->getQuoteDocumentsForUpload(QuoteTypeId::Health);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $documentTypes = collect($documentTypes)->groupBy('category');

        $paymentMethods = app(LookupService::class)->getPaymentMethods();
        $healthPlanTypes = HealthPlanType::where('is_active', 1)->select('id', 'text')->get();
        $quoteDocuments = app(QuoteDocumentService::class)->getQuoteDocuments($quoteType, $record->id);
        $quoteDocuments = $quoteDocuments->map(function ($quoteDocument) {
            $quoteDocument->created_by_name = isset($quoteDocument->createdBy->name) ? $quoteDocument->createdBy->name : null;

            return $quoteDocument;
        });

        $activitiesData = app(ActivitiesService::class)->getActivityByLeadId($record->id, strtolower($quoteType));
        $activities = [];
        foreach ($activitiesData as $activity) {
            $updatedActivity = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => User::where('id', $activity->assignee_id)->first()->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
            ];
            array_push($activities, $updatedActivity);
        }
        $advisors = [];

        if (
            $record->health_team_type == HealthTeamType::EBP ||
            $record->health_team_type == HealthTeamType::RM_NB || $record->health_team_type == HealthTeamType::RM_SPEED
        ) {
            $advisors = app(CRUDService::class)->getEBPAndRMAdvisors();
        } else {
            $advisors = app(CRUDService::class)->getAdvisorsByModelType(strtolower($quoteType));
        }

        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Health);

        if (! empty($insuranceProviders)) {
            $insuranceProviders = $insuranceProviders?->map(function ($paymentMethod) {
                return [
                    'value' => $paymentMethod->id,
                    'label' => $paymentMethod->text,
                ];
            })->sortBy('label')->values();
        }
        $allowedDuplicateLOB = app(CRUDService::class)->getAllowedDuplicateLOB($quoteType, $record->code);

        $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();

        $quoteNotes = QuoteNoteRepository::getBy($record->id, QuoteTypes::HEALTH->name);

        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        return inertia('HealthRevivalQuote/Show', [
            'quote' => $record,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'genderOptions' => app(CRUDService::class)->getGenderOptions(),
            'leadStatuses' => array_values($leadStatuses->toArray()),
            'membersDetail' => $membersDetails,
            'memberCategories' => $memberCategories,
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'quoteType' => QuoteTypes::HEALTH,
            'customerAdditionalContactsData' => $customerAdditionalContacts,
            'ecomDetails' => $ecomDetails,
            'payments' => $payments,
            'paymentMethods' => $paymentMethods,
            'documentTypes' => $documentTypes,
            'quoteRequest' => $paymentEntityModel,
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            'storageUrl' => storageUrl(),
            'emirates' => Emirate::where('is_active', 1)->select('id', 'text')->get(),
            'salaryBands' => app(LookupService::class)->getSalaryBands(),
            'genderOptions' => app(CRUDService::class)->getGenderOptions(),
            'quoteDocuments' => array_values($quoteDocuments->toArray()),
            'activities' => $activities,
            'advisors' => $advisors,
            'insuranceProviders' => $insuranceProviders,
            'healthPlanTypes' => $healthPlanTypes,
            'allowedDuplicateLOB' => $allowedDuplicateLOB,
            'noteDocumentType' => $noteDocumentType,
            'quoteNotes' => $quoteNotes,
            'cdnPath ' => $cdnPath,
        ]);
    }

    public function edit($uuid)
    {
        $formOptionsData = HealthRevivalQuoteRepository::getFormOptions();
        $quote = HealthRevivalQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'HealthRevivalQuote/Form',
            [
                'formOptions' => $formOptionsData,
                'quote' => $quote,
            ]
        );
    }

    public function update($uuid)
    {
        $quote = HealthRevivalQuoteRepository::getBy('uuid', $uuid);
        $quote->update(request()->all());

        return redirect()->route('health-revival-quotes-show', $quote->uuid);
    }
}
