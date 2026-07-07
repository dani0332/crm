<script setup>
import { createReusableTemplate } from '@vueuse/core';
import { reactive } from 'vue';
import LeadHistorySection from '@/inertia/Components/LeadHistorySection.vue';
import SelectPlan from '../../Components/SelectPlan.vue';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import CustomerAcceptanceLogsSection from '@/inertia/Components/CustomerAcceptanceLogs/Section.vue';
import OcrLogs from '../../Components/OcrLogs.vue';

const props = defineProps({
  insuredDetails: Array,
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
  isFuncsEnabled: Object,
  amlStatusName: String,
});

const page = usePage();
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const canAny = permissions => useCanAny(permissions);
const modelClass = 'App\\Models\\PersonalQuote';
const processingOCBEmail = ref(false);
const modelClassCyber = 'App\\Models\\CyberQuote';

const countDays = computed(() =>
  useDaysSinceStale(props.quoteRequest?.stale_at ?? props.quote?.stale_at),
);
const quoteStatusEnum = page.props.quoteStatusEnum;
const quote = page.props?.quote;

const { isRequired } = useRules();
const hasRole = role => useHasRole(role);
const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
  planDetails: false,
  sendConfirm: false,
});

const leadDuplicateForm = useForm({
  modelType: 'Cyber',
  parentType: 'Cyber',
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

const formatNumber = value => {
  if (!value || value === '-') return '-';
  return parseFloat(value).toLocaleString('en-US');
};

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

  insured_first_name: page.props.insuredDetails?.first_name || '',
  insured_last_name: page.props.insuredDetails?.last_name || '',
  emirates_id_number: page.props.insuredDetails?.id_number || null,
  emirates_id_expiry_date: page.props.insuredDetails?.id_expiry_date || null,
  emirates_id_issuing_date: page.props.insuredDetails?.id_issuance_date || null,

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
    page.props.quote?.cyber_quote?.emirate_of_registration_id ?? null,
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
      text: 'Coverage Up to',
      value: 'coverageUpTo',
    },
    {
      text: 'Quote Number',
      value: 'quoteNumber',
    },
    {
      text: 'Price without VAT (DHS)',
      value: 'priceWithoutVat',
    },
    {
      text: 'VAT (DHS)',
      value: 'vat',
    },
    {
      text: 'Price (with VAT) (DHS)',
      value: 'priceWithVat',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const selectedPlans = ref([]);
const selectedPlanType = ref(null);
const viewButtonLoading = ref(false);
const planDetails = ref(null);

const planDetailsTabs = ref([
  { index: 0, label: 'Plan Details' },
  { index: 1, label: 'Included Benefits' },
  { index: 2, label: 'Policy Wordings' },
]);

const selectedProviderPlan = ref({
  id: page.props?.quote?.plan_id,
  planName: page.props?.quote?.plans?.name,
  providerName: page.props?.quote?.plans?.providerName,
  premium: page.props?.quote?.plans?.premium || page.props?.quote?.premium,
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
  let url = `/quotes/cyber/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      let plansData = [];
      if (typeof res.data === 'string') {
        availablePlansTable.data = res.data;
      } else if (
        res.data?.quotes?.plans &&
        Array.isArray(res.data.quotes.plans)
      ) {
        plansData = res.data.quotes.plans;
        availablePlansTable.data = plansData.map(plan => ({
          ...plan,
          isManualUpdate: plan.isManualUpdate ?? false,
          isDisabled: plan.isDisabled ?? false,
          coverageUpTo: plan.coverage ?? '-',
          quoteNumber: plan.insurerQuoteNo ?? '-',
          priceWithoutVat: plan.discountPremium ?? '0',
          vat: plan.vat ?? '0',
          priceWithVat: (
            parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
          ).toFixed(2),
        }));
      } else if (Array.isArray(res.data) && res.data.length > 0) {
        plansData = res.data;
        availablePlansTable.data = plansData.map(plan => ({
          ...plan,
          isManualUpdate: plan.isManualUpdate ?? false,
          isDisabled: plan.isDisabled ?? false,
          coverageUpTo: plan.coverage ?? '-',
          quoteNumber: plan.insurerQuoteNo ?? '-',
          priceWithoutVat: plan.discountPremium ?? '0',
          vat: plan.vat ?? '0',
          priceWithVat: (
            parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
          ).toFixed(2),
        }));
      } else {
        availablePlansTable.data = [];
      }

      if (plansData.length > 0 && page.props?.quote?.plan_id) {
        const selectedPlan = plansData.find(
          plan => plan.id == page.props.quote.plan_id,
        );
        if (selectedPlan) {
          selectedProviderPlan.value.id = selectedPlan.id;
          selectedProviderPlan.value.planName =
            selectedPlan.planName || selectedPlan.name;
          selectedProviderPlan.value.providerName = selectedPlan.providerName;
          selectedProviderPlan.value.premium =
            selectedPlan.premium || page.props?.quote?.premium;
        }
      }
    })
    .catch(err => {
      console.log(err);
      availablePlansTable.data = [];
    })
    .finally(() => {
      availablePlansTable.isLoading = false;
    });
};

const getPlanDetails = id => {
  viewButtonLoading.value = true;
  try {
    const foundPlan = availablePlansTable.data.find(plan => plan.id === id);
    if (foundPlan) {
      planDetails.value = foundPlan;
      modals.planDetails = true;
      viewButtonLoading.value = false;
    } else {
      axios
        .get(`/cyber/${page.props.quote.uuid}/plan_details/${id}`)
        .then(res => {
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

const copyLink = () => {
  copy(page.props.planURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboardd',
      position: 'top',
    });
};

const confirmSendEmail = () => {
  processingOCBEmail.value = true;
  axios
    .post(`/quotes/cyber/${page.props.quote.uuid}/send-email-one-click-buy`, {
      responseType: 'json',
    })
    .then(response => {
      processingOCBEmail.value = false;
      notification.success({
        title: response.data.success,
        position: 'top',
      });
    })
    .catch(error => {
      processingOCBEmail.value = false;
      console.log(error);
    })
    .finally(() => {
      processingOCBEmail.value = false;
      modals.sendConfirm = false;
    });
};

const [DirhamSignTemplate, DirhamSignReuseTemplate] = createReusableTemplate();

const formatDob = dob => {
  return dob ? useDateFormat(dob, 'DD-MM-YYYY').value : '-';
};
</script>

<template>
  <div>
    <Head title="Cyber Quotes" />

    <DirhamSignTemplate>
      <svg
        viewBox="0 0 14 14"
        class="w-3 h-3 inline-block"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M13.0747 6.85449L12.9829 6.76976C12.8346 6.62854 12.6581 6.55792 12.4674 6.55792H11.4788C11.4929 6.72739 11.5 6.89687 11.5 7.08045C11.5 7.26405 11.4929 7.43351 11.4788 7.61004H12.1496C12.6581 7.61004 13.0747 8.0902 13.0747 8.69041V8.95873L12.9829 8.86694C12.8346 8.73277 12.6581 8.66216 12.4674 8.66216H11.3305C10.7868 11.0418 8.88736 12.334 5.89341 12.334H2.05212C2.05212 12.334 2.57464 11.9315 2.57464 10.5828V8.66216H1.93208C1.41661 8.66216 1 8.17494 1 7.5818V7.31347L1.09886 7.39821C1.24008 7.53237 1.41661 7.61004 1.60726 7.61004H2.57464V6.55792H1.93208C1.41661 6.55792 1 6.0707 1 5.47756V5.20924L1.09886 5.30103C1.24008 5.4352 1.41661 5.50581 1.60726 5.50581H2.57464V3.66283C2.57464 2.27178 2.05212 1.83398 2.05212 1.83398H5.89341C8.80262 1.83398 10.7515 3.11206 11.3234 5.50581H12.1496C12.6581 5.50581 13.0747 5.98597 13.0747 6.58617V6.85449ZM5.75219 2.35651H4.1493V5.50581H9.53699C9.16981 3.31683 7.91997 2.35651 5.75219 2.35651ZM9.66409 7.08045C9.66409 6.89687 9.65703 6.72739 9.64996 6.55792H4.1493V7.61004H9.64996C9.65703 7.43351 9.66409 7.26405 9.66409 7.08045ZM4.1493 11.8044H5.76631C8.0612 11.7479 9.19099 10.6464 9.53699 8.66216H4.1493V11.8044Z"
          fill="currentColor"
        />
      </svg>
    </DirhamSignTemplate>

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Cyber Insurance Details</h2>
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
            :href="route('cyber-quotes-edit', quote.uuid)"
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
                permissionsEnum.CYBER_QUOTES_EDIT,
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
                permissionsEnum.CYBER_QUOTES_EDIT,
                permissionsEnum.VIEW_ALL_LEADS,
              ])
            "
          />
        </template>

        <Link
          v-if="
            canAny([
              permissionsEnum.CYBER_QUOTES_EDIT,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="route('cyber-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div">
            Cyber Quotes
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
                <dt class="font-medium">Ref-ID</dt>
                <dd>{{ quote.code }}</dd>
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
                <dt class="font-medium">NATIONALITY</dt>
                <dd>{{ quote.nationality?.text || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IS ECOMMERCE</dt>
                <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF INSURANCE</dt>
                <dd>Cyber</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CYBER COVERAGE UP TO</dt>
                <dd>
                  {{
                    quote?.cyber_quote?.coverage
                      ? '$ ' + quote.cyber_quote.coverage.text
                      : '-'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IM AML STATUS</dt>
                <dd>{{ amlStatusName || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURER AML STATUS</dt>
                <dd>{{ quote?.insurer_aml_status || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
              </div>
              <template v-if="can(permissionsEnum.VIEW_UTM_SECTION)">
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM SOURCE</dt>
                  <dd>{{ quote.quote_detail?.utm_source }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM MEDIUM</dt>
                  <dd>{{ quote.quote_detail?.utm_medium }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM CAMPAIGN</dt>
                  <dd>{{ quote.quote_detail?.utm_campaign }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM CONTENT</dt>
                  <dd>{{ quote.quote_detail?.utm_content }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM TERM</dt>
                  <dd>{{ quote.quote_detail?.utm_term }}</dd>
                </div>
              </template>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURER API STATUS</dt>
                <dd>{{ quote?.insurer_api_status || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">API ISSUANCE STATUS</dt>
                <dd>{{ quote?.api_issuance_status || 'N/A' }}</dd>
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
                  <dt class="font-medium">INSURED'S FIRST NAME</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.insured_first_name"
                      :rules="[isRequired]"
                      placeholder="INSURED'S FIRST NAME"
                      class="w-full"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">INSURED'S LAST NAME</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.insured_last_name"
                      :rules="[isRequired]"
                      placeholder="INSURED'S LAST NAME"
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
                  <dd>{{ formatDob(quote.dob) }}</dd>
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
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote.nationality?.text || '-' }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES ID ISSUING DATE</dt>
                  <dd>
                    <DatePicker
                      v-model="customerProfileForm.emirates_id_issuing_date"
                      placeholder="EMIRATES ID ISSUING DATE"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES OF RESIDENCE</dt>
                  <dd>
                    <ComboBox
                      v-model="customerProfileForm.emirate_of_registration_id"
                      :single="true"
                      placeholder="SELECT EMIRATES OF RESIDENCE"
                      :options="emiratesOptions"
                      class="w-full"
                      :disabled="!isProfileUpdateAllow"
                    />
                  </dd>
                </div>
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
                  <dt class="font-medium">EMIRATES OF RESIDENCE</dt>
                  <dd>
                    <ComboBox
                      v-model="customerProfileForm.emirate_of_registration_id"
                      :single="true"
                      placeholder="SELECT EMIRATES OF RESIDENCE"
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
      modelType="Cyber"
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

    <EALeadInfo
      :source="quote.source"
      :ea-model="quote.ea_model"
      :lead-generator="quote.lead_generator"
      :expert-advisor="quote.expert_advisor"
      :ea-manager-approved-at="quote.ea_manager_approved_at"
      :ea-manager-rejected-at="quote.ea_manager_rejected_at"
    />

    <EAApprovalActions
      quote-type="cyber"
      :quote-id="quote.id"
      :source="quote.source"
      :ea-model="quote.ea_model"
      :quote-status-id="quote.quote_status_id"
      :advisor-id="quote.advisor_id"
      :expert-advisor-id="quote.expert_advisor_id"
      :ea-assigned-advisor-approved-at="quote.ea_assigned_advisor_approved_at"
      :ea-expert-advisor-approved-at="quote.ea_expert_advisor_approved_at"
      :ea-assigned-advisor-rejected-at="quote.ea_assigned_advisor_rejected_at"
      :ea-expert-advisor-rejected-at="quote.ea_expert_advisor_rejected_at"
      :ea-manager-approved-at="quote.ea_manager_approved_at"
      :ea-manager-rejected-at="quote.ea_manager_rejected_at"
      @updated="$inertia.reload({ only: ['quote'] })"
    />

    <div class="p-4 rounded shadow mb-6 bg-white" v-if="quote.is_ecommerce">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center flex-wrap gap-2">
            <h3 class="text-lg font-semibold text-primary-800">E-COM Detail</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PRICE</dt>
                <dd>
                  {{ selectedProviderPlan.premium || quote.premium || '' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AUTHORIZED AT</dt>
                <dd>{{ quote.paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAID AT</dt>
                <dd>{{ quote.payment_paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT STATUS</dt>
                <dd>{{ quote.payment_status_id_text ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PROVIDER NAME</dt>
                <dd>{{ selectedProviderPlan.providerName ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN NAME</dt>
                <dd>{{ selectedProviderPlan.planName ?? 'N/A' }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

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

          <div
            class="flex justify-end gap-3 mb-4"
            v-if="availablePlansTable.data.length > 0"
          >
            <x-button
              size="sm"
              color="orange"
              @click.prevent="modals.sendConfirm = true"
            >
              Send OCB Email
            </x-button>
            <x-button size="sm" color="orange" @click.prevent="copyLink">
              Copy Link
            </x-button>
          </div>
          <x-modal
            v-model="modals.sendConfirm"
            title="Send Email"
            show-close
            backdrop
          >
            <p>Are you sure send email to customer?</p>
            <template #actions>
              <div class="text-right space-x-4">
                <x-button
                  size="sm"
                  ghost
                  @click.prevent="modals.sendConfirm = false"
                  :disable="processingOCBEmail"
                >
                  Cancel
                </x-button>
                <x-button
                  size="sm"
                  color="error"
                  :loading="processingOCBEmail"
                  @click.prevent="confirmSendEmail"
                >
                  Send
                </x-button>
              </div>
            </template>
          </x-modal>

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
                  <span>PROVIDER NAME</span>
                  <span class="diamond-icon"></span>
                </div>
              </template>
              <template #header-name>
                <span>PLANS</span>
              </template>
              <template #header-coverageUpTo>
                <span>Coverage Up to (USD)</span>
              </template>
              <template #header-quoteNumber>
                <span>Quote Number</span>
              </template>
              <template #header-priceWithoutVat>
                <span>Price without VAT (<DirhamSignReuseTemplate />)</span>
              </template>
              <template #header-vat>
                <span>VAT (<DirhamSignReuseTemplate />)</span>
              </template>
              <template #header-priceWithVat>
                <span>Price (with VAT) (<DirhamSignReuseTemplate />)</span>
              </template>
              <template #header-action>
                <span>Action</span>
              </template>
              <template #item-providerName="item">
                <p>{{ item.providerName }}</p>
                <div class="flex gap-1">
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
                <span>{{ item.name }}</span>
              </template>
              <template #item-coverageUpTo="item">
                <span>{{ formatNumber(item.coverageUpTo) }}</span>
              </template>
              <template #item-quoteNumber="item">
                <span>{{ item.quoteNumber || '-' }}</span>
              </template>
              <template #item-priceWithoutVat="item">
                <span>{{
                  item.priceWithoutVat
                    ? parseFloat(item.priceWithoutVat).toFixed(2)
                    : '0.00'
                }}</span>
              </template>
              <template #item-vat="item">
                <span>{{
                  item.vat ? parseFloat(item.vat).toFixed(2) : '0.00'
                }}</span>
              </template>
              <template #item-priceWithVat="item">
                <span>{{
                  item.priceWithVat
                    ? parseFloat(item.priceWithVat).toFixed(2)
                    : '0.00'
                }}</span>
              </template>
              <template #item-action="item">
                <div class="flex gap-3">
                  <x-button
                    size="sm"
                    color="primary"
                    outlined
                    @click.prevent="
                      selectedPlanType = 'normalPlans';
                      getPlanDetails(item.id);
                    "
                    :loading="viewButtonLoading"
                    class="min-w-[100px] !rounded-xl !px-5 !py-1 !font-normal"
                  >
                    View
                  </x-button>
                  <span>
                    <SelectPlan
                      v-if="selectedProviderPlan.id != item.id"
                      @update:selectedPlanChanged="handlePlanSelected"
                      :plan="item"
                      :quoteType="'Cyber'"
                      :uuid="quote.uuid"
                      :code="quote.code"
                      :plans="availablePlansTable.data || []"
                      :extraDetails="{
                        selectedPlansIds: [selectedProviderPlan?.id],
                      }"
                      :payments="payments"
                      :insuranceProviderId="item.insuranceProviderId"
                      button-size="sm"
                      button-class="!rounded-xl !px-5 !py-1 !font-normal"
                    />

                    <x-button
                      v-else
                      size="sm"
                      color="orange"
                      outlined
                      :disabled="true"
                      class="min-w-[100px] !rounded-xl !px-5 !py-1 !font-normal"
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
                  <TabPanel class="bg-white">
                    <div class="p-6">
                      <dl class="grid md:grid-cols-2 gap-x-8 gap-y-6">
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
                          <dt class="text-sm font-medium text-gray-700">
                            Insurance Quote No
                          </dt>
                          <dd class="text-gray-900">
                            {{ planDetails.insurerQuoteNo ?? '-' }}
                          </dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700">
                            Price
                          </dt>
                          <dd class="text-gray-900">
                            {{ planDetails.actualPremium ?? '0' }}
                          </dd>
                        </div>

                        <div class="grid sm:grid-cols-2">
                          <dt class="text-sm font-medium text-gray-700">
                            Coverage Up to
                          </dt>
                          <dd class="text-gray-900">
                            {{ formatNumber(planDetails.coverage ?? '-') }}
                          </dd>
                        </div>
                      </dl>
                    </div>
                  </TabPanel>

                  <TabPanel>
                    <div class="p-6">
                      <div
                        v-if="
                          planDetails.benefits &&
                          planDetails.benefits.inclusion &&
                          planDetails.benefits.inclusion.length > 0
                        "
                        class="space-y-3 max-w-3xl"
                      >
                        <div
                          v-for="benefit in planDetails.benefits.inclusion"
                          :key="benefit.code"
                          class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-150"
                        >
                          <div class="flex-shrink-0 mt-0.5">
                            <svg
                              class="w-5 h-5 text-emerald-600"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                              />
                            </svg>
                          </div>
                          <span
                            class="text-sm text-gray-800 font-medium leading-relaxed"
                          >
                            {{ benefit.text }}
                          </span>
                        </div>
                      </div>
                      <div v-else class="text-center py-8 text-gray-500">
                        No benefits available
                      </div>
                    </div>
                  </TabPanel>

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

                          <div class="flex-shrink-0">
                            <x-tooltip placement="bottom">
                              <span
                                class="text-blue-600 font-medium underline decoration-dotted decoration-primary-700"
                                >{{ doc.text }}</span
                              >
                              <template #tooltip
                                >Policy wordings for this cyber plan</template
                              >
                            </x-tooltip>
                          </div>

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
      :quoteType="'Cyber'"
      :isFuncsEnabled="isFuncsEnabled"
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
      :quote="quote"
      :quoteStatusEnum="quoteStatusEnum"
      :policyIssuanceStatus="policyIssuanceStatus"
      modelType="Cyber"
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
      quoteType="cyber"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <CustomerAcceptanceLogsSection
      :leadId="quote.id"
      lob="cyber"
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
      quoteType="cyber"
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
      v-if="can(permissionsEnum.API_LOG_VIEW)"
      :type="modelClassCyber"
      :id="$page.props.quote.cyber_quote?.id"
    />

    <AuditLogs
      :title="'KYC Audit Logs'"
      :type="'App\\Models\\InsuredKyc'"
      :id="quote?.latest_insured?.insured_kyc?.id"
    />

    <AuditLogs
      :quote-type="quoteType"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
      :expanded="sectionExpanded"
    />

    <PolicyIssuanceApiLogs
      :type="modelClass"
      :quoteTypeId="$page.props.quoteTypeId"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <OcrLogs
      v-if="can(permissionsEnum.API_LOG_VIEW)"
      :type="modelClass"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Cyber'"
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
