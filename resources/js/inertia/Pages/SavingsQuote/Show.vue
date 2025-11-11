<script setup>
import { createReusableTemplate } from '@vueuse/core';
import { reactive } from 'vue';
import SelectPlan from '../../Components/SelectPlan.vue';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts.vue';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import CustomerAcceptanceLogsSection from '@/inertia/Components/CustomerAcceptanceLogs/Section.vue';

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
  cdnPath: String,
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
});

const page = usePage();
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const canAny = permissions => useCanAny(permissions);
const modelClass = 'App\\Models\\PersonalQuote';

const countDays = computed(() =>
  useDaysSinceStale(props.quoteRequest?.stale_at ?? props.quote?.stale_at),
);
const quoteStatusEnum = page.props.quoteStatusEnum;
const historyLoading = ref(false);

const { isRequired } = useRules();
const hasRole = role => useHasRole(role);
const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
  planDetails: false,
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

const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,

  insured_first_name: page.props.quote?.customer.insured_first_name || '',
  insured_last_name: page.props.quote?.customer.insured_last_name || '',
  emirates_id_number: page.props.quote?.customer.emirates_id_number || null,
  emirates_id_expiry_date:
    page.props.quote?.customer.emirates_id_expiry_date || null,

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

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  onLoadAvailablePlansData();
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

const availablePlansTable = reactive({
  data: [],
  isLoading: false,
  columns: [
    {
      text: 'Provider Name',
      value: 'providerName',
    },
    {
      text: 'Plans',
      value: 'name',
    },
    {
      text: 'Investment Frequency',
      value: 'investmentFrequency',
    },
    {
      text: 'Minimum Investment',
      value: 'minimumInvestment',
    },
    {
      text: 'Policy Term (Years)',
      value: 'policyTerm',
    },
    {
      text: 'Price',
      value: 'price',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

// const selectedPlans = ref([]); // Commented out - checkboxes disabled
const selectedPlanType = ref(null);
const toggleLoader = ref(false); // Still needed for individual plan toggles
const viewButtonLoading = ref(false);
const planDetails = ref(null);

// Form for individual plan updates
const planForm = useForm({
  quote_uuid: '',
  plan_id: '',
  provider_name: '',
  actual_premium: 0,
  insurer_quote_no: '',
  is_disabled: false,
  is_manual_update: false,
  current_url: '',
});

const normalPlansIds = reactive({
  ids: [],
});
const seniorPlansIds = reactive({
  ids: [],
});

// Define tabs for plan details modal
const planDetailsTabs = ref([
  { index: 0, label: 'Plan Details' },
  { index: 1, label: 'Eligibility' },
  { index: 2, label: 'Included Benefits' },
  { index: 3, label: 'Key Features Document' },
  { index: 4, label: 'Policy Wordings' },
]);

const selectedProviderPlan = ref({
  id: page.props?.quote?.plan_id,
  planName: page.props?.quote?.plans?.name,
  providerName: page.props?.quote?.plans?.providerName,
  premium: page.props?.quote?.plans?.premium,
});

const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
  onLoadAvailablePlansData();
};

const onLoadAvailablePlansData = async () => {
  availablePlansTable.isLoading = true;
  let data = {
    jsonData: true,
  };
  let url = `/quotes/savings/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      // Process the response data to flatten regular and lumpsum plans
      const processedPlans = [];

      // Process regular plans
      if (res.data.regular && Array.isArray(res.data.regular)) {
        res.data.regular.forEach(plan => {
          const processedPlan = {
            ...plan,
            investmentFrequency: 'Regular',
            currency: plan.currencyName || 'USD',
            minimumInvestment: getEligibilityValue(
              plan,
              'minimumInvestmentAmount',
            ),
            policyTerm: getEligibilityValue(plan, 'policyTerm'),
            isManualUpdate: plan.isManualUpdate || false,
            isDisabled: plan.isDisabled || false,
            // Add properties needed by SelectPlan component
            actualPremium: plan.actualPremium, // Keep actual value - advisor must manually set premium to enable selection
            insuranceProviderId: plan.providerId || plan.insuranceProviderId,
            providerCode: plan.providerCode,
          };
          processedPlans.push(processedPlan);
        });
      }

      // Process lumpsum plans
      if (res.data.lumpsum && Array.isArray(res.data.lumpsum)) {
        res.data.lumpsum.forEach(plan => {
          const processedPlan = {
            ...plan,
            investmentFrequency: 'Lumpsum',
            currency: plan.currencyName || 'USD',
            minimumInvestment: getEligibilityValue(
              plan,
              'minimumInvestmentAmount',
            ),
            policyTerm: getEligibilityValue(plan, 'policyTerm'),
            isManualUpdate: plan.isManualUpdate || false,
            isDisabled: plan.isDisabled || false,
            // Add properties needed by SelectPlan component
            actualPremium: plan.actualPremium, // Keep actual value - advisor must manually set premium to enable selection
            insuranceProviderId: plan.providerId || plan.insuranceProviderId,
            providerCode: plan.providerCode,
          };
          processedPlans.push(processedPlan);
        });
      }

      availablePlansTable.data = processedPlans;
    })
    .catch(err => {
      console.log(err);
      availablePlansTable.data = [];
    })
    .finally(() => {
      availablePlansTable.isLoading = false;
    });
};

// Helper function to extract value from eligibility array
const getEligibilityValue = (plan, code) => {
  // Check if eligibilities property exists (new API format)
  if (plan?.eligibilities && Array.isArray(plan.eligibilities)) {
    const found = plan.eligibilities.find(item => item.code === code);
    return found ? found.value : 'N/A';
  }

  return 'N/A';
};

// Commented out - bulk actions disabled since checkboxes are hidden
// const onTogglePlans = toggle => {
//   toggleLoader.value = true;

//   const planIds = useArrayUnique(
//     selectedPlans.value.map(p => {
//       return p.id;
//     }),
//   ).value;

//   axios
//     .post(route('manualPlanToggle', { quoteType: 'savings' }), {
//       modelType: 'Savings',
//       planIds: planIds,
//       quote_uuid: page.props.quote.uuid,
//       toggle: toggle,
//     })
//     .then(response => {
//       notification.success({
//         title: 'Plans has been updated',
//         position: 'top',
//       });
//       onLoadAvailablePlansData();
//       router.reload({
//         preserveScroll: true,
//       });
//     })
//     .catch(error => {
//       notification.error({
//         title: error,
//         position: 'top',
//       });
//     })
//     .finally(() => {
//       toggleLoader.value = false;
//       selectedPlans.value = [];
//     });
// };

const getPlanDetails = id => {
  viewButtonLoading.value = true;
  try {
    // Find the plan in the current data instead of making an API call
    const foundPlan = availablePlansTable.data.find(plan => plan.id === id);
    if (foundPlan) {
      planDetails.value = foundPlan;
      modals.planDetails = true;
      viewButtonLoading.value = false;
    } else {
      // Fallback to API call if plan not found in current data
      axios
        .get(`/savings/${page.props.quote.uuid}/plan_details/${id}`)
        .then(res => {
          // The API now returns the correct investmentFrequency, so we don't need to process it
          planDetails.value = res.data;
          modals.planDetails = true;
          viewButtonLoading.value = false;
        })
        .catch(err => {
          notification.error({
            title: 'Error',
            message: 'Plan Details Not Found',
            position: 'top',
          });
          console.log(err);
          viewButtonLoading.value = false;
        });
    }
  } catch (err) {
    console.log(err);
    notification.error({
      title: 'Error',
      message: 'Something went wrong',
      position: 'top',
    });
    viewButtonLoading.value = false;
  }
};

const onLoadAvailablePlansDataAndPlanDetails = async () => {
  await onLoadAvailablePlansData();
  // Refresh plan details if modal is open
  if (modals.planDetails && planDetails.value) {
    getPlanDetails(planDetails.value.id);
  }
};

const { copy, copied } = useClipboard();
const onCopyText = text => {
  copy(text);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const selectedPlanIds = computed(() => {
  return [];
});

// Create reusable template for manual toggle like Car
const [ToggleManualButtonTemplate, ToggleManualButtonReuseTemplate] =
  createReusableTemplate();

// Manual toggle state
const isManualUpdate = ref(false);
const toggleManualLoader = ref(false);

const onToggleManual = () => {
  toggleManualLoader.value = true;
  setTimeout(() => {
    toggleManualLoader.value = false;
  }, 300);
};

const onToggleIndividualPlan = () => {
  if (!planDetails.value) return;

  toggleLoader.value = true;

  axios
    .post(route('manualPlanToggle', { quoteType: 'savings' }), {
      modelType: 'Savings',
      planIds: [planDetails.value.id],
      quote_uuid: page.props.quote.uuid,
      toggle: planDetails.value.isDisabled,
    })
    .then(response => {
      notification.success({
        title: 'Plan has been updated',
        position: 'top',
      });
      onLoadAvailablePlansDataAndPlanDetails();
    })
    .catch(error => {
      notification.error({
        title: 'Error updating plan',
        position: 'top',
      });
      // Reset the toggle state on error
      planDetails.value.isDisabled = !planDetails.value.isDisabled;
    })
    .finally(() => {
      toggleLoader.value = false;
    });
};

const onUpdateIndividualPlan = () => {
  if (!planDetails.value) return;

  // Update form with current plan details
  planForm.quote_uuid = page.props.quote.uuid;
  planForm.plan_id = planDetails.value.id;
  planForm.provider_name = planDetails.value.providerName;
  planForm.actual_premium = planDetails.value.actualPremium || 0;
  planForm.insurer_quote_no = planDetails.value.insurerQuoteNo || '';
  planForm.is_disabled = planDetails.value.isDisabled;
  planForm.is_manual_update = planDetails.value.isManualUpdate;
  planForm.current_url = usePage().url;

  // Submit the form
  planForm.post(route('savingsPlanUpdate'), {
    preserveScroll: true,
    onSuccess: () => {
      onLoadAvailablePlansDataAndPlanDetails();
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

// Centralized tooltip mappings for plan details
const PLAN_TOOLTIP_MAPPINGS = {
  eligibility: {
    'Entry age': 'Eligible age to buy the plan',
    'Policy term (years)': 'Policy duration available for the plan',
    'Minimum investment': 'Minimum amount of investment required',
  },
  includedBenefits: {
    'Flexible premium payments':
      'Monthly, quarterly, semi-annual, or annual options',
    'Added life insurance coverage': 'Financial security for loved ones',
    'Investment options': 'Wide range of investment funds managed by experts',
    'Rate of return': 'Potential growth on your investment',
  },
};

// Generic helper function to get tooltip text for plan detail fields
const getPlanDetailTooltip = (fieldText, section) => {
  const tooltips = PLAN_TOOLTIP_MAPPINGS[section];
  if (!tooltips || !fieldText) return null;

  // Check for exact matches or partial matches
  for (const [key, value] of Object.entries(tooltips)) {
    if (fieldText.toLowerCase().includes(key.toLowerCase())) {
      return value;
    }
  }

  return null;
};

// Convenience functions for backward compatibility and clarity
const getEligibilityTooltip = fieldText =>
  getPlanDetailTooltip(fieldText, 'eligibility');
const getIncludedBenefitsTooltip = fieldText =>
  getPlanDetailTooltip(fieldText, 'includedBenefits');
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
          :cdn="cdnPath"
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex flex-wrap gap-4 justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Available Plans
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <!-- Commented out - bulk actions disabled since checkboxes are hidden
          <div class="flex justify-end items-center flex-wrap gap-2">
            <div
              class="flex gap-2 mb-4"
              v-if="
                readOnlyMode.isDisable === true &&
                !availablePlansTable.isLoading
              "
            >
              <x-button-group
                v-if="selectedPlans.length > 0"
                size="sm"
                class="mr-2"
              >
                <x-button
                  @click.prevent="onTogglePlans(false)"
                  :loading="toggleLoader"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Show
                </x-button>
                <x-button
                  @click.prevent="onTogglePlans(true)"
                  :loading="toggleLoader"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Hide
                </x-button>
              </x-button-group>

              <x-button
                v-if="availablePlansTable.data.length > 0"
                size="sm"
                color="orange"
                class="mr-2"
                @click.prevent="onCopyText('/quotes/savings/' + quote.uuid)"
              >
                Copy Link
              </x-button>
            </div>
          </div>
          -->

          <div
            v-if="
              availablePlansTable.data &&
              typeof availablePlansTable.data == 'string'
            "
          >
            <p
              class="text-center text-primary-600 uppercase"
              v-if="typeof availablePlansTable.data == 'string'"
            >
              {{ availablePlansTable.data }}
            </p>
          </div>
          <div v-else>
            <div
              v-if="availablePlansTable.isLoading"
              class="flex justify-center my-8"
            >
              <x-spinner size="lg" />
            </div>
            <DataTable
              v-else
              table-class-name="tablefixed compact-rows"
              :headers="availablePlansTable.columns"
              :items="availablePlansTable.data || []"
              border-cell
              hide-rows-per-page
              :rows-per-page="15"
              :hide-footer="availablePlansTable.data.length < 15"
            >
              <template #header-providerName>
                <div class="flex items-center gap-2">
                  <x-tooltip placement="bottom">
                    <span
                      class="underline decoration-dotted decoration-primary-700"
                      >Provider Name</span
                    >
                    <template #tooltip>Insurance provider</template>
                  </x-tooltip>
                  <span class="diamond-icon"></span>
                </div>
              </template>
              <template #header-name>
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Plans</span
                  >
                  <template #tooltip>Plan name</template>
                </x-tooltip>
              </template>
              <template #header-investmentFrequency>
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Investment Frequency</span
                  >
                  <template #tooltip>How often you plan to invest</template>
                </x-tooltip>
              </template>
              <template #header-minimumInvestment>
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Minimum Investment</span
                  >
                  <template #tooltip
                    >Minimum amount of investment required</template
                  >
                </x-tooltip>
              </template>
              <template #header-policyTerm>
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Policy Term (Years)</span
                  >
                  <template #tooltip
                    >Policy duration available for the plan</template
                  >
                </x-tooltip>
              </template>
              <template #header-price>
                <x-tooltip placement="bottom">
                  <span
                    class="underline decoration-dotted decoration-primary-700"
                    >Price</span
                  >
                  <template #tooltip>Price of the plan</template>
                </x-tooltip>
              </template>
              <template #item-providerName="item">
                <p class="text-primary-600 uppercase">
                  {{ item.providerName }}
                </p>
                <div class="flex gap-1">
                  <x-tag
                    v-if="item.isManualUpdate"
                    size="xs"
                    color="primary"
                    class="mt-0.5 text-[10px]"
                  >
                    Manual
                  </x-tag>
                  <x-tag
                    v-if="item.isDisabled"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px]"
                  >
                    Hidden
                  </x-tag>
                </div>
              </template>
              <template #item-name="item">
                <span class="text-primary-600 uppercase">{{ item.name }}</span>
              </template>
              <template #item-investmentFrequency="item">
                <span class="text-primary-600">{{
                  item.investmentFrequency
                }}</span>
              </template>
              <template #item-minimumInvestment="item">
                <span class="text-primary-600">{{
                  item.minimumInvestment
                }}</span>
              </template>
              <template #item-currency="item">
                <span class="text-primary-600">{{ item.currency }}</span>
              </template>
              <template #item-policyTerm="item">
                <span class="text-primary-600">{{ item.policyTerm }}</span>
              </template>
              <template #item-price="item">
                <span class="text-primary-600">
                  {{ item.actualPremium ? item.actualPremium : '' }}
                </span>
              </template>
              <template #item-action="item">
                <div class="flex gap-2">
                  <x-button
                    size="xs"
                    color="primary"
                    outlined
                    @click.prevent="
                      selectedPlanType = 'normalPlans';
                      getPlanDetails(item.id);
                    "
                    :loading="viewButtonLoading"
                  >
                    View
                  </x-button>
                  <span>
                    <SelectPlan
                      v-if="selectedProviderPlan.id != item.id"
                      @update:selectedPlanChanged="handlePlanSelected"
                      :plan="item"
                      :quoteType="'Savings'"
                      :uuid="quote.uuid"
                      :code="quote.code"
                      :plans="availablePlansTable.data || []"
                      :extraDetails="{
                        selectedPlansIds: [selectedProviderPlan?.id],
                      }"
                      :payments="payments"
                      :insuranceProviderId="item.insuranceProviderId"
                    />

                    <x-button
                      v-else
                      size="xs"
                      color="orange"
                      outlined
                      :disabled="true"
                    >
                      Selected
                    </x-button>
                  </span>
                </div>
              </template>
            </DataTable>
          </div>

          <x-modal
            v-model="modals.planDetails"
            size="xl"
            :title="`${planDetails?.providerName} - ${planDetails?.name}`"
            show-close
            backdrop
          >
            <div v-if="planDetails" class="w-full no-border">
              <TabGroup>
                <TabList
                  class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
                >
                  <Tab
                    v-for="{ index, label } in planDetailsTabs"
                    as="template"
                    :key="index"
                    v-slot="{ selected }"
                  >
                    <button
                      :class="[
                        'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                        'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                        selected
                          ? 'bg-white shadow text-primary-600'
                          : 'hover:bg-white/50',
                      ]"
                    >
                      {{ label }}
                    </button>
                  </Tab>
                </TabList>

                <TabPanels class="mt-2 text-sm min-h-[50vh]">
                  <!-- Plan Details Tab -->
                  <TabPanel class="bg-white">
                    <div class="p-6">
                      <!-- Plan Toggle Controls -->
                      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
                        <!-- <div class="grid sm:grid-cols-2 mb-3">
                          <x-toggle
                            v-model="planDetails.isDisabled"
                            color="success"
                            label="Hide Plan?"
                            @change="onToggleIndividualPlan"
                            :loading="toggleLoader"
                          />
                        </div> -->
                        <div class="grid sm:grid-cols-2 mb-3">
                          <ToggleManualButtonTemplate v-slot="{ isDisabled }">
                            <x-toggle
                              v-model="planDetails.isManualUpdate"
                              color="success"
                              label="Manual"
                              :disabled="isDisabled"
                              @change="onToggleManual"
                              :loading="toggleManualLoader"
                            />
                          </ToggleManualButtonTemplate>

                          <x-tooltip
                            v-if="
                              page.props.lockLeadSectionsDetails?.plan_selection
                            "
                            placement="bottom"
                          >
                            <ToggleManualButtonReuseTemplate
                              :isDisabled="true"
                            />
                            <template #tooltip>
                              No further action allowed on issued policy, If
                              changes are required, such as increase in price,
                              please proceed through the 'Send Update' feature
                              using the 'Correction of Policy' option.
                            </template>
                          </x-tooltip>
                          <ToggleManualButtonReuseTemplate v-else />
                        </div>
                      </dl>

                      <!-- Form Fields using dt/dd grid pattern like Car -->
                      <dl class="grid md:grid-cols-2 gap-x-8 gap-y-6 mb-8">
                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700">
                            Provider Name
                          </dt>
                          <dd class="text-gray-900">
                            {{ planDetails.providerName }}
                          </dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700">
                            Plan Name
                          </dt>
                          <dd class="text-gray-900">{{ planDetails.name }}</dd>
                        </div>

                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700 mt-2">
                            Insurance Quote No.:
                          </dt>
                          <x-input
                            v-model="planDetails.insurerQuoteNo"
                            placeholder="Enter quote number"
                            size="sm"
                            :disabled="
                              !planDetails.isManualUpdate ||
                              page.props.lockLeadSectionsDetails?.plan_selection
                            "
                          />
                        </div>
                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700 mt-2">
                            Price:
                          </dt>
                          <x-input
                            v-model="planDetails.actualPremium"
                            placeholder="Enter price"
                            size="sm"
                            type="number"
                            :disabled="
                              !planDetails.isManualUpdate ||
                              page.props.lockLeadSectionsDetails?.plan_selection
                            "
                          />
                        </div>

                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700">
                            <x-tooltip placement="bottom">
                              <span
                                class="underline decoration-dotted decoration-primary-700"
                              >
                                Investment Frequency
                              </span>
                              <template #tooltip
                                >How often you plan to invest</template
                              >
                            </x-tooltip>
                          </dt>
                          <dd class="text-gray-900">
                            {{ planDetails.investmentFrequency }}
                          </dd>
                        </div>
                        <div class="grid sm:grid-cols-2"></div>
                      </dl>

                      <!-- Bottom section with dates and update button -->
                      <div class="border-t border-gray-300 pt-6 mt-6">
                        <div class="flex justify-end">
                          <div class="text-right">
                            <div class="text-sm text-gray-600 mb-2">
                              <span class="font-medium">Created Date:</span>
                              <span class="ml-2">{{ quote.created_at }}</span>
                            </div>
                            <div class="text-sm text-gray-600 mb-6">
                              <span class="font-medium">Updated At:</span>
                              <span class="ml-2">{{ quote.updated_at }}</span>
                            </div>
                            <x-button
                              color="primary"
                              size="sm"
                              :disabled="
                                page.props.lockLeadSectionsDetails
                                  ?.plan_selection
                              "
                              @click="onUpdateIndividualPlan"
                              :loading="planForm.processing"
                            >
                              Update
                            </x-button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </TabPanel>

                  <!-- Eligibility Tab -->
                  <TabPanel>
                    <div class="p-6">
                      <div
                        v-if="
                          planDetails.eligibilities &&
                          planDetails.eligibilities.length > 0
                        "
                        class="grid grid-cols-2 gap-x-8 gap-y-6"
                      >
                        <div
                          v-for="item in planDetails.eligibilities"
                          :key="item.id"
                          class="grid grid-cols-2 gap-x-4"
                        >
                          <div class="text-gray-700 font-medium text-sm">
                            <x-tooltip
                              placement="bottom"
                              v-if="getEligibilityTooltip(item.text)"
                            >
                              <span
                                class="underline decoration-dotted decoration-primary-700"
                              >
                                {{ item.text }}
                              </span>
                              <template #tooltip>{{
                                getEligibilityTooltip(item.text)
                              }}</template>
                            </x-tooltip>
                            <span v-else>{{ item.text }}</span>
                          </div>
                          <div class="text-gray-900 text-sm">
                            {{ item.value }}
                          </div>
                        </div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-500">
                        No eligibility criteria available
                      </div>
                    </div>
                  </TabPanel>

                  <!-- Included Benefits Tab -->
                  <TabPanel>
                    <div class="p-6">
                      <div
                        v-if="
                          planDetails.includedBenefits &&
                          planDetails.includedBenefits.length > 0
                        "
                        class="grid grid-cols-2 gap-x-8 gap-y-6"
                      >
                        <div
                          v-for="item in planDetails.includedBenefits"
                          :key="item.id"
                          class="grid grid-cols-2 gap-x-4"
                        >
                          <div class="text-gray-700 font-medium text-sm">
                            <x-tooltip
                              placement="bottom"
                              v-if="getIncludedBenefitsTooltip(item.text)"
                            >
                              <span
                                class="underline decoration-dotted decoration-primary-700"
                              >
                                {{ item.text }}
                              </span>
                              <template #tooltip>{{
                                getIncludedBenefitsTooltip(item.text)
                              }}</template>
                            </x-tooltip>
                            <span v-else>{{ item.text }}</span>
                          </div>
                          <div class="text-gray-900 text-sm">
                            {{ item.value }}
                          </div>
                        </div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-500">
                        No included benefits available
                      </div>
                    </div>
                  </TabPanel>

                  <!-- Key Features Document Tab -->
                  <TabPanel>
                    <div class="p-4">
                      <div
                        v-if="
                          planDetails.keyFeatureDocument &&
                          planDetails.keyFeatureDocument.length > 0
                        "
                        class="space-y-3"
                      >
                        <div
                          v-for="doc in planDetails.keyFeatureDocument"
                          :key="doc.id"
                          class="inline-flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                          <!-- PDF Icon -->
                          <div class="flex-shrink-0">
                            <svg
                              class="w-6 h-6 text-red-500"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                              ></path>
                            </svg>
                          </div>

                          <!-- Document Name -->
                          <div class="flex-shrink-0">
                            <x-tooltip placement="bottom">
                              <span
                                class="text-blue-600 font-medium underline decoration-dotted decoration-primary-700"
                                >{{ doc.text }}</span
                              >
                              <template #tooltip
                                >Summary of plan benefits and features</template
                              >
                            </x-tooltip>
                          </div>

                          <!-- Download Icon -->
                          <div class="flex-shrink-0">
                            <a
                              :href="doc.value"
                              target="_blank"
                              class="text-blue-600 hover:text-blue-800"
                            >
                              <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                              >
                                <path
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                              </svg>
                            </a>
                          </div>
                        </div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-500">
                        No key feature documents available
                      </div>
                    </div>
                  </TabPanel>

                  <!-- Policy Wordings Tab -->
                  <TabPanel>
                    <div class="p-4">
                      <div
                        v-if="
                          planDetails.policyWordings &&
                          planDetails.policyWordings.length > 0
                        "
                        class="space-y-3"
                      >
                        <div
                          v-for="doc in planDetails.policyWordings"
                          :key="doc.id"
                          class="inline-flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                          <!-- PDF Icon -->
                          <div class="flex-shrink-0">
                            <svg
                              class="w-6 h-6 text-red-500"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                clip-rule="evenodd"
                              ></path>
                            </svg>
                          </div>

                          <!-- Document Name -->
                          <div class="flex-shrink-0">
                            <x-tooltip placement="bottom">
                              <span
                                class="text-blue-600 font-medium underline decoration-dotted decoration-primary-700"
                                >{{ doc.text }}</span
                              >
                              <template #tooltip
                                >Policy wordings for this savings plan</template
                              >
                            </x-tooltip>
                          </div>

                          <!-- Download Icon -->
                          <div class="flex-shrink-0">
                            <a
                              :href="doc.link"
                              target="_blank"
                              class="text-blue-600 hover:text-blue-800"
                            >
                              <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                              >
                                <path
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                              </svg>
                            </a>
                          </div>
                        </div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-500">
                        No policy wordings available
                      </div>
                    </div>
                  </TabPanel>
                </TabPanels>
              </TabGroup>
            </div>
          </x-modal>
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

    <LeadHistory :quote="quote" :expanded="sectionExpanded" />

    <ApiLogs :type="modelClass" :id="$page.props.quote.id" />

    <AuditLogs
      :quote-type="quoteType"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
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
