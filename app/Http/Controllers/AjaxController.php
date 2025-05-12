<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\Kyc;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\KycEntityDocRequest;
use App\Http\Requests\KycIndividualDocRequest;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\Customer;
use App\Models\CustomerDetail;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\PersonalQuote;
use App\Models\QuoteMemberDetail;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Repositories\LookupRepository;
use App\Services\ActivitiesService;
use App\Services\CRUDService;
use App\Services\QuoteDocumentService;
use App\Traits\CentralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class AjaxController extends Controller
{
    use CentralTrait;

    protected $quoteDocumentService;

    public function __construct(QuoteDocumentService $quoteDocumentService)
    {
        $this->quoteDocumentService = $quoteDocumentService;
    }

    public function carModelBasedOnCarMake(Request $request)
    {
        $carmodel = CarModel::activeWithCode($request->make_code)
            ->select('id', 'text', 'code')->orderBy('text')->get();

        return response()->json($carmodel);
    }

    public function carModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = CarMake::activeWithId($request->id)->value('code');
        if (! $carMakeCode) {
            $carMakeCode = $request->id;
        }
        $carmodel = CarModel::activeWithCode($carMakeCode)
            ->select('id', 'text', 'code', 'car_make_code', 'is_commercial')->orderBy('text')->get();

        return response()->json($carmodel);
    }

    public function getCarMake()
    {
        $carMakes = CarMake::active()->select('id', 'text', 'code')->orderBy('text')->get();

        return response()->json($carMakes);
    }

    public function getCarModelDetails(Request $request)
    {
        $carModelDetail = CarModelDetail::active()
            ->select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id', 'text', 'id', 'is_default')
            ->where('car_model_id', $request->car_model_id)
            ->get();
        if (! $carModelDetail) {
            $carModelDetail = CarModel::active()
                ->select('cylinder', 'seat_capacity', 'vehicle_type_id')
                ->whereId($request->car_model_id)
                ->get();
        }

        return response()->json($carModelDetail);
    }

    public function getCarModelTrimValues(Request $request)
    {
        $carModelDetail = CarModelDetail::active()
            ->select('cylinder', 'seating_capacity as seat_capacity', 'vehicle_type_id')
            ->whereId($request->id)
            ->first();

        return response()->json($carModelDetail);
    }

    public function updatePaymentStatus(Request $request)
    {
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return response()->json(['success' => false]);
        }
        $payment = Payment::where('code', $request->code)->first();
        if (! $payment) {
            return response()->json(['success' => false]);
        }

        $payment->payment_status_id = PaymentStatusEnum::PAID;
        $payment->captured_at = now();
        $payment->save();
        $paymentLog = new PaymentStatusLog([
            'previous_payment_status_id' => $payment->paymentStatusLogs->last()->current_payment_status_id,
            'current_payment_status_id' => PaymentStatusEnum::PAID,
            'payment_code' => $request->code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentLog->save();
        $quoteModel->quote_status_id = QuoteStatusEnum::TransactionApproved;
        $quoteModel->save();

        app(CRUDService::class)->calculateScore($quoteModel, $request->modelType);

        return response()->json(['success' => true]);
    }

    public function generatePaymentLink(Request $request)
    {
        $payment = Payment::where('code', '=', $request->paymentCode)->first();
        if (! $payment) {
            return response()->json(['success' => false]);
        }
        if ($payment->payment_link != null && now() < Carbon::parse($payment->payment_link_created_at)->addDays(3)) {
            return response()->json(['success' => true, 'payment_link' => $payment->payment_link]);
        } else {
            $quoteModel = $this->getQuoteObject($request->modelType, $request->quoteId);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));

            $description = (get_class($quoteModel) == PersonalQuote::class) ? ($payment->personalPlan->text ?? '') : ($quoteModel->plan->text ?? '');

            $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');

            $paymentLink = $payment->payment_methods_code == PaymentMethodsEnum::InsureNowPayLater ? $paymentLink.'tabby' : $paymentLink.'checkout';

            $paymentParams = [
                'code' => $payment->code,
                'quoteTypeId' => $quoteTypeId,
            ];
            $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

            $invoiceRequestData = [
                'firstName' => $quoteModel->first_name,
                'lastName' => $quoteModel->last_name,
                'email' => $quoteModel->email,
                'emailSubject' => 'Payment Request',
                'items' => [
                    [
                        'description' => $description,
                        'totalPrice' => [
                            'currencyCode' => 'AED',
                            'value' => ceil($payment->captured_amount * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'total' => [
                    'currencyCode' => 'AED',
                    'value' => ceil($payment->captured_amount * 100),
                ],
                'merchantOrderReference' => strtoupper($payment->code),
            ];

            info('Request object for '.$quoteModel->uuid.' is '.json_encode($invoiceRequestData));

            return response()->json(['success' => true, 'payment_link' => $paymentLinkURL]);
        }
    }

    public function commercialCarModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = $request->get('make_code');

        if ($carMakeCode) {
            $carModel = CarModel::where('car_make_code', $carMakeCode)
                ->select('id', 'text', 'code')
                ->where('is_commercial', true)
                ->where('is_active', true)
                ->orderBy('text')
                ->get();

            return response()->json($carModel);
        } else {
            return response()->json([]);
        }
    }

    public function uploadKycIndividualDocument($quoteType, KycIndividualDocRequest $request)
    {
        try {
            $quote = $this->getQuoteObjectBy($quoteType, $request->quote_uuid, 'uuid');

            $data = $request->validated();
            $data['nationality_text'] = Nationality::where('id', $data['nationality_id'])->value('text');
            $data['country_name'] = Nationality::where('id', $data['country_of_residence'])->value('country_name');
            $data['birth_place'] = Nationality::where('id', $data['place_of_birth'])->value('country_name');
            $data['resident_status_text'] = LookupRepository::where('code', $data['resident_status'])->where('key', LookupsEnum::RESIDENT_STATUS)->value('text');
            $data['id_type_text'] = LookupRepository::where('code', $data['id_type'])->where('key', LookupsEnum::DOCUMENT_ID_TYPE)->value('text');
            $data['mode_of_contact_text'] = LookupRepository::where('code', $data['mode_of_contact'])->where('key', LookupsEnum::MODE_OF_CONTACT)->value('text');
            $data['mode_of_delivery_text'] = LookupRepository::where('code', $data['mode_of_delivery'])->where('key', LookupsEnum::MODE_OF_DELIVERY)->value('text');
            $data['employment_sector_text'] = LookupRepository::where('code', $data['employment_sector'])->where('key', LookupsEnum::EMPLOYMENT_SECTOR)->value('text');
            $data['company_position_text'] = LookupRepository::where('code', $data['company_position'])->where('key', LookupsEnum::COMPANY_POSITION)->value('text');
            $data['professional_title_text'] = LookupRepository::where('code', $data['professional_title'])->where('key', LookupsEnum::PROFESSIONAL_TITLE)->value('text');
            $data['premium'] = $quote->premium;
            $data['payment_method'] = isset($quote->payments[0]) ? $quote->payments[0]->paymentMethod->name : '';
            $data['product_type'] = ucfirst($quoteType).' Insurance';
            $data['document_type_code'] = DocumentTypeCode::KYCDOC;

            $pdf = PDF::loadView('pdf.kyc_individual_document', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();

            $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true, false);

            if ($document) {
                $customerDetail = CustomerDetail::updateOrCreate(
                    ['customer_id' => $data['customer_id']],
                    [
                        'customer_id' => $data['customer_id'],
                        'country_of_residence' => $data['country_of_residence'],
                        'place_of_birth' => $data['place_of_birth'],
                        'residential_status' => $data['resident_status'],
                        'residential_address' => $data['residential_address'],
                        'customer_tenure' => $data['customer_tenure'],
                        'id_type' => $data['id_type'],
                        'id_number' => $data['id_number'],
                        'id_issuance_date' => $data['id_issue_date'],
                        'id_expiry_date' => $data['id_expiry_date'],
                        'source_of_income' => $data['income_source'],
                        'employer_company_name' => $data['company_name'],
                        'job_title' => $data['professional_title'] ?? null,
                        'employment_sector' => $data['employment_sector'] ?? null,
                        'trade_license_no' => $data['trade_license'] ?? null,
                        'position_in_company' => $data['company_position'] ?? null,
                        'mode_of_contact' => $data['mode_of_contact'] ?? null,
                        'mode_of_delivery' => $data['mode_of_delivery'] ?? null,
                        'pep' => $data['pep'] ?? null,
                        'financial_sanctions' => $data['financial_sanctions'] ?? null,
                        'dual_nationality' => $data['dual_nationality'] ?? null,
                        'transaction_pattern' => $data['transaction_pattern'] ?? null,
                        'premium_tenure' => $data['premium_tenure'] ?? null,
                        'in_sanction_list' => $data['in_sanction_list'] ?? null,
                        'deal_sanction_list' => $data['deal_sanction_list'] ?? null,
                        'is_operation_high_risk' => $data['is_operation_high_risk'] ?? null,
                        'is_partner' => $data['is_partner'] ?? null,

                    ]);
                $customerDetail->refresh();

                $quote->first_name = $data['first_name'];
                $quote->last_name = $data['last_name'];
                $quote->dob = $data['dob'];
                $quote->nationality_id = $data['nationality_id'];
                $quote->kyc_decision = Kyc::COMPLETE;
                $quote->save();

                $customer = Customer::find($request->customer_id);
                // TODO:: Need to update the insurer details when contact and insured person FR approved
                $customer->insured_first_name = $data['first_name'];
                $customer->insured_last_name = $data['last_name'];

                if ($data['id_type'] == Kyc::DOCUMENT_TYPE_EMIRATES) {
                    $customer->emirates_id_number = $data['id_number'];
                    $customer->emirates_id_expiry_date = $data['id_expiry_date'];
                }

                $customer->update();

                // Reminder:: this patch included because of new structure old structure will be remove soon.
                $normalizedIdNumber = $customerDetail?->id_number;
                if ($customerDetail->id_type === 'emiratesId' && strlen(str_replace('-', '', $normalizedIdNumber)) === 15) {
                    $normalizedIdNumber = str_replace('-', '', $normalizedIdNumber);
                    $normalizedIdNumber = substr($normalizedIdNumber, 0, 3).'-'.
                        substr($normalizedIdNumber, 3, 4).'-'.
                        substr($normalizedIdNumber, 7, 7).'-'.
                        substr($normalizedIdNumber, 14, 1);
                }

                $insured = Insured::where(['id_type' => $customerDetail->id_type, 'id_number' => $normalizedIdNumber, 'customer_type' => CustomerTypeEnum::Individual])->first();
                InsuredKyc::updateOrCreate(['insured_id' => $insured->id], [
                    'country_of_residence' => $customerDetail->country_of_residence,
                    'place_of_birth' => $customerDetail->place_of_birth,
                    'residential_status' => $customerDetail->residential_status,
                    'residential_address' => $customerDetail->residential_address,
                    'id_type' => $customerDetail->id_type,
                    'id_number' => $customerDetail->id_number,
                    'id_issuance_date' => $customerDetail->id_issuance_date,
                    'id_expiry_date' => $customerDetail->id_expiry_date,
                    'source_of_income' => $customerDetail->source_of_income,
                    'employer_company_name' => $customerDetail->employer_company_name,
                    'job_title' => $customerDetail->job_title,
                    'employment_sector' => $customerDetail->employment_sector,
                    'position_in_company' => $customerDetail->position_in_company,
                    'trade_license_no' => $customerDetail->trade_license_no,
                    'pep' => $customerDetail->pep,
                    'financial_sanctions' => $customerDetail->financial_sanctions,
                    'dual_nationality' => $customerDetail->dual_nationality,
                    'customer_tenure' => $customerDetail->customer_tenure,
                    'transaction_pattern' => $customerDetail->transaction_pattern,
                    'premium_tenure' => $customerDetail->premium_tenure,
                    'mode_of_contact' => $customerDetail->mode_of_contact,
                    'mode_of_delivery' => $customerDetail->mode_of_delivery,
                    'risk_score' => $customerDetail->risk_score,
                    'in_sanction_list' => $customerDetail->in_sanction_list,
                    'deal_sanction_list' => $customerDetail->deal_sanction_list,
                    'is_operation_high_risk' => $customerDetail->is_operation_high_risk,
                    'is_partner' => $customerDetail->is_partner,
                ]);

                return response()->json(['success' => true]);
            }
        } catch (\Exception $ex) {
            info("KYC Individual $request->quote_uuid - ERROR:".$ex->getMessage());
        }

        return response()->json(['error' => false]);
    }
    public function updateRisk($quoteType, Request $request)
    {
        $request->validate([
            'quote_uuid' => 'required',
        ]);
        $quote = $this->getQuoteObjectBy($quoteType, $request->quote_uuid, 'uuid');
        $detail = $this->getQuoteDetailObject($quoteType, $quote->id);
        $detail->risk_score_override = $request->risk_override;
        $detail->risk_score_override_date = Carbon::now();
        $detail->risk_score_override_by = auth()->user()->id;
        $detail->save();

        app(CRUDService::class)->calculateScore($quote, $quoteType);
    }

    public function quoteDetail($quoteType, $id)
    {
        if ($quoteType && $id) {
            $quote = $this->getQuoteObjectBy($quoteType, $id, 'uuid');

            $detail = $this->getQuoteDetailObject($quoteType, $quote->id);

            return $detail;
        } else {
            return response()->json(['success' => false]);
        }
    }

    public function uploadKycEntityDocument($quoteType, KycEntityDocRequest $request)
    {
        try {
            $quote = $this->getQuoteObjectBy($quoteType, $request->quote_uuid, 'uuid');
            if (! isset($quote->quoteRequestEntityMapping)) {
                return response()->json(['message' => 'Trade License not found.']);
            }
            $data = $request->validated();
            $data['corporation_country'] = Nationality::where('id', $data['country_of_corporation'])->value('country_name');
            $data['manager_country'] = Nationality::where('id', $data['manager_nationality'])->value('text');
            $data['industry_type_text'] = LookupRepository::where('code', $data['industry_type'])->where('key', LookupsEnum::COMPANY_TYPE)->value('text');
            $data['legal_structure_text'] = LookupRepository::where('code', $data['legal_structure'])->where('key', LookupsEnum::LEGAL_STRUCTURE)->value('text');
            $data['issuance_place_text'] = LookupRepository::where('code', $data['place_of_issue'])->where('key', LookupsEnum::ISSUANCE_PLACE)->value('text');
            $data['document_type_text'] = LookupRepository::where('code', $data['id_document_type'])->where('key', LookupsEnum::ENTITY_DOCUMENT_TYPE)->value('text');
            $data['issuing_authority_text'] = LookupRepository::where('code', $data['issuing_authority'])->where('key', LookupsEnum::ISSUING_AUTHORITY)->value('text');
            $data['manager_position_text'] = LookupRepository::where('code', $data['manager_position'])->where('key', LookupsEnum::UBO_RELATION)->value('text');
            $data['product_type'] = QuoteType::where('code', ucfirst($quoteType))->value('text');
            $data['document_type_code'] = DocumentTypeCode::KYCDOC;
            $pdf = PDF::loadView('pdf.kyc_entity_document', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();

            $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true, false);
            $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($quoteType));
            if ($document) {
                $entityDetails = Entity::where('id', $quote->quoteRequestEntityMapping->entity->id)->first();
                $entityDetails->update([
                    'mobile_no' => $data['mobile_number'],
                    'email' => $data['email'],
                    'website' => $data['website'],
                    'legal_structure' => $data['legal_structure'],
                    'industry_type_code' => $data['industry_type'],
                    'country_of_corporation' => $data['country_of_corporation'],
                    'registered_address' => $data['registered_address'],
                    'communication_address' => $data['communication_address'],
                    'id_type' => $data['id_document_type'],
                    'id_number' => $data['id_number'],
                    'id_issuance_date' => $data['id_issue_date'],
                    'id_expiry_date' => $data['id_expiry_date'],
                    'issuance_place' => $data['place_of_issue'],
                    'id_issuance_authority' => $data['issuing_authority'],
                    'pep' => $data['pep'] ?? null,
                    'financial_sanctions' => $data['financial_sanctions'] ?? null,
                    'dual_nationality' => $data['dual_nationality'] ?? null,
                    'in_sanction_list' => $data['in_sanction_list'] ?? null,
                    'is_sanction_match' => $data['is_sanction_match'] ?? null,
                    'in_fatf' => $data['in_fatf'] ?? null,
                    'deal_sanction_list' => $data['deal_sanction_list'] ?? null,
                    'is_operation_high_risk' => $data['is_operation_high_risk'] ?? null,
                    'customer_tenure' => $data['customer_tenure'] ?? null,
                    'transaction_pattern' => $data['transaction_pattern'] ?? null,
                    'transaction_activities' => $data['transaction_activities'] ?? null,
                    'mode_of_contact' => $data['mode_of_contact'] ?? null,
                    'mode_of_delivery' => $data['mode_of_delivery'] ?? null,
                    'transaction_volume' => $data['transaction_volume'] ?? null,
                    'is_owner_high_risk' => $data['is_owner_high_risk'] ?? null,
                ]);
                $entityDetails->refresh();

                QuoteMemberDetail::updateOrCreate([
                    'code' => $quote->quoteRequestEntityMapping->entity->code,
                    'customer_entity_id' => $quote->quoteRequestEntityMapping->entity->id,
                ], [
                    'code' => $quote->quoteRequestEntityMapping->entity->code,
                    'customer_type' => CustomerTypeEnum::Entity,
                    'customer_entity_id' => $quote->quoteRequestEntityMapping->entity->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quote->id,
                    'first_name' => $data['manager_name'],
                    'dob' => $data['manager_dob'],
                    'nationality_id' => $data['manager_nationality'],
                    'relation_code' => $data['manager_position'],
                ]);

                $quote->first_name = $data['first_name'];
                $quote->last_name = $data['last_name'];
                $quote->kyc_decision = Kyc::COMPLETE;
                $quote->save();

                // Reminder:: this patch included because of new structure, Entity and Entity quote request mapping will be remove soon.
                $insuredDetails = Insured::where(['customer_type' => CustomerTypeEnum::Entity, 'trade_license_no' => $entityDetails->trade_license_no])->first();
                // Reminder this will remove when AML refactor is done.
                $insuredDetails->update([
                    'industry_type_code' => $entityDetails->industry_type_code ?? null,
                ]);
                $insuredDetails->refresh();
                InsuredKyc::updateOrCreate(['insured_id' => $insuredDetails->id], [
                    'mobile_no' => $entityDetails->mobile_no ?? null,
                    'email' => $entityDetails->email ?? null,
                    'website' => $entityDetails->website ?? null,
                    'legal_structure' => $entityDetails->legal_structure ?? null,
                    'country_of_corporation' => $entityDetails->country_of_corporation ?? null,
                    'registered_address' => $entityDetails->registered_address ?? null,
                    'communication_address' => $entityDetails->communication_address ?? null,
                    'id_type' => $entityDetails->id_type ?? null,
                    'id_number' => $entityDetails->id_number ?? null,
                    'id_issuance_date' => $entityDetails->id_issuance_date ?? null,
                    'id_expiry_date' => $entityDetails->id_expiry_date ?? null,
                    'issuance_place' => $entityDetails->issuance_place ?? null,
                    'id_issuance_authority' => $entityDetails->id_issuance_authority ?? null,
                    'pep' => $entityDetails->pep ?? null,
                    'financial_sanctions' => $entityDetails->financial_sanctions ?? null,
                    'dual_nationality' => $entityDetails->dual_nationality ?? null,
                    'customer_tenure' => $entityDetails->customer_tenure ?? null,
                    'transaction_volume' => $entityDetails->transaction_volume ?? null,
                    'transaction_activities' => $entityDetails->transaction_activities ?? null,
                    'transaction_pattern' => $entityDetails->transaction_pattern ?? null,
                    'mode_of_contact' => $entityDetails->mode_of_contact ?? null,
                    'mode_of_delivery' => $entityDetails->mode_of_delivery ?? null,
                    'in_sanction_list' => $entityDetails->in_sanction_list ?? null,
                    'is_sanction_match' => $entityDetails->is_sanction_match ?? null,
                    'in_fatf' => $entityDetails->in_fatf ?? null,
                    'is_owner_high_risk' => $entityDetails->is_owner_high_risk ?? null,
                    'deal_sanction_list' => $entityDetails->deal_sanction_list ?? null,
                    'is_operation_high_risk' => $entityDetails->is_operation_high_risk ?? null,
                    'is_adverse_media' => $entityDetails->in_adverse_media ?? null,
                    'is_owner_pep' => $entityDetails->is_owner_pep ?? null,
                    'is_controlling_pep' => $entityDetails->is_controlling_pep ?? null,
                ]);

                return response()->json(['success' => true]);
            }
        } catch (\Exception $ex) {
            info("KYC Entity $request->quote_uuid - ERROR:".$ex->getMessage());
        }

        return response()->json(['message' => 'Something went wrong, contact to administrator.']);
    }

    public function bikeModelBasedOnCarMakeId(Request $request)
    {
        $carMakeCode = CarMake::activeWithId($request->id)->value('code');
        if (! $carMakeCode) {
            $carMakeCode = $request->id;
        }
        $carmodel = CarModel::activeWithCode($carMakeCode)
            ->select('id', 'text', 'code', 'car_make_code')
            ->where('quote_type_id', QuoteTypeId::Bike)
            ->orderBy('text')
            ->get();

        return response()->json($carmodel);
    }
    public function getBikeModelDetails(Request $request)
    {
        $bikeModelDetail = CarModelDetail::active()
            ->select('cubic_capacity', 'seating_capacity as seat_capacity')
            ->where('car_model_id', $request->bike_model_id)
            ->get();

        return response()->json($bikeModelDetail);
    }

    public function getBatchNamesByQuoteTypeId(Request $request)
    {
        $renewalBatch = RenewalBatch::orderBy('name')->select(['name as text']);
        if (isset($request->quote_type_id) && $request->quote_type_id == QuoteTypeId::Car) {
            /** Motor Selected */
            $renewalBatch->where('quote_type_id', QuoteTypeId::Car);
        } elseif (isset($request->quote_type_id) && $request->quote_type_id != QuoteTypeId::Car) {
            /** Non-Motor Selected */
            $renewalBatch->where('quote_type_id', '<>', QuoteTypeId::Car)->orWhereNull('quote_type_id');
        }

        return response()->json($renewalBatch->groupBy('name')->get()); // laravel automatically converts to JSON
    }
}
