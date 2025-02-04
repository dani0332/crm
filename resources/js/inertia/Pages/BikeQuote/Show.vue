<script setup>
import AvailablePlans from '@/inertia/Pages/BikeQuote/AvailablePlans.vue';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';

defineProps({
  quote: Object,
  documentTypes: Object,
  quoteStatuses: Object,
  paymentMethods: Object,
  insuranceProviders: Object,
  personalPlans: Object,
  isBetaUser: Boolean,
  storageUrl: String,
  quoteType: String,
  quoteTypeId: Number,
  can: Object,
  activities: Object,
  advisors: Object,
  lostReasons: Object,
  quoteStatusEnum: Object,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  record: Object,
  bikeQuotePlanAddons: Array,
  yearsOfManufacture: Array,
  emailStatuses: Array,
  carPlanTypeEnum: Object,
  carPlanExclusionsCodeEnum: Object,
  carPlanFeaturesCodeEnum: Object,
  carPlanAddonsCodeEnum: Object,
  planURL: String,
  genderOptions: Object,
  isNewPaymentStructure: Boolean,
  paymentTooltipEnum: Object,
  websiteURL: String,
  linkedQuoteDetails: Object,
  vatPercentage: Number,

  permissions: Object,
  enums: Object,
  payments: Array,
  bookPolicyDetails: Array,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  amlStatusName: String,
});

const assumptionState = reactive({
  isEditing: false,
});

const page = usePage();
const { isRequired } = useRules();

const modelClass = 'App\\Models\\PersonalQuote';

const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const permissionsEnum = page.props.permissionsEnum;
const leadSource = page.props.leadSource;
const notification = useToast();
const permissionEnum = page.props.permissionsEnum;
const canAny = permissions => useCanAny(permissions);

const bike_current_insurance_status = computed(() => {
  if (
    page.props.quote.bike_quote?.current_insurance_status != '' &&
    page.props.quote.bike_quote?.current_insurance_status != null
  ) {
    return page.props.quote.bike_quote?.current_insurance_status;
  } else {
    return 'ACTIVE_COMP';
  }
});

const assumptionsForm = useForm({
  cubic_capacity: page.props.quote?.bike_quote?.cubic_capacity,
  seat_capacity: page.props.quote?.bike_quote?.seat_capacity,
  vehicle_type_id: 1,
  is_modified: page.props.quote.bike_quote?.is_modified,
  is_bank_financed: page.props.quote.bike_quote?.is_bank_financed,
  is_gcc_standard: page.props.quote.bike_quote?.is_gcc_standard,
  current_insurance_status: bike_current_insurance_status.value,
  year_of_first_registration:
    page.props.quote.bike_quote?.year_of_first_registration,
  bike_quote_id: page.props.quote.bike_quote?.id,
  bike_uuid: page.props?.quote?.uuid,
  bike_code: page.props?.quote?.code,
  bike_id: page.props?.quote?.id,
});

const onUpdateAssumption = () => {
  assumptionsForm.post('/quotes/bike/bikeAssumptionsUpdate', {
    preserveScroll: true,
    onSuccess: response => {
      assumptionState.isEditing = false;
    },
    onError: error => {
      notification.error({
        title: 'Something went wront, please try again later.',
        position: 'top',
      });
    },
  });
};

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const isOptions = computed(() => {
  return [
    { value: 0, label: 'No' },
    { value: 1, label: 'Yes' },
  ];
});

const currentInsuranceOptions = computed(() => {
  return [
    { value: 'ACTIVE_TPL', label: 'ACTIVE_TPL' },
    { value: 'ACTIVE_COMP', label: 'ACTIVE_COMP' },
    { value: 'EXPIRED', label: 'EXPIRED' },
  ];
});

const bikeBodyType = computed(() => {
  return [{ value: 1, label: 'Bike' }];
});

const industryTypeOptions = computed(() => {
  return page.props.industryType?.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});
const emiratesOptions = computed(() => {
  return page.props.emirates?.map(em => ({
    value: em.id,
    label: em.text,
  }));
});
const isProfileUpdateAllow = computed(() => {
  return hasAnyRole([
    page.props.rolesEnum.PA,
    page.props.rolesEnum.OE,
    page.props.rolesEnum.NRA,
  ]);
});

const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,

  insured_first_name: page.props.quote?.customer?.insured_first_name || '',
  insured_last_name: page.props.quote?.customer?.insured_last_name || '',
  emirates_id_number: page.props.quote?.customer?.emirates_id_number || null,
  emirates_id_expiry_date:
    page.props.quote?.customer?.emirates_id_expiry_date || null,

  entity_id: page.props.quote?.quote_request_entity_mapping?.entity_id ?? null,
  trade_license_no:
    page.props.quote?.quote_request_entity_mapping?.entity?.trade_license_no ??
    null,
  company_name:
    page.props.quote?.quote_request_entity_mapping?.entity?.company_name ??
    null,
  company_address:
    page.props.quote?.quote_request_entity_mapping?.entity?.company_address ??
    null,
  entity_type_code:
    page.props.quote?.quote_request_entity_mapping?.entity_type_code ??
    'Parent',
  industry_type_code:
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.industry_type_code ?? null,
  emirate_of_registration_id:
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.emirate_of_registration_id ?? null,
});

const updateProfileDetails = isValid => {
  if (!isValid) return;

  customerProfileForm.post(route('update-customer-profile'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Customer profile details update Successfully',
        position: 'top',
      });
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

const entityDetailsFound = ref(false);
const getParentEntityModel = ref(false);
const tradeLicenseEntity = reactive({
  entity_id: null,
  trade_license: null,
  company_name: null,
  company_address: null,
  triggeredFrom: false,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const entityTypeChange = event => {
  if (event === 'SubEntity') {
    getParentEntityModel.value = true;
  }
};

const searchByTradeLicense = trigger => {
  let url = `/kyc/aml-fetch-entity?trade_license=${customerProfileForm.trade_license_no}`;
  axios
    .get(url)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;
        entityDetailsFound.value = true;
        tradeLicenseEntity.entity_id = response.id;
        tradeLicenseEntity.trade_license = response.trade_license_no;
        tradeLicenseEntity.company_name = response.company_name;
        tradeLicenseEntity.company_address = response.company_address;
        tradeLicenseEntity.triggeredFrom = trigger === 'SubEntity';

        notification.success({
          title: res.data.message,
          position: 'top',
        });
      } else {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
      }
    })
    .catch(err => {
      console.log(err);
    });
};

const linkEntity = () => {
  let entityDetails = {
    quote_type_id: page.props.quoteTypeId,
    quote_request_id: page.props.quote.id,
    entity_id: tradeLicenseEntity.entity_id,
    triggeredFrom: tradeLicenseEntity.triggeredFrom,
  };
  axios
    .post(route('link-entity-details'), entityDetails)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;

        // Append Entity data in fields
        customerProfileForm.trade_license_no = response.trade_license_no;
        customerProfileForm.company_name = response.company_name;
        customerProfileForm.company_address = response.company_address;
        customerProfileForm.entity_type_code =
          response?.quote_request_entity_mapping[0]?.entity_type_code ?? '';
        customerProfileForm.industry_type_code = response.industry_type_code;
        customerProfileForm.emirate_of_registration_id =
          response.emirate_of_registration_id;

        notification.success({
          title: res.data.message,
          position: 'top',
        });
        entityDetailsFound.value = false;
      }
    })
    .catch(err => {
      console.log(err);
    });
};
const paymentStatusEnum = page.props.paymentStatusEnum;

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  isAddUpdate.value = true;
};

const fetchUpdatedQuote = async () => {
  try {
    const response = await axios.get(
      `/quotes/get-bike-quote/${page.props.quote.uuid}`,
    );

    page.props.quote = response.data;

    notification.success({
      title: 'Quote details updated successfully',
      position: 'top',
    });
  } catch (error) {
    console.error('Error fetching updated quote:', error);

    notification.error({
      title: 'Something went wrong while updating the quote details',
      position: 'top',
    });
  }
};
</script>

<template>
  <div>
    <Head title="Bike Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Bike Detail</h2>
      <div class="flex gap-2">
        <Link
          v-if="quote.quote_detail?.insly_id"
          :href="`/legacy-policy/${quote.quote_detail?.insly_id}`"
          preserve-scroll
        >
          <x-button
            size="sm"
            color="#ff5e00"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            View Legacy policy
          </x-button>
        </Link>
        <Link
          v-else-if="
            quote.source == leadSource.RENEWAL_UPLOAD &&
            canAny([
              permissionsEnum.VIEW_LEGACY_DETAILS,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="
            route(
              'view-legacy-policy.renewal-uploads',
              quote.previous_quote_policy_number,
            )
          "
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link
            v-if="!isDisabled"
            :href="route('bike-quotes-edit', quote.uuid)"
          >
            <x-button size="sm" tag="div" v-if="readOnlyMode.isDisable === true"
              >Edit</x-button
            >
          </Link>
          <x-button v-else :disabled="isDisabled" size="sm" tag="div"
            >Edit</x-button
          >
        </LeadEditBtnTemplate>

        <x-tooltip
          v-if="lockLeadSectionsDetails.lead_details"
          placement="bottom"
        >
          <LeadEditBtnReuseTemplate
            v-if="
              canAny([
                permissionsEnum.BikeQuotesEdit,
                permissionsEnum.VIEW_ALL_LEADS,
              ])
            "
            :isDisabled="true"
          />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <template v-else>
          <LeadEditBtnReuseTemplate
            v-if="
              canAny([
                permissionsEnum.BikeQuotesEdit,
                permissionsEnum.VIEW_ALL_LEADS,
              ])
            "
          />
        </template>

        <Link
          v-if="can(permissionsEnum.BikeQuotesList)"
          :href="route('bike-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"> Bike Quotes </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center flex-wrap gap-2">
        <h2 class="text-lg font-semibold text-primary-800">E-COM Detail</h2>
      </div>
      <x-divider class="my-4" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PRICE</dt>
            <dd>{{ quote?.premium ?? '' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ quote?.paid_at ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">AML STATUS</dt>
            <dd>{{ amlStatusName ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ quote?.payment_status?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ quote?.car_plan?.insurance_provider?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT METHOD</dt>
            <dd>{{ quote?.payments[0]?.payment_method?.name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PLAN NAME</dt>
            <dd>{{ quote?.car_plan?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ECOMMERCE</dt>
            <dd>{{ quote?.is_ecommerce == 1 ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">QUOTE LINK</dt>
            <dd>{{ quote?.quote_link ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ORDER REFERENCE</dt>
            <dd>{{ quote?.payments[0]?.reference ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT REFERENCE</dt>
            <dd>{{ quote?.payments[0]?.code ?? '' }}</dd>
          </div>
        </dl>
        <AddOn
          v-if="bikeQuotePlanAddons.length > 0"
          :quotePlanAddons="bikeQuotePlanAddons"
        />
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #body>
          <x-divider class="my-4"></x-divider>

          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div
                class="grid sm:grid-cols-2"
                v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
              >
                <dt class="font-medium">ID</dt>
                <dd>{{ quote?.id }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip position="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      Ref-ID
                    </label>
                    <template #tooltip> Reference ID </template>
                  </x-tooltip>
                </div>
                <div>{{ quote?.code }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ quote?.customer_type }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote?.advisor?.name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AML STATUS</dt>
                <dd v-if="quote?.kyc_decision === 'Complete'">
                  KYC - Complete
                </dd>
                <dd v-else>KYC - Pending</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER AGE</dt>
                <dd>{{ quote?.customer_age }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote?.source }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ quote?.created_at }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED BY</dt>
                <dd class="break-words">{{ quote?.created_by?.email }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">UPDATED BY</dt>
                <dd class="break-words">{{ quote?.updated_by?.email }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ quote?.updated_at }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LOST REASON</dt>
                <dd>{{ quote?.quote_detail?.lost_reason?.text }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DEVICE</dt>
                <dd>{{ quote?.device }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IS ECOMMERCE</dt>
                <dd>{{ quote?.is_ecommerce ? 'Yes' : 'No' }}</dd>
              </div>
            </dl>
          </div>

          <div class="mt-6">
            <h3 class="font-semibold text-primary-800">Quote Details</h3>
            <x-divider class="mb-4 mt-1" />
          </div>

          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Bike Make</dt>
                <dd>{{ quote?.bike_quote?.bike_make?.text }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Bike Model</dt>
                <dd>{{ quote?.bike_quote?.bike_model?.text }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">CC</dt>
                <dd>{{ quote?.bike_quote?.cubic_capacity }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Bike model year</dt>
                <dd>{{ quote?.bike_quote?.year_of_manufacture }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">First Registration Date</dt>
                <dd>{{ quote?.bike_quote?.year_of_first_registration }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Bike value</dt>
                <dd>{{ quote?.bike_quote?.bike_value }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Bike value (at enquiry)</dt>
                <dd>{{ quote?.bike_quote?.bike_value_tier }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Seat Capacity</dt>
                <dd>{{ quote?.bike_quote?.seat_capacity }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Emirate Of Registration</dt>
                <dd>{{ quote?.bike_quote?.emirates?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Type Of Bike Insurance</dt>
                <dd>{{ quote?.bike_quote?.car_type_insurance?.text }}</dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="quote.source == 'Renewal_upload'"
              >
                <dt class="font-medium uppercase">Currently Insured With</dt>
                <dd>{{ quote?.currently_insured_with?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Claim History</dt>
                <dd>{{ quote?.bike_quote?.claim_history?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">
                  Can You Provide No-Claims Letter From Your Previous Insurers?
                </dt>
                <dd>
                  {{
                    quote?.bike_quote?.has_ncd_supporting_documents
                      ? 'Yes'
                      : 'No'
                  }}
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      Parent Ref-ID
                    </label>
                    <template #tooltip> Parent Reference ID </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <Link
                    v-if="quote?.parent_duplicate_quote_id"
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.uuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ quote?.parent_duplicate_quote_id ?? '' }}
                  </Link>
                </dd>
              </div>

              <div
                class="grid sm:grid-cols-2"
                v-if="linkedQuoteDetails.childLeadsCount == 1"
              >
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      CHILD REF-ID
                    </label>
                    <template #tooltip>
                      The Child Reference ID acts as an individual identifier
                      for dependents under the main lead. It's our way of
                      efficiently organizing and accessing each person's records
                      within the system.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <Link
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.childLeadsUuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ linkedQuoteDetails.childLeads ?? '' }}
                  </Link>
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          {{
            quote.customer_type == page.props.customerTypeEnum.Individual
              ? 'Customer '
              : 'Entity '
          }}
          Profile
        </h3>
        <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
          KYC - Complete
        </x-tag>
        <x-tag color="amber" v-else> KYC - Pending </x-tag>
      </div>
      <x-divider class="mb-4 mt-1" />
      <x-form @submit="updateProfileDetails" :auto-focus="false">
        <div class="text-sm">
          <dl
            v-if="
              quote.customer_type === page.props.customerTypeEnum.Individual
            "
            class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words"
          >
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">FIRST NAME</dt>
              <dd>{{ quote?.first_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">LAST NAME</dt>
              <dd>{{ quote?.last_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">INSURED FIRST NAME</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.insured_first_name"
                  :rules="[isRequired]"
                  placeholder="INSURED FIRST NAME"
                  class="w-full"
                  :disabled="!isProfileUpdateAllow"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">INSURED LAST NAME</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.insured_last_name"
                  :rules="[isRequired]"
                  placeholder="INSURED LAST NAME"
                  class="w-full"
                  :disabled="!isProfileUpdateAllow"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MOBILE NUMBER</dt>
              <dd>{{ quote?.mobile_no }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMAIL</dt>
              <dd>{{ quote?.email }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">NATIONALITY</dt>
              <dd>{{ quote?.nationality?.text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">DATE OF BIRTH</dt>
              <dd>{{ quote?.dob }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
              <dd>
                {{ quote.customer.receive_marketing_updates ? 'Yes' : 'No' }}
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMIRATES ID NUMBER</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.emirates_id_number"
                  :rules="[isRequired]"
                  placeholder="EMIRATES ID NUMBER"
                  class="w-full"
                  :disabled="!isProfileUpdateAllow"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMIRATES ID EXPIRY DATE</dt>
              <dd>
                <DatePicker
                  v-model="customerProfileForm.emirates_id_expiry_date"
                  :rules="[isRequired]"
                  placeholder="EMIRATES ID EXPIRY DATE"
                  :disabled="!isProfileUpdateAllow"
                  :min-date="new Date()"
                />
              </dd>
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="font-medium uppercase">UAE licence held for</dt>
              <dd>{{ quote?.bike_quote?.uae_license_held_for?.text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium uppercase">
                Home Country Driving License Held For
              </dt>
              <dd>{{ quote?.bike_quote?.back_home_license_held_for?.text }}</dd>
            </div>
            <RiskRatingScoreDetails
              v-if="quote"
              :quote="quote"
              :modelType="'Bike'"
            />
          </dl>
          <dl
            v-if="quote.customer_type === page.props.customerTypeEnum.Entity"
            class="grid md:grid-cols-2 gap-x-6 gap-y-4"
          >
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">FIRST NAME</dt>
              <dd>{{ quote?.first_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">LAST NAME</dt>
              <dd>{{ quote?.last_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MOBILE NUMBER</dt>
              <dd>{{ quote?.mobile_no }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMAIL</dt>
              <dd>{{ quote?.email }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">COMPANY NAME</dt>
              <dd>{{ customerProfileForm.company_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">TRADE LICENSE NO</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.trade_license_no"
                  placeholder="TRADE LICENSE NO"
                  type="text"
                  class="w-full"
                />
                <x-button
                  @click.prevent="searchByTradeLicense"
                  size="xs"
                  color="primary"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Search
                </x-button>
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMIRATES OF REGISTRATION</dt>
              <dd>
                <ComboBox
                  v-model="customerProfileForm.emirate_of_registration_id"
                  :single="true"
                  placeholder="SELECT EMIRATES OF REGISTRATION"
                  :options="emiratesOptions"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">COMPANY ADDRESS</dt>
              <dd>
                <x-input
                  v-model="customerProfileForm.company_address"
                  placeholder="COMPANY ADDRESS"
                  type="text"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">INDUSTRY TYPE</dt>
              <dd>
                <ComboBox
                  :single="true"
                  v-model="customerProfileForm.industry_type_code"
                  placeholder="SELECT INDUSTRY TYPE"
                  :options="industryTypeOptions"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">ENTITY TYPE</dt>
              <dd>
                <ComboBox
                  @update:modelValue="entityTypeChange($event)"
                  :single="true"
                  v-model:modelValue="customerProfileForm.entity_type_code"
                  placeholder="SELECT ENTITY TYPE"
                  :options="[
                    { label: 'Parent', value: 'Parent' },
                    { label: 'Sub Entity', value: 'SubEntity' },
                  ]"
                  class="w-full"
                />
              </dd>
            </div>
          </dl>
          <div class="flex justify-end" v-if="readOnlyMode.isDisable === true">
            <x-button
              v-if="isProfileUpdateAllow"
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="customerProfileForm.processing"
              type="submit"
            >
              Update Profile
            </x-button>
          </div>
        </div>
      </x-form>
    </div>
    <x-modal v-model="getParentEntityModel" size="lg" show-close backdrop>
      <h3 class="font-semibold text-center text-lg mb-10">
        Search Entity by Parent Entity Trade License No
      </h3>
      <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Parent Entity Trade License No</dt>
          <dd>
            <x-input
              v-model="customerProfileForm.trade_license_no"
              placeholder="TRADE LICENSE NO"
              type="text"
              class="w-full"
            />
          </dd>
        </div>
      </dl>
      <template #actions>
        <div class="flex justify-end">
          <x-button
            color="primary"
            size="sm"
            :loading="customerProfileForm.processing"
            @click.prevent="searchByTradeLicense('SubEntity')"
            v-if="readOnlyMode.isDisable === true"
          >
            Search
          </x-button>
        </div>
      </template>
    </x-modal>
    <x-modal v-model="entityDetailsFound" size="lg" show-close backdrop>
      <h3 class="font-semibold text-center text-lg mb-10">
        Entity found with the entered Trade License number
      </h3>
      <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Trade License No</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.trade_license"
              placeholder="TRADE LICENSE NO"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Company Name</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.company_name"
              placeholder="Company Name"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Company Address</dt>
          <dd>
            <x-input
              v-model="tradeLicenseEntity.company_address"
              placeholder="Company Address"
              type="text"
              class="w-full"
              disabled
            />
          </dd>
        </div>
      </dl>
      <template #actions>
        <div class="text-left space-x-4">
          <x-button
            size="sm"
            color="orange"
            @click.prevent="linkEntity"
            v-if="readOnlyMode.isDisable === true"
          >
            Link
          </x-button>
        </div>
      </template>
    </x-modal>

    <MemberDetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :quote_type="quoteType"
    />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :quote-status-enum="quoteStatusEnum"
    />

    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="quoteType"
    />

    <AdditionalContacts :quote="quote" :quote-type="quoteType" />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      :quote="quote"
      modelType="Bike"
      :insly-id="quote?.quote_detail?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />
    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="quoteType"
      :payments="quote.payments"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Assumptions</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-field label="CC" required>
            <x-input
              v-model="assumptionsForm.cubic_capacity"
              type="number"
              placeholder="CC"
              class="w-full"
              :rules="[isRequired]"
              :disabled="!assumptionState.isEditing"
            />
          </x-field>
        </div>
        <div class="w-full md:w-1/2">
          <x-field label="Seat Capacity" required>
            <x-input
              v-model="assumptionsForm.seat_capacity"
              type="number"
              placeholder="Seat Capacity"
              class="w-full"
              :rules="[isRequired]"
              :disabled="!assumptionState.isEditing"
            />
          </x-field>
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Vehicle Body Type" required>
              <x-select
                v-model="assumptionsForm.vehicle_type_id"
                :options="bikeBodyType"
                placeholder="Vehicle Body Type"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Is Bike modified?" required>
              <x-select
                v-model="assumptionsForm.is_modified"
                :options="isOptions"
                placeholder="Is Modified"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Is Bank Financed" required>
              <x-select
                v-model="assumptionsForm.is_bank_financed"
                :options="isOptions"
                placeholder="Is Bank Financed"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Is GCC Standard?" required>
              <x-select
                v-model="assumptionsForm.is_gcc_standard"
                :options="isOptions"
                placeholder="Is GCC Standard"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Current Insurance Status" required>
              <x-select
                v-model="assumptionsForm.current_insurance_status"
                :options="currentInsuranceOptions"
                placeholder="Current Insurance Status"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-field label="Year Of First Registration" required>
              <x-select
                v-model="assumptionsForm.year_of_first_registration"
                :options="
                  $page.props.yearsOfManufacture.map(year => {
                    return { value: year.id.toString(), label: year.text };
                  })
                "
                placeholder="Year Of First Registration"
                class="w-full"
                :rules="[isRequired]"
                :disabled="!assumptionState.isEditing"
              />
            </x-field>
          </div>
        </div>
      </div>
      <div class="flex justify-end" v-if="readOnlyMode.isDisable === true">
        <x-button
          v-if="assumptionState.isEditing"
          class="mt-4 mr-2"
          color="orange"
          size="sm"
          @click.prevent="assumptionState.isEditing = false"
        >
          Cancel
        </x-button>
        <x-button
          v-if="assumptionState.isEditing"
          class="mt-4"
          color="primary"
          size="sm"
          :loading="assumptionsForm.processing"
          @click.prevent="onUpdateAssumption"
        >
          Update
        </x-button>
        <x-button
          v-if="!assumptionState.isEditing"
          class="mt-4"
          color="emerald"
          size="sm"
          @click.prevent="assumptionState.isEditing = true"
        >
          Edit Assumptions
        </x-button>
      </div>
    </div>

    <AvailablePlans
      :advisor="advisor"
      :insuranceProviders="insuranceProviders"
      :planURL="planURL"
      :carPlanAddonsCodeEnum="carPlanAddonsCodeEnum"
      :carPlanTypeEnum="carPlanTypeEnum"
      :carPlanExclusionsCodeEnum="carPlanExclusionsCodeEnum"
      :quote="quote"
      :carPlanFeaturesCodeEnum="carPlanFeaturesCodeEnum"
      :websiteURL="websiteURL"
      :linkedQuoteDetails="linkedQuoteDetails"
      @plan-selected="fetchUpdatedQuote"
    />

    <PaymentTableNew
      v-if="isNewPaymentStructure"
      :quoteType="quoteType"
      :payments="quote.payments"
      :paymentDocument="paymentDocument"
      :proformaPayment="
        quote.payments.find(
          item =>
            item.payment_methods_code ===
            page.props.paymentMethodsEnum.ProformaPaymentRequest,
        )
      "
      :quoteRequest="quote"
      :paymentStatusEnum="page.props.paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="storageUrl"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <QuotePayments
      v-else
      :can="can"
      :payments="quote.payments"
      :quote-type="quoteType"
      :payment-methods="paymentMethods"
      :insurance-providers="insuranceProviders"
      :is-beta-user="isBetaUser"
      :personal-plans="personalPlans"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="record.uuid"
      :code="record.code"
      :quote="record"
      :modelType="quoteType"
      :expanded="sectionExpanded"
    />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      modelType="Bike"
      :expanded="sectionExpanded"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :modelType="quoteType"
      :insly-id="quote?.quote_detail?.insly_id"
      :expanded="sectionExpanded"
      quote-type="Bike"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionEnum.VIEW_INSLY_BOOK_POLICY,
          permissionEnum.SEND_INSLY_BOOK_POLICY,
          permissionsEnum.VIEW_ALL_LEADS,
        ])
      "
      :quote="quote"
      quoteType="Bike"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
    />

    <EmailStatus :emailStatuses="emailStatuses" />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="quote"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <CustomerChatLogs
      :customerName="quote?.first_name + ' ' + quote?.last_name"
      :quoteId="quote.uuid"
      :quoteType="'BIKE'"
      :expanded="sectionExpanded"
    />

    <AuditLogs
      :id="$page.props.quote.id"
      :quote-type="quoteType"
      :quoteCode="$page.props.quote.code"
    />

    <ApiLogs :type="modelClass" :id="$page.props.quote.id" />

    <LeadHistory :quote="$page.props.quote" />

    <lead-raw-data
      :modelType="'Bike'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>
