<script setup>
import CustomerAcceptanceLogsSection from '@/inertia/Components/CustomerAcceptanceLogs/Section.vue';
import LeadHistorySection from '@/inertia/Components/LeadHistorySection.vue';
import { useSavingsPlans } from '@/inertia/Composables/useSavingsPlans';
import { createReusableTemplate } from '@vueuse/core';
import { onMounted, reactive } from 'vue';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import AvailablePlans from './Partials/AvailablePlans.vue';
import { applyEmiratesNumberMasking } from '@/inertia/Composables/utilities.js';

const props = defineProps({
  quote: Object,
  documentTypes: Object,
  noteDocumentType: Object,
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
  duplicateAllowedLobs: Array,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  quoteRequest: Object,
  quoteDocuments: Object,
  quoteNotes: Object,
  paymentTooltipEnum: Object,
  permissions: Object,
  enums: Object,
  bookPolicyDetails: Array,
  payments: Array,
  isNewPaymentStructure: Boolean,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  emailStatuses: Array,
  ecomSavingsInsuranceQuoteUrl: String,
  websiteURL: String,
  lookUpData: Object,
  localLookups: Object,
});

const page = usePage();
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const canAny = permissions => useCanAny(permissions);
const modelClass = 'App\\Models\\PersonalQuote';
const modelClassSavings = 'App\\Models\\SavingsQuote';
const genericRequestEnum = page.props.genericRequestEnum;
const countDays = computed(() =>
  useDaysSinceStale(props.quoteRequest?.stale_at ?? props.quote?.stale_at),
);
const quoteStatusEnum = page.props.quoteStatusEnum;

const { isRequired } = useRules();
const hasRole = role => useHasRole(role);
const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
});

const leadDuplicateForm = useForm({
  modelType: 'Savings',
  parentType: 'Savings',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

// Initialize useSavingsPlans composable for helper functions and ecom logic
const {
  getPaymentTermTitle,
  formatNumber,
  calculateTotalAnnualPrice,
  ecomDetail,
  planExchangeRate,
  sharedAvailablePlans,
  getEcomDisplayPrice,
  totalAnnualPrice,
  getTotalAnnualPriceAED,
  updateEcomDetailFromPlans,
  totalPriceAED,
} = useSavingsPlans({
  quote: computed(() => props.quote),
  localLookups: computed(() => props.localLookups),
  lookUpData: computed(() => props.lookUpData),
  insuranceProviders: computed(() => props.insuranceProviders),
  notification,
});

// Alias for compatibility
const numberFormat = formatNumber;

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post(route('createDuplicate'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
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

const selectedInsuranceProviderPlan = computed(() => {
  const plan = props.quote?.insurance_provider_plan;

  if (Array.isArray(plan)) {
    return plan[0] ?? null;
  }

  return plan ?? null;
});

const selectedInsuranceProviderPlanCode = computed(() => {
  return selectedInsuranceProviderPlan.value?.code ?? null;
});

const passportOcrEligiblePlanCodes = computed(() => {
  return page.props.ocrEligiblePlanCodes?.PP ?? []; //PP = passport
});

// Show passport fields only when an eligible plan (by code) is selected on quote
const shouldShowPassportFields = computed(() => {
  if (!selectedInsuranceProviderPlanCode.value) {
    return false;
  }

  return passportOcrEligiblePlanCodes.value.includes(
    selectedInsuranceProviderPlanCode.value,
  );
});

const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,

  insured_first_name: page.props.quote?.latest_insured?.first_name,
  insured_last_name: page.props.quote?.latest_insured?.last_name,
  emirates_id_number:
    page.props.quote?.latest_insured?.id_number &&
    applyEmiratesNumberMasking(page.props.quote.latest_insured.id_number),
  emirates_id_expiry_date:
    page.props.quote?.latest_insured?.insured_kyc?.id_expiry_date,

  passport_number:
    page.props.quote.passport_visa_details?.passport_number ?? null,
  passport_country:
    page.props.quote.passport_visa_details?.passport_country ?? null,
  passport_expiry_date:
    page.props.quote.passport_visa_details?.passport_expiry_date ?? null,

  entity_id: page.props.quote?.quote_request_entity_mapping?.entity_id ?? null,
  trade_license_no:
    page.props.quote?.latest_insured?.id_type ===
    genericRequestEnum.TRADE_LICENSE
      ? page.props.quote?.latest_insured?.id_number
      : null,
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
        tradeLicenseEntity.entity_id = response.id; // this is the insured id
        tradeLicenseEntity.trade_license = response.id_number;
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
    entity_id: tradeLicenseEntity.entity_id, // this is the insured id
    triggeredFrom: tradeLicenseEntity.triggeredFrom,
  };
  axios
    .post(route('link-entity-details'), entityDetails)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;

        // Append Entity data in fields
        customerProfileForm.trade_license_no = response.trade_license_no; // this details fetched from entity table
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

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const sectionExpanded = computed(() => true);
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  isAddUpdate.value = true;
};

// Handle plan selected event from AvailablePlans component
const handlePlanSelected = plan => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
};
</script>

<template>
  <div>
    <Head title="Savings Quotes" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Savings Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
      </template>
      <template #default v-if="readOnlyMode.isDisable === true">
        <LeadNotes
          :documentType="noteDocumentType"
          :notes="quoteNotes"
          :modelType="quoteType"
          :quote="quote"
        />
        <Link
          v-if="quote.quote_detail?.insly_id"
          :href="`/legacy-policy/${quote.quote_detail?.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link
            v-if="!isDisabled"
            :href="route('savings-quotes-edit', quote.uuid)"
          >
            <x-button size="sm" tag="div">Edit</x-button>
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
                permissionsEnum.SAVINGS_QUOTES_EDIT,
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
                permissionsEnum.SAVINGS_QUOTES_EDIT,
                permissionsEnum.VIEW_ALL_LEADS,
              ])
            "
          />
        </template>

        <Link
          v-if="
            canAny([
              permissionsEnum.SAVINGS_QUOTES_EDIT,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="route('savings-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div">
            Savings Quotes
          </x-button>
        </Link>
      </template>
    </StickyHeader>

    <x-modal
      v-model="modals.duplicate"
      size="md"
      title="Duplicate Lead"
      show-close
      backdrop
      is-form
      persistent
      @submit="onCreateDuplicate"
    >
      <div class="grid gap-4">
        <x-field label="LOBs" required>
          <x-select
            v-model="leadDuplicateForm.lob_team"
            :options="
              duplicateAllowedLobs.map(lob => ({
                value: lob,
                label: lob,
              }))
            "
            :rules="[isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
          />
        </x-field>
        <x-field label="Reason" required>
          <x-select
            v-model="leadDuplicateForm.lob_team_sub_selection"
            :rules="[isRequired]"
            class="w-full"
            :options="[
              { value: 'new_enquiry', label: 'New enquiry' },
              { value: 'record_only', label: 'Record purposes only' },
            ]"
          />
        </x-field>
      </div>
      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          @click.prevent="modals.duplicate = false"
          size="sm"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          color="orange"
          type="submit"
          :loading="leadDuplicateForm.processing"
        >
          Create Duplicate
        </x-button>
      </template>
    </x-modal>

    <div class="p-4 rounded shadow mb-6 mt-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center flex-wrap gap-2"></div>
        </template>
        <template #body>
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div
                class="grid sm:grid-cols-2"
                v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
              >
                <dt class="font-medium">ID</dt>
                <dd>{{ quote.id }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <x-tooltip placement="bottom">
                  <label
                    class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                  >
                    Ref-ID
                  </label>
                  <template #tooltip> Reference ID </template>
                </x-tooltip>
                <div>{{ quote.code }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ quote.customer_type }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote?.advisor?.name }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ quote.created_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ quote.updated_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                <dd>
                  {{ quote.quote_detail?.next_followup_date }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
              </div>

              <!-- Sub-source fields -->
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      IMCRM SUB-SOURCE
                    </label>
                    <template #tooltip>{{
                      quote?.sub_source?.description || 'N/A'
                    }}</template>
                  </x-tooltip>
                </div>
                <div>{{ quote?.sub_source?.text || 'N/A' }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      SUB SOURCE OPTION
                    </label>
                    <template #tooltip>{{
                      quote?.sub_source_option?.description || 'N/A'
                    }}</template>
                  </x-tooltip>
                </div>
                <div>{{ quote?.sub_source_option?.text || 'N/A' }}</div>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LOST REASON</dt>
                <dd>{{ quote.quote_detail?.lost_reason?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IS ECOMMERCE</dt>
                <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      PARENT REF-ID
                    </label>
                    <template #tooltip> Parent Reference ID </template>
                  </x-tooltip>
                </div>
                <div>
                  <Link
                    v-if="quote.parent_duplicate_quote_id"
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.uuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ quote.parent_duplicate_quote_id ?? '' }}
                  </Link>
                </div>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF INSURANCE</dt>
                <dd>Savings</dd>
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
                <dt class="font-medium">PURPOSE OF INVESTMENT</dt>
                <dd>{{ quote?.savings_quote?.purpose?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TENURE OF SAVINGS</dt>
                <dd>{{ quote?.savings_quote?.tenure?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENCY</dt>
                <dd>{{ quote?.savings_quote?.currency?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INVESTMENT AMOUNT</dt>
                <dd>{{ quote?.savings_quote?.investment_amount }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDITIONAL INFORMATION</dt>
                <dd>{{ quote?.savings_quote?.additional_notes || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INVESTMENT FREQUENCY</dt>
                <dd>{{ quote?.savings_quote?.investment_frequency?.text }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              {{
                quote.customer_type == page.props.customerTypeEnum.Individual
                  ? 'Customer '
                  : 'Entity '
              }}
              Profile
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex mb-4 justify-end">
            <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
              KYC - Complete
            </x-tag>
            <x-tag color="amber" v-else> KYC - Pending </x-tag>
          </div>

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
                  <dd>{{ quote.first_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LAST NAME</dt>
                  <dd>{{ quote.last_name }}</dd>
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
                  <dd>{{ quote.mobile_no }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMAIL</dt>
                  <dd class="break-words">{{ quote.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ quote.dob }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">AGE</dt>
                  <dd>{{ quote.age }}</dd>
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
                      @input="
                        applyEmiratesNumberMasking(
                          customerProfileForm.emirates_id_number,
                        )
                      "
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

                <template v-if="shouldShowPassportFields">
                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">PASSPORT NUMBER</dt>
                    <dd>
                      <x-input
                        v-model="customerProfileForm.passport_number"
                        placeholder="PASSPORT NUMBER"
                        class="w-full"
                        :disabled="!isProfileUpdateAllow"
                      />
                    </dd>
                  </div>

                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">PASSPORT COUNTRY</dt>
                    <dd>
                      <x-input
                        v-model="customerProfileForm.passport_country"
                        placeholder="PASSPORT COUNTRY"
                        class="w-full"
                        :disabled="!isProfileUpdateAllow"
                      />
                    </dd>
                  </div>

                  <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">PASSPORT EXPIRY DATE</dt>
                    <dd>
                      <DatePicker
                        v-model="customerProfileForm.passport_expiry_date"
                        placeholder="PASSPORT EXPIRY DATE"
                        :disabled="!isProfileUpdateAllow"
                        :min-date="new Date()"
                      />
                    </dd>
                  </div>
                </template>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">GENDER</dt>
                  <dd>{{ quote.gender_label }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote.nationality?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">MARITAL STATUS</dt>
                  <dd>{{ quote?.savings_quote?.marital_status?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">SALARY</dt>
                  <dd>{{ quote.savings_quote?.salary }}</dd>
                </div>
                <RiskRatingScoreDetails :quote="quote" :modelType="'Savings'" />
              </dl>
              <dl
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Entity
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">FIRST NAME</dt>
                  <dd>{{ quote.first_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LAST NAME</dt>
                  <dd>{{ quote.last_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">MOBILE NUMBER</dt>
                  <dd>{{ quote.mobile_no }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMAIL</dt>
                  <dd class="break-words">{{ quote.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">COMPANY NAME</dt>
                  <dd class="break-words">
                    {{ customerProfileForm.company_name }}
                  </dd>
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
              <div class="flex justify-end">
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
        </template>
      </Collapsible>
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
        <x-button
          color="primary"
          size="sm"
          :loading="customerProfileForm.processing"
          @click.prevent="searchByTradeLicense('SubEntity')"
          v-if="readOnlyMode.isDisable === true"
        >
          Search
        </x-button>
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
        <x-button size="sm" color="orange" @click.prevent="linkEntity">
          Link
        </x-button>
      </template>
    </x-modal>

    <MemberDetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <AdditionalContacts
      :quote="quote"
      :quote-type="quoteType"
      :expanded="sectionExpanded"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Savings"
      :quote="quote"
      :insly-id="quote?.quote_detail?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
      :expanded="sectionExpanded"
    />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :expanded="sectionExpanded"
    />

    <AvailablePlans
      :quote="quote"
      :payments="payments"
      :insuranceProviders="insuranceProviders"
      :ecomSavingsInsuranceQuoteUrl="ecomSavingsInsuranceQuoteUrl"
      :websiteURL="websiteURL"
      :lockLeadSectionsDetails="lockLeadSectionsDetails"
      :lookUpData="lookUpData"
      :localLookups="localLookups"
      @plan-selected="handlePlanSelected"
    />
    <!-- @plans-loaded="handlePlansLoaded" -->

    <!-- Ecom Plan Detail -->
    <div v-show="ecomDetail != null" class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex flex-wrap gap-4 justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">E-COM Detail</h3>
          </div>
        </template>

        <template #body>
          <x-divider class="my-4" />
          <div>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Price</dt>
                <dd>
                  {{ numberFormat(getEcomDisplayPrice(ecomDetail)) }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Total Annual Price</dt>
                <dd>
                  {{ numberFormat(totalAnnualPrice) }}
                </dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="ecomDetail?.currency != 'AED'"
              >
                <dt class="font-medium uppercase">Total Price AED</dt>
                <dd>
                  {{ numberFormat(totalPriceAED || 0) }}
                </dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="ecomDetail?.currency != 'AED'"
              >
                <dt class="font-medium uppercase">Total Annual Price AED</dt>
                <dd>
                  {{ getTotalAnnualPriceAED }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Payment Term</dt>
                <dd>
                  {{ getPaymentTermTitle(ecomDetail?.paymentTerm) ?? 'N/A' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Authorised AT</dt>
                <dd>{{ quote?.payments?.[0]?.authorized_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">PAID AT</dt>
                <dd>{{ quote?.payment_paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT STATUS</dt>
                <dd>{{ quote?.payment_status?.text ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PROVIDER NAME</dt>
                <dd>{{ ecomDetail?.providerName }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT METHOD</dt>
                <dd>
                  {{ quote?.payments?.[0]?.payment_method?.name ?? 'N/A' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN NAME</dt>
                <dd>{{ ecomDetail?.planName }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ECOMMERCE</dt>
                <dd>{{ quote.is_ecommerce == 1 ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">QUOTE LINK</dt>
                <dd>{{ ecomSavingsInsuranceQuoteUrl + quote.uuid }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN SOURCE</dt>
                <dd v-if="ecomDetail == null">N/A</dd>
                <dd v-else>{{ ecomDetail?.isApi ? 'API' : 'Manual' }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="quoteType"
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
      :expanded="sectionExpanded"
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
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
      :expanded="sectionExpanded"
    />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      :quoteStatusEnum="quoteStatusEnum"
      :policyIssuanceStatus="policyIssuanceStatus"
      modelType="savings"
      :expanded="sectionExpanded"
      :payments="payments"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quoteDocuments || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :insly-id="quote?.quote_detail?.insly_id"
      :expanded="sectionExpanded"
      quoteType="savings"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <CustomerAcceptanceLogsSection
      :leadId="quote.id"
      lob="savings"
      :expanded="sectionExpanded"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionsEnum.VIEW_INSLY_BOOK_POLICY,
          permissionsEnum.SEND_INSLY_BOOK_POLICY,
          permissionsEnum.VIEW_ALL_LEADS,
        ])
      "
      :quote="quote"
      quoteType="savings"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
    />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="quote"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <EmailStatus :emailStatuses="emailStatuses" />

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
      :expanded="sectionExpanded"
    />

    <LeadHistorySection
      :expanded="sectionExpanded"
      :quoteId="quote.id"
      :quoteTypeId="$page.props.quoteTypeId"
    />

    <ApiLogs
      :type="modelClassSavings"
      :id="$page.props.quote.savings_quote?.id"
    />

    <AuditLogs
      :quote-type="quoteType"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
      :expanded="sectionExpanded"
    />

    <OcrLogs
      v-if="can(permissionsEnum.API_LOG_VIEW)"
      :type="modelClass"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Savings'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>

<style scoped>
.compact-rows :deep(tbody tr) {
  height: 40px !important;
}

.compact-rows :deep(tbody td) {
  padding: 8px 12px !important;
  vertical-align: middle;
}

.compact-rows :deep(thead th) {
  padding: 10px 12px !important;
}

/* Style the diamond icon in the header */
.compact-rows :deep(thead th:first-child .header-text) {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Diamond shape icon */
.compact-rows :deep(.diamond-icon) {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: transparent;
  transform: rotate(45deg);
  margin-left: 8px;
  border: 1px solid white;
}

/* Target vue3-easy-data-table checkboxes specifically - only for this component */
.compact-rows :deep(.easy-checkbox label:before) {
  border-color: #10b981 !important;
}

.compact-rows
  :deep(.easy-checkbox input[type='checkbox']:checked + label:before) {
  background-color: #10b981 !important;
  border-color: #10b981 !important;
}

.compact-rows
  :deep(.easy-checkbox input[type='checkbox'].allSelected + label:before),
.compact-rows
  :deep(.easy-checkbox input[type='checkbox'].partSelected + label:before) {
  background-color: #10b981 !important;
  border-color: #10b981 !important;
}

/* Remove borders from modal and tab components */
.no-border {
  border: none !important;
}

.no-border :deep(.tab-group),
.no-border :deep(.tab-list),
.no-border :deep(.tab-panel),
.no-border :deep(.tab-panels) {
  border: none !important;
}

/* Remove any default borders from headless ui components */
:deep(.tab-group) {
  border: none !important;
}

:deep(.tab-list) {
  border: none !important;
}

:deep(.tab-panel) {
  border: none !important;
}

:deep(.tab-panels) {
  border: none !important;
}
</style>
