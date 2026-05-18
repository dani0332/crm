<script setup>
import {
  applyEmiratesNumberMasking,
  useIsQuoteCreatedAfterCutoff,
} from '@/inertia/Composables/utilities.js';

import BorLogsSection from '@/inertia/Components/Bor/BorLogsSection.vue';
import CustomerAcceptanceLogsSection from '@/inertia/Components/CustomerAcceptanceLogs/Section.vue';
import LeadHistorySection from '@/inertia/Components/LeadHistorySection.vue';
import OcrLogs from '@/inertia/Components/OcrLogs.vue';
import OcrNotification from '@/inertia/Components/OcrNotification.vue';
import MemberDetails from '../../Components/MemberDetails.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import LazyAvailablePlan from '../HomeQuote/Partials/AvailablePlans.vue';

const props = defineProps({
  quote: Object,
  quoteType: String,
  quoteTypeId: Number,
  leadStatuses: Array,
  advisors: Array,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  modelType: String,
  notProductionApproval: Boolean,
  allowedDuplicateLOB: Array,
  can: Object,
  isBetaUser: Boolean,
  payments: Array,
  quoteRequest: Object,
  permissions: Object,
  paymentMethods: Object,
  insuranceProviders: Array,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  quoteDocuments: Object,
  documentTypes: Object,
  noteDocumentType: Object,
  quoteNotes: Object,
  vatPercentage: Number,
  paymentTooltipEnum: Object,
  bookPolicyDetails: Array,
  isNewPaymentStructure: Boolean,
  emailStatuses: Array,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  amlStatusName: String,
  paymentGatewayEnum: Array,
  isFuncsEnabled: Array,
  quoteStatuses: Object,
  homeCutOffDate: String,
});

const page = usePage();
const { isRequired, emiratesNumber } = useRules();
const notification = useNotifications('toast');
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;
const hasRole = role => useHasRole(role);
const permissionEnum = page.props.permissionsEnum;
const canAny = permissions => useCanAny(permissions);
const paymentStatusEnum = page.props.paymentStatusEnum;
const quoteStatusEnum = page.props.quoteStatusEnum;
const can = permission => useCan(permission);
const modelClass = 'App\\Models\\PersonalQuote';
const modelClassHome = 'App\\Models\\HomeQuote';
const processingOCBEmailNB = ref(false);
const genericRequestEnum = page.props.genericRequestEnum;
const countDays = computed(() =>
  useDaysSinceStale(props.quoteRequest?.stale_at),
);
const compareDueDate = useCompareDueDate;

const modals = reactive({
  duplicate: false,
  activity: false,
  activityConfirm: false,
  sendConfirm: false,
});

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    if (props.quote.quote_status_id == quoteStatusEnum.PolicyBooked) {
      return true;
    }
    return false;
  }
  return (
    (props.quote.quote_status_id == quoteStatusEnum.TransactionApproved ||
      props.quote.quote_status_id == quoteStatusEnum.Lost) ??
    false
  );
});

const leadDuplicateForm = useForm({
  modelType: 'home',
  parentType: 'home',
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

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post('/quotes/createDuplicate', {
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

const confirmDeleteData = reactive({
  activity: null,
  contact: null,
});

const contactLoader = ref(false),
  activityActionEdit = ref(false),
  toggleLoader = ref(false);
const rules = {
  isRequired: v => !!v || 'This field is required',
};

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Home',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
  leadStatusForm.post(
    route('updateLeadStatus', {
      modelType: 'Home',
      QuoteUId: page.props.quote.id,
    }),
    {
      preserveScroll: true,
      onSuccess: response => {
        router.reload({ only: ['quoteRequest'] });
      },
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
    },
  );
};

const activityTable = [
  { text: 'Client Name', value: 'client_name' },
  { text: 'Lead Status', value: 'quote_status.text' },
  { text: 'Title', value: 'title' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Action', value: 'action' },
];

const activityForm = useForm({
  entityUId: page.props.quote.uuid,
  entityId: page.props.quote.id,
  modelType: 'Home',
  parentType: 'Home',
  quoteType: 2,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivityStatusUpdate = id => {
  activityForm.activity_id = id;
  activityForm.post(route('activities.updateStatus'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Lead Activity Done',
        position: 'top',
      });
    },
  });
};

const activityEdit = data => {
  activityActionEdit.value = true;
  modals.activity = true;
  activityForm.activity_id = data.id;
  activityForm.uuid = data.uuid;
  activityForm.title = data.title;
  activityForm.description = data.description;
  activityForm.due_date = data.due_date
    ? data.due_date.split(' ')[0].split('-').reverse().join('-') +
      'T' +
      data.due_date.split(' ')[1]
    : null;
  activityForm.assignee_id = data.assignee_id;
  activityForm.status = data.status;
};

const onActivitySubmit = isValid => {
  if (!isValid) return;
  if (activityActionEdit.value) {
    activityForm.post(route('activities.update.activity', activityForm.uuid), {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Activity Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  } else {
    activityForm.post(route('activities.create.activity'), {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  }
};

const activityDelete = id => {
  modals.activityConfirm = true;
  confirmDeleteData.activity = id;
};

const activityDeleteConfirmed = () => {
  router.post(
    route('activities.destroy', confirmDeleteData.activity),
    {
      isInertia: true,
      quote_uuid: page.props.quote.uuid,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.error({
          title: 'Activity Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activityConfirm = false;
      },
    },
  );
};

const dateToYMD = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    return `${year}-${month}-${day}`;
  }
  return '';
};

const policyDetails = useForm({
  premium: page.props.quote.premium,
  policy_number: page.props.quote.policy_number || '',
  policy_start_date: dateToYMD(page.props.quote.policy_start_date),
  policy_expiry_date: dateToYMD(page.props.quote.policy_expiry_date) || '',
  policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
  quote_status_id: page.props.quote.quote_status_id,
  canEdit:
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved &&
    page.props.notProductionApproval,
  editMode: false,
  modelType: page.props.modelType,
  quote_id: page.props.quote.id,
});

const isProfileUpdateAllow = computed(() => {
  return hasAnyRole([
    page.props.rolesEnum.PA,
    page.props.rolesEnum.OE,
    page.props.rolesEnum.NRA,
  ]);
});

const enabledCustomerType =
  page.props.quote?.latest_insured?.customer_type ??
  page.props.customerTypeEnum.Individual;
const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote?.insured?.customer_type || null,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,

  insured_first_name: page.props.quote?.latest_insured?.first_name || '',
  insured_last_name: page.props.quote?.latest_insured?.last_name || '',
  emirates_id_number: page.props.quote.emirates_id_number || null,
  emirates_id_expiry_date:
    page.props.quote?.customer?.emirates_id_expiry_date || null,

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
        tradeLicenseEntity.entity_id = response.id;
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
    .catch(err => {});
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
    .catch(err => {});
};

const readOnlyMode = reactive({
  isDisable: true,
});

onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);
  onLoadAvailablePlansData();
  window.addEventListener('ocr-notification', handleOcrNotification);
});

onUnmounted(() => {
  window.removeEventListener('ocr-notification', handleOcrNotification);
});

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);

const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

watch(
  () => page.props.quote.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      leadStatusForm.leadStatus = newValue;
    }
  },
);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();
const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  isAddUpdate.value = true;
};

const applyEmiratesIdNumMasking = emiratesId =>
  (customerProfileForm.emirates_id_number =
    applyEmiratesNumberMasking(emiratesId));

function capitalizeString(str) {
  if (!str) return 'N/A';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

const availablePlansTable = reactive({
  data: [],
  isLoading: false,
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plan Name', value: 'name' },
    { text: 'Insurer Quote No', value: 'insurerQuoteNo' },
    { text: 'Price', value: 'actualPremium' },
    { text: 'Total Price', value: 'discountPremium' },
    { text: 'Action', value: 'action' },
  ],
});

const homePlansIds = reactive({ ids: [] });
const selectedPlans = ref([]);
const exportLoader = ref(false);
const selectedPlanIds = computed(() => {
  return page.props.payments.length > 0
    ? page.props.payments.map(plan => plan.plan_id)
    : [];
});

const availableAllPlans = ref([]);

const onLoadAvailablePlansData = async () => {
  availablePlansTable.isLoading = true;
  const url = `/quotes/home/available-plans/${page.props.quote.uuid}`;
  const data = { jsonData: true };

  try {
    const response = await axios.post(url, data);

    if (response.status === 200) {
      const homePlans = response.data;
      availablePlansTable.data = homePlans.quotes.plans;
      availableAllPlans.value = homePlans.quotes.plans;
      homePlansIds.ids = homePlans.quotes.plans.map(plan => plan.id);
    }
  } catch (error) {
  } finally {
    availablePlansTable.isLoading = false;
  }
};

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => p.id),
  ).value;

  axios
    .post(route('homeManualPlanToggle', { quoteType: 'Home' }), {
      modelType: 'PersonalQuote',
      planIds: planIds,
      personal_quote_uuid: page.props.quote.uuid,
      toggle: toggle,
    })
    .then(response => {
      notification.success({
        title: 'Plans has been updated',
        position: 'top',
      });
      onLoadAvailablePlansData();
      router.reload({ preserveScroll: true });
    })
    .catch(error => {
      notification.error({ title: error, position: 'top' });
    })
    .finally(() => {
      toggleLoader.value = false;
      selectedPlans.value = [];
    });
};

const planDetails = ref(null);

const viewPlanDetailsLoader = ref({});

const getPlanDetails = async item => {
  if (!item?.id) {
    notification.error({
      title: 'Error',
      message: 'Invalid plan selected',
      position: 'top',
    });
    return;
  }

  viewPlanDetailsLoader.value[item.id] = true;

  try {
    if (!availableAllPlans.value || !Array.isArray(availableAllPlans.value)) {
      throw new Error('Plans data is not available or invalid');
    }

    const selectedPlan = availableAllPlans.value.find(
      plan => plan.id === item.id,
    );
    if (!selectedPlan) {
      throw new Error(`Plan with ID ${item.id} not found`);
    }

    const {
      name = '',
      providerCode = '',
      providerName = '',
      actualPremium = '',
      discountPremium = '',
      benefits = {},
      isDisabled = false,
      isManualPlan = false,
      vat = '',
      insurerQuoteNo = '',
      isRatingAvailable = false,
      excess = '',
      policyWordings = [],
    } = selectedPlan;

    const {
      building = '',
      content = '',
      personalBelonging = '',
      contentAndPersonalBelonging = '',
      fineArtAndCollectible = '',
      jewelleryAndValuable = '',
      exclusion = [],
      additionalCover = [],
    } = benefits;

    planDetails.value = {
      listQuotePlanName: name,
      providerCode,
      providerName,
      actualPremium,
      discountPremium,
      listQuotePlanBenefitsInclusions: {
        building,
        contents: content,
        personalBelongings: personalBelonging,
        contentsAndPersonalBelongings: contentAndPersonalBelonging,
        'Fine Art And Collectible(s)': fineArtAndCollectible,
        'Jewellery And Valuable(s)': jewelleryAndValuable,
      },
      listQuotePlanBenefitsExclusions: exclusion,
      listQuotePlanBenefitsAditionalCovers: additionalCover,
      is_disabled: isDisabled,
      is_manual_update: isManualPlan,
      vat,
      insurer_quote_no: insurerQuoteNo,
      isRatingAvailable,
      excess,
      id: item.id,
      listQuotePlanBenefitsPolicyDetails: policyWordings,
      listQuotePlanBenefitsPolicyDetailLink: policyWordings[0]?.link || '',
      permissionsEnum: permissionEnum,
    };
    modals.planDetails = true;
  } catch (error) {
    notification.error({
      title: 'Error',
      message: error.message || 'Failed to fetch plan details',
      position: 'top',
    });
  } finally {
    viewPlanDetailsLoader.value[item.id] = false;
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

const onExportPlans = () => {
  if (selectedPlans.value.length < 1 || selectedPlans.value.length > 5) {
    notification.error({
      title: 'Please select 1 to 5 plans to download PDF.',
      position: 'top',
    });
    return;
  }
  exportLoader.value = true;
  const planIds = selectedPlans.value.map(p => p.id);
  axios
    .post(
      '/api/v1/quotes/home/export-plans-pdf',
      { plan_ids: planIds, quote_uuid: page.props.quote.uuid },
      { responseType: 'json' },
    )
    .then(response => {
      const link = document.createElement('a');
      link.href = response.data.data;
      link.setAttribute('download', response.data.name);
      document.body.appendChild(link);
      link.click();
      notification.success({ title: 'Plans Exported', position: 'top' });
    })
    .catch(error => {
      const errorMsg =
        error.response?.data?.errors?.error?.[0] ||
        error.response?.data?.message ||
        'Failed to export PDF. Please try again.';
      notification.error({ title: errorMsg, position: 'top' });
    })
    .finally(() => {
      exportLoader.value = false;
    });
};

const selectedProviderPlan = ref({
  id: page.props?.quote?.plan_id,
  planName: page.props?.quote?.plans?.name,
  providerName: page.props?.quote?.plans?.providerName,
  premium: page.props?.quote?.plans?.premium,
});

const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.name;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
  onLoadAvailablePlansData();
};

const onMemberUpdated = async () => {
  await router.reload({
    preserveScroll: true,
    only: ['membersDetails'],
  });
  page.props.membersDetails = [...page.props.membersDetails];
};

const copyLink = () => {
  copy(page.props.planURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const getLookupValueText = (lookupKey, id, defaultValue = '') => {
  const lookUpData = page?.props?.quote?.lookUpData || {};
  const lookupArray = Array.isArray(lookUpData[lookupKey])
    ? lookUpData[lookupKey]
    : [];
  const matchedValue = lookupArray.find(item => item.id === id);
  return matchedValue?.text || defaultValue;
};

const emailTableColumns = reactive({
  columns: [
    { text: 'Id', value: 'id' },
    { text: 'Email Subject', value: 'email_subject' },
    { text: 'Email Address', value: 'email_address' },
    { text: 'Status', value: 'email_status' },
    { text: 'Reason', value: 'reason' },
    { text: 'Template Id', value: 'template_id' },
    { text: 'Customer Id', value: 'customer_id' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
  ],
});

const fullAddress = computed(() => {
  const address = page.props?.customerAddressData;
  if (!address) return null;

  const { office_number, floor_number, building_name, street, area, city, landmark } = address;
  const parts = [office_number, floor_number, building_name, street, area, city, landmark];
  if (parts.every(part => part == null)) return null;
  return parts.filter(part => part).join(', ');
});

const possessionTypeText = computed(() => {
  const id = page.props?.quote?.home_quote?.possession_type_id;
  if (!id || !page.props?.lookUpData?.possessionType?.length) return '';
  const matchedItem = page.props.lookUpData.possessionType.find(item => item.id === id);
  return matchedItem ? matchedItem.text : '';
});

const accommodationTypeText = computed(() => {
  const id = page.props?.quote?.home_quote?.accommodation_type_id;
  if (!id || !page.props?.lookUpData?.accommodationType?.length) return '';
  const matchedItem = page.props.lookUpData.accommodationType.find(item => item.id === id);
  return matchedItem ? matchedItem.text : '';
});

const ownerOccupancyText = computed(() => {
  const id = page.props?.quote?.home_quote?.owner_occupancy_type_id;
  if (!id || !page.props?.lookUpData?.ownerOccupancies?.length) return '';
  const matchedItem = page.props.lookUpData.ownerOccupancies.find(item => item.id === id);
  return matchedItem ? matchedItem.text : '';
});

const confirmSendEmail = () => {
  processingOCBEmailNB.value = true;
  axios
    .post(`/quotes/home/${page.props.quote.uuid}/send-email-ocb-nb`, { responseType: 'json' })
    .then(response => {
      notification.success({ title: response.data.success, position: 'top' });
    })
    .catch(() => {})
    .finally(() => {
      processingOCBEmailNB.value = false;
      modals.sendConfirm = false;
    });
};

const isPlanDetailEnabled = computed(() => false);

const shouldShowPlanDetailsSection = computed(() => {
  const cutoffDate = props.homeCutOffDate
    ? new Date(props.homeCutOffDate)
    : new Date('2025-04-10 21:30:00');

  if (useIsQuoteCreatedAfterCutoff(page.props.quote.created_at, cutoffDate)) {
    return false;
  }

  const homeQuote = page.props.quote?.home_quote;
  if (!homeQuote) return true;

  return !(
    !!homeQuote.accommodation_type_id &&
    !!homeQuote.possession_type_id &&
    (!!homeQuote.building_value || !!homeQuote.contents_value_id || !!homeQuote.personal_belongings_value_id)
  );
});

const ocrLoadingDocType = ref(null);
const ocrLoadingDocTypes = reactive(new Set());
const isDocTypeLoading = docType => ocrLoadingDocTypes.has(docType);
const hasOcrInProgress = computed(() => ocrLoadingDocTypes.size > 0);
const ocrDocumentTypeEnum = usePage().props.ocrDocumentTypeEnum;
const policyDetailReloadKey = ref(0);
const bookPolicyReloadKey = ref(0);

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

function handleOcrNotification(event) {
  const { docType, status, userId } = event.detail || {};
  const currentUserId = usePage().props.auth.user.id;
  if (userId !== currentUserId) return;

  const supportedDocTypes = [
    ocrDocumentTypeEnum?.TAX_INVOICE?.value,
    ocrDocumentTypeEnum?.TAX_INVOICE_RAISED_BY_BUYER?.value,
    ocrDocumentTypeEnum?.CERTIFICATE_OF_ISSUANCE?.value,
  ];

  if (status === 'start') {
    if (supportedDocTypes.includes(docType)) {
      ocrLoadingDocTypes.add(docType);
      ocrLoadingDocType.value = docType;
    }
  } else {
    if (supportedDocTypes.includes(docType)) {
      ocrLoadingDocTypes.delete(docType);
    }
    router.reload({
      onSuccess: () => {
        ocrLoadingDocType.value = null;
        ocrLoadingDocTypes.clear();
        policyDetailReloadKey.value++;
        bookPolicyReloadKey.value++;
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
    <Head title="Home Revival Detail" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Home Revival Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
        <x-button
          v-if="quote?.customer?.pcp_tag == true"
          size="sm"
          color="#BFA100"
          tag="div"
        >
          Private Client
        </x-button>
      </template>
      <template #default v-if="readOnlyMode.isDisable === true">
        <LeadNotes
          :documentType="noteDocumentType"
          :notes="quoteNotes"
          :modelType="modelType"
          :quote="quote"
        />
        <Link
          v-if="quote?.insly_id"
          :href="`/legacy-policy/${quote.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link :href="route('home-revival-quotes-list')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Home Revival List
          </x-button>
        </Link>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link
            v-if="!isDisabled"
            :href="route('home-revival-quotes-edit', quote.uuid)"
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
          <LeadEditBtnReuseTemplate :isDisabled="true" />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <LeadEditBtnReuseTemplate v-else />
      </template>
    </StickyHeader>
    <x-divider class="my-4" />

    <x-modal
      v-model="modals.duplicate"
      title="Duplicate Lead"
      size="md"
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
          :rules="[rules.isRequired]"
          placeholder="Select LOB For Duplication"
          class="w-full"
          multiple
        />
        <x-select
          v-model="leadDuplicateForm.lob_team_sub_selection"
          label="Reason"
          :rules="[rules.isRequired]"
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

    <!-- Home Details Section -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">Home Details</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4 mb-3" />
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
                <dt class="font-medium">FIRST NAME</dt>
                <dd>{{ quote.first_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST NAME</dt>
                <dd>{{ quote.last_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">EMAIL</dt>
                <dd>{{ quote.email }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">MOBILE</dt>
                <dd>{{ quote.mobile_no }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COVERAGE TYPE</dt>
                <dd>
                  {{
                    getLookupValueText(
                      'coverages',
                      quote?.home_quote?.coverage_type_id,
                    )
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ACCOMMODATION TYPE</dt>
                <dd>{{ accommodationTypeText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POSSESSION TYPE</dt>
                <dd>{{ possessionTypeText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">OWNER OCCUPANCY</dt>
                <dd>{{ ownerOccupancyText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDRESS</dt>
                <dd>{{ fullAddress ?? quote?.address ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS BUILDING</dt>
                <dd>{{ quote?.home_quote?.has_building ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BUILDING VALUE (AED)</dt>
                <dd>{{ quote?.home_quote?.building_value ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS CONTENTS</dt>
                <dd>{{ quote?.home_quote?.has_contents ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CONTENTS VALUE (AED)</dt>
                <dd>{{ quote?.home_quote?.contents_value_id ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS PERSONAL BELONGINGS</dt>
                <dd>
                  {{
                    quote?.home_quote?.has_personal_belongings ? 'Yes' : 'No'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PERSONAL BELONGINGS VALUE (AED)</dt>
                <dd>
                  {{ quote?.home_quote?.personal_belongings_value_id ?? '' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PREMIUM</dt>
                <dd>{{ quote.premium }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY NUMBER</dt>
                <dd>{{ quote.policy_number }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
                <dd>{{ quote.previous_quote_policy_number }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY EXPIRY DATE</dt>
                <dd>{{ quote.policy_expiry_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED AT</dt>
                <dd>{{ quote.created_at }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <!-- Quote Status -->
    <QuoteStatus
      :quote="quote"
      :quoteStatuses="leadStatuses"
      :advisors="advisors"
      :lostReasons="lostReasons"
      :modelType="modelType"
      :permissions="permissions"
      :lockLeadSectionsDetails="lockLeadSectionsDetails"
    />

    <!-- Available Plans -->
    <LazyAvailablePlan
      :uuid="quote.uuid"
      :quote="quote"
      :payments="payments"
      :permissions="permissions"
      :insurance-providers="insuranceProviders"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <!-- Payments -->
    <QuotePayments
      :quote="quote"
      :payments="payments"
      :paymentMethods="paymentMethods"
      :vatPercentage="vatPercentage"
      :paymentTooltipEnum="paymentTooltipEnum"
      :bookPolicyDetails="bookPolicyDetails"
      :isNewPaymentStructure="isNewPaymentStructure"
      :sendUpdateOptions="sendUpdateOptions"
      :sendUpdateLogs="sendUpdateLogs"
      :hasPolicyIssuedStatus="hasPolicyIssuedStatus"
      :linkedQuoteDetails="linkedQuoteDetails"
      :lockLeadSectionsDetails="lockLeadSectionsDetails"
      :paymentDocument="paymentDocument"
      :paymentGatewayEnum="paymentGatewayEnum"
      :isFuncsEnabled="isFuncsEnabled"
      :modelType="modelType"
      :quoteTypeId="quoteTypeId"
    />

    <!-- Activities -->
    <QuoteActivities
      :quote="quote"
      :activities="activities"
      :modelType="modelType"
    />

    <!-- Documents -->
    <QuoteDocuments
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      :documentTypes="documentTypes"
      :quoteDocuments="quoteDocuments"
      :modelType="modelType"
      :quoteTypeId="quoteTypeId"
    />

    <!-- Notes -->
    <QuoteNotes
      :quote="quote"
      :quoteNotes="quoteNotes"
      :noteDocumentType="noteDocumentType"
      :modelType="modelType"
    />

    <!-- Lead History -->
    <LeadHistorySection :quote="quote" :modelType="modelType" />

    <!-- BOR Logs -->
    <BorLogsSection :quote="quote" :modelType="modelType" />

    <!-- Customer Acceptance Logs -->
    <CustomerAcceptanceLogsSection :quote="quote" :modelType="modelType" />

    <!-- Member Details -->
    <MemberDetails
      :quote="quote"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :membersDetails="membersDetails"
      :customerTypeEnum="customerTypeEnum"
      :UBORelations="UBORelations"
      :UBOsDetails="UBOsDetails"
      :quoteTypeId="quoteTypeId"
      :modelType="modelType"
      @member-updated="onMemberUpdated"
    />

    <!-- Email Status -->
    <div v-if="emailStatuses?.length" class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible>
        <template #header>
          <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <DataTable
            :headers="emailTableColumns.columns"
            :items="emailStatuses"
            border-cell
            hide-rows-per-page
            hide-footer
          />
        </template>
      </Collapsible>
    </div>
  </div>
</template>
