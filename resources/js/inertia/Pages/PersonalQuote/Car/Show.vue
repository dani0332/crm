<script setup>
import AssignTier from './Partials/AssignTier.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import FollowUpReasons from './Partials/FollowUpReasons.vue';
import PaymentTable from './Partials/PaymentTable.vue';

defineProps({
  quote: Object,
  leadStatuses: Object,
  ecomDetails: Object,
  membersDetail: Array,
  memberCategories: Array,
  salaryBands: Array,
  nationalities: Array,
  emirates: Array,
  advisors: Array,
  listQuotePlans: { Array, String },
  quoteDocuments: Array,
  documentTypes: Object,
  cdnPath: String,
  ecomHealthInsuranceQuoteUrl: String,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  tiers: Array,
  carPlanFeaturesCodeEnum: Object,
  carPlanExclusionsCodeEnum: Object,
  carPlanAddonsCodeEnum: Object,
  modelType: String,
  notProductionApproval: Boolean,
  allowedDuplicateLOB: Array,
  permissions: Object,
  genderOptions: Object,
  isQuoteDocumentEnabled: Boolean,
  isBetaUser: Boolean,
  payments: Array,
  quoteRequest: Object,
  can: Object,
  paymentMethods: Array,
  sendPolicy: Boolean,
  isPlanUpdateActive: Boolean,
  yearsOfManufacture: Array,
  access: Object,
  record: Object,
  quoteType: String,
  paymentEntityModel: Object,
  displaySendPolicyButton: { Boolean, Number },
  isRenewalUser: Boolean,
  emailStatuses: Array,
  carQuotePlanAddons: Array,
  notesForCustomers: Object,
  websiteURL: String,
  docUploadURL: String,
  planURL: String,
  storageUrl: String,
  insuranceProviders: Array,
  insuranceProvidersByQuoteType: Object,
  advisor: Object,
  carMakeText: String,
  carModelText: String,
  embeddedProducts: Array,
  genericRequestEnum: Object,
  allowQuoteLogAction: Boolean,
  lostApproveReasons: Array,
  lostRejectReasons: Array,
  leadDocsStoragePath: String,
  kyoEndPoint: String,
  carLostChangeStatus: Boolean,
  isTierRAssigned: Boolean,
  tiersExceptTierR: Array,
  leadSourceEnum: Object,
  carPlanTypeEnum: Object,
  bookPolicyDetails: Array,
  documentTypesByCategory: Array,
  customerTypeEnum: Object,
  memberRelations: Array,
  membersDetails: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  isCommercialVehicles: Boolean,
  carInsuranceProviders: Array,
  paymentTooltipEnum: Object,
  isNewPaymentStructure: Boolean,
  vatPercentage: Number,
  commercialRules: Boolean,

  clientInquiryLogs: Array,
  puaTypeEnum: Object,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  paymentDocument: Array,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  customerAddressData: Object,
  amlStatusName: String,
});
const page = usePage();
const notification = useNotifications('toast');
const showfollowup = ref(false);

const canAny = permissions => useCanAny(permissions);
const selectedProviderPlan = ref({
  id: page.props.record.plan_id,
  planName: page.props.record.plan_id_text,
  providerName: page.props.record.car_plan_provider_id_text,
  premium: page.props.record.premium,
});

const modelClass = 'App\\Models\\CarQuote';

/*
* comment for now, will be used in later after confirmation

const prefillPlanPremium = ref('');

const computedPlanDetails = reactive({
  premium: '',
  planName: '',
  providerName: ''
});

const prefillPlanId = ref(page.props.quote.prefill_plan_id);

//compare plan selected at and prefill plan selected at
const updateComputedPlanDetails = () => {

  console.log('updateComputedPlanDetails called');

  let planSelectedAt = new Date(page.props.record.plan_selected_at);
  let prefillPlanSelectedAt = new Date(page.props.record.prefill_plan_selected_at);

  console.log('plan selected at', planSelectedAt, prefillPlanSelectedAt);
  console.log('plan selected at', ' PlanId:', page.props.record.plan_id, " : PREFILL PLAN ID", page.props.record.prefill_plan_id);

  if ( (page.props.record.plan_id && !page.props.record.prefill_plan_id) ||  (planSelectedAt > prefillPlanSelectedAt) ) {

      console.log('plan selected at is greater than prefill plan selected at :' , "PRICE", page.props.record.premium, "PLAN", page.props.record.plan_id_text, "PROVIDER",  page.props.record.car_plan_provider_id_text);
      computedPlanDetails.premium = page.props.record.premium,
      computedPlanDetails.planName = page.props.record.plan_id_text,
      computedPlanDetails.providerName = page.props.record.car_plan_provider_id_text
  } else
  {
      console.log('plan selected at is less than prefill plan selected at');
      computedPlanDetails.premium = '',
      computedPlanDetails.planName = page.props.record.prefill_plan_id_text,
      computedPlanDetails.providerName = page.props.record.prefill_plan_provider_id_text
  }
};

onMounted(() => {
  updateComputedPlanDetails();
});*/

const processingOCBEmailNB = ref(false);
const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;
const quoteStatusEnum = page.props.quoteStatusEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const leadSource = page.props.leadSourceEnum;

const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const { isRequired, isEmail, isNumber, isMobile } = useRules();

const isCarLostStatus = statusId => {
  return (
    statusId == page.props.quoteStatusEnum.CarSold ||
    statusId == page.props.quoteStatusEnum.Uncontactable
  );
};
const prepareDate = date => {
  return (
    date.split(' ')[0].split('-').reverse().join('-') + 'T' + date.split(' ')[1]
  );
};
const leadStatusForm = useForm({
  modelType: 'Car',
  leadId: page.props.record.id,
  quote_uuid: page.props.record.uuid,
  assigned_to_user_id: page.props.record.advisor_id,
  leadStatus: page.props.record.quote_status_id || null,
  notes: page.props.record.notes || null,
  lostReason: page.props.record.lost_reason_id || null,
  next_followup_date: page.props.record.next_followup_date
    ? prepareDate(page.props.record.next_followup_date)
    : null,
  tier_id: page.props.record.tier_id || null,
  lost_approval_status:
    page.props.paymentEntityModel.car_lost_quote_log?.status != ''
      ? page.props.paymentEntityModel.car_lost_quote_log?.status
      : '',
  approve_reason_id:
    page.props.paymentEntityModel.car_lost_quote_log?.reason_id ||
    page.props.lostApproveReasons[0]?.id,
  reject_reason_id:
    page.props.paymentEntityModel.car_lost_quote_log?.reason_id ||
    page.props.lostRejectReasons[0]?.id,
  lost_notes: page.props.paymentEntityModel.car_lost_quote_log?.notes || '',
  mo_proof_document: null,
  proof_document: null,
  car_lost_quote_log_id:
    page.props.paymentEntityModel.car_lost_quote_log?.id || 0,
});

const leadApprovalStatusOptions = computed(() => {
  let arr = [];
  if (!page.props.allowQuoteLogAction) {
    arr.push({
      value: page.props.genericRequestEnum.PENDING,
      label: page.props.genericRequestEnum.PENDING,
    });
  }

  arr.push({
    value: page.props.genericRequestEnum.APPROVED,
    label: page.props.genericRequestEnum.APPROVED,
  });
  arr.push({
    value: page.props.genericRequestEnum.REJECTED,
    label: page.props.genericRequestEnum.REJECTED,
  });

  // arr.unshift({ value: '', label: 'Select Status' });
  return arr;
});

const carLostQuoteLogsTable = reactive({
  columns: [
    { text: 'Modified At', value: 'created_at' },
    { text: 'Modified By', value: 'modified_by' },
    { text: 'Notes', value: 'notes' },
    { text: 'Lead Status', value: 'quote_status.text' },
    { text: 'Approval Status', value: 'status' },
    { text: 'Documents', value: 'documents' },
  ],
});

const { copy, copied } = useClipboard();
const paymentDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Payment ID',
      value: 'code',
    },
    {
      text: 'Payment Status',
      value: 'payment_status',
    },
    {
      text: 'Plan Name',
      value: 'plan_name',
    },
    {
      text: 'Captured Amount',
      value: 'captured_amount',
    },
    {
      text: 'Status Change Date',
      value: 'created_at',
    },
    {
      text: 'Captured At',
      value: 'captured_at',
    },
    {
      text: 'Authorized At',
      value: 'authorized_at',
    },
    {
      text: 'Payment method',
      value: 'payment_method_name',
    },
    {
      text: 'Reference',
      value: 'reference',
    },

    {
      text: 'Action',
      value: 'action',
    },
  ],
});
const coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
const halfLiveInsurer = [
  'SI',
  'OI',
  'Watania',
  'DNIRC',
  'NIA',
  'UI',
  'IHC',
  'NT',
];
const selectedPlans = ref([]);
const selectedPlan = ref({});

function repairTypeCheck(repairType) {
  return repairType.repairType === page.props.carPlanTypeEnum.COMP
    ? coreInsurer.includes(repairType.providerCode)
      ? 'Premium workshop'
      : halfLiveInsurer.includes(repairType.providerCode)
        ? 'Non-Agency workshop'
        : 'NON-AGENCY'
    : repairType.repairType;
}

const availablePlansTable = reactive({
  data: [],
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plan Name', value: 'name' },
    { text: 'Repair Type', value: 'repairType' },
    { text: 'Insurer Quote No.', value: 'insurerQuoteNo' },
    { text: 'TPL Limit', value: 'benefits' },
    { text: 'Car Trim', value: 'insurerTrimText' },
    { text: 'PAB cover', value: 'addons' },
    { text: 'Roadside assistance', value: 'roadSideAssistance' },
    { text: 'Oman cover TPL', value: 'omanCoverTPL' },
    { text: 'Price', value: 'actualPremium' },
    { text: 'Discounted Price', value: 'discountPremium' },
    { text: 'Total Price', value: 'premiumWithVat' },
    { text: 'Excess', value: 'excess' },
    { text: 'Action', value: 'action' },
  ],
});

const lazyEmbeddedProducts = ref([]);
const lazyEmbeddedProductsLoading = ref(false);

/*
// comment for now, will be used in later after confirmation
watch(availablePlansTable, (newPlans) =>  {

  console.log('plan selected at - inside watch availablePlans - ');
  let planSelectedAt = new Date(page.props.record.plan_selected_at);
  let prefillPlanSelectedAt = new Date(page.props.record.prefill_plan_selected_at);

  console.log('plan selected at - prefillPlanId - ' , prefillPlanId.value, " : plan SelectedAT: ", planSelectedAt, " : prefillPlanSelectedAt ", prefillPlanSelectedAt);

  //find selected plan from available plans and calculate prefilled plan premium
  if(prefillPlanId.value && prefillPlanSelectedAt > planSelectedAt )
  {
    console.log('plan selected at updating premium of prefill plan')
    let selectedPlan = newPlans.data.find(
        plan => plan.id === page.props.record.prefill_plan_id,
      );

    computedPlanDetails.premium = (selectedPlan.discountPremium + selectedPlan.vat + getAddonVat(selectedPlan)).toFixed(2);
  }
  else
  {
    console.log('plan selected at - prefillPlanSelectedAt is less than planSelectedAt' );
  }

}); */

const notesForCustomersTable = reactive({
  columns: [
    { text: 'Id', value: 'id' },
    { text: 'Description', value: 'description' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Created By', value: 'created_by' },
  ],
});

const notesForCustomersTableItems = computed(() => {
  return page.props.notesForCustomers.map(notesForCustomer => {
    return {
      id: notesForCustomer.id,
      description: notesForCustomer.description,
      created_at: notesForCustomer.created_at,
      created_by: notesForCustomer.createdby
        ? notesForCustomer.createdby.name
        : '',
    };
  });
});

const leadActivities = reactive({
  columns: [
    { text: 'Client Name', value: 'client_name' },
    { text: 'Lead Status', value: 'quote_status.text' },
    { text: 'Title', value: 'title' },
    { text: 'Followup Date', value: 'due_date' },
    { text: 'Assigned To', value: 'assignee' },
    { text: 'Done', value: 'status', width: 60, align: 'center' },
    { text: 'Action', value: 'action' },
  ],
});

const emailStatusTable = reactive({
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

// history data
const historyData = ref(null);
const historyLoading = ref(false);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/lead-history?modelType=car&recordId=${page.props.paymentEntityModel.id}&quoteTypeId=${page.props.quoteTypeId}`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};
``;
const onLoadAvailablePlansData = async () => {
  let data = {
    jsonData: true,
  };
  let url = `/quotes/car/available-plans/${page.props.record.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      availablePlansTable.data = res.data;

      loadEmbeddedProducts();
    })
    .catch(err => {
      console.log(err);
    });
};

const loadEmbeddedProducts = async () => {
  let url = `/embedded/get-by-quote?quote_id=${page.props.record.id}&quote_type_id=${page.props.quoteTypeId}`;
  let data = {
    jsonData: true,
  };
  lazyEmbeddedProductsLoading.value = true;
  axios
    .get(url, data)
    .then(res => {
      lazyEmbeddedProducts.value = res.data;
      lazyEmbeddedProductsLoading.value = false;
    })
    .catch(err => {
      console.log(err);
    });
};

const historyDataTable = [
  { text: 'Modified At', value: 'created_at' },
  { text: 'Modified By', value: 'created_by.email' },
  { text: 'Lead Status From', value: 'previous_quote_status.text' },
  { text: 'Lead Status To', value: 'current_quote_status.text' },
  { text: 'Notes', value: 'notes' },
];

const availablePlansItems = computed(() => {
  if (!Array.isArray(availablePlansTable.data)) {
    return [];
  }
  return typeof availablePlansTable.data !== 'string'
    ? availablePlansTable.data
    : [];
});

const totalPriceVAT = computed(() => {
  let vat = 0;
  availablePlansItems?.value.forEach(item => {
    item.addons.forEach(addon => {
      addon.carAddonOption.forEach(option => {
        if (option.isSelected && option.price != 0) {
          vat += parseInt(option.price) + option.vat;
        }
      });
    });
  });
  return vat;
});

const paymentItems = computed(() => {
  return page.props.payments.map(payment => {
    return {
      code: payment.code.toLowerCase(),
      payment_status: payment.payment_status.text,
      payment_status_id: payment.payment_status_id,
      plan_name: page.props.paymentEntityModel.plan.text,
      captured_amount: payment.captured_amount,
      created_at:
        payment.payment_status_logs.length > 0
          ? payment.payment_status_logs.at(-1).created_at
          : null,
      captured_at: payment.captured_at,
      authorized_at: payment.authorized_at,
      payment_method_name: payment.payment_method.name,
      reference: payment.reference,
      payment_method_code: payment.payment_method.code,
    };
  });
});

const isRenewalUpload = computed(() => {
  return page.props.record.source == page.props.leadSourceEnum.RENEWAL_UPLOAD;
});

const leadStatusOptions = computed(() => {
  const isLeadPool = hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool]);
  const isPA = hasAnyRole([rolesEnum.Admin, rolesEnum.PA]);
  const renewal_batch = page.props.record.renewal_batch;
  const previous_quote_policy_number =
    page.props.record.previous_quote_policy_number;
  const source = page.props.record.source;
  const renewal_upload = page.props.leadSourceEnum.RENEWAL_UPLOAD;
  const statuses = Array.isArray(page.props.leadStatuses)
    ? page.props.leadStatuses
    : Object.values(page.props.leadStatuses);

  const filteredLeadStatuses = statuses?.map(status => {
    if (
      (!isLeadPool &&
        [
          page.props.quoteStatusEnum.Fake,
          page.props.quoteStatusEnum.Duplicate,
        ].includes(status.id)) ||
      (!isPA && status.id === page.props.quoteStatusEnum.TransactionApproved) ||
      ((renewal_batch === '' ||
        previous_quote_policy_number === '' ||
        source != renewal_upload) &&
        status.id === page.props.quoteStatusEnum.Lost)
    ) {
      return {
        value: status.id,
        label: status.text,
        disabled: true,
      };
    }
    // if (status.id == page.props.quoteStatusEnum.PolicyIssued && page.props.isQuoteDocumentEnabled) return true;
    // else if (status.id != page.props.quoteStatusEnum.PolicyIssued) return true;
    return {
      value: status.id,
      label: status.text,
    };
  });

  return filteredLeadStatuses;
});

const leadStatusDisabled = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.record.quote_status_id ==
      page.props.quoteStatusEnum.TransactionApproved ||
    ((page.props.record.quote_status_id ==
      page.props.quoteStatusEnum.Duplicate ||
      page.props.record.quote_status_id == page.props.quoteStatusEnum.Fake) &&
      !hasAnyRole([rolesEnum.LeadPool, rolesEnum.Admin])) ||
    (!page.props.carLostChangeStatus && !page.props.allowQuoteLogAction)
  );
});

const assumptionState = reactive({
  isEditing: false,
});

const assumptionsForm = useForm({
  cylinder: page.props.record.cylinder || null,
  seat_capacity: page.props.record.seat_capacity || null,
  vehicle_type_id: page.props.record.vehicle_type_id || null,
  is_modified: page.props.record.is_modified || 0,
  is_bank_financed: page.props.record.is_bank_financed || 0,
  is_gcc_standard: [0, 1].includes(page.props.record.is_gcc_standard)
    ? page.props.record.is_gcc_standard
    : null,
  current_insurance_status: page.props.record.current_insurance_status || null,
  year_of_first_registration:
    page.props.record.year_of_first_registration || null,
  car_quote_id: page.props.record.id,
});

const onUpdateAssumption = () => {
  assumptionsForm.post('/quotes/car/carAssumptionsUpdate', {
    preserveScroll: true,
    onSuccess: () => {
      assumptionState.isEditing = false;
    },
  });
};

const vehicleTypeOptions = computed(() => {
  return page.props.vehicleTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

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

const dateToYMD = date => {
  if (date) {
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const policyDetailsState = reactive({
  isEditing: false,
});

const planQuoteInsurerNumber = computed(() => {
  let quotePlanList = page.props?.listQuotePlans;
  if (!quotePlanList || typeof quotePlanList === 'string') return null;
  let obj = quotePlanList?.filter(item => item.id == page.props.record.plan_id);
  return obj === undefined ? null : obj[0]?.insurerQuoteNo || null;
});
const vatAmount = computed(() => {
  return (page.props.record.premium * 0.05).toFixed(2);
});

const priceWithoutVat = computed(() => {
  return page.props.record.premium - vatAmount.value;
});

const policyDetailsForm = useForm({
  quote_policy_number: page.props.record.policy_number || null,

  quote_policy_issuance_date:
    dateToYMD(page.props.record.policy_issuance_date) || '',
  quote_policy_price_vat_notapplicable: null,
  quote_policy_price_vat_applicable: priceWithoutVat || '',
  quote_policy_vat_total_amount: vatAmount.value || null,
  quote_policy_start_date: dateToYMD(page.props.record.policy_start_date) || '',
  quote_policy_expiry_date:
    dateToYMD(page.props.record.policy_expiry_date) || '',
  quote_premium: page.props.record.premium || null,
  quote_plan_insurer_quote_number: planQuoteInsurerNumber.value || null,
  quote_policy_issuance_status: null,
  modelType: 'Car',
  quote_id: page.props.record.id,
});

const onUpdatePolicyDetails = () => {
  policyDetailsForm.post('/quotes/Car/update-quote-policy', {
    preserveScroll: true,
    onSuccess: () => {
      policyDetailsState.isEditing = false;
    },
  });
};

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const modals = reactive({
  duplicate: false,
  doc: false,
  addContact: false,
  contactPrimaryConfirm: false,
  contactDeleteConfirm: false,
  activity: false,
  activityConfirm: false,
  changeInsurer: false,
  notes: false,
  plan: false,
  docConfirm: false,
  createPlan: false,
  sendConfirm: false,
  showEmailEventsModal: false,
});

const confirmData = reactive({
  contactPrimary: null,
});

const leadDuplicateForm = useForm({
  modelType: 'car',
  parentType: 'car',
  entityId: page.props.record.id,
  entityCode: page.props.record.code,
  entityUId: page.props.record.uid,
  lob_team: null,
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const openSendOCBConfirmNB = () => {
  modals.sendOCBConfirmNB = true;
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

const contactLoader = ref(false);
const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: page.props.record.id,
  customer_id: page.props.record.customer_id,
  quote_type: 'car',
});

const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;
  additionalContact
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/customer-additional-contact/add`, {
      preserveScroll: true,
      onSuccess: () => {
        additionalContact.reset();
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onError: err => {
        notification.error({ title: err.error, position: 'top' });
      },
      onFinish: () => {
        modals.addContact = false;
      },
    });
};

const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const additionalContactDelete = id => {
  modals.contactDeleteConfirm = true;
  confirmDeleteData.contact = id;
};

const confirmDeleteData = reactive({
  contact: null,
  activity: null,
});

const additionalContactPrimaryConfirmed = () => {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: page.props.record.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'car',
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Primary Contact Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactPrimaryConfirm = false;
      },
    },
  );
};

const customerAdditionInfoList = computed(() => {
  return page.props.customerAdditionalContacts.filter(item => {
    if (
      !(
        item.value == page.props.record.email ||
        item.value == page.props.record.mobile_no
      )
    ) {
      return true;
    }
  });
});

const additionalContactDeleteConfirmed = () => {
  router.post(
    `/customer-additional-contact/${confirmDeleteData.contact}/delete`,
    {
      isInertia: true,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.error({
          title: 'Additional Contact Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactDeleteConfirm = false;
      },
    },
  );
};

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const activityActionEdit = ref(false);

const activityForm = useForm({
  entityUId: page.props.record.uuid,
  entityId: page.props.record.id,
  modelType: 'Car',
  parentType: 'Car',
  quoteType: 1,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props.auth?.user?.id ?? null,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivitySubmit = isValid => {
  if (!isValid) return;
  if (activityActionEdit.value) {
    activityForm.post(`/activities/${activityForm.uuid}/update`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
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

const activityDelete = id => {
  modals.activityConfirm = true;
  confirmDeleteData.activity = id;
};

const activityDeleteConfirmed = () => {
  router.post(
    `/activities/${confirmDeleteData.activity}/delete`,
    {
      isInertia: true,
      quote_uuid: page.props.record.uuid,
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

const changeInsurerForm = useForm({
  uuid: '',
  plan_id: '',
  provider_code: '',
});

const confirmChangeInsurer = plan => {
  modals.changeInsurer = true;
  changeInsurerForm.uuid = page.props.record.uuid;
  changeInsurerForm.plan_id = plan.id;
  changeInsurerForm.provider_code = plan.providerCode;
};

const onConfirmChangeInsurer = () => {
  changeInsurerForm.post('change-insurer', {
    preseveScroll: true,
    preserveState: true,
    onSuccess: () => {
      notification.success({
        title: 'Insurer Changed successfully',
        position: 'top',
      });
    },
    onError: err => {
      notification.error({
        title: 'Something went wrong',
        position: 'top',
      });
      conslo.log(err);
    },
    onFinish: () => {
      modals.changeInsurer = false;
    },
  });
};

const notesForm = useForm({
  quote_id: page.props.record.id,
  quote_type_id: page.props.quoteTypeId,
  quote_uuid: page.props.record.uuid,
  customer_name: page.props.record.first_name,
  customer_email: page.props.record.email,
  quote_cdb_id: page.props.record.code,
  description: null,
});

const addNotes = () => {
  quotes / car / change - insurer;
  notesForm.reset();
  modals.notes = true;
};

const onNoteSubmit = isValid => {
  if (!isValid) return;
  notesForm
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/quotes/car/addNoteForCustomer`, {
      preserveScroll: true,
      onSuccess: () => {
        notesForm.reset();
        notification.success({
          title: 'Note Send To Customer',
          position: 'top',
        });
      },
      onError: err => {
        notification.error({ title: err.error, position: 'top' });
      },
      onFinish: () => {
        modals.notes = false;
      },
    });
};

const copyPlanURL = item => {
  var paymentLink = `${page.props.websiteURL}/car-insurance/quote/${page.props.record.uuid}/payment/?providerCode=${item.providerCode}&planId=${item.id}`;
  copy(paymentLink);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const selectPlan = item => {
  selectedPlan.value = item;
  modals.plan = true;
};

const getAddonVat = item => {
  let addonVat = 0;
  item.addons.forEach(addon => {
    addon.carAddonOption.forEach(option => {
      if (option.isSelected && option.price != 0) {
        addonVat += parseInt(option.price) + option.vat;
      }
    });
  });
  return addonVat;
};

const copyLink = () => {
  copy(page.props.planURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const onLeadStatus = () => {
  leadStatusForm
    .transform(data => {
      let date = data.next_followup_date;
      if (date !== null && date !== '') {
        date = new Date(date);
        date =
          date.toISOString().split('T')[0].split('-').reverse().join('-') +
          ' ' +
          date.toTimeString().split(' ')[0];
      }
      data.next_followup_date = date;
      return data;
    })
    .post(`/quotes/Car/${page.props.record.id}/update-lead-status`, {
      preserveScroll: true,
      onError: errors => {
        console.log(errors);
        notification.error({
          title: errors.value,
          position: 'top',
        });
      },
      onSuccess: () => {
        notification.success({
          title: 'Lead Status Updated',
          position: 'top',
        });
      },
    });
};
const toggleLoader = ref(false);

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => {
      return p.id;
    }),
  ).value;

  axios
    .post(route('manualPlanToggle', { quoteType: 'Car' }), {
      modelType: 'Car',
      planIds: planIds,
      car_quote_uuid: usePage().props.record.uuid,
      toggle: toggle,
    })
    .then(response => {
      notification.success({
        title: 'Plans has been updated',
        position: 'top',
      });
      onLoadAvailablePlansData();
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
const exportLoader = ref(false);
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
      '/api/v1/quotes/car/export-plans-pdf',
      {
        plan_ids: planIds,
        quote_uuid: page.props.record.uuid,
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
      console.log(error);
    })
    .finally(() => {
      exportLoader.value = false;
    });
};
const confirmSendEmail = () => {
  const first_name = page.props.record.first_name || '';
  const last_name = page.props.record.last_name || '';
  axios
    .post(
      `/quotes/car/${page.props.record.uuid}/send-email-one-click-buy`,
      {
        quote_type_id: page.props.quoteTypeId,
        quote_id: page.props.record.id,
        quote_uuid: page.props.record.uuid,
        quote_cdb_id: page.props.record.code,
        quote_previous_expiry_date:
          page.props.record.previous_policy_expiry_date,
        quote_currently_insured_with: page.props.record.currently_insured_with,
        quote_car_make: page.props.carMakeText,
        quote_car_model: page.props.carModelText,
        quote_car_year_of_manufacture: page.props.record.year_of_manufacture,
        quote_previous_policy_number:
          page.props.record.previous_quote_policy_number,
        customer_name: `${first_name} ${last_name}`,
        customer_email: page.props.record.email,
        advisor_name: page.props.advisor ? page.props.advisor.name : null,
        advisor_email: page.props.advisor ? page.props.advisor.email : null,
        advisor_mobile_no: page.props.advisor
          ? page.props.advisor.mobile_no
          : null,
        advisor_landline_no: page.props.advisor
          ? page.props.advisor.landline_no
          : null,
      },
      {
        responseType: 'json',
      },
    )

    .then(response => {
      notification.success({
        title: response.data.success,
        position: 'top',
      });
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      modals.sendConfirm = false;
    });
};

const confirmSendOCBEmailNB = () => {
  processingOCBEmailNB.value = true;
  axios
    .post(`/quotes/car/${page.props.record.uuid}/send-email-ocb-nb`, {
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
      console.log(error);
    })
    .finally(() => {
      processingOCBEmailNB.value = false;
      modals.sendOCBConfirmNB = false;
    });
};

const disableFollowUp = ref(false);
const followUpstatus = ref('');
const followUpEmails = ref([]);
const followUpId = ref('');
const hideFollowUp = ref(true);

const emailsHeaders = ref([
  { text: 'Email', value: 'customer_email' },
  { text: 'Subject', value: 'subject' },
  { text: 'Schedule Date', value: 'schedule_date' },
  { text: 'Status', value: 'status' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Actions', value: 'actions' },
]);
const followUpActions = ref([]);
const actionsHeaders = ref([
  { text: 'Type', value: 'type' },
  { text: 'Reason', value: 'reason.text' },
  { text: 'Notes', value: 'notes' },
  { text: 'Resume Date', value: 'resume_date' },
  { text: 'Created By', value: 'action_by_email' },
  { text: 'Created At', value: 'created_at' },
]);

const getFollowUpsByQuote = () => {
  try {
    axios
      .get(`${page.props.kyoEndPoint}/followups/car/${page.props.record.uuid}`)
      .then(response => {
        let { status, emails, actions, id } = response.data.data;

        if (id) {
          hideFollowUp.value = false;

          if (status == 'PENDING' || status == 'IN_PROGRESS') {
            disableFollowUp.value = false;
          } else disableFollowUp.value = true;

          followUpstatus.value = status;
          followUpEmails.value = emails;
          followUpActions.value = actions;
          followUpId.value = id;
        } else {
          hideFollowUp.value = true;
        }
      })
      .catch(error => {
        hideFollowUp.value = true;
      });
  } catch (error) {
    hideFollowUp.value = true;
  }
};

const closeModal = v => {
  if (v) disableFollowUp.value = v;
  showfollowup.value = false;
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);

  onLoadAvailablePlansData();
  if (can(permissionEnum.PAUSE_AUTO_FOLLOWUPS)) {
    getFollowUpsByQuote();
  }
});

//activities
const emailEventsTable = [
  { text: 'Type', value: 'type' },
  { text: 'Sub Type', value: 'sub_type' },
  { text: 'DateTime', value: 'event_date' },
];

const emailEvents = ref([]);
const loadingEmailEvents = ref(false);

const loadEmailEvents = email => {
  loadingEmailEvents.value = email.id;
  let data = {
    isInertial: true,
    message_id: email.message_id,
    customer_email: email.customer_email,
  };

  axios
    .post(`/followups/emails/events`, data)
    .then(response => {
      loadingEmailEvents.value = false;
      if (response.data.length) {
        modals.showEmailEventsModal = true;
        emailEvents.value = response.data;
      } else {
        notification.success({
          title: 'Email Events not available.',
          position: 'top',
        });
      }
    })
    .catch(error => {
      loadingEmailEvents.value = false;
      notification.error({
        title: 'Something went wrong while fetching events.',
        position: 'top',
      });
      modals.showEmailEventsModal = false;
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
  customer_id: page.props.record.customer_id,
  customer_type: page.props.record.customer_type,
  quote_type: page.props.modelType,
  quote_type_id: page.props.quoteTypeId,
  quote_request_id: page.props.record.id,

  insured_first_name: page.props.record.insured_first_name || '',
  insured_last_name: page.props.record.insured_last_name || '',
  emirates_id_number: page.props.record.emirates_id_number || null,
  emirates_id_expiry_date: page.props.record.emirates_id_expiry_date || null,

  entity_id: page.props.record.entity_id ?? null,
  trade_license_no: page.props.record.trade_license_no ?? null,
  company_name: page.props.record.company_name ?? null,
  company_address: page.props.record.company_address ?? null,
  entity_type_code: page.props.record.entity_type_code ?? 'Parent',
  industry_type_code: page.props.record.industry_type_code ?? null,
  emirate_of_registration_id:
    page.props.record.emirate_of_registration_id ?? null,
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
    quote_request_id: page.props.record.id,
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

const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'paymentEntityModel'],
  });
};

const isPlanDetailEnabled = computed(() => {
  if (page.props.commercialRules) {
    // Check rules for commercial
    return true;
  }
  if (page.props.record.source == page.props.leadSourceEnum.RENEWAL_UPLOAD) {
    return page.props.record.vehicle_type_id_text == 'BIKE';
  }
  return false;
});
if (isPlanDetailEnabled.value && page.props.record.insurer_name !== '') {
  selectedProviderPlan.value.premium = page.props.record.price_with_vat;
  selectedProviderPlan.value.providerName = page.props.record.insurer_name;
}

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);

const copyUploadURL = () => {
  copy(page.props.docUploadURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

watch(
  () => page.props.record.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      leadStatusForm.leadStatus = newValue;
    }
  },
);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();
const [AddPlanButtonTemplate, AddPlanButtonReuseTemplate] =
  createReusableTemplate();
const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  selectedProviderPlan.value.id = null;
  selectedProviderPlan.value.planName = '';
  selectedProviderPlan.value.providerName = '';
  selectedProviderPlan.value.premium = '';
  isAddUpdate.value = true;
};

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

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved
  );
});

const convertToNumber = (value, decimalPlace = 2) => {
  // Step 1: Round to (decimalPlace + 2) decimal places
  const roundToExtra =
    Math.round(value * Math.pow(10, decimalPlace + 2)) /
    Math.pow(10, decimalPlace + 2);

  // Step 2: Round to (decimalPlace + 1) decimal places
  const roundToOneLess =
    Math.round(roundToExtra * Math.pow(10, decimalPlace + 1)) /
    Math.pow(10, decimalPlace + 1);

  // Step 3: Round to (decimalPlace) decimal places
  const roundToFinal =
    Math.round(roundToOneLess * Math.pow(10, decimalPlace)) /
    Math.pow(10, decimalPlace);

  return roundToFinal;
};
</script>

<template>
  <div>
    <Head title="Car Detail" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Car Detail</h2>
      </template>
      <template #default>
        <Link
          v-if="
            record?.insly_id &&
            canAny([
              permissionEnum.VIEW_LEGACY_DETAILS,
              permissionEnum.VIEW_ALL_LEADS,
            ])
          "
          :href="`/legacy-policy/${record.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <template
          v-if="
            !can(permissionEnum.ApprovePayments) &&
            allowedDuplicateLOB.length > 0
          "
        >
          <x-button
            v-if="hasAnyRole([rolesEnum.LeadPool]) && !isRenewalUpload"
            class="mr-2"
            size="sm"
            color="#ff5e00"
            @click.prevent="openSendOCBConfirmNB"
          >
            Send NB OCB To Customer
          </x-button>
          <x-button
            v-if="
              !hasAnyRole([
                rolesEnum.CarAdvisor,
                rolesEnum.CarDeputyManager,
                rolesEnum.CarManager,
              ])
            "
            class="mr-2"
            size="sm"
            color="#ff5e00"
            @click.prevent="openDuplicate"
          >
            Duplicate Lead
          </x-button>
        </template>
        <Link :href="route('car.index')">
          <x-button size="sm" tag="div">Car List</x-button>
        </Link>
      </template>
    </StickyHeader>
    <x-divider class="my-4" />
    <AssignTier
      v-if="
        !can(permissionEnum.ApprovePayments) &&
        hasRole(rolesEnum.LeadPool) &&
        isTierRAssigned
      "
      :quote="record"
      :tiers="tiersExceptTierR"
      :expanded="sectionExpanded"
    />

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
                <dd>{{ selectedProviderPlan.premium ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AUTHORISED AT</dt>
                <dd>{{ record.paid_at ?? 'N/A' }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAID AT</dt>
                <dd>{{ record.payment_paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT STATUS</dt>
                <dd>{{ record.payment_status_id_text ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PROVIDER NAME</dt>
                <dd>{{ selectedProviderPlan.providerName ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT METHOD</dt>
                <dd>
                  {{
                    record.payment_gateway === 'NGENIUS'
                      ? 'CREDIT CARD'
                      : (record.payment_gateway ?? 'N/A')
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN NAME</dt>
                <dd>{{ selectedProviderPlan.planName ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ECOMMERCE</dt>
                <dd>{{ record.is_ecommerce == 1 ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">QUOTE LINK</dt>
                <dd>{{ record.quote_link ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ORDER REFERENCE</dt>
                <dd>{{ record.order_reference ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT REFERENCE</dt>
                <dd>{{ record.payment_reference ?? 'N/A' }}</dd>
              </div>
            </dl>
            <div class="grid sm:grid-cols-1 mt-3">
              <dt class="font-medium mb-3">ADDONS</dt>
              <dd v-if="carQuotePlanAddons.length > 0">
                <table style="width: 100%">
                  <thead></thead>
                  <tbody>
                    <tr
                      v-for="(addon, index) in carQuotePlanAddons"
                      :key="addon"
                      class="flex justify-between w-100"
                    >
                      <td style="width: 20%">{{ index + 1 }}</td>
                      <td style="width: 20%">{{ addon.car_addon_text }}</td>
                      <td style="width: 20%">
                        {{ addon.car_addon_option_value }}
                      </td>
                      <td style="width: 20%">
                        {{
                          addon.car_quote_request_addon_price == 0
                            ? 'Free'
                            : addon.car_quote_request_addon_price
                        }}
                      </td>
                      <td>
                        <input
                          v-if="addon.car_quote_request_addon_price"
                          type="checkbox"
                          checked
                          disabled
                          class="car-quote-ecom-non-free-plan-check"
                        />
                        <input
                          v-else
                          type="checkbox"
                          checked
                          disabled
                          class="car-quote-ecom-free-plan-check"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </dd>
              <dd v-else>N/A</dd>
            </div>
          </div>
        </template>
      </Collapsible>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">Car Details</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4 mb-3" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
                <div>{{ record.code }}</div>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ quote.customer_type }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPANY NAME</dt>
                <dd>{{ quote.car_company_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPANY ADDRESS</dt>
                <dd>{{ quote.car_company_address }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BATCH</dt>
                <dd>{{ record.quote_batch_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CUSTOMER AGE</dt>
                <dd>{{ record.customer_age }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LEAD SOURCE</dt>
                <dd>{{ record.source }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CAR MAKE</dt>
                <dd>{{ record.car_make_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CAR MODEL</dt>
                <dd>{{ record.car_model_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CYLINDER</dt>
                <dd>{{ record.cylinder }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRIM</dt>
                <dd>{{ record.car_model_detail_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CAR MODEL YEAR</dt>
                <dd>{{ record.year_of_manufacture }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">FIRST REGISTRATION DATE</dt>
                <dd>{{ record.year_of_first_registration }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CAR VALUE</dt>
                <dd>{{ record.car_value }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CAR VALUE (AT ENQUIRY)</dt>
                <dd>{{ record.car_value_tier }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">VEHICLE TYPE</dt>
                <dd>{{ record.vehicle_type_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SEAT CAPACITY</dt>
                <dd>{{ record.seat_capacity }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">EMIRATE OF REGISTRATION</dt>
                <dd>{{ record.emirate_of_registration_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF CAR INSURANCE</dt>
                <dd>{{ record.current_insurance_status }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                <dd>{{ record.currently_insured_with_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIM HISTORY</dt>
                <dd>{{ record.claim_history_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">
                  CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?
                </dt>
                <dd>
                  {{ record.has_ncd_supporting_documents ? 'Yes' : 'No' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ record.created_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR ASSIGNED DATE</dt>
                <dd>{{ record.advisor_assigned_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LEAD COST</dt>
                <dd>{{ record.cost_per_lead }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">FOLLOW UP DATE</dt>
                <dd>{{ record.next_followup_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ record.updated_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">UPDATED BY</dt>
                <dd>{{ record.updated_by }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDITIONAL NOTES</dt>
                <dd>{{ record.additional_notes }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ record.advisor_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR/PROMO CODE</dt>
                <dd>{{ record.promo_code }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DEVICE</dt>
                <dd>{{ record.device }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CALCULATED VALUE</dt>
                <dd>{{ record.calculated_value ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED BY</dt>
                <dd>{{ record.created_by }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      PARENT REF-ID
                    </label>
                    <template #tooltip> Parent Reference ID </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <Link
                    v-if="record.parent_duplicate_quote_id"
                    :href="
                      getDetailPageRoute(
                        linkedQuoteDetails.uuid,
                        linkedQuoteDetails.quote_type_id,
                      )
                    "
                    class="text-primary-500 hover:underline"
                  >
                    {{ record.parent_duplicate_quote_id ?? '' }}
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
              <div
                class="grid sm:grid-cols-2"
                v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
              >
                <dt class="font-medium">ID</dt>
                <dd>{{ record.id }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ENQUIRY COUNT</dt>
                <dd>{{ record.enquiry_count }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ record.transaction_approved_at }}</dd>
              </div>
            </dl>
          </div>
          <x-divider class="mb-4 mt-4" />

          <LeadEditBtnTemplate v-slot="{ isDisabled }">
            <Link v-if="!isDisabled" :href="route('car.edit', record.uuid)">
              <x-button
                size="sm"
                color="primary"
                tag="div"
                v-if="readOnlyMode.isDisable === true"
                >Edit</x-button
              >
            </Link>
            <x-button
              v-else
              :disabled="isDisabled"
              size="sm"
              color="primary"
              tag="div"
              >Edit</x-button
            >
          </LeadEditBtnTemplate>

          <div
            v-if="
              quote.quote_status_id !=
                page.props.quoteStatusEnum.PolicyCancelled ||
              linkedQuoteDetails.childLeadsCount == 0
            "
            class="flex justify-end mb-4"
          >
            <x-tooltip
              v-if="lockLeadSectionsDetails.lead_details"
              placement="bottom"
            >
              <LeadEditBtnReuseTemplate :isDisabled="true" />
              <template #tooltip
                >This lead is now locked as the policy has been booked. If
                changes are needed, go to 'Send Update', select 'Add Update',
                and choose 'Correction of Policy'</template
              >
            </x-tooltip>
            <LeadEditBtnReuseTemplate v-else />
          </div>
        </template>
      </Collapsible>
    </div>

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
        </x-field>
        <x-field label="Reason" required>
          <x-select
            v-model="leadDuplicateForm.lob_team_sub_selection"
            :rules="[rules.isRequired]"
            class="w-full"
            :options="[
              { value: 'new_enquiry', label: 'New enquiry' },
              { value: 'record_only', label: 'Record purposes only' },
            ]"
          />
        </x-field>
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click="modals.duplicate = false">
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              {{
                record.customer_type == page.props.customerTypeEnum.Individual
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
                  record.customer_type ===
                  page.props.customerTypeEnum.Individual
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">FIRST NAME</dt>
                  <dd>{{ record.first_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LAST NAME</dt>
                  <dd>{{ record.last_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">INSURED FIRST NAME</dt>
                  <dd>
                    <x-input
                      v-model="customerProfileForm.insured_first_name"
                      :rules="[isRequired]"
                      placeholder="INSURED FIRST NAME"
                      class="w-full"
                      :disabled="
                        !isProfileUpdateAllow ||
                        linkedQuoteDetails.childLeadsCount > 0
                      "
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
                      :disabled="
                        !isProfileUpdateAllow ||
                        linkedQuoteDetails.childLeadsCount > 0
                      "
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">MOBILE NUMBER</dt>
                  <dd>{{ record.mobile_no }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMAIL</dt>
                  <dd>{{ record.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">ADDRESS TYPE</dt>
                  <dd>{{ customerAddressData?.type }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    {{
                      !customerAddressData?.type ||
                      customerAddressData?.type === 'Home'
                        ? 'RESIDENCE ADDRESS'
                        : 'OFFICE ADDRESS'
                    }}
                  </dt>
                  <dd>{{ fullAddress }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">NATIONALITY</dt>
                  <dd>{{ record.nationality_id_text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ record.dob }}</dd>
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
                      :rules="[isRequired]"
                      placeholder="EMIRATES ID NUMBER"
                      class="w-full"
                      :disabled="
                        !isProfileUpdateAllow ||
                        linkedQuoteDetails.childLeadsCount > 0
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
                      :disabled="
                        !isProfileUpdateAllow ||
                        linkedQuoteDetails.childLeadsCount > 0
                      "
                      :min-date="new Date()"
                    />
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">UAE LICENCE HELD FOR</dt>
                  <dd>{{ record.uae_license_held_for_id_text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">HOME COUNTRY LICENSE HELD FOR</dt>
                  <dd>{{ record.back_home_license_held_for_id_text ?? '' }}</dd>
                </div>
                <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
              </dl>
              <dl
                v-if="
                  record.customer_type === page.props.customerTypeEnum.Entity
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">FIRST NAME</dt>
                  <dd>{{ record.first_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">LAST NAME</dt>
                  <dd>{{ record.last_name }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">MOBILE NUMBER</dt>
                  <dd>{{ record.mobile_no }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">EMAIL</dt>
                  <dd>{{ record.email }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">ADDRESS TYPE</dt>
                  <dd>{{ customerAddressData?.type }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">
                    {{
                      !customerAddressData?.type ||
                      customerAddressData?.type === 'Home'
                        ? 'RESIDENCE ADDRESS'
                        : 'OFFICE ADDRESS'
                    }}
                  </dt>
                  <dd>{{ fullAddress }}</dd>
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
              <div
                v-if="
                  quote.quote_status_id !=
                    page.props.quoteStatusEnum.PolicyCancelled ||
                  linkedQuoteDetails.childLeadsCount == 0
                "
                class="flex justify-end"
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
        <x-button size="sm" color="orange" @click.prevent="linkEntity">
          Link
        </x-button>
      </template>
    </x-modal>

    <MemberDetails
      v-if="record.customer_type == page.props.customerTypeEnum.Individual"
      :quote="quote"
      :membersDetails="membersDetails"
      :nationalities="nationalities"
      :memberRelations="memberRelations"
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <UBODetails
      v-if="record.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="quoteType"
      :expanded="sectionExpanded"
    />

    <LastYearPolicyDetail
      :canAddBatchNumber="hasRole(rolesEnum.CarManager)"
      :expanded="sectionExpanded"
      :quote="record"
      modelType="Car"
      :insly-id="record?.insly_id"
      v-if="
        record.source == page.props.leadSourceEnum.RENEWAL_UPLOAD ||
        record.source == page.props.leadSourceEnum.INSLY
      "
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
            <div class="w-full md:w-50">
              <div class="flex flex-col gap-4">
                <ComboBox
                  v-model="leadStatusForm.leadStatus"
                  :single="true"
                  label="Status"
                  class="w-full uppercase"
                  placeholder="Please select Lead Status"
                  :disabled="
                    leadStatusDisabled || lockLeadSectionsDetails.lead_status
                  "
                  :options="leadStatusOptions"
                  filterable
                />
                <x-field
                  label="Lost Reason"
                  class="uppercase"
                  required
                  v-if="leadStatusForm.leadStatus == quoteStatusEnum.Lost"
                >
                  <x-select
                    v-model="leadStatusForm.lostReason"
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
                </x-field>
                <x-field
                  label="Followup Date"
                  class="uppercase"
                  v-if="
                    leadStatusForm.leadStatus == quoteStatusEnum.FollowupCall ||
                    leadStatusForm.leadStatus == quoteStatusEnum.Interested ||
                    leadStatusForm.leadStatus == quoteStatusEnum.NoAnswer
                  "
                >
                  <DatePicker
                    v-model="leadStatusForm.next_followup_date"
                    withTime
                    :rules="[isRequired]"
                    placeholder="Please select follow-up date & time"
                    :error="leadStatusForm.errors.next_followup_date"
                    class="w-full"
                    :disabled="lockLeadSectionsDetails.lead_status"
                  />
                  <!-- <x-input
								v-model="leadStatusForm.next_followup_date"
								:value="new Date(leadStatusForm.next_followup_date).toLocaleDateString('en-US')"
								type="datetime-local"
								placeholder="Please select follow-up date & time"
								class="w-full"
								:error="leadStatusForm.errors.next_followup_date"
							/> -->
                </x-field>
                <x-select
                  v-if="leadStatusForm.leadStatus == quoteStatusEnum.IMRenewal"
                  v-model="leadStatusForm.tier_id"
                  label="Tier"
                  :options="[
                    { value: null, label: 'Select Tier' },
                    ...tiers?.map(item => ({
                      value: item.id,
                      label: item.name,
                    })),
                  ]"
                  placeholder="Please Select Tier"
                  class="w-full uppercase"
                  :error="leadStatusForm.errors.tier_id"
                  :disabled="lockLeadSectionsDetails.lead_status"
                />
                <x-field
                  label="Notes"
                  class="uppercase"
                  :required="
                    leadStatusForm.leadStatus == quoteStatusEnum.FollowupCall ||
                    leadStatusForm.leadStatus == quoteStatusEnum.Interested ||
                    leadStatusForm.leadStatus == quoteStatusEnum.NoAnswer
                  "
                >
                  <x-textarea
                    v-model="leadStatusForm.notes"
                    type="text"
                    placeholder="Lead Notes"
                    class="w-full"
                    :rules="
                      leadStatusForm.leadStatus ==
                        quoteStatusEnum.FollowupCall ||
                      leadStatusForm.leadStatus == quoteStatusEnum.Interested ||
                      leadStatusForm.leadStatus == quoteStatusEnum.NoAnswer
                        ? [isRequired]
                        : []
                    "
                    :error="leadStatusForm.errors.notes"
                    :disabled="
                      allowStatusUpdate ||
                      isCarLostStatus(record.quote_status_id) ||
                      lockLeadSectionsDetails.lead_status
                    "
                  />
                </x-field>
                <x-field
                  label="Car Sold / Uncontactable Proof"
                  class="uppercase"
                  v-if="
                    leadStatusForm.leadStatus == quoteStatusEnum.CarSold ||
                    leadStatusForm.leadStatus == quoteStatusEnum.Uncontactable
                  "
                >
                  <input
                    @input="
                      leadStatusForm.proof_document = $event.target.files[0]
                    "
                    type="file"
                    :disabled="
                      (isCarLostStatus(record.quote_status_id) &&
                        !carLostChangeStatus) ||
                      lockLeadSectionsDetails.lead_status
                    "
                    placeholder="Car Sold / Uncontactable Proof"
                    class="form-control w-full"
                  />
                </x-field>
                <x-field class="uppercase" label="Transaction Type">
                  <x-input
                    type="text"
                    v-model="record.transaction_type_text"
                    class="w-full"
                    :disabled="true"
                  />
                </x-field>
              </div>
            </div>
            <div
              class="w-full md:w-50"
              v-if="isCarLostStatus(record.quote_status_id)"
            >
              <div class="flex flex-col gap-4">
                <x-field required label="Approval Status" class="uppercase">
                  <x-select
                    v-model="leadStatusForm.lost_approval_status"
                    :options="leadApprovalStatusOptions"
                    :disabled="
                      !allowQuoteLogAction ||
                      lockLeadSectionsDetails.lead_status
                    "
                    placeholder="Approval Status"
                    class="w-full"
                    :rules="[isRequired]"
                  />
                </x-field>

                <x-field
                  required
                  label="Approval Reasons"
                  class="uppercase"
                  v-if="
                    leadStatusForm.lost_approval_status ==
                    genericRequestEnum.APPROVED
                  "
                >
                  <x-select
                    v-model="leadStatusForm.approve_reason_id"
                    :options="
                      lostApproveReasons.map(item => ({
                        value: item.id,
                        label: item.text,
                      }))
                    "
                    :disabled="
                      !allowQuoteLogAction ||
                      !hasRole(rolesEnum.MarketingOperations) ||
                      lockLeadSectionsDetails.lead_status
                    "
                    placeholder="Approval Reasons"
                    class="w-full"
                    :rules="[isRequired]"
                  />
                </x-field>
                <x-field
                  required
                  label="Rejection Reasons"
                  class="uppercase"
                  v-if="
                    leadStatusForm.lost_approval_status ==
                    genericRequestEnum.REJECTED
                  "
                >
                  <x-select
                    v-model="leadStatusForm.reject_reason_id"
                    :options="
                      lostRejectReasons.map(item => ({
                        value: item.id,
                        label: item.text,
                      }))
                    "
                    :disabled="
                      !allowQuoteLogAction ||
                      !hasRole(rolesEnum.MarketingOperations) ||
                      lockLeadSectionsDetails.lead_status
                    "
                    placeholder="Rejection Reasons"
                    class="w-full"
                    :rules="[isRequired]"
                  />
                </x-field>
                <template
                  v-if="
                    [
                      genericRequestEnum.APPROVED,
                      genericRequestEnum.REJECTED,
                    ].includes(leadStatusForm.lost_approval_status)
                  "
                >
                  <x-field class="uppercase" label="Notes">
                    <x-textarea
                      v-model="leadStatusForm.lost_notes"
                      :disabled="
                        !allowQuoteLogAction ||
                        lockLeadSectionsDetails.lead_status
                      "
                      placeholder="Notes"
                      class="w-full"
                    />
                  </x-field>
                  <x-field
                    required
                    label="Car Sold / Uncontactable Proof"
                    class="uppercase"
                  >
                    <input
                      @input="
                        leadStatusForm.mo_proof_document =
                          $event.target.files[0]
                      "
                      type="file"
                      :disabled="
                        !allowQuoteLogAction ||
                        lockLeadSectionsDetails.lead_status
                      "
                      placeholder="Car Sold / Uncontactable Proof"
                      class="w-full"
                      :rules="[isRequired]"
                    />
                  </x-field>
                </template>
              </div>
            </div>
          </div>

          <DataTable
            v-if="paymentEntityModel.car_lost_quote_logs?.length > 0"
            table-class-name="mt-5 tablefixed compact"
            :headers="carLostQuoteLogsTable.columns"
            :items="paymentEntityModel.car_lost_quote_logs || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="availablePlansItems.length < 15"
          >
            <template #item-modified_by="item">
              {{ item.action_by_id ? item.advisor.email : 'Management' }}
            </template>
            <template #item-documents="item">
              <template v-for="doc in item.documents" :key="doc">
                <p class="my-2">
                  <a
                    class="underline"
                    target="_blank"
                    :href="leadDocsStoragePath + doc.path"
                    >Document</a
                  >
                </p>
              </template>
            </template>
            <template #item-created_at="item">
              {{ dateFormat(item.created_at) }}
            </template>
          </DataTable>

          <x-divider class="mb-1 mt-10" />
          <StatusUpdateButtonTemplate v-slot="{ isDisabled }">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :disabled="
                allowStatusUpdate ||
                (!carLostChangeStatus && !allowQuoteLogAction) ||
                isDisabled
              "
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">Assumptions</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
            <div class="w-full md:w-1/2">
              <x-field label="Cylinder" class="uppercase" required>
                <x-input
                  v-model="assumptionsForm.cylinder"
                  type="number"
                  placeholder="cylinder"
                  class="w-full"
                  :rules="[isRequired]"
                  :disabled="!assumptionState.isEditing"
                />
              </x-field>
            </div>
            <div class="w-full md:w-1/2">
              <x-field label="Seat Capacity" class="uppercase" required>
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
                <x-field label="Vehicle Body Type" class="uppercase" required>
                  <x-select
                    v-model="assumptionsForm.vehicle_type_id"
                    :options="vehicleTypeOptions"
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
                <x-field
                  label="Is Vehicle modified?"
                  class="uppercase"
                  required
                >
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
                <x-field label="Is Bank Financed" class="uppercase" required>
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
                <x-field label="Is GCC Standard?" class="uppercase" required>
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
                <x-field label="Current Insurance" class="uppercase" required>
                  <x-select
                    v-model="assumptionsForm.current_insurance_status"
                    :options="currentInsuranceOptions"
                    placeholder="Current Insurance"
                    class="w-full"
                    :rules="[isRequired]"
                    :disabled="!assumptionState.isEditing"
                  />
                </x-field>
              </div>
            </div>
            <div class="w-full md:w-1/2">
              <div class="flex flex-col gap-4">
                <x-field
                  label="Year Of First Registration"
                  class="uppercase"
                  required
                >
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
          <div
            v-if="
              quote.quote_status_id !=
                page.props.quoteStatusEnum.PolicyCancelled ||
              page.props.linkedQuoteDetails.childLeadsCount == 0
            "
          >
            <div
              class="flex justify-end"
              v-if="!hasRole(rolesEnum.PA) && can(permissionEnum.CarQuotesEdit)"
            >
              <x-button
                v-if="assumptionState.isEditing"
                class="mt-4 mr-2"
                color="orange"
                size="sm"
                @click.prevent="assumptionState.isEditing = false"
              >
                Cancel
              </x-button>
              <template v-if="!can(permissionEnum.ApprovePayments)">
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
                  v-if="
                    !assumptionState.isEditing &&
                    (access.carManagerCanEdit ||
                      access.carAdvisorCanEdit ||
                      !hasAnyRole([rolesEnum.CarAdvisor, rolesEnum.CarManager]))
                  "
                  class="mt-4"
                  color="emerald"
                  size="sm"
                  @click.prevent="assumptionState.isEditing = true"
                >
                  Edit Assumptions
                </x-button>
              </template>
            </div>
          </div>
        </template>
      </Collapsible>
    </div>

    <PlanDetails
      v-if="isPlanDetailEnabled"
      :insuranceProviders="insuranceProvidersByQuoteType"
      :quote="quote"
      :quoteType="quoteType"
      :vatPrice="vatPercentage"
    />

    <div v-else class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Available Plans
              <x-tag size="sm">{{ availablePlansItems.length || 0 }}</x-tag>
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="flex mb-4 justify-end">
            <template v-if="!hasRole(rolesEnum.PA)">
              <x-tooltip
                v-if="!hideFollowUp && can(permissionEnum.PAUSE_AUTO_FOLLOWUPS)"
              >
                <x-button
                  class="ml-2 mr-2"
                  :disabled="disableFollowUp"
                  size="sm"
                  color="rose"
                  @click="showfollowup = !showfollowup"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Pause Follow-up to customer
                </x-button>
                <template #tooltip>
                  <span
                    >When Activated, The button temporarily suspends the
                    automatic sending of follow-up emails to clients</span
                  >
                </template>
              </x-tooltip>
              <x-button-group v-if="selectedPlans.length > 0" size="sm">
                <x-button
                  @click.prevent="onTogglePlans(false)"
                  :disabled="page.props.linkedQuoteDetails.childLeadsCount > 0"
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
                class="ml-2 mr-2"
                @click.prevent="onExportPlans"
                :loading="exportLoader"
                :disabled="page.props.linkedQuoteDetails.childLeadsCount > 0"
              >
                Download PDF
              </x-button>
              <x-button
                @click.prevent="modals.sendConfirm = true"
                size="sm"
                color="orange"
                class="mr-2"
                :disabled="
                  record.advisor_id != $page.props.auth.user.id ||
                  page.props.linkedQuoteDetails.childLeadsCount > 0
                "
                v-if="readOnlyMode.isDisable === true"
              >
                Send OCB Email to Customer
              </x-button>
            </template>

            <AddPlanButtonTemplate v-slot="{ isDisabled }">
              <x-button
                @click.prevent="modals.createPlan = true"
                size="sm"
                color="orange"
                class="mr-2"
                v-if="
                  can(permissionEnum.CarQuotesPlansCreate) &&
                  (access.carManagerCanEdit ||
                    access.carAdvisorCanEdi ||
                    hasRole(rolesEnum.Admin))
                "
                :disabled="isDisabled"
              >
                Add Plan
              </x-button>
            </AddPlanButtonTemplate>

            <x-tooltip
              v-if="page.props.lockLeadSectionsDetails.plan_selection"
              placement="bottom"
            >
              <AddPlanButtonReuseTemplate :isDisabled="true" />
              <template #tooltip
                >No further actions can be taken on an issued policy. For
                changes, such as a change in insurer, go to 'Send Update',
                select 'Add Update', and choose 'Cancellation from inception and
                reissuance.</template
              >
            </x-tooltip>
            <AddPlanButtonReuseTemplate
              v-else
              :isDisabled="page.props.linkedQuoteDetails.childLeadsCount > 0"
            />

            <template v-if="!hasRole(rolesEnum.PA)">
              <x-button
                @click.prevent="copyLink"
                size="sm"
                color="emerald"
                v-if="
                  typeof availablePlansTable.data !== 'string' &&
                  availablePlansTable.data.length > 0
                "
                :disabled="page.props.linkedQuoteDetails.childLeadsCount > 0"
              >
                Copy Link
              </x-button>
            </template>
          </div>
          <DataTable
            table-class-name="compact"
            v-model:items-selected="selectedPlans"
            :headers="availablePlansTable.columns"
            :items="availablePlansItems || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="availablePlansItems.length < 15"
          >
            <template
              #item-providerName="{
                providerName,
                isManualUpdate,
                isRenewal,
                isDisabled,
                puaPremium,
                puaType,
                isSystemDiscountPrice,
              }"
            >
              <p>{{ providerName }}</p>
              <div class="flex gap-1">
                <x-tag
                  v-if="isManualUpdate"
                  size="xs"
                  color="primary"
                  class="mt-0.5 text-[10px]"
                >
                  Manual
                </x-tag>
                <x-tag
                  v-if="isRenewal"
                  size="xs"
                  color="success"
                  class="mt-0.5 text-[10px]"
                >
                  Renewal
                </x-tag>
                <x-tag
                  v-if="isDisabled"
                  size="xs"
                  color="error"
                  class="mt-0.5 text-[10px]"
                >
                  Hidden
                </x-tag>
                <x-tag
                  v-if="isSystemDiscountPrice"
                  size="xs"
                  color="danger"
                  class="mt-0.5 text-[10px]"
                >
                  SDP
                </x-tag>
                <x-tag
                  v-if="puaType"
                  size="xs"
                  class="mt-0.5 text-[10px] text-white"
                  style="background-color: #e00000"
                >
                  <x-tooltip placement="right">
                    <template #tooltip>
                      <span
                        class="font-medium"
                        v-if="puaType == puaTypeEnum.PPUA"
                      >
                        {{ puaTypeEnum.PPUA_TOOLTIP }}
                      </span>
                      <span class="font-medium" v-else>
                        {{ puaTypeEnum.PENDING_UNDERWRITER_APPROVAL_TOOLTIP }}
                      </span>
                    </template>
                    {{ puaType }}
                  </x-tooltip>
                </x-tag>
              </div>
            </template>
            <template #item-name="item">
              <span
                class="text-primary-600 cursor-pointer"
                @click.prevent="selectPlan(item)"
                >{{ item.name }}</span
              >
            </template>
            <template #item-repairType="repairType">
              <span>{{ repairTypeCheck(repairType) }}</span>
            </template>
            <template #item-benefits="{ benefits }">
              <!-- <span>{{ benefits.feature }}</span> -->
              <template v-for="feature in benefits.feature" :key="feature">
                <template v-if="feature.code">
                  <span
                    v-if="
                      feature.code ===
                        carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT ||
                      feature.code === carPlanFeaturesCodeEnum.DAMAGE_LIMIT
                    "
                  >
                    {{ feature.value }}
                  </span>
                </template>
                <span
                  v-else-if="
                    feature.text ===
                    carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT_TEXT
                  "
                >
                  {{ feature.value }}
                </span>
              </template>
            </template>
            <template #item-addons="{ addons }">
              <template v-for="addon in addons" :key="addon">
                <template v-for="option in addon.carAddonOption" :key="option">
                  <span v-if="addon.code">
                    <template
                      v-if="
                        addon.code.toLowerCase() ===
                          carPlanAddonsCodeEnum.DRIVER_COVER.toLowerCase() ||
                        addon.code.toLowerCase() ===
                          carPlanAddonsCodeEnum.PASSENGER_COVER.toLowerCase()
                      "
                    >
                      {{ addon.text }}: {{ option.value }} <br />
                    </template>
                  </span>
                  <template
                    v-else-if="
                      addon.text.toLowerCase() ===
                        carPlanAddonsCodeEnum.DRIVER_COVER_TEXT.toLowerCase() ||
                      addon.text.toLowerCase() ===
                        carPlanAddonsCodeEnum.PASSENGER_COVER_TEXT.toLowerCase()
                    "
                  >
                    {{ addon.text }}: {{ option.value }} <br />
                  </template>
                </template>
              </template>
            </template>
            <template #item-omanCoverTPL="{ benefits }">
              <template v-for="planExc in benefits.exclusion" :key="planExc">
                <span
                  v-if="
                    planExc.code &&
                    (planExc.code.toLowerCase() ===
                      carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() ||
                      planExc.code.toLowerCase() ===
                        carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())
                  "
                >
                  {{ planExc.text }}: {{ planExc.value }}
                </span>
              </template>
              <template v-for="planInc in benefits.inclusion" :key="planInc">
                <span
                  v-if="
                    planInc.code &&
                    (planInc.code.toLowerCase() ===
                      carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() ||
                      planInc.code.toLowerCase() ===
                        carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())
                  "
                >
                  {{ planInc.text }}: {{ planInc.value }}
                </span>
              </template>
            </template>
            <template #item-roadSideAssistance="{ benefits }">
              <template
                v-for="planAss in benefits.roadSideAssistance"
                :key="planAss.text"
              >
                {{ planAss.text }}: {{ planAss.value }} <br />
              </template>
            </template>
            <template #item-actualPremium="{ actualPremium }">
              {{
                actualPremium ? parseFloat(actualPremium).toFixed(2) : '0.00'
              }}
            </template>
            <template #item-discountPremium="{ discountPremium }">
              {{
                discountPremium
                  ? parseFloat(discountPremium).toFixed(2)
                  : '0.00'
              }}
            </template>
            <template #item-premiumWithVat="item">
              {{
                convertToNumber(
                  item.discountPremium + item.vat + getAddonVat(item),
                )
              }}
            </template>
            <template #item-action="item">
              <div class="flex gap-2">
                <x-button
                  v-if="
                    quote.quote_status_id !=
                      page.props.quoteStatusEnum.PolicyCancelled ||
                    page.props.linkedQuoteDetails.childLeadsCount == 0
                  "
                  size="xs"
                  color="primary"
                  outlined
                  @click.prevent="selectPlan(item)"
                >
                  View
                </x-button>
                <div v-if="readOnlyMode.isDisable === true">
                  <x-button
                    size="xs"
                    color="error"
                    outlined
                    @click.prevent="copyPlanURL(item)"
                    v-if="item.discountPremium + item.vat + totalPriceVAT > 0"
                    :disabled="
                      page.props.linkedQuoteDetails.childLeadsCount > 0
                    "
                  >
                    Copy
                  </x-button>
                </div>
                <!-- <template
                  v-if="item.actualPremium > 0 && item.id != record.plan_id"
                >
                  <x-button
                    v-if="
                      access.carAdvisorCanEditPaymentCancelledRefund ||
                      access.carAdvisorCanEditInsurer ||
                      access.carManagerCanEditInsurer
                    "
                    size="xs"
                    color="error"
                    outlined
                    @click="confirmChangeInsurer(item)"
                    :disabled="
                      page.props.linkedQuoteDetails.childLeadsCount > 0
                    "
                  >
                    Change Insurer
                  </x-button>
                </template> -->

                <span>
                  <SelectPlan
                    v-if="selectedProviderPlan.id != item.id"
                    @update:selectedPlanChanged="handlePlanSelected"
                    :plan="item"
                    :quoteType="quoteType"
                    :has-child-lead="
                      page.props.linkedQuoteDetails.childLeadsCount > 0
                    "
                    :uuid="quote.uuid"
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
        </template>
      </Collapsible>

      <x-modal
        v-model="modals.changeInsurer"
        title="Change Insurer"
        show-close
        backdrop
      >
        <p>Are you sure to change insurer?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.changeInsurer = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              :loading="changeInsurerForm.processing"
              @click.prevent="onConfirmChangeInsurer"
            >
              Yes
            </x-button>
          </div>
        </template>
      </x-modal>

      <FollowUpReasons
        v-if="can(permissionEnum.PAUSE_AUTO_FOLLOWUPS)"
        :modelValue="showfollowup"
        @update:modelValue="value => closeModal(value)"
        :uuid="page.props.record.id"
        :source="page.props.record.source"
        :followUpId="followUpId"
        :kyoEndPoint="kyoEndPoint"
        :quoteUuid="page.props.record.uuid"
      />

      <x-modal
        v-model="modals.plan"
        size="xl"
        :title="`${selectedPlan?.providerName} - ${selectedPlan?.name}`"
        show-close
        backdrop
        :has-actions="false"
      >
        <LazyAvailablePlan
          :plan="selectedPlan"
          :genders="genderOptions"
          :record="record"
          :access="access"
          :genericRequestEnum="genericRequestEnum"
          :notAdvisorAndManagerAndPA="
            !hasAnyRole([
              rolesEnum.CarAdvisor,
              rolesEnum.CarManager,
              rolesEnum.PA,
            ])
          "
          :isPlanUpdateActive="isPlanUpdateActive"
          :hidden="
            !hasAnyRole([
              rolesEnum.CarAdvisor,
              rolesEnum.CarManager,
              rolesEnum.PA,
            ])
          "
          :totalSelectedAddonsPriceWithVat="totalPriceVAT"
          @onLoadAvailablePlansData="onLoadAvailablePlansData"
        />
      </x-modal>

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
            <x-button size="sm" color="error" @click.prevent="confirmSendEmail">
              Send
            </x-button>
          </div>
        </template>
      </x-modal>
      <AppModal
        :actions="true"
        :showHeader="true"
        v-model:modelValue="modals.sendOCBConfirmNB"
        :backdrop-close="false"
      >
        <template #header>
          <p>Send Email OCB NB</p>
        </template>
        <template #default>
          <p>Are you sure send email to customer?</p>
        </template>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.sendOCBConfirmNB = false"
              :disable="processingOCBEmailNB"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              :loading="processingOCBEmailNB"
              @click.prevent="confirmSendOCBEmailNB"
            >
              Send
            </x-button>
          </div>
        </template>
      </AppModal>
      <x-modal
        v-model="modals.createPlan"
        size="xl"
        title="Create Car Quote"
        show-close
        backdrop
      >
        <LazyCreatePlan
          :record="record"
          :insuranceProviders="insuranceProviders"
          :available-plans="availablePlansItems"
          @success="onCreatePlan"
          @error="onPlanError"
        />
        <!-- missing @success="onCreatePlan"
         missing @error="onPlanError" -->
      </x-modal>
    </div>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="record.id"
      :paymentCode="record.code"
      :quoteType="quoteType"
    />

    <PaymentTableNew
      v-if="isNewPaymentStructure"
      quoteType="Car"
      :payments="payments"
      :proformaPayment="
        payments.find(
          item =>
            item.payment_methods_code ===
            page.props.paymentMethodsEnum.ProformaPaymentRequest,
        )
      "
      :paymentDocument="paymentDocument"
      :quoteRequest="paymentEntityModel"
      :paymentStatusEnum="paymentStatusEnum"
      :paymentTooltipEnum="paymentTooltipEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="storageUrl"
      :isPlanDetailEnabled="isPlanDetailEnabled"
      :expanded="sectionExpanded"
    />
    <PaymentTable
      v-else
      :payments="payments"
      :quoteRequest="paymentEntityModel"
      :paymentStatusEnum="paymentStatusEnum"
      :isCommercialVehicles="isCommercialVehicles"
      :carInsuranceProviders="carInsuranceProviders"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name };
        })
      "
    />

    <div
      class="p-4 rounded shadow mb-6 bg-white"
      v-if="can(permissionEnum.PAUSE_AUTO_FOLLOWUPS)"
    >
      <x-modal
        v-model="modals.showEmailEventsModal"
        size="lg"
        title="Email Events"
        show-close
        backdrop
      >
        <DataTable
          table-class-name="compact"
          :headers="emailEventsTable"
          :items="emailEvents"
          border-cell
          hide-rows-per-page
          :rows-per-page="10"
          :hide-footer="followUpEmails.length < 10"
        >
        </DataTable>
      </x-modal>
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <h3 class="font-semibold text-primary-800 text-lg">
            Auto Followup - Emails
          </h3>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <DataTable
            table-class-name="tablefixed compact"
            :headers="emailsHeaders"
            :items="followUpEmails || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="followUpEmails.length < 15"
          >
            <template #item-actions="item">
              <div class="flex gap-2">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  :loading="loadingEmailEvents == item.id"
                  @click.prevent="loadEmailEvents(item)"
                >
                  View Events
                </x-button>
              </div>
            </template>
          </DataTable>

          <h3 class="font-semibold text-primary-800 text-lg mt-5 mb-4">
            Auto Followup - Actions
          </h3>
          <DataTable
            table-class-name="tablefixed compact"
            :headers="actionsHeaders"
            :items="followUpActions || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="followUpActions.length < 15"
          >
          </DataTable>
        </template>
      </Collapsible>
    </div>

    <EmbeddedProducts
      :data="lazyEmbeddedProducts || []"
      :link="record.uuid"
      :code="record.code"
      :quote="record"
      :modelType="quoteType"
      :expanded="sectionExpanded"
      :isEpLoading="lazyEmbeddedProductsLoading"
      :key="lazyEmbeddedProductsLoading"
    />

    <PolicyDetail
      v-if="isQuoteDocumentEnabled"
      :quote="quote"
      :availablePlans="availablePlansTable.data"
      :modelType="quoteType"
      :payments="payments"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="page.props.quoteDocuments || []"
      :storageUrl="storageUrl"
      :quote="record"
      :expanded="sectionExpanded"
      @copyUploadURL="copyUploadURL"
      quoteType="Car"
      :paymentStatusEnum="paymentStatusEnum"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionEnum.VIEW_INSLY_BOOK_POLICY,
          permissionEnum.SEND_INSLY_BOOK_POLICY,
          permissionEnum.VIEW_ALL_LEADS,
        ])
      "
      :quote="quote"
      :quoteType="quoteType"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
    />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="record"
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
            <div>
              <template
                v-if="
                  !can(permissionEnum.ApprovePayments) && !hasRole(rolesEnum.PA)
                "
              >
                <template v-if="displaySendPolicyButton">
                  <!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                                data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
                </template>
              </template>
              <x-button
                v-if="
                  record.payment_status_id === permissionEnum.AUTHORISED &&
                  !hasRole(rolesEnum.PA)
                "
                @click.prevent="onAddPaymentModal"
                size="sm"
                color="orange"
                class="mr-2"
              >
                Copy upload Link
              </x-button>
            </div>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <DataTable
            table-class-name="tablefixed compact"
            :headers="emailStatusTable.columns"
            :items="emailStatuses || []"
            show-index
            border-cell
            hide-rows-per-page
            hide-footer
          >
            <template #item-action="item">
              <div class="flex gap-2">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  @click.prevent="onEditMember(item)"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Edit
                </x-button>
                <x-button
                  size="xs"
                  color="error"
                  outlined
                  @click.prevent="memberDelete(item.id)"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Delete
                </x-button>
              </div>
            </template>
          </DataTable>
        </template>
      </Collapsible>
    </div>

    <!-- <div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Notes for Customer
					<x-tag size="sm">{{ notesForCustomers.length || 0 }}</x-tag>
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">
						<x-button @click.prevent="addNotes" size="sm" color="orange" class="mr-2">
							Send Notes to Customer
						</x-button>
					</template>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="notesForCustomersTable.columns"
				:items="notesForCustomersTableItems || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
			</DataTable>
			<x-modal v-model="modals.notes" size="lg" show-close backdrop>
        		<template #header> New Note for Customer </template>

				<x-form @submit="onNoteSubmit" :auto-focus="false">
				<div class="grid">

					<x-textarea
					v-model="notesForm.description"
					label=""
					placeholder = "Type Here.."
					rows = 10
					:adjust-to-text="false"
					:rules="[
						isRequired,
					]"
					class="w-full"
					/>
					<small class = "">Max allowed 500 characters</small>

				</div>

				<div class="text-right space-x-4 mt-12">
					<small class = "text-red-600">(Note: Once added it cannot be edited or deleted.)</small>
					<x-button size="sm" @click.prevent="modals.notes = false">
					Cancel
					</x-button>

					<x-button
					size="sm"
					color="emerald"
					:loading="notesForm.processing"
					type="submit"
					>
					Send Note
					</x-button>
				</div>
				</x-form>
          </x-modal>
		</div>  -->

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Lead Activities
              <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="mb-4 flex justify-end">
            <template
              v-if="
                !can(permissionEnum.ApprovePayments) && !hasRole(rolesEnum.PA)
              "
            >
              <x-button
                @click.prevent="addActivity"
                size="sm"
                color="orange"
                class="mr-2"
                v-if="readOnlyMode.isDisable === true"
              >
                Add Activity
              </x-button>
            </template>
          </div>
          <DataTable
            table-class-name="tablefixed compact"
            :headers="leadActivities.columns"
            :items="activities || []"
            show-index
            border-cell
            hide-rows-per-page
            hide-footer
          >
            <template #item-status="{ status, id }">
              <x-checkbox
                color="emerald"
                size="xl"
                :modelValue="status === 1"
                :disabled="status === 1"
                @change="onActivityStatusUpdate(id)"
              />
            </template>
            <template #item-action="item">
              <div class="flex gap-2">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  :disabled="item.status === 1"
                  @click.prevent="activityEdit(item)"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Edit
                </x-button>
                <x-button
                  size="xs"
                  color="error"
                  outlined
                  :disabled="item.status === 1"
                  @click.prevent="activityDelete(item.id)"
                  v-if="
                    readOnlyMode.isDisable === true &&
                    item.user_id &&
                    item.user_id != null
                  "
                  :key="item.user_id"
                >
                  Delete
                </x-button>
              </div>
            </template>
          </DataTable>
        </template>
      </Collapsible>
      <x-modal
        v-model="modals.activityConfirm"
        title="Delete Activity"
        show-close
        backdrop
      >
        <p>Are you sure you want to delete this activity?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.activityConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              :loading="activityForm.processing"
              @click.prevent="activityDeleteConfirmed"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal>
      <x-modal
        v-model="modals.activity"
        size="lg"
        :title="`${activityActionEdit ? 'Edit' : 'Add'} Lead Activity`"
        show-close
        backdrop
        is-form
        @submit="onActivitySubmit"
      >
        <div class="grid gap-4">
          <x-field label="Title" required>
            <x-input
              v-model="activityForm.title"
              :rules="[isRequired]"
              class="w-full"
            />
          </x-field>
          <x-field label="Description" required>
            <x-textarea
              v-model="activityForm.description"
              :adjust-to-text="false"
              class="w-full"
            />
          </x-field>
          <x-field label="Assignee" required>
            <x-select
              v-model="activityForm.assignee_id"
              :options="advisorOptions"
              :rules="[isRequired]"
              placeholder="Select Assignee"
              class="w-full"
            />
          </x-field>
          <x-field label="Due Date" required>
            <date-picker
              v-model="activityForm.due_date"
              :rules="[isRequired]"
              class="w-full"
              withTime
              :timezone="'UTC'"
            />
          </x-field>
        </div>

        <template #secondary-action>
          <x-button
            ghost
            tabindex="-1"
            size="sm"
            @click.prevent="modals.activity = false"
          >
            Cancel
          </x-button>
        </template>
        <template #primary-action>
          <x-button
            size="sm"
            color="emerald"
            :loading="activityForm.processing"
            type="submit"
          >
            {{ activityActionEdit ? 'Update' : 'Save' }}
          </x-button>
        </template>
      </x-modal>
    </div>

    <CustomerAdditionalContacts
      quoteType="Car"
      :customerId="record.customer_id"
      :quoteId="record.id"
      :contacts="customerAdditionInfoList"
      :quoteEmail="record.email"
      :quoteMobile="record.mobile_no"
      :canDelete="false"
      :has-child-lead="page.props.linkedQuoteDetails.childLeadsCount > 0"
      :expanded="sectionExpanded"
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

    <CustomerChatLogs
      :customerName="record?.first_name + ' ' + record?.last_name"
      :quoteId="quote.uuid"
      :quoteType="'CAR'"
      :expanded="sectionExpanded"
    />
  </div>
  <AuditLogs
    :quoteType="quoteType"
    :type="modelClass"
    :id="$page.props.record.id"
    :quoteCode="$page.props.record.code"
    :expanded="sectionExpanded"
  />
  <ApiLogs
    v-if="can(permissionEnum.API_LOG_VIEW)"
    :type="modelClass"
    :id="$page.props.record.id"
    :expanded="sectionExpanded"
  />

  <ClientInquiryLogs
    v-if="clientInquiryLogs?.length > 0"
    :logs="clientInquiryLogs"
  />

  <lead-raw-data
    :modelType="'Car'"
    :code="$page.props.quote.code"
  ></lead-raw-data>
</template>
