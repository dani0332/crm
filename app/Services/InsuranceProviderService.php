<?php

namespace App\Services;

use App\Enums\InsurerProviderEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\BrokerCommission;
use App\Models\InsuranceProvider;
use App\Models\QuoteTag;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class InsuranceProviderService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = DB::table('insurance_provider as ip')
            ->select(
                'ip.id',
                'ip.code',
                'ip.text',
                'ip.updated_at',
                'ip.created_at',
                'ip.text_ar',
                'ip.is_active',
                'ip.lower_limit',
                'ip.upper_limit',
                'ip.sort_order'
            );
    }

    public function getEntity($id)
    {
        return $this->query->where('ip.id', $id)->first();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('ip.created_at', [$dateFrom, $dateTo]);
            }
            if (in_array('text_ar', $searchProperties) && isset($request->text_ar) && $request->text_ar != '') {
                $this->query->Where('text_ar', 'like', '%'.$request->text_ar.'%');
            }
            if (in_array('text', $searchProperties) && isset($request->text) && $request->text != '') {
                $this->query->Where('text', 'like', '%'.$request->text.'%');
            }
            if (in_array('code', $searchProperties) && isset($request->code) && $request->code != '') {
                $this->query->Where('code', 'like', '%'.$request->code.'%');
            }
            foreach ($searchProperties as $item) {
                if (! empty($request[$item]) && $item != 'created_at') {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    }
                }
            }
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 0) {
                $column = 'ip.id';
            }
            if ($column == 1) {
                $column = 'ip.code';
            }
            if ($column == 2) {
                $column = 'ip.text';
            }
            if ($column == 3) {
                $column = 'ip.text_ar';
            }
            if ($column == 4) {
                $column = 'ip.created_at';
            }
            if ($column == 5) {
                $column = 'ip.updated_at';
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('ip.created_at', 'DESC');
        }
    }

    public function saveInsuranceProvider(Request $request)
    {
        $data = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'lower_limit' => $request->lower_limit,
            'upper_limit' => $request->upper_limit,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ];

        return InsuranceProvider::create($data);
    }

    public function updateInsuranceProvider(Request $request, $id)
    {
        $updateArray = [
            'text' => $request->text,
            'text_ar' => $request->text_ar,
            'code' => $request->code,
            'lower_limit' => $request->lower_limit,
            'upper_limit' => $request->upper_limit,
            'is_active' => $request->is_active == 'on' ? 1 : 0,
        ];
        InsuranceProvider::where('id', $id)->update($updateArray);

        return true;
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'input|title|required',
            'text' => 'input|text|title|required',
            'text_ar' => 'input|text|title',
            'created_at' => 'input|title|date|range',
            'updated_at' => 'input|title|date',
            'lower_limit' => 'input|number|title|required|min:0',
            'upper_limit' => 'input|number|title|required|min:0',
            'is_active' => 'input|checkbox',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'code':
                $title = 'Code';
                break;
            case 'lower_limit':
                $title = 'Lower Limit';
                break;
            case 'upper_limit':
                $title = 'Upper Limit';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'text':
                $title = 'Insurance Provider Name';
                break;
            case 'text_ar':
                $title = 'Insurance Provider Name (Arabic)';
                break;
            case 'is_active':
                $title = 'Is Active';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'created_at,updated_at',
            'list' => '',
            'update' => 'id,created_at,updated_at',
            'show' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'text', 'text_ar', 'created_at'];
    }

    /**
     * get insurance provider by code
     *
     * @return mixed
     */
    public function getProviderByCode($code)
    {
        return InsuranceProvider::where('code', $code)->first();
    }

    /**
     * Get the status of credit card and split frequency for a given quote type and insurance provider.
     *
     * @param  string  $quoteType
     * @param  int  $insuranceProviderId
     * @param  int  $businessTypeOfInsuranceId
     * @param  object|null  $quote
     * @return array
     */
    public function getPaymentConfiguration($quoteType, $insuranceProviderId, $businessTypeOfInsuranceId, $quote = null)
    {
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        $brokerCommissionQuery = BrokerCommission::where('insurance_provider_id', $insuranceProviderId)->active();

        // Check if the quote type is one of the specified types
        if (in_array($quoteTypeId, [QuoteTypes::getId(QuoteTypes::BUSINESS), QuoteTypes::getId(QuoteTypes::CORPLINE), QuoteTypes::getId(QuoteTypes::GROUP_MEDICAL)])) {
            $quoteTypeId = QuoteTypeId::Business;
            $brokerCommissionQuery->where('business_type_of_insurance_id', $businessTypeOfInsuranceId);
        }
        $brokerCommissionQuery->where('quote_type_id', $quoteTypeId);
        $brokerCommission = $brokerCommissionQuery->first();

        // Get the insurance provider details
        $insuranceProvider = InsuranceProvider::find($insuranceProviderId);

        $isGIGInsuranceProvider = false;
        $isMultiplePaymentsEnabled = false;

        if ($insuranceProvider) {
            $isGIGInsuranceProvider = $insuranceProvider->code === InsurerProviderEnum::GIG_INSURANCE;
            $isMultiplePaymentsEnabled = $insuranceProvider->multiple_payments;
        }

        $isTapCaptureProcessStart = false;
        if ($quote) {
            $isTapCaptureProcessStart = QuoteTag::where([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START,
                'value' => 1,
            ])->exists();
        }

        return [
            $brokerCommission ? true : false,
            $brokerCommission,
            $isGIGInsuranceProvider,
            $isTapCaptureProcessStart,
            $isMultiplePaymentsEnabled,
        ];
    }
}
