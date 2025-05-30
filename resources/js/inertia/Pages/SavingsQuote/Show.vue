<script setup>
import { reactive } from 'vue';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts.vue';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';

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
  sendConfirm: false,
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
      text: 'Currency',
      value: 'currency',
    },
    {
      text: 'Policy Term (Years)',
      value: 'policyTerm',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const selectedPlans = ref([]);
const selectedPlanType = ref(null);
const toggleLoader = ref(false);
const viewButtonLoading = ref(false);
const planDetails = ref(null);
const exportLoader = ref(false);

const normalPlansIds = reactive({
  ids: [],
});
const seniorPlansIds = reactive({
  ids: [],
});

const onLoadAvailablePlansData = async () => {
  availablePlansTable.isLoading = true;
  let data = {
    jsonData: true,
  };
  let url = `/quotes/savings/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      console.log('onLoadAvailablePlansData', res.data);

      // Process the response data to flatten regular and lumpsum plans
      const processedPlans = [];

      // Process regular plans
      if (res.data.regular && Array.isArray(res.data.regular)) {
        res.data.regular.forEach(plan => {
          const processedPlan = {
            ...plan,
            investmentFrequency: 'Regular',
            currency: 'USD',
            minimumInvestment: getEligibilityValue(plan.eligibility, 'minimum_investment_amount'),
            policyTerm: getEligibilityValue(plan.eligibility, 'policy_term'),
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
            currency: 'USD',
            minimumInvestment: getEligibilityValue(plan.eligibility, 'minimum_investment_amount'),
            policyTerm: getEligibilityValue(plan.eligibility, 'policy_term'),
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
const getEligibilityValue = (eligibility, code) => {
  if (!Array.isArray(eligibility)) return 'N/A';

  const found = eligibility.find(item => item.code === code);
  return found ? found.value : 'N/A';
};

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => {
      return p.id;
    }),
  ).value;

  axios
    .post(route('manualPlanToggle', { quoteType: 'savings' }), {
      modelType: 'Savings',
      planIds: planIds,
      quote_uuid: page.props.quote.uuid,
      toggle: toggle,
    })
    .then(response => {
      notification.success({
        title: 'Plans has been updated',
        position: 'top',
      });
      onLoadAvailablePlansData();
      router.reload({
        preserveScroll: true,
      });
    })
    .catch(error => {
      notification.error({
        title: error,
        position: 'top',
      });
    })
    .finally(() => {
      toggleLoader.value = false;
      selectedPlans.value = [];
    });
};

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
          // Process the plan data similar to how we process it in onLoadAvailablePlansData
          const processedPlan = {
            ...res.data,
            investmentFrequency: res.data.planTypeId === 9961 ? 'Regular' : 'Lumpsum',
            currency: 'USD',
            minimumInvestment: getEligibilityValue(res.data.eligibility, 'minimum_investment_amount'),
            policyTerm: getEligibilityValue(res.data.eligibility, 'policy_term'),
          };
          planDetails.value = processedPlan;
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

const onExportPlans = () => {
  if (selectedPlans.value.length === 0) {
    notification.error({
      title: 'Please select at least one plan to export',
      position: 'top',
    });
    return;
  }

  exportLoader.value = true;

  const planIds = selectedPlans.value.map(plan => plan.id);

  axios
    .post(route('exportPlans', { quoteType: 'savings' }), {
      modelType: 'Savings',
      planIds: planIds,
      quote_uuid: page.props.quote.uuid,
    })
    .then(response => {
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `savings-plans-${page.props.quote.code}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      notification.success({
        title: 'Plans exported successfully',
        position: 'top',
      });
    })
    .catch(error => {
      notification.error({
        title: 'Error exporting plans',
        position: 'top',
      });
    })
    .finally(() => {
      exportLoader.value = false;
    });
};

const sendOCBEmail = () => {
  axios
    .post(route('sendOCBEmail', { quoteType: 'savings' }), {
      modelType: 'Savings',
      quote_uuid: page.props.quote.uuid,
    })
    .then(response => {
      notification.success({
        title: 'OCB Email sent successfully to customer',
        position: 'top',
      });
      modals.sendConfirm = false;
    })
    .catch(error => {
      notification.error({
        title: 'Error sending OCB Email',
        position: 'top',
      });
      modals.sendConfirm = false;
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
          :notes="quoteDocuments"
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
            v-if="can(permissionsEnum.SavingsQuotesEdit)"
            :isDisabled="true"
          />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <template v-else>
          <!-- TODO: Uncomment v-if when tested on local and add v-if="can(permissionsEnum.SavingsQuotesEdit)" -->
          <!-- <LeadEditBtnReuseTemplate v-if="can(permissionsEnum.SavingsQuotesEdit)" /> -->
          <LeadEditBtnReuseTemplate />
        </template>

        <Link
          v-if="can(permissionsEnum.SavingsQuotesList)"
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
                <dd>{{ quote?.savings_quote?.additional_notes }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INVESTMENT FREQUENCY</dt>
                <dd>{{ quote?.savings_quote?.investment_frequency?.text }}</dd>
              </div>
            </dl>
          </div>

          <div class="mt-6">
            <h3 class="font-semibold text-primary-800">Selected Plans</h3>
            <x-divider class="mb-4 mt-1" />
          </div>

          // TODO
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
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    HAVE YOU CONSUMED ANY PRODUCTS WITH NICOTINE FOR THE PAST 12
                    MONTHS?
                  </dt>
                  <dd>{{ quote?.savings_quote?.takes_nicotine }}</dd>
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
          <div class="flex justify-end items-center flex-wrap gap-2">
            <div class="flex gap-2 mb-4" v-if="readOnlyMode.isDisable === true && !availablePlansTable.isLoading">
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
                v-if="selectedPlans.length > 0"
                size="sm"
                color="emerald"
                @click.prevent="onExportPlans"
                :loading="exportLoader"
              >
                Download PDF
              </x-button>
              <x-tooltip placement="top" align="left">
                <x-button
                  @click.prevent="modals.sendConfirm = true"
                  size="sm"
                  color="orange"
                  class="mr-2"
                  :disabled="quote.advisor_id != $page.props.auth.user.id"
                >
                  Send Savings Plans email to Customer
                </x-button>
                <template #tooltip>
                  <div>
                    When clicked, this button sends the One Click Buy (OCB)
                    email to the customer with updated savings plans and coverage
                    options, helping them finalize their purchase with ease.
                  </div>
                </template>
              </x-tooltip>
              <x-button
                v-if="availablePlansTable.data.length > 0"
                size="sm"
                color="orange"
                class="mr-2"
                @click.prevent="
                  onCopyText('/quotes/savings/' + quote.uuid)
                "
              >
                Copy Link
              </x-button>
            </div>
          </div>

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
              v-model:items-selected="selectedPlans"
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
                  Provider Name
                  <span class="diamond-icon"></span>
                </div>
              </template>
              <template #item-providerName="item">
                <p class="text-gray-800 uppercase">
                  {{ item.providerName }}
                </p>
              </template>
              <template #item-name="item">
                <span class="text-gray-800 uppercase">{{ item.name }}</span>
              </template>
              <template #item-investmentFrequency="item">
                <span class="text-gray-800">{{ item.investmentFrequency }}</span>
              </template>
              <template #item-minimumInvestment="item">
                <span>{{ item.minimumInvestment }}</span>
              </template>
              <template #item-currency="item">
                <span>{{ item.currency }}</span>
              </template>
              <template #item-policyTerm="item">
                <span>{{ item.policyTerm }}</span>
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
            <div v-if="planDetails" class="space-y-6">
              <!-- Plan Basic Info -->
              <div>
                <h4 class="text-lg font-semibold text-primary-800 mb-3">Plan Information</h4>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                  <div>
                    <dt class="font-medium text-gray-600">Provider</dt>
                    <dd class="text-gray-900">{{ planDetails.providerName }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-600">Plan Name</dt>
                    <dd class="text-gray-900">{{ planDetails.name }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-600">Investment Frequency</dt>
                    <dd class="text-gray-900">{{ planDetails.investmentFrequency }}</dd>
                  </div>
                  <div>
                    <dt class="font-medium text-gray-600">Currency</dt>
                    <dd class="text-gray-900">{{ planDetails.currency }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Eligibility -->
              <div v-if="planDetails.eligibility && planDetails.eligibility.length > 0">
                <h4 class="text-lg font-semibold text-primary-800 mb-3">Eligibility</h4>
                <div class="space-y-3">
                  <div v-for="item in planDetails.eligibility" :key="item.id" class="border-l-4 border-primary-500 pl-4">
                    <dt class="font-medium text-gray-700">{{ item.text }}</dt>
                    <dd class="text-gray-600 text-sm">{{ item.description }}</dd>
                    <dd class="text-gray-900 font-medium">{{ item.value }}</dd>
                  </div>
                </div>
              </div>

              <!-- Benefits -->
              <div v-if="planDetails.includedBenefits && planDetails.includedBenefits.length > 0">
                <h4 class="text-lg font-semibold text-primary-800 mb-3">Included Benefits</h4>
                <div class="space-y-3">
                  <div v-for="item in planDetails.includedBenefits" :key="item.id" class="border-l-4 border-green-500 pl-4">
                    <dt class="font-medium text-gray-700">{{ item.text }}</dt>
                    <dd class="text-gray-600 text-sm">{{ item.description }}</dd>
                    <dd class="text-gray-900 font-medium">{{ item.value }}</dd>
                  </div>
                </div>
              </div>

              <!-- Key Feature Document -->
              <div v-if="planDetails.keyFeatureDocument && planDetails.keyFeatureDocument.length > 0">
                <h4 class="text-lg font-semibold text-primary-800 mb-3">Documents</h4>
                <div class="space-y-2">
                  <div v-for="doc in planDetails.keyFeatureDocument" :key="doc.id">
                    <a :href="doc.value" target="_blank" class="text-primary-600 hover:text-primary-800 underline">
                      {{ doc.text }}
                    </a>
                  </div>
                </div>
              </div>
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
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :insly-id="quote?.quote_detail?.insly_id"
      :expanded="sectionExpanded"
      quoteType="savings"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionsEnum.VIEW_INSLY_BOOK_POLICY,
          permissionsEnum.SEND_INSLY_BOOK_POLICY,
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

    <x-modal
      v-model="modals.sendConfirm"
      size="md"
      title="Send OCB Email"
      show-close
      backdrop
    >
      <div class="space-y-4">
        <p class="text-gray-600">
          Are you sure you want to send the One Click Buy (OCB) email to the customer?
          This will send them the selected savings plans with updated rates and coverage options.
        </p>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
          <div class="flex">
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                <strong>Customer:</strong> {{ quote.first_name }} {{ quote.last_name }}
              </p>
              <p class="text-sm text-blue-700">
                <strong>Email:</strong> {{ quote.email }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          @click.prevent="modals.sendConfirm = false"
          size="sm"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          color="orange"
          @click.prevent="sendOCBEmail"
        >
          Send Email
        </x-button>
      </template>
    </x-modal>
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
  border-color: #10B981 !important;
}

.compact-rows :deep(.easy-checkbox input[type='checkbox']:checked + label:before) {
  background-color: #10B981 !important;
  border-color: #10B981 !important;
}

.compact-rows :deep(.easy-checkbox input[type='checkbox'].allSelected + label:before),
.compact-rows :deep(.easy-checkbox input[type='checkbox'].partSelected + label:before) {
  background-color: #10B981 !important;
  border-color: #10B981 !important;
}
</style>
