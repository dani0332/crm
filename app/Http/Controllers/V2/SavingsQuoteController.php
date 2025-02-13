<?php

namespace App\Http\Controllers\V2;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Events\LeadsCount;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsQuoteRequest;
use App\Models\Nationality;
use App\Repositories\LostReasonRepository;
use App\Services\CentralService;
use App\Services\Quotes\SavingsQuoteService;

class SavingsQuoteController extends Controller
{
    public function __construct(
        public SavingsQuoteService $savingsQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_LIST, ['only' => ['index']]);
    }

    public function index()
    {
        $advisors = $this->savingsQuoteService->getAdvisors();
        $quoteStatuses = $this->savingsQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $renewalBatches = $this->savingsQuoteService->getRenewalBatches();
        $authorizedDays = $this->savingsQuoteService->getPaymentAuthorizedDays();

        $query = $this->savingsQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->savingsQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->savingsQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        return inertia('SavingsQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'investmentFrequencies' => InvestmentFrequencyEnum::withLabels(),
        ]);
    }

    public function create()
    {
        $data = $this->savingsQuoteService->getFormOptions();

        return inertia('SavingsQuote/Form', $data);
    }

    public function store(SavingsQuoteRequest $request)
    {
        $response = $this->savingsQuoteService->create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        LeadsCount::dispatch($this->savingsQuoteService->getData(forExport: true, getTotalCount: true));

        return redirect(route('savings-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $data = $this->savingsQuoteService->getFormOptions();
        $quote = $this->savingsQuoteService->getOne($uuid);

        return inertia('SavingsQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    public function update(SavingsQuoteRequest $request, $uuid)
    {
        $this->savingsQuoteService->update($uuid, $request->validated());

        return redirect(route('savings-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        $data = $this->savingsQuoteService->getShowData($uuid);

        return inertia('SavingsQuote/Show', $data);

        // (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::PET->value);
        // /* End - Temporarily adding for correcting historic data */

        // $quote = PetQuoteRepository::getBy('uuid', $uuid);
        // if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
        //     $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
        //         return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        //     })->values();
        // }
        // $nationalities = Nationality::where('is_active', 1)->select('id', 'text')->get();
        // $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::PET->value);
        // $sendUpdateOptions = [];
        // $sendUpdateLogs = [];
        // $sendUpdateEnum = (object) [];
        // $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        // if ($hasPolicyIssuedStatus) {
        //     $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::PET->id());
        //     $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
        //     $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        // }

        // $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        // $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::PET->id(), $quoteStatuses);

        // $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;

        // $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
        // $amlStatusName = AMLStatusCode::getName($quote->aml_status);

        // return inertia('PetQuote/Show', [
        //     'cdnPath' => $cdnPath,
        //     'vatPercentage' => $vatPercentage,
        //     'bookPolicyDetails' => $bookPolicyDetails,
        //     'sendUpdateOptions' => $sendUpdateOptions,
        //     'sendUpdateLogs' => $sendUpdateLogs,
        //     'sendUpdateEnum' => $sendUpdateEnum,
        //     'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
        // ]);
    }
}
