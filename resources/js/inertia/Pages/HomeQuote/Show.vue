<script setup>
import {
  applyEmiratesNumberMasking,
  useIsQuoteCreatedAfterCutoff,
} from '@/inertia/Composables/utilities.js';

import MemberDetails from '../../Components/MemberDetails.vue';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import BorLogsSection from '@/inertia/Components/Bor/BorLogsSection.vue';
import OcrNotification from '@/inertia/Components/OcrNotification.vue';
import OcrLogs from '@/inertia/Components/OcrLogs.vue';

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
  storageUrl: String,
  quoteNotes: Object,
  cdnPath: String,
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
  historyLoading = ref(false),
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

//activities
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

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'home',
      recordId: page.props.quote.id,
    }),
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];

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
    {
      text: 'Provider Name',
      value: 'providerName',
    },
    {
      text: 'Plan Name',
      value: 'name',
    },
    {
      text: 'Insurer Quote No',
      value: 'insurerQuoteNo',
    },
    {
      text: 'Price',
      value: 'actualPremium',
    },
    {
      text: 'Total Price',
      value: 'discountPremium',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const homePlansIds = reactive({
  ids: [],
});
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
  const data = {
    jsonData: true,
  };

  try {
    const response = await axios.post(url, data);

    if (response.status === 200) {
      // Assuming the normal plans are stored in `data` field
      const homePlans = response.data;

      // If you need to update the table and store the ids
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
    selectedPlans.value.map(p => {
      return p.id;
    }),
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

const planDetails = ref(null);

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

    const planDetailsData = {
      listQuotePlanName: name,
      providerCode,
      providerName,
      actualPremium,
      discountPremium,
      listQuotePlanBenefitsInclusions: {
        building: building,
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

    planDetails.value = planDetailsData;
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
  const planIds = selectedPlans.value.map(p => {
    return p.id;
  });
  axios
    .post(
      '/api/v1/quotes/home/export-plans-pdf',
      {
        plan_ids: planIds,
        quote_uuid: page.props.quote.uuid,
      },
      {
        responseType: 'json',
      },
    )
    .then(response => {
      const link = document.createElement('a');
      let fileName = response.data.name;
      link.href = response.data.data;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      notification.success({
        title: 'Plans Exported',
        position: 'top',
      });
    })
    .catch(error => {
      // Display error message in a toast notification
      if (error.response && error.response.data) {
        // For validation errors (422)
        if (error.response.status === 422 && error.response.data.errors) {
          const errorMessages = error.response.data.errors;
          // Display the first error message we find
          if (errorMessages.error && errorMessages.error.length > 0) {
            notification.error({
              title: errorMessages.error[0],
              position: 'top',
            });
          } else {
            // Find first error message in the object
            for (const key in errorMessages) {
              if (errorMessages[key] && errorMessages[key].length) {
                notification.error({
                  title: errorMessages[key][0],
                  position: 'top',
                });
                break;
              }
            }
          }
        } else if (error.response.data.message) {
          // For other types of errors with a message
          notification.error({
            title: error.response.data.message,
            position: 'top',
          });
        } else {
          // Generic error if no specific message found
          notification.error({
            title: 'Failed to export PDF. Please try again.',
            position: 'top',
          });
        }
      } else {
        // Fallback for network errors
        notification.error({
          title:
            'Failed to export PDF. Please check your connection and try again.',
          position: 'top',
        });
      }
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
      title: 'Link copied to clipboardd',
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

  if (!address) {
    return null; // Return null if customerAddressData is null or undefined
  }

  const {
    office_number,
    floor_number,
    building_name,
    street,
    area,
    city,
    landmark,
  } = address;

  const parts = [
    office_number,
    floor_number,
    building_name,
    street,
    area,
    city,
    landmark,
  ];

  // Check if all parts are null or undefined
  const allPartsAreNull = parts.every(part => part == null);

  if (allPartsAreNull) {
    return null;
  }

  // Filter out null or undefined parts and join the rest with comma and space
  return parts.filter(part => part).join(', ');
});

const possessionTypeText = computed(() => {
  const id = page.props?.quote?.home_quote?.possession_type_id;

  if (!id || !page.props?.lookUpData?.possessionType?.length) {
    return '';
  }

  const matchedItem = page.props.lookUpData.possessionType.find(
    item => item.id === id,
  );

  return matchedItem ? matchedItem.text : '';
});

const accommodationTypeText = computed(() => {
  const id = page.props?.quote?.home_quote?.accommodation_type_id;

  if (!id || !page.props?.lookUpData?.accommodationType?.length) {
    return '';
  }

  const matchedItem = page.props.lookUpData.accommodationType.find(
    item => item.id === id,
  );

  return matchedItem ? matchedItem.text : '';
});

const ownerOccupancyText = computed(() => {
  const id = page.props?.quote?.home_quote?.owner_occupancy_type_id;

  if (!id || !page.props?.lookUpData?.ownerOccupancies?.length) {
    return '';
  }

  const matchedItem = page.props.lookUpData.ownerOccupancies.find(
    item => item.id === id,
  );

  return matchedItem ? matchedItem.text : '';
});

const confirmSendEmail = () => {
  processingOCBEmailNB.value = true;
  axios
    .post(`/quotes/home/${page.props.quote.uuid}/send-email-ocb-nb`, {
      responseType: 'json',
    })
    .then(response => {
      processingOCBEmailNB.value = false;
      notification.success({
        title: response.data.success,
        position: 'top',
      });
    })
    .catch(error => {
      processingOCBEmailNB.value = false;
    })
    .finally(() => {
      processingOCBEmailNB.value = false;
      modals.sendConfirm = false;
    });
};

const viewPlanDetailsLoader = ref({});

const isPlanDetailEnabled = computed(() => {
  // TODO: Remove this after testing or when taking to Stage after BA confirmation
  // if (page.props.quote.source == page.props.leadSource.RENEWAL_UPLOAD) {
  //   return true;
  // }
  return false;
});

// New computed property to check lead date
const shouldShowPlanDetailsSection = computed(() => {
  // First check if lead is created before the cutoff date
  const cutoffDate = props.homeCutOffDate
    ? new Date(props.homeCutOffDate)
    : new Date('2025-04-10 21:30:00');

  if (useIsQuoteCreatedAfterCutoff(page.props.quote.created_at, cutoffDate)) {
    return false;
  }

  const homeQuote = page.props.quote?.home_quote;
  if (!homeQuote) {
    return true;
  }

  const hasAccommodationTypeId = !!homeQuote.accommodation_type_id;

  const hasCoverageTypeId = !!homeQuote.possession_type_id;

  const hasRequiredValueFields =
    !!homeQuote.building_value ||
    !!homeQuote.contents_value_id ||
    !!homeQuote.personal_belongings_value_id;

  return !(
    hasAccommodationTypeId &&
    hasCoverageTypeId &&
    hasRequiredValueFields
  );
});

// OCR loader state (align with Car implementation)
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
    <Head title="Home Detail" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Home Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
        <x-button
          v-if="quote?.customer.pcp_tag == true"
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
          :cdn="cdnPath"
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

        <Link :href="route('home-quotes-list')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Home List </x-button>
        </Link>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link
            v-if="!isDisabled"
            :href="route('home-quotes-edit', quote.uuid)"
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

    <!-- Home Ecom Details -->
    <div class="p-4 rounded shadow mb-6 bg-white">
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
          </div>
        </template>
      </Collapsible>
    </div>
    <!-- Home Ecom Details -->

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
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ enabledCustomerType ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPANY NAME</dt>
                <dd>{{ quote.company_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPANY ADDRESS</dt>
                <dd>{{ quote.company_address }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IM AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <!-- Reminder:: Insurer AML Status applies only to Travel and Car, so it shows as N/A otherwise. -->
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURER AML STATUS</dt>
                <dd>{{ capitalizeString(quote?.insurer_aml_status) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote.advisor?.name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ quote.created_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
              </div>
              <!-- Sub-source fields -->
<div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUB SOURCE</dt>
                <dd>{{ quote?.sub_source?.text || quote?.sub_source_id || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUB SOURCE OPTION</dt>
                <dd>{{ quote?.sub_source_option?.text || quote?.sub_source_options_id || 'N/A' }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PRIMARY REF ID</dt>
                <dd>{{ quote?.primary_ref_id || 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ quote.updated_at }}</dd>
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
                <dt class="font-medium">RENEWAL BATCH</dt>
                <dd>{{ quote.renewal_batch }}</dd>
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
                <dt class="font-medium">ADDITIONAL NOTES</dt>
                <dd>{{ quote.notes }}</dd>
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
                <dt class="font-medium">OWNERSHIP STATUS</dt>
                <dd>{{ possessionTypeText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF PROPERTY</dt>
                <dd>{{ accommodationTypeText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF OWNER'S OCCUPANCY</dt>
                <dd>{{ ownerOccupancyText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS CONTENTS</dt>
                <dd>
                  {{ quote?.home_quote?.contents_value_id ? 'Yes' : 'No' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CONTENTS AED</dt>
                <dd>
                  {{
                    getLookupValueText(
                      'contentValues',
                      quote?.home_quote?.contents_value_id,
                    )
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS BUILDING</dt>
                <dd>{{ quote?.home_quote?.building_value ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BUILDING AED</dt>
                <dd>{{ quote?.home_quote?.building_value }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">HAS PERSONAL BELONGINGS</dt>
                <dd>
                  {{
                    quote?.home_quote?.personal_belongings_value_id
                      ? 'Yes'
                      : 'No'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PERSONAL BELONGINGS AED</dt>
                <dd>
                  {{
                    getLookupValueText(
                      'personalBelongingValues',
                      quote?.home_quote?.personal_belongings_value_id,
                    )
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIM HISTORY</dt>
                <dd>
                  {{ quote?.home_quote?.has_claimed_losses ? 'Yes' : 'No' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                <dd>{{ quote.currently_insured_with_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                <dd>{{ quote.next_followup_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DETAILS</dt>
                <dd>{{ quote.details }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ quote.transaction_approved_at }}</dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="can(permissionEnum.VIEW_PCP)"
              >
                <dt class="font-medium">PC-QUALIFIED</dt>
                <dd>{{ quote.pc_qualified_formatted }}</dd>
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
                  enabledCustomerType == page.props.customerTypeEnum.Individual
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
                  <dd>{{ quote.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote.nationality?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ quote.dob }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">GENDER</dt>
                  <dd>{{ quote.gender }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                  <dd>{{ quote.receive_marketing_updates ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMIRATES ID NUMBER</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.emirates_id_number"
                      :rules="[isRequired, emiratesNumber]"
                      placeholder="xxx-xxxx-xxxxxxx-x"
                      @input="
                        applyEmiratesIdNumMasking(
                          customerProfileForm.emirates_id_number,
                        )
                      "
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
                  <dt class="font-medium">ADDRESS</dt>
                  <dd>{{ fullAddress }}</dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LOCATION AREA</dt>
                  <dd>{{ quote?.home_quote?.subArea?.text }}</dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">FLOOR AND VILLA/ APARTMENT NUMBER</dt>
                  <dd>{{ page?.props?.customerAddressData?.office_number }}</dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">VILLA/ BUILDING NAME</dt>
                  <dd>{{ page?.props?.customerAddressData?.building_name }}</dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">STREET NAME</dt>
                  <dd>{{ page?.props?.customerAddressData?.street }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">PRIVATE CLIENT</dt>
                  <dd>{{ quote.customer.pcp_tag_formatted }}</dd>
                </div>
                <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
              </dl>
              <dl
                v-if="enabledCustomerType == page.props.customerTypeEnum.Entity"
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
                    <x-select
                      v-model="customerProfileForm.emirate_of_registration_id"
                      :options="emiratesOptions"
                      class="w-full"
                      placeholder="SELECT EMIRATES OF REGISTRATION"
                      filterable
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
                      :options="industryTypeOptions"
                      class="w-full"
                      placeholder="SELECT INDUSTRY TYPE"
                      filterable
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">ENTITY TYPE</dt>
                  <dd>
                    <x-select
                      :modelValue="customerProfileForm.entity_type_code"
                      :options="[
                        { label: 'Parent', value: 'Parent' },
                        { label: 'Sub Entity', value: 'SubEntity' },
                      ]"
                      class="w-full"
                      placeholder="SELECT ENTITY TYPE"
                      filterable
                      @update:modelValue="entityTypeChange($event)"
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
      </dl>
      <template #actions>
        <x-button size="sm" color="orange" @click.prevent="linkEntity">
          Link
        </x-button>
      </template>
    </x-modal>

    <MemberDetails
      v-if="enabledCustomerType == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :quote_type="modelType"
      @memberUpdated="onMemberUpdated"
      :expanded="sectionExpanded"
    />

    <UBODetails
      v-if="enabledCustomerType == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="modelType"
      :expanded="sectionExpanded"
    />

    <CustomerAdditionalContacts
      quoteType="Home"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
      :expanded="sectionExpanded"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Home"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
      :expanded="sectionExpanded"
    />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :quote-status-enum="quoteStatusEnum"
    />

    <PlanDetails
      v-if="shouldShowPlanDetailsSection"
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :expanded="sectionExpanded"
      :vatPrice="vatPercentage"
      :isAddUpdate="isAddUpdate"
    />

    <div v-else class="p-4 rounded shadow mb-6 bg-white">
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
          <div class="flex mb-4 justify-end">
            <div class="flex gap-2 mb-4" v-if="readOnlyMode.isDisable === true">
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
                @click.prevent="copyLink"
              >
                Copy Link
              </x-button>
              <x-button
                v-if="selectedPlans.length > 0"
                size="sm"
                color="emerald"
                @click.prevent="onExportPlans"
                :loading="exportLoader"
              >
                Download PDF
              </x-button>
              <x-button
                @click.prevent="modals.sendConfirm = true"
                size="sm"
                color="orange"
                class="mr-2"
                :disabled="quote.advisor_id != $page.props.auth.user.id"
                v-if="
                  readOnlyMode.isDisable === true &&
                  quote.source != $page.props.leadSource.RENEWAL_UPLOAD
                "
              >
                Send OCB Email to Customer
              </x-button>
            </div>
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
                  :disable="processingOCBEmailNB"
                >
                  Cancel
                </x-button>
                <x-button
                  size="sm"
                  color="error"
                  :loading="processingOCBEmailNB"
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
              table-class-name="tablefixed"
              :headers="availablePlansTable.columns"
              :items="availablePlansTable.data || []"
              border-cell
              hide-rows-per-page
              :rows-per-page="15"
              :hide-footer="availablePlansTable.data.length < 15"
              @onLoadAvailablePlansData="onLoadAvailablePlansData"
            >
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

                  <x-tag
                    v-if="item.isRenewal"
                    size="xs"
                    color="success"
                    class="mt-0.5 text-[10px]"
                  >
                    Renewal Plan
                  </x-tag>

                  <x-tooltip>
                    <x-tag
                      v-if="item.puaType"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px]"
                    >
                      PUA
                    </x-tag>
                    <template #tooltip>
                      <span
                        >Pending Underwriter Approval (PUA) indicates that this
                        quote is prepared using our internal rating calculator.
                        Please contact the client to get the required documents,
                        to proceed with generating a quote on the insurer portal
                        and connect with the underwriter to obtain their
                        approval.</span
                      >
                    </template>
                  </x-tooltip>
                </div>
              </template>
              <template #item-name="item">
                <span class="text-primary-600 uppercase">{{ item.name }}</span>
              </template>
              <template #item-insurerQuoteNo="item">
                <span class="text-primary-600">{{ item.insurerQuoteNo }}</span>
              </template>
              <template #item-actualPremium="item">
                <span class="text-primary-600">
                  {{
                    item.actualPremium
                      ? parseFloat(item.actualPremium).toFixed(2)
                      : '0.00'
                  }}
                </span>
              </template>
              <template #item-discountPremium="item">
                <span class="text-primary-600">
                  {{
                    item.discountPremium + item.vat
                      ? parseFloat(item.discountPremium + item.vat).toFixed(2)
                      : '0.00'
                  }}
                </span>
              </template>
              <template #item-action="item">
                <div class="flex gap-2">
                  <x-button
                    size="xs"
                    color="primary"
                    outlined
                    @click.prevent="getPlanDetails(item)"
                    :loading="viewPlanDetailsLoader[item.id]"
                  >
                    View
                  </x-button>

                  <span>
                    <SelectPlan
                      v-if="selectedProviderPlan.id != item.id"
                      @update:selectedPlanChanged="handlePlanSelected"
                      :plan="item"
                      :quoteType="'Home'"
                      :uuid="quote.uuid"
                      :code="quote.code"
                      :plans="availablePlansTable.data || []"
                      :extraDetails="{
                        selectedPlansIds: [selectedProviderPlan?.id],
                      }"
                      :payments="payments"
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
            :title="`${planDetails?.providerName}`"
            show-close
            backdrop
          >
            <LazyAvailablePlan
              :plan="planDetails"
              @onLoadAvailablePlansData="
                () => {
                  onLoadAvailablePlansData();
                }
              "
            />
          </x-modal>
        </template>
      </Collapsible>
    </div>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="quoteType"
      :payments="payments"
    />

    <!-- Payments -->
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
      :paymentStatusEnum="paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="storageUrl"
      :bookPolicyDetails="bookPolicyDetails"
      :expanded="sectionExpanded"
      :paymentGatewayEnum="paymentGatewayEnum"
      :isFuncsEnabled="isFuncsEnabled"
      :isPlanDetailSectionEnabled="shouldShowPlanDetailsSection"
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
    <!-- Payments -->

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
      :key="policyDetailReloadKey"
      :quote="quote"
      modelType="home"
      :expanded="sectionExpanded"
      :policyIssuanceStatus="policyIssuanceStatus"
      :payments="payments"
      :showOcrNotification="hasOcrInProgress || !!ocrLoadingDocType"
      :ocrLoadingDocType="ocrLoadingDocType"
      :ocrLoadingDocTypes="ocrLoadingDocTypes"
      :isDocTypeLoading="isDocTypeLoading"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :modelType="quoteType"
      :insly-id="quote?.insly_id"
      :expanded="sectionExpanded"
      quote-type="Home"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BorLogsSection
      :leadId="quote.id"
      :lob="quoteType"
      :customerData="{
        customerType: quote.customer_type,
        firstName: quote.first_name,
        lastName: quote.last_name,
        companyName: quote.company_name,
        currentlyInsuredWith: quote.currently_insured_with,
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
          permissionEnum.VIEW_ALL_LEADS,
        ])
      "
      :key="bookPolicyReloadKey"
      :quote="quote"
      quoteType="home"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
      :showOcrNotification="hasOcrInProgress || !!ocrLoadingDocType"
      :ocrLoadingDocType="ocrLoadingDocType"
      :ocrLoadingDocTypes="ocrLoadingDocTypes"
      :isDocTypeLoading="isDocTypeLoading"
    />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="quote"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <DataTable
            table-class-name="tablefixed compact"
            :headers="emailTableColumns.columns"
            :items="emailStatuses || []"
            show-index
            border-cell
            hide-rows-per-page
            hide-footer
          >
          </DataTable>
        </template>
      </Collapsible>
    </div>

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />

    <FtcEmailTrack
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
    />

    <AuditLogs
      :id="$page.props.quote.id"
      :quote-type="quoteType"
      :quoteCode="$page.props.quote.code"
    />

    <AuditLogs
      :title="'KYC Audit Logs'"
      :type="'App\\Models\\InsuredKyc'"
      :id="quote?.latest_insured?.insured_kyc?.id"
    />

    <ApiLogs :type="modelClassHome" :id="$page.props?.quote?.home_quote?.id" />

    <OcrLogs
      v-if="can(permissionEnum.API_LOG_VIEW)"
      :type="modelClass"
      :id="$page.props?.quote?.id"
      :expanded="sectionExpanded"
    />

    <LeadHistory :quote="$page.props.quote" />

    <CustomerChatLogs
      :customerName="quote?.first_name + ' ' + quote?.last_name"
      :quoteId="quote.uuid"
      :quoteType="'HOME'"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Home'"
      :uuid="$page.props.quote.uuid"
    ></lead-raw-data>
  </div>
</template>
