<?php

namespace App\Repositories;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\CustomerUploadRequest;
use App\Imports\CustomersImport;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Models\Entity;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Maatwebsite\Excel\Facades\Excel;

class CustomerRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Customer::class;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        $allQuotes =
        $customerIds =
        $entitiesIds = [];
        $filterValue = request()->get('search_value');
        $filterType = request()->get('search_type');
        $filterColumns = ['email', 'first_name', 'entity_name', 'insured_first_name', 'mobile_no', 'uuid'];

        if (in_array($filterType, $filterColumns) && (! empty($filterType) && ! empty($filterValue))) {

            if ($filterType == 'entity_name') {
                $entitiesIds = Entity::where('company_name', $filterValue)->pluck('id');
                if ($entitiesIds->isEmpty()) {
                    return $allQuotes;
                }
            } else {
                $customerIds = Customer::where($filterType, $filterValue)->pluck('id');
                if ($customerIds->isEmpty()) {
                    return $allQuotes;
                }
            }

            $carQuotes = CarQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Car.'" as quote_type_id'),
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            $homeQuotes = HomeQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Home.'" as quote_type_id'),
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            $healthQuotes = HealthQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Health.'" as quote_type_id'),
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            $lifeQuotes = LifeQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Life.'" as quote_type_id'),
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            $businessQuotes = BusinessQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Business.'" as quote_type_id'),
                    'business_type_of_insurance_id'])
                ->orderBy('created_at', 'desc');

            $travelQuotes = TravelQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date',
                    \DB::raw('"'.QuoteTypeId::Travel.'" as quote_type_id'),
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            $personalQuotes = PersonalQuote::with(['advisor', 'customer'])
                ->when(! empty($customerIds), function ($customer) use ($customerIds) {
                    $customer->whereIn('customer_id', $customerIds);
                })
                ->when(! empty($entitiesIds), function ($entity) use ($entitiesIds) {
                    $entity->whereHas('quoteRequestEntityMapping', function ($healthEntityMapping) use ($entitiesIds) {
                        $healthEntityMapping->whereIn('entity_id', $entitiesIds);
                    });
                })
                ->where('quote_status_id', QuoteStatusEnum::TransactionApproved)
                ->select(['uuid', 'code', 'customer_id', 'policy_number', 'advisor_id', 'policy_start_date', 'policy_expiry_date', 'quote_type_id',
                    \DB::raw("'' as business_type_of_insurance_id"),
                ])
                ->orderBy('created_at', 'desc');

            return $personalQuotes
                ->union($healthQuotes)
                ->union($lifeQuotes)
                ->union($travelQuotes)
                ->union($homeQuotes)
                ->union($businessQuotes)
                ->union($carQuotes)->simplePaginate()->withQueryString();
        }

        return $allQuotes;

    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->with(['nationality'])->where($column, $value)->firstOrFail();
    }

    /**
     * @return bool
     */
    public function fetchStoreAdditionalContact($customerId, $data)
    {
        if ($data['key'] === GenericRequestEnum::EMAIL) {
            $isExistEmail = CustomerAdditionalContact::where('customer_id', $customerId)
                ->where('value', $data['value'])->where('key', 'email')->first();

            if ($isExistEmail) {
                return back()->with('success', 'Email Address already Exist. Please try another.');
            }

            $customer = $this->findOrFail($customerId);
            $customer->additionalContactInfo()->create($data);

            return $customer;
        } elseif ($data['key'] === GenericRequestEnum::MOBILE_NO) {
            $isExistMobile = CustomerAdditionalContact::where('customer_id', $customerId)
                ->where('value', $data['value'])->where('key', 'mobile_no')->first();

            if ($isExistMobile) {
                return back()->with('success', 'Mobile Number already Exist. Please try another.');
            }

            $customer = $this->findOrFail($customerId);
            $customer->additionalContactInfo()->create($data);

            return $customer;
        }

    }

    public function fetchGetAdditionalContacts($customerId, $quoteMobileNo)
    {
        $customer = $this->where('id', $customerId)->first();
        $additionalContacts = CustomerAdditionalContact::where('customer_id', $customerId)->orderBy('created_at', 'desc')->get();

        if (isset($customer) && $quoteMobileNo != $customer->mobile_no) {
            $customerMobileNo = (object) [
                'key' => 'mobile_no',
                'value' => isset($customer->mobile_no) ? $customer->mobile_no : '',
                'created_at' => isset($customer->created_at) ? $customer->created_at : '',
            ];
            $additionalContacts->push($customerMobileNo);
        }

        return $additionalContacts;
    }

    public function fetchCustomerUploadRecordsCreate(CustomerUploadRequest $customerUploadRequest, SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService)
    {
        if ($customerUploadRequest->hasFile('file_name')) {
            return Excel::import(new CustomersImport(
                $customerUploadRequest->myalfred_expiry_date,
                $customerUploadRequest->cdb_id,
                $customerUploadRequest->inviatation_email,
                $sendEmailCustomerService,
                $berlinService
            ), $customerUploadRequest->file('file_name'));
        }

        vAbort('Something went wrong while uploading');
    }

    public function fetchReplicatePreviousAdditionalContacts($old_customer_id, $new_customer_id)
    {
        $customerPreviousContactInfo = CustomerAdditionalContact::where('customer_id', $old_customer_id)->get();
        foreach ($customerPreviousContactInfo as $customerPreInfo) {
            CustomerAdditionalContact::updateOrCreate([
                'customer_id' => $new_customer_id,
                'key' => $customerPreInfo->key,
                'value' => $customerPreInfo->value,
            ]);
        }
    }

    public function fetchUpdateCustomerDetails($customerId, $data)
    {
        info('before updating customer details');

        $customer = Customer::with('nationality')->findOrFail($customerId);
        $customer->update($data->only(['insured_first_name', 'insured_last_name', 'nationality_id', 'dob']));

        $customer->detail()->updateOrCreate(['customer_id' => $customerId], $data->only([
            'place_of_birth',
            'country_of_residence',
            'residential_address',
            'residential_status',
            'id_type',
            'id_issuance_date',
            'mode_of_contact',
            // 'transaction_value',
            'mode_of_delivery',
            'employment_sector',
            'customer_tenure',
        ]));

        $customer->refresh();

        info('customer detail updated');

        return $customer;
    }

}
