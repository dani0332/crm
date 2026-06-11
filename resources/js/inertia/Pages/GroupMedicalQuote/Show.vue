<script setup>
import EntityRiskRatingScoreDetails from '../../Components/EntityRiskRatingScoreDetails.vue';
import MigratePayment from '../../Components/MigratePayment.vue';
import PaymentTableNew from '../../Components/PaymentTableNew.vue';
import BorLogsSection from '@/inertia/Components/Bor/BorLogsSection.vue';
import OcrNotification from '@/inertia/Components/OcrNotification.vue';
import OcrLogs from '@/inertia/Components/OcrLogs.vue';
import {
  notifyGmQuoteEmirateUpdated,
  useGmQuoteEmirateCrossTabListen,
} from '@/inertia/Composables/useGmQuoteEmirateCrossTabSync.js';

const props = defineProps({
  quote: Object,
  quoteDetails: Object,
  allowedDuplicateLOB: Array,
  genderOptions: Object,
  typeCode: String,
  lostReasons: Object,
  quoteStatuses: Object,
  customerAdditionalContacts: Array,
  customerTypeEnum: Object,
  companyTypes: Array,
  nationalities: Array,
  UBORelations: Array,
  UBOsDetails: Array,
  membersDetails: Array,
  memberRelations: Array,
  canAddBatchNumber: Boolean,
  documentTypes: Object,
  insuranceProviders: Object,
  vatPercentage: Number,
  paymentTooltipEnum: Object,
  paymentMethods: Array,
  isNewPaymentStructure: Boolean,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  permissions: Object,
  enums: Object,
  bookPolicyDetails: Array,
  payments: Array,
  isEmirateOfRegistrationLocked: Boolean,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  amlStatusName: String,
  paymentGatewayEnum: Array,
  isFuncsEnabled: Array,
  activities: Array,
  advisors: Array,
  gmCategoryIntakeDisplay: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
useGmQuoteEmirateCrossTabListen({
  quoteUuid: computed(() => page.props.quote?.uuid),
  quoteId: computed(() => page.props.quote?.id),
});
const notification = useToast();
const { isRequired } = useRules();
const leadSource = page.props.leadSource;
const genericRequestEnum = page.props.genericRequestEnum;
let countDays = ref(useDaysSinceStale(props.quote?.stale_at));

const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);
const hasRole = role => useHasRole(role);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const quoteStatusEnum = page.props.quoteStatusEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;

const permissionEnum = page.props.permissionsEnum;
const canAny = permissions => useCanAny(permissions);
const modelClass = 'App\\Models\\BusinessQuote';

const historyData = ref(null),
  historyLoading = ref(false);

const isDuplicateAllowed = computed(() => {
  return page.props.allowedDuplicateLOB.includes(page.props.typeCode);
});

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const dateFormatYMD = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';

const { copy } = useClipboard();

const gmEcommerceCopyLink = computed(
  () => page.props.gmEcommerceCopyLink ?? { enabled: false },
);
const isGmEcommerceCopyLinkDisabled = computed(
  () => !gmEcommerceCopyLink.value.enabled,
);
const gmEcommerceCopyLinkLoading = ref(false);

const onCopyGmEcommerceJourneyLink = () => {
  if (isGmEcommerceCopyLinkDisabled.value) {
    return;
  }
  gmEcommerceCopyLinkLoading.value = true;
  axios
    .post(route('amt.ecommerce-copy-link', page.props.quote.uuid))
    .then(res => {
      copy(res.data.url);
      notification.success({
        title:
          'Link copied to clipboard. Share it with the customer so they can continue their journey.',
        position: 'top',
      });
    })
    .catch(err => {
      const message =
        err.response?.data?.message ??
        'Could not copy the ecommerce link. Please try again.';
      notification.error({
        title: message,
        position: 'top',
      });
    })
    .finally(() => {
      gmEcommerceCopyLinkLoading.value = false;
    });
};

const natureOfCompanyActivityText = computed(
  () => props.quote?.business_activity?.name ?? '—',
);

const hasExistingGroupHealthInsurancePolicyText = computed(() => {
  const value = props.quote?.has_existing_group_policy;

  if (value === true || value === 1 || value === '1') {
    return 'Yes';
  }

  if (value === false || value === 0 || value === '0') {
    return 'No';
  }

  return '—';
});

const numberOfCategoriesDisplay = computed(() => {
  const saved = props.quote?.number_of_categories;
  if (saved != null && saved !== '') {
    return String(saved);
  }

  if (props.gmCategoryIntakeDisplay.length > 0) {
    return String(props.gmCategoryIntakeDisplay.length);
  }

  const intake = props.quote?.gm_category_intake;
  if (Array.isArray(intake) && intake.length > 0) {
    return String(intake.length);
  }

  return '0';
});

const categoryRows = computed(() => {
  if (props.gmCategoryIntakeDisplay.length > 0) {
    return props.gmCategoryIntakeDisplay;
  }

  const n =
    parseInt(props.quote?.number_of_categories) ||
    (Array.isArray(props.quote?.gm_category_intake)
      ? props.quote.gm_category_intake.length
      : 0);

  return Array.from({ length: n }, (_, i) => ({
    serial: i + 1,
    category_label: '—',
    existing_insurance_provider: 'N/A',
    existing_tpa: 'N/A',
    existing_network: 'N/A',
    existing_policy_renewal_date: 'N/A',
    number_of_people: '—',
  }));
});

const quotePlanTypeDisplay = computed(
  () => props.quote?.health_plan_type_text ?? '—',
);

const modals = reactive({
  duplicate: false,
  member: false,
  memberConfirm: false,
  doc: false,
  docConfirm: false,
  plan: false,
  createPlan: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
  customerEntityNotFound: false,
});

const leadDuplicateForm = useForm({
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  let data = {
    modelType: 'business',
    parentType: 'business',
    entityId: page.props.quote.id,
    entityCode: page.props.quote.code,
    entityUId: page.props.quote.uuid,
    lob_team: leadDuplicateForm.lob_team,
    lob_team_sub_selection: leadDuplicateForm.lob_team_sub_selection,
  };
  axios
    .post(route('createDuplicate'), data)
    .then(res => {
      modals.duplicate = false;
      notification.success({
        title: 'Lead duplicated successfully',
        position: 'top',
      });
    })
    .catch(err => {
      notification.error('Something went wrong');
    });
};

const leadStatusForm = useForm({
  modelType: 'Business',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quoteDetails.notes || null,
  lostReason: page.props.quoteDetails.lost_reason_id || null,
  current_quote_status_id: page.props.quote.quote_status_id || null,
});

const leadStatusOptions = computed(() => {
  return page.props.quoteStatuses.map(status => {
    var statusDisabled = false;
    // below status are not editable by advisor
    if (status.id == quoteStatusEnum.PaymentLinkSentToCustomer) {
      statusDisabled = !can(permissionsEnum.SUPER_LEAD_STATUS_CHANGE);
    }
    if (status.id == quoteStatusEnum.PaymentInitiated) {
      statusDisabled = !can(permissionsEnum.SUPER_LEAD_STATUS_CHANGE);
    }

    return {
      value: status.id,
      label: status.text,
      disabled: statusDisabled,
    };
  });
});

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Bussiness/${page.props.quote.id}/update-lead-status`,
    {
      preserveScroll: true,
      onError: errors => {
        notification.error({
          title: errors.value,
          position: 'top',
        });
      },
      onSuccess: response => {
        countDays.value = useDaysSinceStale(response.props.quote?.stale_at);
        // Optimized partial reload: only reload quote data, preserve state and scroll
        router.reload({
          only: ['quote', 'gmEcommerceCopyLink'],
          preserveState: true,
          preserveScroll: true,
        });
      },
    },
  );
};

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'business',
      recordId: page.props.quote.id,
    }),
  );
  const finalRes = await res.json();
  historyData.value = (Array.isArray(finalRes) ? finalRes : []).map(row => {
    const hasNewAdvisor =
      row.NewAdvisor != null && String(row.NewAdvisor).trim() !== '';

    const hasOldAdvisor =
      row.OldAdvisor != null && String(row.OldAdvisor).trim() !== '';

    const prefix = hasOldAdvisor ? 'Advisor Re-assigned' : 'Advisor Assigned';

    const advisorText = hasNewAdvisor
      ? hasOldAdvisor
        ? `${prefix}: ${row.OldAdvisor} → ${row.NewAdvisor}`
        : `${prefix}: ${row.NewAdvisor}`
      : '';

    return {
      ...row,
      NewNotes: advisorText
        ? row.NewNotes && String(row.NewNotes).trim() !== ''
          ? `${row.NewNotes} | ${advisorText}`
          : advisorText
        : (row.NewNotes ?? ''),
    };
  });
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];

const companyConcernOptions = [
  { label: 'Parent', value: 'Parent' },
  { label: 'Sub Entity', value: 'SubEntity' },
];

const companyTypeOptions = computed(() => {
  return page.props.companyTypes.map(comp_type => ({
    value: comp_type.code,
    label: comp_type.text,
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

const emirateOfRegistrationError = computed(() => {
  if (customerProfileForm.errors.emirate_of_registration_id) {
    return customerProfileForm.errors.emirate_of_registration_id;
  }
  const value = customerProfileForm.emirate_of_registration_id;
  const isEmpty =
    value === null || value === undefined || value === '' || value === false;
  if (
    enabledCustomerType === page.props.customerTypeEnum.Entity &&
    isEmpty &&
    !page.props.isEmirateOfRegistrationLocked
  ) {
    return 'Emirate of registration is required.';
  }
  return null;
});

const enabledCustomerType =
  page.props.quote.latest_insured?.customer_type ??
  page.props.customerTypeEnum.Entity;
const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: enabledCustomerType,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,
  insured_first_name:
    page.props.quote.latest_insured?.first_name ??
    page.props.quote.customer_insured_first_name ??
    '',
  insured_last_name:
    page.props.quote.latest_insured?.last_name ??
    page.props.quote.customer_insured_last_name ??
    '',
  emirates_id_number:
    (page.props.quote?.emirates_id_number ??
      page.props.quote?.customer.emirates_id_number) ||
    null,
  emirates_id_expiry_date:
    page.props.quote?.customer.emirates_id_expiry_date || null,

  entity_id: page.props.quote?.quote_request_entity_mapping?.entity_id ?? null,
  trade_license_no:
    page.props.quote?.latest_insured?.id_type ===
    genericRequestEnum.TRADE_LICENSE
      ? page.props.quote?.latest_insured?.id_number
      : null,
  company_name:
    page.props.quote.company_name ??
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
    page.props.quote?.emirate_of_registration_id ??
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.emirate_of_registration_id ??
    null,
});

const resolvedEmirateOfRegistrationId = computed(
  () =>
    page.props.quote?.quote_request_entity_mapping?.entity
      ?.emirate_of_registration_id ??
    page.props.quote?.emirate_of_registration_id ??
    null,
);

watch(resolvedEmirateOfRegistrationId, newVal => {
  if (newVal !== customerProfileForm.emirate_of_registration_id) {
    customerProfileForm.emirate_of_registration_id = newVal;
  }
});

const updateProfileDetails = isValid => {
  if (!isValid) return;

  customerProfileForm.post(route('update-customer-profile'), {
    preserveScroll: true,
    onSuccess: () => {
      notifyGmQuoteEmirateUpdated({
        quoteUuid: page.props.quote?.uuid,
        quoteId: page.props.quote?.id,
        source: 'gm-lead-profile',
      });
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

const loader = reactive({
  tradeSearch: false,
  tradeDetail: false,
});
const searchByTradeLicense = trigger => {
  loader.tradeSearch = true;
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
    })
    .finally(() => (loader.tradeSearch = false));
};

const linkEntity = () => {
  loader.tradeDetail = true;
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

        notifyGmQuoteEmirateUpdated({
          quoteUuid: page.props.quote?.uuid,
          quoteId: page.props.quote?.id,
          source: 'gm-link-entity',
        });
        notification.success({
          title: res.data.message,
          position: 'top',
        });
        entityDetailsFound.value = false;
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => (loader.tradeDetail = false));
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
  window.addEventListener('ocr-notification', handleOcrNotification);
});
onUnmounted(() => {
  window.removeEventListener('ocr-notification', handleOcrNotification);
});

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(
    uuid,
    quote_type_id,
    page.props.quote.business_type_of_insurance_id,
  );

// Optimized watcher: use computed for stable reference, reducing reactivity overhead
const quoteStatusId = computed(() => page.props.quote?.quote_status_id);
watch(
  quoteStatusId,
  (newValue, oldValue) => {
    if (newValue !== oldValue && newValue !== undefined) {
      leadStatusForm.leadStatus = newValue;
    }
  },
  { immediate: false, flush: 'post' },
);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();
const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved
  );
});

// OCR loader state for GM (align with Car/Home)
const ocrLoadingDocType = ref(null);
const ocrLoadingDocTypes = reactive(new Set());
const isDocTypeLoading = docType => ocrLoadingDocTypes.has(docType);
const hasOcrInProgress = computed(() => ocrLoadingDocTypes.size > 0);
const ocrDocumentTypeEnum = usePage().props.ocrDocumentTypeEnum;
// Check if all required policy fields are filled (moved from OcrNotification to avoid duplicates)
const checkRequiredPolicyFields = () => {
  const quote = usePage().props?.quote;
  if (!quote) return false;
  const requiredFields = [
    { field: 'policy_number', property: 'quote_policy_number' },
    { field: 'policy_start_date', property: 'quote_policy_start_date' },
    { field: 'policy_expiry_date', property: 'quote_policy_expiry_date' },
    { field: 'price_vat_applicable', property: 'price_vat_applicable' },
  ];
  return requiredFields.every(item => {
    const value = quote[item.field] || quote[item.property];
    return value !== null && value !== undefined && String(value).trim() !== '';
  });
};
// Reload keys for forced component re-renders after OCR
const policyDetailReloadKey = ref(0);
const bookPolicyReloadKey = ref(0);
function handleOcrNotification(event) {
  const { docType, status, userId } = event.detail || {};
  const currentUserId = usePage().props.auth.user.id;
  // Only process notifications for the current user
  if (userId !== currentUserId) {
    return;
  }
  // For 'start' status, add document type to loading set
  if (status === 'start') {
    const supportedDocTypes = [
      ocrDocumentTypeEnum?.TAX_INVOICE?.value,
      ocrDocumentTypeEnum?.TAX_INVOICE_RAISED_BY_BUYER?.value,
      ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
    ];
    if (supportedDocTypes.includes(docType)) {
      ocrLoadingDocTypes.add(docType);
      // Also set the old ref for backwards compatibility
      ocrLoadingDocType.value = docType;
    }
  } else {
    // For 'end' or 'fail' status, remove document type from loading set and reload data
    const supportedDocTypes = [
      ocrDocumentTypeEnum?.TAX_INVOICE?.value,
      ocrDocumentTypeEnum?.TAX_INVOICE_RAISED_BY_BUYER?.value,
      ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
    ];
    if (supportedDocTypes.includes(docType)) {
      ocrLoadingDocTypes.delete(docType);
    }
    router.reload({
      onSuccess: () => {
        // Clear both the old ref and the reactive Set for immediate UI update
        ocrLoadingDocType.value = null;
        ocrLoadingDocTypes.clear();
        policyDetailReloadKey.value++;
        bookPolicyReloadKey.value++;
        // Check policy fields completion after data reload (only for CERTIFICATE_OF_ISSUANCE)
        if (
          status === 'end' &&
          !event.detail?.error &&
          docType === ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value
        ) {
          const allFieldsFilled = checkRequiredPolicyFields();
          if (!allFieldsFilled) {
            notification.info({
              title: 'Some required fields are still missing in Policy details',
              position: 'top',
            });
          }
        }
      },
      preserveState: true,
      preserveScroll: true,
      only: ['payments', 'bookPolicyDetails', 'quote', 'quoteDocuments'],
    });
  }
}
</script>

<template>
  <div>
    <OcrNotification />
    <Head title="Group Medical Lead Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <div class="flex items-center gap-2">
        <h2 class="text-xl font-semibold">Group Medical Lead Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
      </div>
      <div></div>
      <div
        class="flex gap-2 mb-3 justify-end"
        v-if="readOnlyMode.isDisable === true"
      >
        <Link
          v-if="
            quoteDetails?.insly_id &&
            canAny([
              permissionsEnum.VIEW_LEGACY_DETAILS,
              permissionsEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="`/legacy-policy/${quoteDetails?.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <Link
          v-else-if="
            quote.source == leadSource.RENEWAL_UPLOAD &&
            quote.previous_quote_policy_number != null &&
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
        <x-button
          v-if="isDuplicateAllowed"
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
        >
          Duplicate Lead
        </x-button>
        <Link :href="route('amt.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Group Medical List
          </x-button>
        </Link>
        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link v-if="!isDisabled" :href="route('amt.edit', quote.uuid)">
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
            v-if="!can(permissionsEnum.canEditQuote)"
            :isDisabled="true"
          />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <template v-else>
          <LeadEditBtnReuseTemplate v-if="!can(permissionsEnum.canEditQuote)" />
        </template>
      </div>
    </div>
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
        <x-select
          v-model="leadDuplicateForm.lob_team"
          label="LOBs"
          :options="
            allowedDuplicateLOB.map(lob => ({
              value: lob,
              label: lob,
            }))
          "
          :rules="[isRequired]"
          placeholder="Select LOB For Duplication"
          class="w-full"
          multiple
        />
        <x-select
          v-model="leadDuplicateForm.lob_team_sub_selection"
          label="Reason"
          :rules="[isRequired]"
          class="w-full"
          :options="[
            { value: 'new_enquiry', label: 'New enquiry' },
            { value: 'record_only', label: 'Record purposes only' },
          ]"
        />
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click="modals.duplicate = false"
          >Cancel</x-button
        >
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">Lead Details</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
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
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      Ref-ID
                    </label>
                    <template #tooltip> Reference ID </template>
                  </x-tooltip>
                </div>
                <div>{{ quote.code }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ enabledCustomerType }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IM AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURER AML STATUS</dt>
                <dd>N/A</dd>
              </div>
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
                <dd class="break-words">{{ quote.company_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                <dd>{{ quote.next_followup_date }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSAPP CODE</dt>
                <dd>{{ quote.transapp_code }}</dd>
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
                <dt class="font-medium">ADDITIONAL NOTES</dt>
                <dd>{{ quote?.additional_notes || 'N/A' }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">OE/AE</dt>
                <dd>
                  {{ quote?.support_user?.name }}
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LOST REASON</dt>
                <dd>
                  {{ quote?.business_quote_request_detail?.lost_reason?.text }}
                </dd>
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
                <dt class="font-medium">NUMBER OF PEOPLE TO BE INSURED</dt>
                <dd>{{ quote.number_of_employees ?? 'N/A' }}</dd>
              </div>

            

              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      EMIRATE OF REGISTRATION
                    </label>
                    <template #tooltip>
                      This value is sourced from the Entity Profile.
                    </template>
                  </x-tooltip>
                </div>
                <div>{{ quote.emirate?.text ?? 'Not Assigned' }}</div>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BUSINESS INSURANCE TYPE</dt>
                <dd>Group Medical</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BRIEF DETAILS</dt>
                <dd class="break-words">{{ quote.brief_details }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY EXPIRY DATE</dt>
                <dd>{{ quote.policy_expiry_date }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RENEWAL BATCH</dt>
                <dd>{{ quote.renewal_batch }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">GENDER</dt>
                <dd>{{ genderText(quote.gender).value }}</dd>
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
              <div
                class="grid sm:grid-cols-2"
                v-if="linkedQuoteDetails.childLeadsCount == 1"
              >
                <div>
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
                </div>
                <div>
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
                </div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RENEWAL IMPORT CODE</dt>
                <dd>{{ quote.renewal_import_code }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DEVICE</dt>
                <dd>{{ quote.device }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PRICE</dt>
                <dd>{{ quote.premium }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
              </div>
            </dl>

            <x-divider class="my-6" />

            <div class="space-y-8">
              <section aria-labelledby="gm-quote-details-heading">
                <div
                  class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4"
                >
                  <h4
                    id="gm-quote-details-heading"
                    class="text-lg font-semibold text-primary-700"
                  >
                    Quote Details
                  </h4>
                  <div
                    v-if="can(permissionsEnum.GMQuoteCopyLink)"
                    class="flex flex-wrap items-center gap-2"
                  >
                    <x-tooltip v-if="!isGmEcommerceCopyLinkDisabled" placement="top">
                      <x-button
                        size="sm"
                        color="orange"
                        class="shrink-0 rounded-lg"
                        :loading="gmEcommerceCopyLinkLoading"
                        @click.prevent="onCopyGmEcommerceJourneyLink"
                      >
                        <span class="border-b border-dotted">Copy Link</span>
                      </x-button>
                      <template #tooltip>
                        Copy this link and send it to the customer so they
                        can resume and complete their application.
                      </template>
                    </x-tooltip>
                    <x-tooltip v-else placement="top">
                      <x-button
                        size="sm"
                        color="orange"
                        class="shrink-0 rounded-lg"
                        disabled
                      >
                        <span class="border-b border-dotted">Copy Link</span>
                      </x-button>
                      <template #tooltip>
                        Copy Link is unavailable after the lead is Transaction
                        Approved.
                      </template>
                    </x-tooltip>
                  </div>
                </div>
                <div
                  class="grid gap-x-15 gap-y-2 sm:grid-cols-2 text-xs text-gray-900"
                >
                  <div class="grid grid-cols-2">
                    <dt
                      class="font-medium uppercase tracking-wide text-gray-600 pb-1 flex gap-1"
                    >
                      <x-tooltip placement="bottom">
                        <span
                          class="cursor-help text-sm underline decoration-dotted decoration-primary-700"
                        >
                          NATURE OF COMPANY'S ACTIVITY
                        </span>
                        <template #tooltip>
                          Select the main business activity of the company. This
                          helps assess the risk profile for the group health
                          insurance.
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd>{{ natureOfCompanyActivityText }}</dd>
                  </div>

                  <div class="grid grid-cols-2">
                    <dt
                      class="font-medium uppercase tracking-wide text-gray-600 pb-1 ml-2"
                    >
                      <x-tooltip placement="bottom">
                        <span
                          class="cursor-help text-sm underline decoration-dotted decoration-primary-700"
                        >
                          WITH EXISTING GROUP HEALTH INSURANCE POLICY
                        </span>
                        <template #tooltip>
                          Indicate if the company currently has a group health
                          insurance policy in place with any provider
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd class="ml-2">{{ hasExistingGroupHealthInsurancePolicyText }}</dd>
                  </div>
                  <div class="grid grid-cols-2">
                    <dt
                      class="font-medium uppercase tracking-wide text-gray-600 pb-1"
                    >
                      <x-tooltip placement="bottom">
                        <span
                          class="cursor-help text-sm underline decoration-dotted decoration-primary-700"
                        >
                          NUMBER OF CATEGORIES
                        </span>
                        <template #tooltip
                          >Enter how many employee categories the group has. Categories usually differ by Benefits or salary band.
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd>{{ numberOfCategoriesDisplay }}</dd>
                  </div>
                  <div class="grid grid-cols-2">
                    <dt
                      class="font-medium uppercase tracking-wide text-gray-600 pb-1 ml-2"
                    >
                      <x-tooltip placement="bottom">
                        <span
                          class="cursor-help text-sm underline decoration-dotted decoration-primary-700"
                        >
                          PLAN TYPE
                        </span>
                        <template #tooltip>
                          Select the type of health insurance plan as defined
                          for this group or category.
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd class="ml-2">{{ quotePlanTypeDisplay }}</dd>
                  </div>
                </div>
              </section>

              <section aria-labelledby="gm-people-per-category-heading">
                <h4
                  id="gm-people-per-category-heading"
                  class="text-base font-semibold text-gray-900 mb-3"
                >
                  People to be insured per category
                </h4>
                <div
                  class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm"
                >
                  <table class="min-w-full border-collapse text-sm">
                    <thead>
                      <tr
                        class="bg-primary-600 text-left text-xs font-semibold uppercase tracking-wide text-white"
                      >
                        <th class="whitespace-nowrap px-3 py-3">S/No</th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Category
                            </span>
                            <template #tooltip>
                              Choose the specific employee category this record
                              refers to. Each category may have different plan
                              Benefits and limits.
                            </template>
                          </x-tooltip>
                        </th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Existing insurance provider
                            </span>
                            <template #tooltip>
                              Select the current health insurance provider for
                              this group.
                            </template>
                          </x-tooltip>
                        </th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Existing third party administrator
                            </span>
                            <template #tooltip>
                              Select the current TPA (Third Party Administrator)
                              managing claims and approvals for the existing
                              policy.
                            </template>
                          </x-tooltip>
                        </th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Existing network
                            </span>
                            <template #tooltip>
                              Select the current medical provider network
                              name/level under the existing policy.
                            </template>
                          </x-tooltip>
                        </th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Existing policy renewal date
                            </span>
                            <template #tooltip>
                              Enter the expiry date of the client's current
                              group health insurance policy as shown on the
                              policy schedule.
                            </template>
                          </x-tooltip>
                        </th>
                        <th class="whitespace-nowrap px-3 py-3">
                          <x-tooltip placement="bottom">
                            <span class="cursor-help underline decoration-dotted decoration-white">
                              Number of people
                            </span>
                            <template #tooltip>
                              Enter the total number of insured members in this
                              group/category (including employees and, if
                              applicable, their dependents).
                            </template>
                          </x-tooltip>
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white text-gray-900">
                      <tr
                        v-for="row in categoryRows"
                        :key="row.serial"
                        class="border-b border-gray-100 last:border-0"
                      >
                        <td class="px-3 py-3 align-top">{{ row.serial }}</td>
                        <td class="px-3 py-3 align-top font-medium">
                          {{ row.category_label }}
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">
                          {{ row.existing_insurance_provider }}
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">
                          {{ row.existing_tpa }}
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">
                          {{ row.existing_network }}
                        </td>
                        <td class="px-3 py-3 align-top text-gray-700">
                          {{ row.existing_policy_renewal_date }}
                        </td>
                        <td class="px-3 py-3 align-top tabular-nums">
                          {{ row.number_of_people }}
                        </td>
                      </tr>
                      <tr v-if="categoryRows.length === 0">
                        <td
                          colspan="7"
                          class="px-3 py-6 text-center text-gray-500"
                        >
                          No members are recorded per category yet.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
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
                enabledCustomerType == page.props.customerTypeEnum.Individual
                  ? 'Customer '
                  : 'Entity '
              }}
              Profile
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex mb-3 justify-end">
            <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
              KYC - Complete
            </x-tag>
            <x-tag color="amber" v-else> KYC - Pending </x-tag>
          </div>

          <x-form @submit="updateProfileDetails" :auto-focus="false">
            <div class="text-sm">
              <dl
                v-if="
                  enabledCustomerType === page.props.customerTypeEnum.Individual
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
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote?.nationality?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ dateFormatYMD(quote.dob) }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">GENDER</dt>
                  <dd>{{ genderText(quote.gender).value }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                  <dd>
                    {{
                      quote.customer.receive_marketing_updates ? 'Yes' : 'No'
                    }}
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES ID NUMBER</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.emirates_id_number"
                      placeholder="xxx-xxxx-xxxxxxx-x"
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
                      placeholder="EMIRATES ID EXPIRY DATE"
                      :disabled="!isProfileUpdateAllow"
                      :min-date="new Date()"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">PRIVATE CLIENT</dt>
                  <dd>{{ quote.customer?.pcp_tag_formatted }}</dd>
                </div>
                <RiskRatingScoreDetails
                  :quote="quote"
                  :modelType="'Business'"
                />
              </dl>
              <dl
                v-if="
                  enabledCustomerType === page.props.customerTypeEnum.Entity
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
                  <dd>{{ quote.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                  <dd>
                    {{
                      quote.customer.receive_marketing_updates ? 'Yes' : 'No'
                    }}
                  </dd>
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
                      class="mt-1"
                      :loading="loader.tradeSearch"
                      v-if="readOnlyMode.isDisable === true"
                    >
                      Search
                    </x-button>
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    <x-tooltip placement="bottom">
                      <span
                        class="cursor-help underline decoration-dotted decoration-gray-400"
                      >
                        EMIRATES OF REGISTRATION
                      </span>
                      <template #tooltip>
                        Defines the legal Emirate of registration of the entity
                        and is required.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-tooltip
                      v-if="props.isEmirateOfRegistrationLocked"
                      placement="top"
                      class="block w-full"
                    >
                      <x-select
                        v-model="customerProfileForm.emirate_of_registration_id"
                        :options="emiratesOptions"
                        class="w-full"
                        placeholder="SELECT EMIRATES OF REGISTRATION"
                        filterable
                        disabled
                        :rules="[isRequired]"
                        :error="emirateOfRegistrationError"
                        required
                      />
                      <template #tooltip>
                        Emirate of registration cannot be changed after the
                        policy is booked.
                      </template>
                    </x-tooltip>
                    <x-select
                      v-else
                      v-model="customerProfileForm.emirate_of_registration_id"
                      :options="emiratesOptions"
                      class="w-full"
                      placeholder="SELECT EMIRATES OF REGISTRATION"
                      filterable
                      :rules="[isRequired]"
                      :error="emirateOfRegistrationError"
                      required
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
                    <x-select
                      v-model="customerProfileForm.industry_type_code"
                      :options="companyTypeOptions"
                      placeholder="SELECT COMPANY TYPE"
                      class="w-full"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">ENTITY TYPE</dt>
                  <dd>
                    <x-select
                      :modelValue="customerProfileForm.entity_type_code"
                      :options="companyConcernOptions"
                      class="w-full"
                      placeholder="SELECT COMPANY CONCERN"
                      filterable
                      @update:modelValue="entityTypeChange($event)"
                    />
                  </dd>
                </div>
                <EntityRiskRatingScoreDetails
                  :quote="quote"
                  :modelType="'business'"
                />
              </dl>
              <div
                class="flex justify-end"
                v-if="readOnlyMode.isDisable === true"
              >
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
        <x-button
          size="sm"
          color="orange"
          @click.prevent="linkEntity"
          :loading="loader.tradeDetail"
          v-if="readOnlyMode.isDisable === true"
        >
          Link
        </x-button>
      </template>
    </x-modal>

    <UBODetails
      v-if="enabledCustomerType == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      quote_type="Business"
      :expanded="sectionExpanded"
    />

    <MemberDetails
      v-if="enabledCustomerType == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      quote_type="Business"
      :expanded="sectionExpanded"
    />

    <!-- Additional Contact -->
    <CustomerAdditionalContacts
      quoteType="Business"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
      :expanded="sectionExpanded"
      :quoteStatusId="quote?.quote_status_id"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Business"
      :quote="quote"
      :insly-id="quoteDetails?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
      :expanded="sectionExpanded"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-select
                  v-model="leadStatusForm.leadStatus"
                  label="Status"
                  :options="leadStatusOptions"
                  :disabled="
                    allowStatusUpdate || lockLeadSectionsDetails.lead_status
                  "
                  placeholder="Lead Status"
                  class="w-full"
                  filterable
                />
                <x-textarea
                  v-model="leadStatusForm.notes"
                  type="text"
                  label="Notes"
                  placeholder="Lead Notes"
                  class="w-full"
                  :disabled="
                    allowStatusUpdate || lockLeadSectionsDetails.lead_status
                  "
                />
              </div>
            </div>
            <div class="w-full md:w-2/3">
              <x-select
                v-if="leadStatusForm.leadStatus == quoteStatusEnum.Lost"
                v-model="leadStatusForm.lostReason"
                label="Lost Reason"
                :options="
                  lostReasons?.map(item => ({
                    value: item.id,
                    label: item.text,
                  }))
                "
                placeholder="Lost Reason is required"
                class="w-full"
                :error="leadStatusForm.errors.lostReason"
                :disabled="lockLeadSectionsDetails.lead_status"
              />
              <x-input
                type="text"
                v-model="quote.transaction_type_text"
                class="w-full"
                label="Transaction Type"
                :disabled="true"
              />
            </div>
          </div>
          <StatusUpdateButtonTemplate v-slot="{ isDisabled }">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
              :disabled="allowStatusUpdate || isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Change Status
            </x-button>
          </StatusUpdateButtonTemplate>
          <div class="flex justify-end">
            <x-tooltip
              v-if="lockLeadSectionsDetails.lead_status"
              placement="bottom"
            >
              <StatusUpdateButtonReuseTemplate :isDisabled="true" />
              <template #tooltip>
                The lead status cannot be manually updated once it has reached
                'Transaction Approved'
              </template>
            </x-tooltip>
            <StatusUpdateButtonReuseTemplate v-else />
          </div>
        </template>
      </Collapsible>
    </div>
    <PlanDetails
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="page.props.quoteType"
      :expanded="sectionExpanded"
      :vatPrice="vatPercentage"
    />

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="page.props.quoteType"
      :payments="quote.payments"
    />

    <PaymentTableNew
      v-if="isNewPaymentStructure"
      :quoteType="page.props.quoteType"
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
      :paymentStatusEnum="paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      quoteSubType="Group Medical"
      :bookPolicyDetails="bookPolicyDetails"
      :expanded="sectionExpanded"
      :paymentGatewayEnum="paymentGatewayEnum"
      :isFuncsEnabled="isFuncsEnabled"
      :isPlanDetailSectionEnabled="true"
    />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :key="policyDetailReloadKey"
      :quote="quote"
      modelType="Business"
      :expanded="sectionExpanded"
      :showOcrNotification="hasOcrInProgress || !!ocrLoadingDocType"
      :ocrLoadingDocType="ocrLoadingDocType"
      :ocrLoadingDocTypes="ocrLoadingDocTypes"
      :isDocTypeLoading="isDocTypeLoading"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :quote="quote"
      :insly-id="quoteDetails?.insly_id"
      :expanded="sectionExpanded"
      quoteType="Business"
      :bookPolicyDetails="bookPolicyDetails"
      :showOcrNotification="hasOcrInProgress || !!ocrLoadingDocType"
      :ocrLoadingDocType="ocrLoadingDocType"
      :ocrLoadingDocTypes="ocrLoadingDocTypes"
      :isDocTypeLoading="isDocTypeLoading"
    />

    <BorLogsSection
      :leadId="quote.id"
      lob="Business"
      :customerData="{
        customerType: quote.customer_type,
        firstName: quote.first_name,
        lastName: quote.last_name,
        companyName: quote.company_name,
        currentlyInsuredWith: quote.insurance_provider_id,
      }"
      :hasPolicyIssuedStatus="hasPolicyIssuedStatus"
      :insuranceProviders="insuranceProviders"
      :expanded="sectionExpanded"
      :documentTypes="documentTypes"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionEnum.VIEW_INSLY_BOOK_POLICY,
          permissionEnum.SEND_INSLY_BOOK_POLICY,
          permissionsEnum.VIEW_ALL_LEADS,
        ])
      "
      :key="bookPolicyReloadKey"
      :quote="quote"
      quoteType="Business"
      modelType="Group Medical"
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
    />

    <Activities
      :quote="quote"
      :quoteType="page.props.quoteType"
      :modelType="page.props.modelType"
      :advisors="advisors"
      :activities="activities"
      :expanded="sectionExpanded"
      :readOnlyMode="readOnlyMode"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div v-if="historyData === null" class="text-center py-3">
            <x-button
              size="sm"
              color="primary"
              outlined
              @click.prevent="onLoadHistoryData"
              :loading="historyLoading"
            >
              Load History Data
            </x-button>
          </div>
          <DataTable
            v-else
            table-class-name="compact"
            :headers="historyDataTable"
            :items="historyData || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="historyData.length < 15"
          />
        </template>
      </Collapsible>
    </div>

    <FtcEmailTrack
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
    />

    <OcrLogs
      v-if="can(permissionEnum.API_LOG_VIEW)"
      :type="modelClass"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <AuditLogs
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
      :showSourceColumn="true"
    />

    <AuditLogs
      :title="'KYC Audit Logs'"
      :type="'App\\Models\\InsuredKyc'"
      :id="quote?.insured?.insured_kyc?.id"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Business'"
      :uuid="$page.props.quote.uuid"
    ></lead-raw-data>
  </div>
</template>
