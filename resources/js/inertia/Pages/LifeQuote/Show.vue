<script setup>
import {
  applyEmiratesNumberMasking,
  numberFormat,
  preventInvalidInputs,
  useIsQuoteCreatedAfterCutoff,
} from '@/inertia/Composables/utilities.js';
import { watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import MemberDetails from '../../Components/MemberDetails.vue';
import MigratePayment from '../../Components/MigratePayment.vue';
import PaymentTableNew from '../../Components/PaymentTableNew.vue';
import RiskRatingScoreDetails from '../../Components/RiskRatingScoreDetails.vue';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import CreatePlanVariant from './Partials/CreateVariant.vue';
import EditPlan from './Partials/EditPlan.vue';
import BorLogsSection from '@/inertia/Components/Bor/BorLogsSection.vue';
import CustomerAcceptanceLogsSection from '@/inertia/Components/CustomerAcceptanceLogs/Section.vue';
import LeadHistorySection from '@/inertia/Components/LeadHistorySection.vue';

const page = usePage();
const props = defineProps({
  quote: Object,
  quoteStatuses: Object,
  quoteType: String,
  quoteTypeId: Number,
  activities: Array,
  advisors: Array,
  customerAdditionalContacts: Array,
  allowedDuplicateLOB: Array,
  lostReasons: Array,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  can: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  documentTypes: Object,
  vatPercentage: Number,
  payments: Array,
  paymentTooltipEnum: Object,
  paymentMethods: Array,
  insuranceProviders: Array,
  permissions: Object,
  bookPolicyDetails: Array,
  isNewPaymentStructure: Boolean,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  amlStatusName: String,
  quoteNotes: Object,
  noteDocumentType: Array,
  modelType: String,
  cdnPath: String,
  ecomLifeInsuranceQuoteUrl: String,
  currencies: Array,
  lifeRiders: Array,
  paymentGatewayEnum: Array,
  isFuncsEnabled: Array,
  emailStatuses: Array,
  isBetaUser: Boolean,
  lifeCutOffDate: String,
});

const genericRequestEnum = page.props.genericRequestEnum;
const { isRequired, emiratesNumber } = useRules();
const notification = useNotifications('toast');
const leadSource = page.props.leadSource;
const modelClass = 'App\\Models\\LifeQuote';
const personalModelClass = 'App\\Models\\PersonalQuote';
const hasRole = role => useHasRole(role);
const permissionEnum = page.props.permissionsEnum;
const quoteStatusEnum = page.props.quoteStatuses;
const selectPlanLoader = ref({});
const emit = defineEmits(['success', 'error']);
const modals = reactive({
  duplicate: false,
  activity: false,
  activityConfirm: false,
  doc: false,
  docConfirm: false,
  createPlan: false,
  sendConfirm: false,
  createPlanVariant: false,
  editPlan: false,
});

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const { copy, copied } = useClipboard();
const loader = ref({
  link: false,
  download: false,
  exchangeRate: false,
});

const planExchangeRate = ref(page.props.quote?.life_quote?.exchange_rate ?? 0);
const isExchangeRateEditable = ref(false);

const selectedProviderPlan = page.props.quote.plan_id;
const selectedProviderPlanVersion =
  page.props?.quote?.quote_customer_plan?.plan?.version ?? 0;

const [AddPlanButtonTemplate, AddPlanButtonReuseTemplate] =
  createReusableTemplate();

const variantPlan = ref(null);
const selectedPlans = ref([]);
let selectedPlan = ref(null);

let ecomDetail = ref(null);
let riders = ref({});

const activeRiders = data => {
  if (!data) {
    return 'N/A';
  }
  return data
    .filter(item => item.active === true)
    .map(item => item.text)
    .join(', ');
};

const viewPlan = item => {
  selectedPlan = item;
  modals.editPlan = true;
};

// watch (
//   () => selectedPlan,
//    (editPlan) => {
//     if(editPlan){

//     }
//   }
// )

// plans
const planDataTable = ref();

const plansTable = reactive({
  isLoading: false,
  data: [],
  columns: [
    {
      text: 'Provider Name',
      value: 'providerName',
      sortable: true,
      fixed: true,
      width: 400,
    },
    {
      text: 'Plan',
      value: 'planName',
      width: 100,
    },
    {
      text: 'Variant',
      value: 'variantVersion',
      width: 100,
    },
    {
      text: 'Type of Plan',
      value: 'planTypeId',
      sortable: true,
    },
    {
      text: 'Insurer Quote Number',
      value: 'insurerQuoteNo',
    },
    {
      text: 'Price',
      value: 'totalPrice',
      sortable: true,
    },
    {
      text: 'Exchange Rate (%)',
      value: 'exchangeRate',
      sortable: true,
    },
    {
      text: 'Price in (AED)',
      value: 'priceInAED',
      sortable: true,
    },
    {
      text: 'Payment Frequency',
      value: 'paymentTerm',
      sortable: true,
    },
    {
      text: 'Currency',
      value: 'currency',
      sortable: true,
    },
    {
      text: 'Sum Assured',
      value: 'sumInsured',
      sortable: true,
    },
    {
      text: 'Policy Term (Years)',
      value: 'policyTerm',
      sortable: true,
    },
    {
      text: 'Total Annual Price',
      value: 'totalAnnualPremium',
      sortable: true,
    },
    {
      text: 'Total Annual Price (AED)',
      value: 'totalAnnualPremiumAED',
      sortable: true,
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const listQuotePlansFiltered = ref([]);

watchEffect(() => {
  listQuotePlansFiltered.value = plansTable.data
    .slice()
    .sort((a, b) => Number(!b.isHidden) - Number(!a.isHidden));
});

const computedListQuotePlans = computed(() => {
  return listQuotePlansFiltered.value;
});

const sendOCAEmail = () => {
  const hiddenPlans = selectedPlans.value.filter(plan => plan.isDisabled);
  if (hiddenPlans.length > 0) {
    notification.error({
      title: 'You cannot select a hidden plan',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }

  if (selectedPlans.value.length < 1) {
    notification.error({
      title: 'Minimum 1 plan should be selected',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }

  if (selectedPlans.value.length > 5) {
    notification.error({
      title: 'Maximum 5 plans can be selected',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }

  loader.value.link = true;

  // send email
  axios
    .post(route('life-quotes-send-oca-email'), {
      quote_uuid: page.props.quote.uuid,
      // Add the version to the plan ID to ensure each selected plan is uniquely identified
      plan_ids: selectedPlans.value.map(
        plan => plan.planId + '_v' + plan.version,
      ),
    })
    .then(res => {
      notification.success({
        title: res.data.message,
        position: 'top',
      });
      loader.value.link = false;
    })
    .catch(err => {
      console.log(err);
      notification.error({
        title: 'Something went wrong',
        position: 'top',
      });
      loader.value.link = false;
    });
};

const downloadComparisionPdf = () => {
  const hiddenPlans = selectedPlans.value.filter(plan => plan.isDisabled);
  if (hiddenPlans.length > 0) {
    notification.error({
      title: 'You cannot select a hidden plan',
      position: 'top',
    });
    loader.value.download = false;
    return;
  }

  if (selectedPlans.value.length < 1) {
    notification.error({
      title: 'Minimum 1 plan should be selected',
      position: 'top',
    });
    loader.value.download = false;
    return;
  }

  if (selectedPlans.value.length > 5) {
    notification.error({
      title: 'Maximum 5 plans can be selected',
      position: 'top',
    });
    loader.value.download = false;
    return;
  }

  loader.value.download = true;

  axios
    .post(
      route('life-quotes-download-comparision-pdf'),
      {
        quote_uuid: page.props.quote.uuid,
        // Add the version to the plan ID to ensure each selected plan is uniquely identified
        plan_ids: selectedPlans.value.map(
          plan => plan.planId + '_v' + plan.version,
        ),
      },
      {
        responseType: 'blob',
      },
    )
    .then(response => {
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;

      // Extract filename from response headers or use default
      const contentDisposition = response.headers['content-disposition'];
      let filename = 'Life Insurance Comparison Table.pdf';
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }

      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      loader.value.download = false;
      notification.success({
        title: 'PDF downloaded successfully',
        position: 'top',
      });
    })
    .catch(error => {
      console.error('Download error:', error);
      loader.value.download = false;
      notification.error({
        title: 'Error downloading PDF',
        position: 'top',
      });
    });
};

const getPaymentTermTitle = months => {
  const mapping = {
    12: 'Monthly',
    4: 'Quarterly',
    2: 'Semi-Annually',
    1: 'Annually',
    [-1]: 'Single Payment',
  };
  return mapping[months] || '';
};

const getTotalAnnualPremium = item => {
  if (!item) return 'N/A';

  const paymentTermTitle = getPaymentTermTitle(item.paymentTerm);
  const mapping = {
    Monthly: 12,
    Quarterly: 4,
    'Semi-Annually': 2,
    Annually: 1,
    'Single Payment': -1,
  };

  if (!paymentTermTitle || !mapping[paymentTermTitle]) return 'N/A';

  let price = 0;
  if (
    item.isApi &&
    item.instantPolicy &&
    item.paymentTerm === page.props.paymentTerms?.ANNUALLY
  ) {
    // metlife annually
    const discountPremium =
      item.discountPremium != null ? item.discountPremium : 0;
    const ridersPrice = item.ridersPrice != null ? item.ridersPrice : 0;
    price = discountPremium + ridersPrice;
  } else if (item.isApi && item.instantPolicy) {
    // metlife monthly, quarterly, semi-annually
    const actualPremium = item.actualPremium != null ? item.actualPremium : 0;
    const ridersPrice = item.ridersPrice != null ? item.ridersPrice : 0;
    price = actualPremium + ridersPrice;
  } else {
    // zurich & manual plan
    if (item.isApi) {
      if (item.actualPremium == null) return 'N/A';
      price = item.actualPremium;
    } else {
      if (item.totalPrice == null) return 'N/A';
      price = item.totalPrice;
    }
  }

  let value;

  if (item.isRateCalculator && mapping[paymentTermTitle] < 0) {
    /* separate handling for RC because it is mapped to -1 and mapping[paymentTermTitle] cant be used multiply correctly */
    value = price;
  } else {
    value = price * mapping[paymentTermTitle];
  }
  return numberFormat(value);
};

const getTotalAnnualPremiumAED = item => {
  if (item.currency !== 'AED' && selectedProviderPlan === item.planId) {
    const paymentTermTitle = getPaymentTermTitle(item.paymentTerm);
    const mapping = {
      Monthly: 12,
      Quarterly: 4,
      'Semi-Annually': 2,
      Annually: 1,
      'Single Payment': -1,
    };

    const premiumInAED =
      Math.round(
        item.isApi
          ? item.actualPremium * planExchangeRate.value * 100
          : item.totalPrice * planExchangeRate.value * 100,
      ) / 100;

    let totalAnnualPremiumAED;

    if (item.isRateCalculator && mapping[paymentTermTitle] < 0) {
      /* separate handling for RC because it is mapped to -1 and mapping[paymentTermTitle] cant be used multiply correctly */
      totalAnnualPremiumAED = premiumInAED;
    } else {
      totalAnnualPremiumAED = premiumInAED * mapping[paymentTermTitle];
    }

    return numberFormat(totalAnnualPremiumAED);
  } else if (item.currency === 'AED') {
    return getTotalAnnualPremium(item);
  }
  return 'N/A';
};

const onCopyText = text => {
  copy(text);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const onCreatePlan = () => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['plansTable.data'],
    onStart: () => {
      modals.createPlan = false;
    },
    onFinish: () => {
      onLoadAvailablePlansData();

      notification.success({
        title: 'Life Plan created successfully',
        position: 'top',
      });
    },
  });
};

const onCreateVariant = () => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['plansTable.data'],
    onStart: () => {
      modals.createPlanVariant = false;
    },
    onFinish: () => {
      notification.success({
        title: 'Plan Variant created successfully',
        position: 'top',
      });

      onLoadAvailablePlansData();
    },
  });
};

const addVariant = plan => {
  variantPlan.value = plan;
  modals.createPlanVariant = true;
};

const onPlanError = error => {
  notification.error({
    title: error?.message || 'An error occurred while processing your request',
    position: 'top',
  });
};

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);
const permissionsEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const canAny = permissions => useCanAny(permissions);

const leadDuplicateForm = useForm({
  modelType: 'life',
  parentType: 'life',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

//activities
const activityActionEdit = ref(false);
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
  modelType: 'Life',
  parentType: 'Life',
  quoteType: 2,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
});

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
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
  activityForm.due_date = useformatDateTimeForPicker(data.due_date);
  activityForm.assignee_id = data.assignee_id;
  activityForm.status = data.status;
  activityForm.quote_id = page.props.quote.id;
};

const onActivitySubmit = isValid => {
  if (!isValid) return;
  if (activityActionEdit.value) {
    activityForm.post(`/activities/${activityForm.uuid}/update`, {
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
    activityForm.post(`/activities/create-activity`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
        notification.success({
          title: 'Activity Added',
          position: 'top',
        });
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
  // `/activities/${confirmDeleteData.activity}/delete`,
  //   {
  //     isInertia: true,
  //     quote_uuid: page.props.quote.uuid,
  //   },
  router.post(route('activities.destroy', page.props.quote.uuid), {
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
  });
};

const modalsDuplicate = ref(false);
const openDuplicate = () => {
  modalsDuplicate.value = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post(route('createDuplicate'), {
    preserveScroll: true,
    onError: function onError(errors) {
      notification.error({
        title: errors[0],
        position: 'top',
      });
    },
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modalsDuplicate.value = false;
    },
  });
};

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.quoteStatuses.map(status => {
    let statusDisabled = false;
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

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved
  );
});

const leadStatusForm = useForm({
  modelType: 'Life',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.quote_detail?.notes || null,
  lostReason: page.props.quote.quote_detail?.lost_reason_id || null,
});

const onLeadStatus = () => {
  leadStatusForm.post(
    route('updateLeadStatus', {
      modelType: 'Life',
      QuoteUId: page.props.quote.id,
    }),
    {
      preserveScroll: true,
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
    },
  );
};

const isProfileUpdateAllow = computed(() => {
  return hasAnyRole([
    page.props.rolesEnum.PA,
    page.props.rolesEnum.OE,
    page.props.rolesEnum.NRA,
  ]);
});

const enabledCustomerType =
  page.props.quote.latest_insured?.customer_type ??
  page.props.customerTypeEnum.Individual;
const customerProfileForm = useForm({
  customer_id: page.props.quote.customer_id,
  customer_type: enabledCustomerType,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.quote.id,
  insured_first_name: page.props.quote?.latest_insured?.first_name || '',
  insured_last_name: page.props.quote?.latest_insured?.last_name || '',
  emirates_id_number: page.props.quote?.emirates_id_number || null,
  emirates_id_expiry_date:
    page.props.quote?.customer.emirates_id_expiry_date || null,

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

const shouldShowPlanDetailsSection = computed(() => {
  const cutoffDate = props.lifeCutOffDate
    ? new Date(props.lifeCutOffDate)
    : new Date('2025-07-25 12:00:00');

  if (useIsQuoteCreatedAfterCutoff(page.props.quote.created_at, cutoffDate)) {
    return false;
  }

  return true;
});

const handleDocumentNotification = event => {
  const { quoteUID, status } = event.detail;

  if (quoteUID === page.props.quote.uuid && status === 'success') {
    router.reload({
      preserveState: true,
      preserveScroll: true,
      only: ['quote'],
      onSuccess: () => {
        if (!shouldShowPlanDetailsSection.value) {
          onLoadAvailablePlansData();
        }
      },
    });
  }
};

onMounted(() => {
  if (!shouldShowPlanDetailsSection.value) {
    onLoadAvailablePlansData();
  }
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
  window.addEventListener('document-notification', handleDocumentNotification);
});

onUnmounted(() => {
  window.removeEventListener(
    'document-notification',
    handleDocumentNotification,
  );
});

const onLoadAvailablePlansData = async () => {
  let data = {
    jsonData: true,
  };
  let url = `/quotes/life/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      const planData = res?.data[0];

      const foundPlan = planData.find(
        plan =>
          plan.planId === selectedProviderPlan &&
          plan.version === selectedProviderPlanVersion &&
          !plan.isDisabled,
      );

      ecomDetail.value = foundPlan;

      plansTable.data = res.data.length > 0 ? res?.data[0] : [];
    })
    .catch(err => {
      console.log(err);
      notification.error({
        title: 'Error loading plans',
        position: 'top',
      });
    });
};

const confirmSendEmail = () => {
  loader.value.link = true;
};

const leadSourceEnum = page.props.leadSource;

const selectPlan = (planId, quoteId, version, planUuid, isUW) => {
  selectPlanLoader.value[planUuid] = true;
  axios
    .post('/personal-quotes/life-plan-selected', {
      planId: planId,
      quoteId: quoteId,
      version: version,
      isUW: isUW,
      callSource: leadSourceEnum?.IMCRM?.toLowerCase(),
    })
    .then(response => {
      selectPlanLoader.value[planUuid] = false;
      notification.success({
        title: 'Plan selected successfully',
        position: 'top',
      });
      emit('success');

      setTimeout(() => {
        location.reload();
      }, 2000);

      // onLoadAvailablePlansData()
    })
    .catch(error => {
      notification.error({
        title: error?.response?.data?.message ?? 'something went wrong',
        position: 'top',
      });
      console.log('error', error);
      emit('error');
      selectPlanLoader.value[planUuid] = true;
    });
};

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

const getBMITag = () => {
  const bmi = page.props.quote.life_quote?.bmi;

  const bmiRanges = [
    {
      min: 0,
      max: 15.99,
      text: 'High Risk-Underweight',
      color: 'red',
      bgColor: 'red-100',
    },
    {
      min: 16,
      max: 18.4,
      text: 'Low Risk-Underweight',
      color: 'yellow',
      bgColor: 'yellow-300',
    },
    {
      min: 18.41,
      max: 25,
      text: 'Normal',
      color: 'green',
      bgColor: 'green-200',
    },
    {
      min: 25.01,
      max: 30,
      text: 'Low Risk-Overweight',
      color: 'yellow',
      bgColor: 'yellow-300',
    },
    {
      min: 30.01,
      max: 40,
      text: 'Low Risk-Obese',
      color: 'yellow',
      bgColor: 'yellow-300',
    },
    {
      min: 40.01,
      max: Infinity,
      text: 'High Risk-Obese',
      color: 'red',
      bgColor: 'red-100',
    },
  ];

  const tag = bmiRanges.find(range => bmi >= range.min && bmi <= range.max);

  return tag || { text: 'Invalid BMI', color: 'gray' };
};

const applyEmiratesIdNumMasking = emiratesId =>
  (customerProfileForm.emirates_id_number =
    applyEmiratesNumberMasking(emiratesId));

const totalAnnualPrice = computed(() => {
  if (!ecomDetail.value) return 'N/A';

  const displayPrice = getEcomDisplayPrice(ecomDetail.value);
  const totalPrice =
    displayPrice * (page.props.quote?.life_quote?.payment_term ?? 1);

  return totalPrice;
});

const emailStatusesTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Id',
      value: 'id',
    },
    {
      text: 'Email Subject',
      value: 'email_subject',
    },
    {
      text: 'Email Address',
      value: 'email_address',
    },
    {
      text: 'Status',
      value: 'email_status',
    },
    {
      text: 'Reason',
      value: 'reason',
    },
    {
      text: 'Template Id',
      value: 'template_id',
    },
    {
      text: 'Customer Id',
      value: 'customer_id',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Updated At',
      value: 'updated_at',
    },
  ],
});

const emailStatusesTableColumns = computed(() => {
  return emailStatusesTable.columns.filter(column => {
    if (!page.props.isAdmin) {
      return column.value !== 'customer_id' && column.value !== 'template_id';
    }
    return column;
  });
});

const updateExchangeRate = item => {
  loader.value.exchangeRate = true;

  axios
    .post('/personal-quotes/life/update-exchange-rate', {
      quoteUID: page.props.quote.uuid,
      exchangeRate: planExchangeRate.value,
    })
    .then(response => {
      notification.success({
        title: 'Exchange rate updated successfully',
        position: 'top',
      });
      isExchangeRateEditable.value = false;

      setTimeout(() => {
        window.location.reload();
      }, 2000);
    })
    .catch(error => {
      notification.error({
        title: 'Failed to update exchange rate',
        position: 'top',
      });
    })
    .finally(() => {
      loader.value.exchangeRate = false;
    });
};

const enableExchangeRateEdit = () => {
  isExchangeRateEditable.value = true;
};

const getTotalAnnualPriceAED = () => {
  if (!ecomDetail.value) return 'N/A';

  const displayPrice = getEcomDisplayPrice(ecomDetail.value);
  const priceInAED =
    Math.round(displayPrice * planExchangeRate.value * 100) / 100;

  return numberFormat(
    priceInAED * (page.props.quote?.life_quote?.payment_term ?? 1),
  );
};

const showSelectedButton = item => {
  // Early return for invalid data
  if (!item?.planId) return false;

  // Get isUnderwritten from the currently selected plan
  const isUnderwritten =
    page.props?.quote?.quote_customer_plan?.plan?.isUnderwritten;

  const isDisabled = item?.isDisabled;
  const planId = item?.planId;
  const version = item?.version;

  return (
    selectedProviderPlan == planId &&
    selectedProviderPlanVersion == (version || 0) &&
    !isDisabled &&
    isUnderwritten === item?.isUnderwritten
  );
};

const insuranceProviderCodeEnum = page.props.insuranceProviderCodeEnum;
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;

const isMetLife = item => {
  if (!item) return false;

  return (
    item.providerCode === insuranceProviderCodeEnum?.MTL &&
    item.instantPolicy === true
  ); // only for metlife instant policy
};

const canSelectMetLifePlan = computed(() => {
  const quote = page.props.quote;
  if (!quote) return false;

  const isApplicationPending =
    quote.quote_status_id === page.props.quoteStatusEnum?.ApplicationPending;
  const hasHealthQuestionnaire =
    quote.documents?.some(
      doc =>
        doc.document_type_code ===
        documentTypeCodeEnum?.LIFE_HEALTH_QUESTIONNAIRE,
    ) ?? false;

  return isApplicationPending && hasHealthQuestionnaire;
});

const getDisplayPrice = item => {
  if (
    item.isApi &&
    item.instantPolicy &&
    item.paymentTerm === page.props.paymentTerms?.ANNUALLY
  ) {
    // metlife annually
    return item.discountPremium + item.ridersPrice;
  }

  if (item.isApi && item.instantPolicy) {
    // metlife monthly, quarterly, semi-annually
    return item.actualPremium + item.ridersPrice;
  }

  if (item.isApi && !item.instantPolicy) {
    // zurich
    return item.actualPremium;
  }

  // manual plan
  return item.totalPrice;
};

const getEcomDisplayPrice = item => {
  if (!item) return 0;

  const paymentTerm =
    item.paymentTerm ?? page.props.quote?.life_quote?.payment_term;

  if (
    item.isApi &&
    item.instantPolicy &&
    paymentTerm === page.props.paymentTerms?.ANNUALLY
  ) {
    // metlife annually
    return item.discountPremium + item.ridersPrice;
  }

  if (item.isApi && item.instantPolicy) {
    // metlife monthly, quarterly, semi-annually
    return item.actualPremium + item.ridersPrice;
  }

  if (item.isApi && !item.instantPolicy) {
    // zurich
    return item.actualPremium;
  }

  // manual plan
  return item.totalPrice;
};

const getDisplayPriceInAED = item => {
  if (!item) return 'N/A';

  if (
    item.isApi &&
    item.instantPolicy &&
    item.paymentTerm === page.props.paymentTerms?.ANNUALLY
  ) {
    // metlife annually
    const discountPremium =
      item.discountPremium != null ? item.discountPremium : 0;
    const ridersPrice = item.ridersPrice != null ? item.ridersPrice : 0;
    return numberFormat(discountPremium + ridersPrice);
  }

  if (item.isApi && item.instantPolicy) {
    // metlife monthly, quarterly, semi-annually
    const actualPremium = item.actualPremium != null ? item.actualPremium : 0;
    const ridersPrice = item.ridersPrice != null ? item.ridersPrice : 0;
    return numberFormat(actualPremium + ridersPrice);
  }

  if (item.isRateCalculator) {
    return item.totalPrice != null ? numberFormat(item.totalPrice) : 'N/A';
  }
  // zurich & manual plan
  return item.actualPremium != null ? numberFormat(item.actualPremium) : 'N/A';
};
</script>
<template>
  <div>
    <Head title="Life Quotes" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Life Detail</h2>
        <x-button
          v-if="quote?.customer?.pcp_tag == true"
          size="sm"
          color="#BFA100"
          tag="div"
        >
          Private Client
        </x-button>
      </template>
      <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
        <div class="flex gap-2" v-if="readOnlyMode.isDisable === true">
          <Link
            v-if="quote.life_quote_request_detail?.insly_id"
            :href="`/legacy-policy/${quote.life_quote_request_detail.insly_id}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
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
          <x-button
            class="ml-2"
            size="sm"
            color="#ff5e00"
            @click.prevent="openDuplicate"
          >
            Duplicate Lead
          </x-button>

          <LeadNotes
            :documentType="noteDocumentType"
            :notes="quoteNotes"
            :modelType="modelType"
            :quote="quote"
          />

          <Link
            v-if="can(permissionsEnum.LifeQuotesList)"
            :href="route('life-quotes-list')"
            preserve-scroll
          >
            <x-button size="sm" color="primary" tag="div">
              Life Quotes
            </x-button>
          </Link>
          <LeadEditBtnTemplate v-slot="{ isDisabled }">
            <Link
              v-if="!isDisabled"
              :href="route('life-quotes-edit', quote.uuid)"
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
                  permissionsEnum.LifeQuotesEdit,
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
                  permissionsEnum.LifeQuotesEdit,
                  permissionsEnum.VIEW_ALL_LEADS,
                ])
              "
            />
          </template>
        </div>
      </div>
    </StickyHeader>

    <x-modal
      v-model="modalsDuplicate"
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
          :options="
            allowedDuplicateLOB.map((lob, index) => ({
              value: lob,
              label: lob,
            }))
          "
          :rules="[isRequired]"
          placeholder="Select LOB For Duplication"
          class="w-full"
          multiple
          label="LOBs"
          required
        />
        <x-select
          v-model="leadDuplicateForm.lob_team_sub_selection"
          :rules="[isRequired]"
          class="w-full"
          :options="[
            { value: 'new_enquiry', label: 'New enquiry' },
            { value: 'record_only', label: 'Record purposes only' },
          ]"
          label="Reason"
          required
        />
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click="modalsDuplicate = false"
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
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote.advisor?.name }}</dd>
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
                <dt class="font-medium">TRANSAPP CODE</dt>
                <dd>{{ quote.quote_detail?.transapp_code }}</dd>
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
                <dt class="font-medium">NOTES</dt>
                <dd>{{ quote?.notes || 'N/A' }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LOST REASON</dt>
                <dd>
                  {{ quote.quote_detail?.lost_reason?.text }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PRICE</dt>
                <dd>{{ quote.premium }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY EXPIRY DATE</dt>
                <dd>{{ quote.policy_expiry_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
              </div>
              <template v-if="can(permissionEnum.VIEW_UTM_SECTION)">
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM SOURCE</dt>
                  <dd>{{ quote.utm_source }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM MEDIUM</dt>
                  <dd>{{ quote.utm_medium }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM CAMPAIGN</dt>
                  <dd>{{ quote.utm_campaign }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM CONTENT</dt>
                  <dd>{{ quote.utm_content }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UTM TERM</dt>
                  <dd>{{ quote.utm_term }}</dd>
                </div>
              </template>
              <div
                class="grid sm:grid-cols-2"
                v-if="can(permissionEnum.VIEW_PCP)"
              >
                <dt class="font-medium">PC-Qualified</dt>
                <dd>{{ quote.pc_qualified_formatted }}</dd>
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
                <dt class="font-medium">TYPE OF INSURANCE</dt>
                <dd>{{ quote.life_quote?.insurance_tenure?.text }}</dd>
              </div>
            </dl>
          </div>
          <hr class="mt-1 mb-1" />
          <div class="mt-2">
            <h3 class="font-semibold text-primary-800 text-lg mb-2">
              Quote Details
            </h3>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PURPOSE OF INSURANCE</dt>
                <dd>{{ quote.life_quote?.purpose_of_insurance?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TENURE OF COVER</dt>
                <dd>{{ quote.life_quote?.number_of_years?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENCY</dt>
                <dd>{{ quote.life_quote?.currency?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUM INSURED VALUE</dt>
                <dd>{{ numberFormat(quote.life_quote?.sum_insured_value) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDITIONAL INFORMATION</dt>
                <dd>{{ quote.life_quote?.others_info }}</dd>
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
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ quote.dob }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">AGE</dt>
                  <dd>{{ quote.life_quote?.age }}</dd>
                </div>

                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">GENDER</dt>
                  <dd>{{ quote.gender }}</dd>
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
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ quote.nationality?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">HEIGHT</dt>
                  <dd>{{ quote.life_quote?.height }} CM</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">WEIGHT</dt>
                  <dd>{{ quote.life_quote?.weight }} KG</dd>
                </div>
                <div
                  class="grid sm:grid-cols-2"
                  v-if="quote.life_quote?.age && quote.life_quote?.age >= 20"
                >
                  <dt class="font-medium">BMI</dt>
                  <dd>
                    {{ quote.life_quote?.bmi }}
                    <span
                      :class="`inline-block px-2 py-1 text-xs font-medium rounded-full bg-${getBMITag().bgColor} text-${getBMITag().color}-800`"
                    >
                      {{ getBMITag().text }}
                    </span>
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">MARITAL STATUS</dt>
                  <dd>{{ quote.life_quote?.marital_status?.text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <x-tooltip position="left">
                    <dt class="font-medium">
                      HAVE YOU CONSUMED ANY PRODUCTS WITH<br />NICOTINE FOR THE
                      PAST 12 MONTHS?
                    </dt>
                    <template #tooltip>
                      <div class="whitespace-normal text-xs">
                        Nicotine-containing products cover a range of items such
                        as tobacco, sisha, vape, nicotine gums, and related
                        products.
                      </div>
                    </template>
                  </x-tooltip>
                  <dd>{{ quote.life_quote?.is_smoker == 1 ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">PRIVATE CLIENT</dt>
                  <dd>{{ quote.customer.pcp_tag_formatted }}</dd>
                </div>
                <RiskRatingScoreDetails :quote="quote" :modelType="'Life'" />
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
      <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4 break-words">
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
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <UBODetails
      v-if="enabledCustomerType == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <CustomerAdditionalContacts
      quoteType="Life"
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
      modelType="Life"
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
      :quote-status-enum="page.props.quoteStatusEnum"
    />

    <PlanDetails
      v-if="shouldShowPlanDetailsSection"
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :vatPrice="vatPercentage"
      :expanded="sectionExpanded"
      :isAddUpdate="isAddUpdate"
    />

    <template v-else>
      <div class="p-4 rounded shadow mb-6 bg-white">
        <Collapsible :expanded="sectionExpanded">
          <template #header>
            <div class="flex flex-wrap gap-4 justify-between items-center">
              <h3 class="font-semibold text-primary-800 text-lg">
                Available Plans
                <x-tag size="sm">{{
                  listQuotePlansFiltered.length || 0
                }}</x-tag>
              </h3>
            </div>
          </template>

          <template #body>
            <x-divider class="my-4" />
            <div class="flex flex-wrap gap-3 justify-end mb-3">
              <x-button
                size="sm"
                @click.prevent="downloadComparisionPdf"
                :loading="loader.download"
                color="emerald"
                v-if="selectedPlans.length > 0"
              >
                Download PDF Comparison
              </x-button>

              <x-button
                @click.prevent="sendOCAEmail"
                size="sm"
                :loading="loader.link"
                color="orange"
                :disabled="doesEmailStatusExist || isOcaButtonDisabled"
                v-if="readOnlyMode.isDisable === true"
              >
                Send OCA Email to Customer
              </x-button>
              <x-button
                v-if="plansTable.data.length > 0"
                size="sm"
                color="orange"
                @click.prevent="
                  onCopyText(ecomLifeInsuranceQuoteUrl + quote.uuid)
                "
              >
                Copy Link
              </x-button>
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
                    >
                      Cancel
                    </x-button>
                    <x-button
                      size="sm"
                      color="error"
                      @click.prevent="confirmSendEmail"
                      :loading="loader.link"
                    >
                      Send
                    </x-button>
                  </div>
                </template>
              </x-modal>

              <AddPlanButtonTemplate v-slot="{ isDisabled }">
                <x-button
                  size="sm"
                  color="emerald"
                  @click.prevent="modals.createPlan = true"
                  :disabled="isDisabled"
                >
                  Add Plan
                </x-button>
              </AddPlanButtonTemplate>

              <x-tooltip
                v-if="page.props.lockLeadSectionsDetails.plan_selection"
                position="left"
                align="center"
                class="yoyo-tip"
              >
                <AddPlanButtonReuseTemplate :isDisabled="true" />
                <template #tooltip>
                  <div class="whitespace-normal text-xs">
                    No further actions can be taken on an issued policy. For
                    changes, such as a change in insurer, go to 'Send Update',
                    select 'Add Update', and choose 'Cancellation from inception
                    and reissuance.
                  </div>
                </template>
              </x-tooltip>
              <AddPlanButtonReuseTemplate v-else />

              <DataTable
                ref="planDataTable"
                v-model:items-selected="selectedPlans"
                table-class-name="tablefixed compact"
                :headers="plansTable.columns"
                :items="computedListQuotePlans || []"
                border-cell
                hide-rows-per-page
                :rows-per-page="15"
                class="flex-wrap"
                :hide-footer="computedListQuotePlans.length < 15"
              >
                <template #item-totalPrice="item">
                  <span class="copay-max">{{
                    numberFormat(getDisplayPrice(item))
                  }}</span>
                </template>

                <template #item-sumInsured="item">
                  <span class="copay-max">{{
                    numberFormat(item.sumInsured)
                  }}</span>
                </template>

                <template #item-planTypeId="item">
                  <span class="copay-max">{{ item.planType }}</span>
                </template>

                <template #item-variantVersion="item">
                  <span v-if="item.version" class="copay-max"
                    >v.{{ item.version }}</span
                  >
                </template>

                <template #item-paymentTerm="item">
                  <span class="copay-max">{{
                    getPaymentTermTitle(item.paymentTerm)
                  }}</span>
                </template>

                <template #item-exchangeRate="item">
                  <div
                    v-if="
                      item.currency != 'AED' &&
                      selectedProviderPlan == item.planId &&
                      selectedProviderPlanVersion == (item.version || 0)
                    "
                    class="flex items-center gap-2"
                  >
                    <div class="flex-1">
                      <x-input
                        v-model="planExchangeRate"
                        type="text"
                        class="w-full"
                        :disabled="!isExchangeRateEditable"
                        @keydown="e => preventInvalidInputs(e, false, true)"
                        placeholder="Exchange rate"
                      />
                    </div>
                    <x-button
                      v-if="!isExchangeRateEditable"
                      size="xs"
                      color="blue"
                      @click="enableExchangeRateEdit"
                    >
                      Edit
                    </x-button>
                    <x-button
                      v-if="isExchangeRateEditable"
                      size="xs"
                      color="emerald"
                      :loading="loader.exchangeRate"
                      :disabled="loader.exchangeRate"
                      @click="updateExchangeRate(item)"
                    >
                      Update
                    </x-button>
                  </div>
                </template>

                <template #item-priceInAED="item">
                  <div
                    v-if="
                      item.currency != 'AED' &&
                      selectedProviderPlan == item.planId &&
                      selectedProviderPlanVersion == (item.version || 0)
                    "
                    class="copay-max"
                  >
                    <div v-if="planExchangeRate != 0 && item.currency != 'AED'">
                      {{
                        numberFormat(
                          Math.round(
                            item.isApi
                              ? item.actualPremium * planExchangeRate * 100
                              : item.totalPrice * planExchangeRate * 100,
                          ) / 100,
                        )
                      }}
                    </div>

                    <div v-else>N/A</div>
                  </div>
                  <div v-else-if="item.currency != 'AED'" class="copay-max">
                    N/A
                  </div>
                  <div v-else class="copay-max">
                    {{ getDisplayPriceInAED(item) }}
                  </div>
                </template>

                <template #item-totalAnnualPremium="item">
                  <span class="copay-max">{{
                    item.isManualPlan
                      ? getTotalAnnualPremium(item)
                      : getTotalAnnualPremium(item)
                  }}</span>
                </template>

                <template #item-totalAnnualPremiumAED="item">
                  <span class="copay-max">{{
                    getTotalAnnualPremiumAED(item)
                  }}</span>
                </template>

                <template #item-providerName="{ providerName, isDisabled }">
                  <p>
                    {{ providerName }}
                  </p>
                  <div class="flex gap-1">
                    <x-tag
                      v-if="isDisabled"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px]"
                    >
                      Hidden
                    </x-tag>
                  </div>
                </template>

                <template
                  #item-planName="{
                    planName,
                    isUnderwritten,
                    isManualPlan,
                    isApi,
                    isRateCalculator,
                  }"
                >
                  <p>
                    {{ planName }}
                  </p>
                  <div class="flex gap-1">
                    <x-tag
                      v-if="isUnderwritten"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px] bg-green-300 text-green-800 font-semibold px-2 py-1 rounded-md"
                    >
                      UW
                    </x-tag>
                    <x-tag
                      v-else-if="isManualPlan && !isApi"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px] bg-gray-200 text-gray-700 font-semibold px-2 py-1 rounded-md"
                    >
                      Manual
                    </x-tag>
                    <x-tag
                      v-else-if="isApi"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px] bg-orange-200 text-orange-700 font-semibold px-2 py-1 rounded-md"
                    >
                      API
                    </x-tag>
                    <x-tag
                      v-else-if="isRateCalculator"
                      size="xs"
                      color="error"
                      class="mt-0.5 text-[10px] bg-green-200 text-green-700 font-semibold px-2 py-1 rounded-md"
                    >
                      Rate Calculator
                    </x-tag>
                  </div>
                </template>

                <template #item-action="item">
                  <div class="flex gap-2 pr-2">
                    <x-button
                      size="xs"
                      color="primary"
                      outlined
                      @click.prevent="viewPlan(item)"
                    >
                      View
                    </x-button>
                    <x-button
                      v-if="!isMetLife(item)"
                      size="xs"
                      color="emerald"
                      outlined
                      @click.prevent="
                        onCopyText(
                          ecomLifeInsuranceQuoteUrl +
                            quote.uuid +
                            `/payment/?providerCode=${item.providerCode}&planId=${item.planId}&version=${item.version}`,
                        )
                      "
                    >
                      Copy
                    </x-button>
                    <span>
                      <x-button
                        v-if="showSelectedButton(item)"
                        size="xs"
                        color="orange"
                        outlined
                        :disabled="true"
                        >Selected</x-button
                      >

                      <x-tooltip
                        v-else-if="
                          !item.isDisabled &&
                          isMetLife(item) &&
                          !canSelectMetLifePlan
                        "
                        placement="top"
                      >
                        <x-button
                          size="xs"
                          color="emerald"
                          outlined
                          :disabled="true"
                        >
                          Select
                        </x-button>
                        <template #tooltip>
                          This plan cannot be manually selected. To proceed, you
                          can guide the client to click 'Buy Now'.
                        </template>
                      </x-tooltip>

                      <x-button
                        v-else-if="
                          !item.isDisabled &&
                          (!isMetLife(item) || canSelectMetLifePlan)
                        "
                        size="xs"
                        color="emerald"
                        outlined
                        :loading="selectPlanLoader[`${item._id}`]"
                        @click.prevent="
                          selectPlan(
                            item.planId,
                            page.props.quote.uuid,
                            item.version,
                            item._id,
                            item.isUnderwritten,
                          )
                        "
                      >
                        Select
                      </x-button>
                    </span>
                    <span v-if="!item.isUnderwritten">
                      <x-button
                        size="xs"
                        color="emerald"
                        @click.prevent="addVariant(item)"
                      >
                        Add Variant
                      </x-button>
                    </span>
                  </div>
                </template>
              </DataTable>
            </div>
          </template>
        </Collapsible>
      </div>
    </template>

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
                  {{
                    numberFormat(
                      getEcomDisplayPrice(ecomDetail) * planExchangeRate,
                    )
                  }}
                </dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="ecomDetail?.currency != 'AED'"
              >
                <dt class="font-medium uppercase">Total Annual Price AED</dt>
                <dd>
                  {{ getTotalAnnualPriceAED() }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Payment Term</dt>
                <dd>
                  {{
                    getPaymentTermTitle(quote?.life_quote?.payment_term) ??
                    'N/A'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">Authorised AT</dt>
                <dd>{{ quote?.payments[0]?.authorized_at ?? 'N/A' }}</dd>
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
                <dd>{{ quote?.payments[0]?.payment_method?.name }}</dd>
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
                <dd>{{ ecomLifeInsuranceQuoteUrl + quote.uuid }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">UNDER WRITTEN</dt>
                <dd v-if="ecomDetail == null">N/A</dd>
                <dd v-else>{{ ecomDetail?.isUnderwritten ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN SOURCE</dt>
                <dd v-if="ecomDetail == null">N/A</dd>
                <dd v-else>{{ ecomDetail?.isApi ? 'API' : 'Manual' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">VARIENT</dt>
                <dd>
                  {{ ecomDetail?.version ? 'V' + ecomDetail?.version : 'N/A' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT REFERENCE</dt>
                <dd>{{ quote?.life_quote?.payment_reference ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ORDER REFERENCE</dt>
                <dd>{{ quote?.order_reference ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDONS</dt>
                <dd>{{ activeRiders(ecomDetail?.riders) ?? 'N/A' }}</dd>
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
            <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <DataTable
            table-class-name="tablefixed compact"
            :headers="emailStatusesTableColumns"
            :items="emailStatuses || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="emailStatuses.length < 15"
          >
            <template #item-email_status="item">
              <span class="text-primary-600 uppercase">{{
                item.email_status
              }}</span>
            </template>
            <template #item-reason="item">
              <span class="text-primary-600 uppercase">{{ item.reason }}</span>
            </template>
          </DataTable>
        </template>
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">
            Documents
            <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
          </h3>
          <div class="flex gap-2">
            <Link
              v-if="
                quote?.insly_id &&
                canAny([
                  permissionsEnum.VIEW_LEGACY_DETAILS,
                  permissionsEnum.VIEW_ALL_LEADS,
                ])
              "
              :href="`/legacy-policy/${quote.insly_id}`"
              preserve-scroll
            >
              <x-button size="sm" color="#ff5e00" tag="div">
                View Legacy policy
              </x-button>
            </Link>
            <x-tooltip placement="top">
              <x-button
                @click.prevent="getupdateDocumentValidate(true)"
                v-if="can(permissionsEnum.DOCUMENT_VERIFY)"
                size="sm"
                color="green"
              >
                Verify Documents
              </x-button>
              <template #tooltip>
                Verify Documents: Clicking this button confirms that all
                submitted documents are accurate and valid.</template
              >
            </x-tooltip>
            <x-button
              @click.prevent="modals.doc = true"
              size="sm"
              color="primary"
              v-if="readOnlyMode.isDisable === true"
            >
              Upload Documents
            </x-button>
            <x-button
              size="sm"
              color="red"
              v-if="
                displaySendPolicyButton &&
                permissions.notProductionApproval &&
                permissions.isQuoteDocumentEnabled
              "
              @click="sendPolicyToClient"
            >
              Send Policy
            </x-button>
          </div>
        </div>
        <DataTable
          table-class-name="compact"
          :headers="quoteDocumentsTable.columns"
          :items="quoteDocuments || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="quoteDocuments.length < 15"
        >
          <template #item-original_name="item">
            <a
              :href="cdnPath + item.doc_url"
              target="_blank"
              class="text-primary-600"
            >
              {{ item.original_name }}
            </a>
          </template>
          <template #item-action="{ doc_name }">
            <div>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="onDocDelete(doc_name)"
                v-if="readOnlyMode.isDisable === true"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>

        <x-modal
          v-model="modals.doc"
          size="xl"
          title="Upload Documents"
          show-close
          backdrop
        >
          <LazyDocumentUploader
            :members="memberDataDocs(travelers)"
            :doc-types="documentTypes"
            :docs="quoteDocuments || []"
            :cdn="cdnPath"
          />
        </x-modal>
        <x-modal
          v-model="modals.docConfirm"
          title="Delete Document"
          show-close
          backdrop
        >
          <p>Are you sure you want to delete this document?</p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.docConfirm = false"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="error"
                @click.prevent="confirmDeleteDoc"
                :loading="quoteDocumentsTable.isLoading"
              >
                Delete
              </x-button>
            </div>
          </template>
        </x-modal>
      </Collapsible>
    </div>

    <LazyCreatePlan
      v-model="modals.createPlan"
      :uuid="quote.uuid"
      :insuranceProviders="insuranceProviders"
      :currencies="currencies"
      :plans="computedListQuotePlans"
      :lifeRiders="lifeRiders"
      :paymentTermEnum="page.props.paymentTerms"
      @success="onCreatePlan"
      @error="onPlanError"
    />

    <CreatePlanVariant
      v-model="modals.createPlanVariant"
      :uuid="quote.uuid"
      :insuranceProviders="insuranceProviders"
      :currencies="currencies"
      :plan="variantPlan"
      :lifeRiders="lifeRiders"
      :quote="quote"
      :paymentTermEnum="page.props.paymentTerms"
      @success="onCreateVariant"
      @error="onPlanError"
    />

    <template>
      <EditPlan
        v-if="modals.editPlan"
        v-model="modals.editPlan"
        :selectedPlan="selectedPlan"
        :uuid="quote.uuid"
        :insuranceProviders="insuranceProviders"
        :currencies="currencies"
        :plans="computedListQuotePlans"
        :lifeRiders="lifeRiders"
        :paymentTermEnum="page.props.paymentTerms"
        @success="onLoadAvailablePlansData"
        @error="onPlanError"
      />
    </template>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      :quoteType="quoteType"
      :payments="payments"
    />

    <PaymentTableNew
      v-if="isNewPaymentStructure"
      :quoteType="quoteType"
      :payments="payments"
      :paymentDocument="paymentDocument"
      :proformaPayment="
        payments.find(
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
      :bookPolicyDetails="bookPolicyDetails"
      :expanded="sectionExpanded"
      :paymentGatewayEnum="paymentGatewayEnum"
      :isFuncsEnabled="isFuncsEnabled"
      :isPlanDetailSectionEnabled="shouldShowPlanDetailsSection"
    />

    <QuotePayments
      v-else
      :can="can"
      :payments="payments"
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
      :quoteStatusEnum="page.props.quoteStatusEnum"
      :policyIssuanceStatus="policyIssuanceStatus"
      modelType="life"
      :expanded="sectionExpanded"
      :payments="payments"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quote?.documents || []"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :expanded="sectionExpanded"
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

    <CustomerAcceptanceLogsSection
      :leadId="quote.id"
      :lob="quoteType"
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
      quoteType="life"
      :modelClass="personalModelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
      :isPlanDetailSectionEnabled="shouldShowPlanDetailsSection"
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
    />

    <LeadHistorySection
      :expanded="sectionExpanded"
      :quoteId="quote.id"
      :quoteTypeId="$page.props.quoteTypeId"
    />

    <FtcEmailTrack
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
    />

    <AuditLogs
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
      :expanded="sectionExpanded"
    />

    <ApiLogs
      :type="modelClass"
      :id="$page.props.quote?.life_quote?.id"
      :quoteCode="$page.props.quote.code"
      :expanded="sectionExpanded"
    />

    <AuditLogs
      :title="'KYC Audit Logs'"
      :type="'App\\Models\\InsuredKyc'"
      :id="quote?.insured?.insured_kyc?.id"
      :expanded="sectionExpanded"
    />

    <EALeadInfo
      :source="quote.source"
      :ea-model="quote.ea_model"
      :lead-generator="quote.lead_generator"
    />

    <lead-raw-data
      :modelType="'Life'"
      :uuid="$page.props.quote.uuid"
    ></lead-raw-data>
  </div>
</template>
