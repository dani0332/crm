<script setup>
import { computed, onMounted } from 'vue';
import PaymentTable from './Partials/PaymentTable.vue';
import LazyAvailablePlan from './../Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import FollowUpReasons from './Partials/FollowUpReasons.vue';

defineProps({
  quote: Object,
  leadStatuses: Array, //
  ecomDetails: Object,
  membersDetail: Array,
  memberCategories: Array,
  salaryBands: Array,
  nationalities: Array,
  emirates: Array,
  advisors: Array,
  listQuotePlans: Array, //
  quoteDocuments: Array,
  documentTypes: Object,
  cdnPath: String,
  ecomHealthInsuranceQuoteUrl: String,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  tiers: Array,
  quoteStatusEnum: Object,
  carPlanFeaturesCodeEnum: Object,
  carPlanExclusionsCodeEnum: Object,
  carPlanAddonsCodeEnum: Object,
  paymentStatusEnum: Object,
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
  //
  isPlanUpdateActive: Boolean,
  yearsOfManufacture: Array,
  access: Object,
  record: Object,
  quoteType: String,
  paymentEntityModel: Object,
  displaySendPolicyButton: Number,
  isRenewalUser: Boolean,
  emailStatuses: Array,
  carQuotePlanAddons: Array,
  notesForCustomers: Object,
  websiteURL: String,
  docUploadURL: String,
  planURL: String,
  storageUrl: String,
  insuranceProviders: Array,
  advisor: Array,
  carMakeText: String,
  carModelText: String,
  embeddedProducts: Array,
  genericRequestEnum: Object,
});
const page = usePage();
const notification = useNotifications('toast');
const showfollowup = ref(false);

const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const { isRequired, isEmail, isNumber, isMobile } = useRules();

const leadStatusForm = useForm({
  modelType: 'Car',
  leadId: page.props.record.id,
  quote_uuid: page.props.record.uuid,
  assigned_to_user_id: page.props.record.advisor_id,
  leadStatus: page.props.record.quote_status_id || null,
  notes: page.props.record.notes || null,
  trans_code: page.props.record.transapp_code || null,
  lostReason: page.props.record.lost_reason_id || null,
  next_followup_date: page.props.record.next_followup_date || null,
  tier_id: page.props.record.tier_id || null,
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
const selectedPlans = ref([]);
const selectedPlan = ref({});

const availablePlansTable = reactive({
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
    { text: 'Actual Premium', value: 'actualPremium' },
    { text: 'Discounted Premium', value: 'discountPremium' },
    { text: 'Premium with VAT.', value: 'premiumWithVat' },
    { text: 'Excess', value: 'excess' },
    { text: 'Action', value: 'action' },
  ],
});

const documentsTable = reactive({
  columns: [
    { text: 'Document Type', value: 'document_type_text' },
    { text: 'Document Name', value: 'document_name_text' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Created By', value: 'created_by' },
    { text: 'Action', value: 'action' },
  ],
});

const documentsTableItems = computed(() => {
  return page.props.quoteDocuments.map(doc => {
    return {
      document_type_text:
        doc.document_type_text.length > 0 ? doc.document_type_text : '',
      document_name_text: doc.doc_name,
      created_at: doc.created_at,
      doc_uuid: doc.doc_uuid,
      doc_url: doc.doc_url,
      created_by: doc.created_by ? doc.created_by.name : '',
    };
  });
});

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
    { text: 'Title', value: 'title' },
    { text: 'Client Name', value: 'client_name' },
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

const customerAdditionalContactsTable = reactive({
  columns: [
    { text: 'Type', value: 'key' },
    { text: 'Value', value: 'value' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Action', value: 'action' },
  ],
});

// history data
const historyData = ref(null);
const historyLoading = ref(false);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=car&recordId=${page.props.paymentEntityModel.id}`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Lead Status', value: 'NewStatus' },
  { text: 'Advisor', value: '' },
  { text: 'Notes', value: 'NewNotes' },
];

const availablePlansItems = computed(() => {
  if (!Array.isArray(page.props.listQuotePlans)) {
    return [];
  }
  return typeof page.props.listQuotePlans !== 'string'
    ? page.props.listQuotePlans
    : [];
});

const totalPriceVAT = computed(() => {
  let vat = 0;
  availablePlansItems?.value.forEach(item => {
    item.addons.forEach(addon => {
      addon.carAddonOption.forEach(option => {
        if (option.isSelected && option.price != 0) {
          vat += option.price + option.vat;
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

const leadStatusOptions = computed(() => {
  const isAdmin = hasRole(rolesEnum.Admin);
  const isLeadPool = isAdmin || hasRole(rolesEnum.LeadPool);
  const isPA = isAdmin || hasRole(rolesEnum.PA);
  const renewal_batch = page.props.record.renewal_batch;
  const previous_quote_policy_number =
    page.props.record.previous_quote_policy_number;
  const source = page.props.record.source;
  const renewal_upload = page.props.leadSourceEnum.RENEWAL_UPLOAD;

  const filteredLeadStatuses = page.props.leadStatuses.filter(status => {
    // if ((!isLeadPool && [9, 35].includes(status.id)) || (!isPA && status.id === 15) || ((renewal_batch === '' || previous_quote_policy_number === '' || source != renewal_upload) && status.id === 17)) {
    //   return false;
    // }
    return true;
  });

  return filteredLeadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});
console.log('leadStatusOptions', leadStatusOptions.value);
const leadStatusDisabled = computed(() => {
  return (
    page.props.record.quote_status_id ==
      page.props.quoteStatusEnum.TransactionApproved ||
    page.props.record.quote_status_id == page.props.quoteStatusEnum.Duplicate ||
    (page.props.record.quote_status_id == page.props.quoteStatusEnum.Fake &&
      !hasAnyRole([rolesEnum.LeadPool, rolesEnum.Admin]))
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
  is_gcc_standard: page.props.record.is_gcc_standard || null,
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

const policyDetailsState = reactive({
  isEditing: false,
});
const policyDetailsForm = useForm({
  quote_policy_number: page.props.record.policy_number || null,
  quote_policy_issuance_date: page.props.record.policy_issuance_date || null,
  quote_policy_start_date: page.props.record.policy_start_date || null,
  quote_policy_expiry_date: page.props.record.renewal_expiry_date || null,
  quote_premium: page.props.record.premium || null,
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
  notes: false,
  plan: false,
  docConfirm: false,
  createPlan: false,
  sendConfirm: false,
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
  assignee_id: null,
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

const copyUploadURL = () => {
  copy(page.props.docUploadURL);
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

const isUploading = ref(false);

const docForm = useForm({
  quote_id: usePage().props.record.id || null,
  quote_uuid: usePage().props.record.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const uploadFile = (doc, files) => {
  let url = '/quotes/car/documents/store';

  if (files.length == 0) return;
  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      file: files[0].file,
    }))
    .post(url, {
      preserveScroll: true,
      preserveState: true,
      onError: errors => {
        docForm.setError(errors.error);
        console.log('errors');
        console.log(errors);
        notification.error({
          title: 'File upload failed',
          position: 'top',
        });
      },
      onSuccess: () => {
        notification.success({
          title: 'File Uploaded',
          position: 'top',
        });
      },
      onFinish: () => {
        isUploading.value = false;
      },
    });
};

const confirmDeleteDocData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteDocData.docs = name;
};

const confirmDeleteDoc = () => {
  documentsTable.isLoading = true;
  router.post(
    `/documents/delete`,
    {
      docName: confirmDeleteDocData.docs,
      quoteId: page.props.record.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        documentsTable.isLoading = false;
        notification.error({
          title: 'File Deleted',
          position: 'top',
        });
      },
    },
  );
};

const copyLink = () => {
  copy(page.props.planURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboardd',
      position: 'top',
    });
};

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Car/${page.props.record.id}/update-lead-status`,
    {
      preserveScroll: true,
      onError: errors => {
        console.log(errors);
      },
      onSuccess: () => {
        notification.success({
          title: 'Lead Status Updated',
          position: 'top',
        });
      },
    },
  );
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
const exportLoader = ref(false);
const onExportPlans = () => {
  if (selectedPlans.value.length < 3 || selectedPlans.value.length > 5) {
    notification.error({
      title: 'Please select 3 to 5 plans to download PDF.',
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

const disableFollowUp = ref(false);
const followUpstatus = ref('');
const followUpEmails = ref([]);
const emailsHeaders = ref([
  { text: 'Email Subject', value: 'customer_email' },
  { text: 'Status', value: 'status' },
  { text: 'Created At', value: 'created_at' },
]);
const followUpActions = ref([]);
const actionsHeaders = ref([
  { text: 'Type', value: 'type' },
  { text: 'Reason Id', value: 'reason_id' },
  { text: 'Notes', value: 'notes' },
  { text: 'Created At', value: 'created_at' },
]);

const getFollowUpsByQuote = () => {
  axios
    .get(
      `https://kyo.alfred.ae/kyostg/api/v1/followups/car/${page.props.record.uuid}`,
    )
    .then(response => {
      let { status, emails, actions } = response.data.data;
      if (status == 'PENDING' || status == 'IN_PROGRESS')
        disableFollowUp.value = true;
      followUpstatus.value = status;
      followUpEmails.value = emails;
      followUpActions.value = actions;
    })
    .catch(error => {
      console.log(error);
    });
};

onMounted(() => getFollowUpsByQuote());
</script>

<template>
  <div>
    <Head title="Car Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">E-COM Detail</h2>
    </div>
    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREMIUM</dt>
            <dd>{{ record.premium ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ record.paid_at ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ record.payment_status_id_text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ record.car_plan_provider_id_text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT METHOD</dt>
            <dd>
              {{
                record.payment_gateway === 'NGENIUS'
                  ? 'CREDIT CARD'
                  : record.payment_gateway
              }}
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PLAN NAME</dt>
            <dd>{{ record.plan_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ECOMMERCE</dt>
            <dd>{{ record.is_ecommerce == 1 ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">QUOTE LINK</dt>
            <dd>{{ record.quote_link ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ORDER REFERENCE</dt>
            <dd>{{ record.order_reference ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT REFERENCE</dt>
            <dd>{{ record.payment_reference ?? '' }}</dd>
          </div>
        </dl>
        <div class="grid sm:grid-cols-1 mt-3">
          <dt class="font-medium mb-3">ADDONS</dt>
          <dd>
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
                  <td style="width: 20%">{{ addon.car_addon_option_value }}</td>
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
        </div>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Car Details</h3>
        <div>
          <template
            v-if="
              !can(permissionEnum.ApprovePayments) &&
              allowedDuplicateLOB.length > 0
            "
          >
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

          <!-- <Link :href="route('health.index')" preserve-scroll>
              <x-button size="sm" color="primary" tag="div"> Health List </x-button>
            </Link> -->

          <Link :href="route('car.index')">
            <x-button size="sm" tag="div">Car List</x-button>
          </Link>
        </div>
      </div>
      <x-divider class="mb-4 mt-1" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">REF-ID</dt>
            <dd>{{ record.code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BATCH</dt>
            <dd>{{ record.quote_batch_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ record.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ record.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ record.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CUSTOMER AGE</dt>
            <dd>{{ record.customer_age }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PHONE NUMBER</dt>
            <dd>{{ record.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ record.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LEAD SOURCE</dt>
            <dd>{{ record.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ record.nationality_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UAE LICENCE HELD FOR</dt>
            <dd>{{ record.uae_license_held_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">HOME COUNTRY DRIVING LICENSE HELD FOR</dt>
            <dd>{{ record.back_home_license_held_for_id_text ?? '' }}</dd>
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
            <dd>{{ record.trim }}</dd>
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
            <dd>{{ record.dob }}</dd>
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
            <dd>{{ record.has_ncd_supporting_documents ? 'Yes' : 'No' }}</dd>
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
            <dt class="font-medium">PARENT REF-ID</dt>
            <dd>{{ record.parent_duplicate_quote_id ?? '' }}</dd>
          </div>
        </dl>
      </div>
      <x-divider class="mb-4 mt-4" />
      <div class="flex justify-end mb-4">
        <Link :href="route('car.edit', record.uuid)">
          <x-button size="sm" color="primary" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>
    <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
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
          <x-button
            color="orange"
            type="submit"
            :loading="leadDuplicateForm.processing"
          >
            Create Duplicate
          </x-button>
        </div>
      </x-form>
    </x-modal>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Last Year's Policy Details
        </h3>
      </div>
      <x-divider class="mb-4 mt-1" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Renewal Batch#</dt>
            <dd>{{ record.renewal_batch }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Previous Policy Number</dt>
            <dd>{{ record.previous_quote_policy_number ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Previous Policy Expiry Date</dt>
            <dd>{{ record.previous_policy_expiry_date ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Previous Policy Premium</dt>
            <dd>{{ record.previous_quote_policy_premium ?? '' }}</dd>
          </div>
          <template v-if="hasRole(rolesEnum.Admin)">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Import Code</dt>
              <dd>{{ record.renewal_import_code }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium"></dt>
              <dd></dd>
            </div>
          </template>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Number</dt>
            <dd>{{ record.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Renewal Expiry Date</dt>
            <dd>{{ record.renewal_expiry_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Lost reason</dt>
            <dd>{{ record.lost_reason }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/3">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="leadStatusDisabled"
              placeholder="Lead Status"
              class="w-full"
            />

            <x-field label="TransApp Code" required>
              <x-input
                v-if="leadStatusForm.leadStatus == 15"
                v-model="leadStatusForm.trans_code"
                placeholder="TransApp Code is required"
                class="w-full"
                :error="leadStatusForm.errors.trans_code"
              />
            </x-field>

            <x-select
              v-if="leadStatusForm.leadStatus == 17"
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
            />

            <x-input
              v-if="
                leadStatusForm.leadStatus == quoteStatusEnum.FollowupCall ||
                leadStatusForm.leadStatus == quoteStatusEnum.Interested ||
                leadStatusForm.leadStatus == quoteStatusEnum.NoAnswer
              "
              v-model="leadStatusForm.next_followup_date"
              label="Followup Date"
              type="datetime-local"
              placeholder="Please select follow-up date & time"
              class="w-full"
              :error="leadStatusForm.errors.next_followup_date"
            />

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
              class="w-full"
              :error="leadStatusForm.errors.tier_id"
            />
          </div>
        </div>
        <div class="w-full md:w-2/3">
          <x-textarea
            v-model="leadStatusForm.notes"
            type="text"
            label="Notes"
            placeholder="Lead Notes"
            class="w-full"
            :disabled="record.quote_status_id == 15"
          />

          <div class="flex justify-end">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
            >
              Change Status
            </x-button>
          </div>
        </div>
      </div>
    </div>

    <PaymentTable
      v-if="hasRole(rolesEnum.BetaUser)"
      :payments="payments"
      :quoteRequest="paymentEntityModel"
      :paymentStatusEnum="paymentStatusEnum"
      :paymentMethods="
        paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name };
        })
      "
    />
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Assumptions</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-field label="Cylinder" required>
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
            <x-field label="Is Vehicle modified?" required>
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
            <x-field label="Current Insurance" required>
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Available Plans
          <x-tag size="sm">{{ availablePlansItems.length || 0 }}</x-tag>
        </h3>
        <div v-if="!hasRole(rolesEnum.PA)">
          <x-tooltip>
            <x-button
              class="ml-2 mr-2"
              :disabled="disableFollowUp"
              size="sm"
              color="rose"
              @click="showfollowup = !showfollowup"
            >
              Pause Follow-up to customer
            </x-button>
            <template #tooltip>
              <span>{{ followUpstatus }}</span>
            </template>
          </x-tooltip>
          <x-button-group v-if="selectedPlans.length > 0" size="sm">
            <x-button
              @click.prevent="onTogglePlans(false)"
              :loading="toggleLoader"
            >
              Show
            </x-button>
            <x-button
              @click.prevent="onTogglePlans(true)"
              :loading="toggleLoader"
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
          >
            Download PDF
          </x-button>
          <!-- <x-button @click.prevent="modals.sendConfirm = true" size="sm" color="orange" class="mr-2" :disabled="record.advisor_id != $page.props.auth.user.id || !record.previous_quote_policy_number">
						Send OCB Email to Customer
					</x-button> -->

          <x-button
            @click.prevent="modals.createPlan = true"
            size="sm"
            color="orange"
            class="mr-2"
            v-if="
              (access.carManagerCanEdit || access.carAdvisorCanEdit) &&
              can(permissionEnum.CarQuotesPlansCreate)
            "
          >
            Add Plan
          </x-button>
          <x-button
            v-else-if="
              hasRole(rolesEnum.Admin) &&
              can(permissionEnum.CarQuotesPlansCreate)
            "
            @click.prevent="modals.createPlan = true"
            size="sm"
            color="orange"
            class="mr-2"
          >
            Add Plan
          </x-button>
          <x-button
            @click.prevent="copyLink"
            size="sm"
            color="emerald"
            v-if="
              typeof listQuotePlans !== 'string' && listQuotePlans.length > 0
            "
          >
            Copy Link
          </x-button>
        </div>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
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
          </div>
        </template>
        <template #item-name="item">
          <span
            class="text-primary-600 cursor-pointer"
            @click.prevent="selectPlan(item)"
            >{{ item.name }}</span
          >
        </template>
        <template #item-benefits="{ benefits }">
          <!-- <span>{{ benefits.feature }}</span> -->
          <template v-for="feature in benefits.feature" :key="feature">
            <template v-if="feature.code">
              <span
                v-if="
                  feature.code === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT ||
                  feature.code === carPlanFeaturesCodeEnum.DAMAGE_LIMIT
                "
              >
                {{ feature.value }}
              </span>
            </template>
            <span
              v-else-if="
                feature.text === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT_TEXT
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
          {{ actualPremium ? parseFloat(actualPremium).toFixed(2) : '0.00' }}
        </template>
        <template #item-discountPremium="{ discountPremium }">
          {{
            discountPremium ? parseFloat(discountPremium).toFixed(2) : '0.00'
          }}
        </template>
        <template #item-premiumWithVat="item">
          {{
            parseFloat(item.discountPremium + item.vat + totalPriceVAT).toFixed(
              2,
            )
          }}
        </template>
        <template #item-action="item">
          <div class="flex gap-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="selectPlan(item)"
            >
              View
            </x-button>
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="copyPlanURL(item)"
              v-if="item.discountPremium + item.vat + totalPriceVAT > 0"
            >
              Copy
            </x-button>
            <template
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
              >
                Change Insurer
              </x-button>
            </template>
          </div>
        </template>
      </DataTable>

      <FollowUpReasons
        :modelValue="showfollowup"
        @update:modelValue="showfollowup = false"
        :uuid="page.props.record.id"
        :source="page.props.record.source"
      />
      <x-modal v-model="modals.plan" size="xl" show-close backdrop>
        <template #header>
          {{ selectedPlan.providerName }} - {{ selectedPlan.name }}
        </template>
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
        />
      </x-modal>
      <x-modal v-model="modals.sendConfirm" show-close backdrop>
        <template #header> Send Email </template>
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
      <x-modal v-model="modals.createPlan" size="xl" show-close backdrop>
        <template #header> Create Car Quote </template>
        <LazyCreatePlan
          :record="record"
          :insuranceProviders="insuranceProviders"
          :listQuotePlans="listQuotePlans"
          @success="onCreatePlan"
          @error="onPlanError"
        />
      </x-modal>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <h3 class="font-semibold text-primary-800 text-lg mb-4">Emails</h3>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="emailsHeaders"
        :items="followUpEmails || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="followUpEmails.length < 15"
      >
      </DataTable>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <h3 class="font-semibold text-primary-800 text-lg mb-4">Actions</h3>
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
    </div>

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="record.uuid"
      :code="record.code"
    />

    <div class="p-4 rounded shadow mb-6 bg-white" v-if="isQuoteDocumentEnabled">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-textarea
            v-model="policyDetailsForm.quote_policy_number"
            type="text"
            label="Policy Number"
            placeholder="Policy Number"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_issuance_date"
            name="quote_policy_issuance_date"
            label="Issuance Date"
            placeholder="Issuance Date"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_start_date"
            type="text"
            label="Policy Start Date"
            placeholder="Policy Start Date"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_expiry_date"
            type="text"
            label="Expiry Date"
            placeholder="Expiry Date"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyDetailsForm.quote_premium"
            type="number"
            label="Price"
            placeholder="Price"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
        <div class="w-full md:w-1/2" />
      </div>
      <div
        class="flex justify-end"
        v-if="
          !hasRole(rolesEnum.PA) &&
          record.quote_status_id == quoteStatusEnum.TransactionApproved
        "
      >
        <x-button
          v-if="policyDetailsState.isEditing"
          class="mt-4 mr-2"
          color="emerald"
          size="sm"
          :loading="policyDetailsForm.processing"
          @click.prevent="policyDetailsState.isEditing = false"
        >
          Cancel
        </x-button>
        <x-button
          v-if="policyDetailsState.isEditing"
          class="mt-4"
          color="emerald"
          size="sm"
          :loading="policyDetailsForm.processing"
          @click.prevent="onUpdatePolicyDetails"
        >
          Update
        </x-button>
        <x-button
          v-if="!policyDetailsState.isEditing"
          class="mt-4"
          color="emerald"
          size="sm"
          @click.prevent="policyDetailsState.isEditing = true"
        >
          Edit
        </x-button>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white" v-if="isQuoteDocumentEnabled">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Documents</h3>
        <div>
          <x-button
            class="mr-2"
            v-if="
              record.payment_status_id === paymentStatusEnum.AUTHORISED &&
              !hasRole(rolesEnum.PA)
            "
            @click.prevent="copyUploadURL"
            size="sm"
            color="orange"
          >
            Copy upload Link
          </x-button>
          <template
            v-if="
              !can(permissionEnum.ApprovePayments) && !hasRole(rolesEnum.PA)
            "
          >
            <!-- <Link :href="`${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link> -->
            <x-button
              @click.prevent="modals.doc = true"
              size="sm"
              color="orange"
            >
              Upload Documents
            </x-button>
            <template v-if="displaySendPolicyButton">
              <x-button
                class="ml-2 mr-2"
                size="sm"
                color="orange"
                @click.prevent="sendQuoteDocumentsToCustomer"
              >
                Send Policy
              </x-button>
            </template>
          </template>
        </div>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="documentsTable.columns"
        :items="documentsTableItems || []"
        show-index
        border-cell
        fixed-checkbox
        hide-rows-per-page
        hide-footer
      >
        <template #item-document_name_text="item">
          <Link :href="storageUrl + item.doc_url">{{
            item.document_name_text
          }}</Link>
        </template>
        <template #item-action="item">
          <div class="flex gap-2">
            <x-button
              v-if="
                !hasRole(rolesEnum.PA) && !can(permissionEnum.ApprovePayments)
              "
              size="xs"
              color="error"
              outlined
              @click.prevent="onDocDelete(item.document_name_text)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <x-modal v-model="modals.docConfirm" show-close backdrop>
        <template #header> Delete Document </template>
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
              :loading="documentsTable.isLoading"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal>
      <x-modal v-model="modals.doc" size="xl" show-close backdrop>
        <template #header> Upload Documents </template>

        <x-alert
          color="error"
          class="mb-5"
          v-if="Object.keys(docForm.errors).length"
        >
          <ul>
            <li v-for="error in docForm?.errors" :key="error">{{ error }}</li>
          </ul>
        </x-alert>

        <div
          v-for="documentType in documentTypes"
          :key="documentType.id"
          class="grid md:grid-cols-2 gap-2 my-4 border-b"
        >
          <div class="flex flex-col gap-1">
            <h5 class="text-sm font-semibold">
              {{ documentType.text }}
            </h5>
            <p class="text-xs">Max files: {{ documentType.max_files }}</p>
            <p class="text-xs">Supported: {{ documentType.accepted_files }}</p>
            <p class="text-xs">Max file size: {{ documentType.max_size }} MB</p>
          </div>
          <div class="pb-4">
            <Dropzone
              :id="documentType.id"
              :accept="documentType.accepted_files"
              :max-files="documentType.max_files"
              :max-size="documentType.max_size"
              :loading="docForm.processing"
              @change="uploadFile(documentType, $event)"
            />
            <a
              v-for="quoteDocument in page.props.quoteDocuments.filter(
                d => d.document_type_code == documentType.code,
              )"
              :key="quoteDocument.id"
              :href="storageUrl + quoteDocument.doc_url"
              target="_blank"
              class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
            >
              {{ quoteDocument.original_name || quoteDocument.doc_name }}
            </a>
          </div>
        </div>
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
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
      <DataTable
        table-class-name="tablefixed compact"
        :headers="emailStatusTable.columns"
        :items="emailStatuses || []"
        show-index
        border-cell
        fixed-checkbox
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
            >
              Edit
            </x-button>
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="memberDelete(item.id)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
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
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Lead Activities
          <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
        </h3>
        <div>
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
            >
              Add Activity
            </x-button>
          </template>
        </div>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="leadActivities.columns"
        :items="activities || []"
        show-index
        border-cell
        fixed-checkbox
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
            >
              Edit
            </x-button>
            <x-button
              size="xs"
              color="error"
              outlined
              :disabled="item.status === 1"
              @click.prevent="activityDelete(item.id)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <x-modal v-model="modals.activityConfirm" show-close backdrop>
        <template #header> Delete Activity </template>
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
      <x-modal v-model="modals.activity" size="lg" show-close backdrop>
        <template #header>
          {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
        </template>

        <x-form @submit="onActivitySubmit" :auto-focus="false">
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
                :rules="[isRequired]"
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

          <div class="text-right space-x-4 mt-12">
            <x-button size="sm" @click.prevent="modals.activity = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="activityForm.processing"
              type="submit"
            >
              {{ activityActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Customer Additional Contacts
          <x-tag size="sm">{{ customerAdditionInfoList.length || 0 }}</x-tag>
        </h3>
        <div>
          <template v-if="!hasRole(rolesEnum.PA)">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="
                additionalContact.reset();
                modals.addContact = true;
              "
            >
              Add Additional Contacts
            </x-button>
          </template>
        </div>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="customerAdditionalContactsTable.columns"
        :items="customerAdditionInfoList || []"
        show-index
        border-cell
        fixed-checkbox
        hide-rows-per-page
        hide-footer
      >
        <template #item-key="item">
          {{
            item.key
              .replace('_', ' ')
              .toLowerCase()
              .replace(/\b[a-z]/g, l => l.toUpperCase())
          }}
        </template>
        <template #item-action="item">
          <div class="flex gap-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="additionalContactPrimary(item)"
            >
              Make Primary
            </x-button>
            <x-button
              v-if="
                item.id &&
                !(
                  record.quote_status_id == quoteStatusEnum.CarSold ||
                  record.quote_status_id == quoteStatusEnum.Uncontactable
                )
              "
              size="xs"
              color="error"
              outlined
              @click.prevent="additionalContactDelete(item.id)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <x-modal v-model="modals.contactDeleteConfirm" show-close backdrop>
        <template #header> Delete Additional Contact </template>
        <p>Are you sure you want to delete this?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.contactDeleteConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              @click.prevent="additionalContactDeleteConfirmed"
              :loading="contactLoader"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal>
      <x-modal v-model="modals.contactPrimaryConfirm" show-close backdrop>
        <template #header> Primary Additional Contact </template>
        <p>Are you sure you want to make this information as Primary?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.contactPrimaryConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="emerald"
              @click.prevent="additionalContactPrimaryConfirmed"
              :loading="contactLoader"
            >
              Confirm
            </x-button>
          </div>
        </template>
      </x-modal>
      <x-modal v-model="modals.addContact" size="lg" show-close backdrop>
        <template #header> Add Additional Contacts </template>

        <x-form @submit="onAdditionalContactSubmit" :auto-focus="false">
          <div class="grid gap-4">
            <x-field label="Type" required>
              <x-select
                v-model="additionalContact.additional_contact_type"
                :options="[
                  { value: 'email', label: 'Email' },
                  { value: 'mobile_no', label: 'Mobile Number' },
                ]"
                :rules="[isRequired]"
                placeholder="Select Type"
                class="w-full"
              />
            </x-field>
            <x-field label="Value" required>
              <x-input
                v-model="additionalContact.additional_contact_val"
                :rules="[
                  isRequired,
                  additionalContact.additional_contact_type === 'email'
                    ? isEmail
                    : isNumber,
                ]"
                class="w-full"
              />
            </x-field>
          </div>

          <div class="text-right space-x-4 mt-12">
            <x-button size="sm" @click.prevent="modals.addContact = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="additionalContact.processing"
              type="submit"
            >
              Save
            </x-button>
          </div>
        </x-form>
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
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
    </div>
  </div>
  <AuditLogs :type="'App\\Models\\CarQuote'" :id="$page.props.record.id" />
  <ApiLogs
    v-if="can(permissionEnum.API_LOG_VIEW)"
    :type="'App\\Models\\CarQuote'"
    :id="$page.props.record.id"
  />
</template>
