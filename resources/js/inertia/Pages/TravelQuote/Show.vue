<script setup>
import { computed } from 'vue';
import RiskRatingScoreDetails from '../../Components/RiskRatingScoreDetails.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';

const page = usePage();
defineProps({
  quote: Object,
  allowedDuplicateLOB: Array,
  advisors: Array,
  renewalAdvisors: Array,
  assignmentTypes: Object,
  isManualAllocationAllowed: Boolean,
  genderOptions: Object,
  leadStatuses: Array,
  permissions: Object,
  enums: Object,
  lostReasons: Array,
  ecomDetails: Object,
  travelers: Array,
  modelType: String,
  quoteTypeId: Number,
  quoteDocuments: Object,
  displaySendPolicyButton: Boolean,
  documentTypes: Object,
  documentType: Object,
  cdnPath: String,
  memberCategories: Array,
  emailStatuses: Array,
  isAdmin: Boolean,
  activities: Array,
  customerAdditionalContacts: Array,
  ecomTravelInsuranceQuoteUrl: String,
  fieldsToDisplay: Object,
  quotes: Array,
  message: String,
  isBetaUser: Boolean,
  payments: Array,
  quoteRequest: Object,
  paymentMethods: Object,
  insuranceProviders: Array,
  embeddedProducts: Array,
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  paymentTooltipEnum: Object,
  storageUrl: String,
  bookPolicyDetails: Array,
  isNewPaymentStructure: Boolean,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  aboveAgeMembers: Number,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  paymentDocument: Array,
  travelDestinations: Object,
  isAmlClearedForQuote: Boolean,
  amlStatusName: String,
  access: Object,
});

const modelClass = 'App\\Models\\TravelQuote';
const permissionEnum = page.props.permissionsEnum;
const permissionsEnum = page.props.permissionsEnum;
const policyIssuanceEnum = page.props.policyIssuanceEnum;
const leadSource = page.props.leadSource;
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const hasAnyRole = roles => useHasAnyRole(roles);
const quoteStatusEnum = page.props.quoteStatusEnum;
const checkedItems = ref([]);
const checkCheckedPlans = computed(() => {
  return true;
});
const checkedCount = computed(() => {
  return checkedItems.value.length;
});
const updateCheckedCount = (id, event) => {
  if (event.target.checked) {
    if (checkedItems.value.length < 6) {
      checkedItems.value.push(id);
    } else {
      return false;
    }
  } else {
    var index = checkedItems.value.indexOf(id);
    if (index != -1) {
      checkedItems.value.splice(id, 1);
    }
  }
};

const dateFormat = date => {
  if (!date) return '';
  return useDateFormat(date, 'DD-MM-YYYY');
};
const dateTimeFormat = date => {
  if (!date) return '';
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss');
};
const notification = useNotifications('toast');

const rolesEnum = page.props.rolesEnum;
const hasRole = role => useHasRole(role);

const normalPlansIds = reactive({
  ids: [],
});
const seniorPlansIds = reactive({
  ids: [],
});

const canSendOcbEmail = computed(() => {
  return (
    hasAnyRole([rolesEnum.LeadPool, rolesEnum.TravelManager]) &&
    page.props.quote.source !== leadSource.RENEWAL_UPLOAD
  );
});

const {
  isRequired,
  policy_number,
  premium,
  policy_expiry_date,
  policy_start_date,
  isEmail,
  isMobileNo,
} = useRules();
const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});
const memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlans = ref([]),
  selectedAdultPlans = ref([]),
  selectedSeniorPlans = ref([]),
  selectedPlansPdf = ref([]),
  exportLoader = ref(false),
  historyLoading = ref(false),
  toggleLoader = ref(false),
  lostReasonId = ref(
    page.props.lostReasons.find(
      reason => reason.text === page.props.quote.lost_reason,
    )?.id || null,
  );
const processingOCBEmailNB = ref(false);
const leadDuplicateForm = useForm({
  modelType: 'travel',
  parentType: 'travel',
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
const openSendOCBConfirmNB = () => {
  modals.sendOCBConfirmNB = true;
};
const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post(route('createDuplicate'), {
    preserveScroll: true,
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};

const titleCase = str => {
  return str
    .toLowerCase()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const genderText = gender => {
  let genderText = '';

  switch (gender) {
    case 'M':
      genderText = 'Male';
      break;
    case 'F':
      genderText = 'Female';
      break;
    default:
      genderText = gender || '';
  }

  return titleCase(genderText);
};

const lostReasonsOptions = computed(() => {
  return page.props.lostReasons.map(reason => ({
    value: reason.id,
    label: reason.text,
  }));
});

const modals = reactive({
  duplicate: false,
  member: false,
  memberConfirm: false,
  doc: false,
  docConfirm: false,
  plan: false,
  createPlan: false,
  activity: false,
  activityConfirm: false,
  planDetails: false,
  mixInquiryConfirm: false,
});

const travelFields = computed(() => {
  let skipFields = [
    'previous_quote_policy_number',
    'previous_quote_policy_premium',
    'previous_policy_expiry_date',
    'policy_start_date',
    'renewal_batch',
    'transapp_code',
  ];
  let fields = {};
  Object.keys(page.props.fieldsToDisplay).map(field => {
    if (!skipFields.includes(field)) {
      fields[field] = page.props.fieldsToDisplay[field];
    }
  });
  return fields;
});

const leadStatusForm = useForm({
  modelType: 'Travel',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  lostReason: lostReasonId.value || '',
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const onLeadStatus = () => {
  leadStatusForm.post(
    route('updateLeadStatus', {
      modelType: 'Travel',
      QuoteUId: page.props.quote.id,
    }),
    {
      preserveScroll: true,
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
      onSuccess: response => {
        const flash_messages = response.props.flash;
        if (!flash_messages) {
          notification.success({
            title: 'Lead Status Updated',
            position: 'top',
          });
        }
      },
    },
  );
};

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const nationalityOptions = computed(() => {
  return page.props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const memberRelationOptions = computed(() => {
  return page.props.memberRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const travelerForm = useForm({
  travel_quote_request_id: page.props.quote.id,
  quote_type: page.props.modelType,
  first_name: '',
  last_name: '',
  name: '',
  dob: '',
  nationality_id: null,
  relation_code: null,
  passport: null,
  uae_resident: null,
  emirates_id_number: '',
  gender: null,
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
});

const travelerFieldReq = reactive({
  nationality: false,
  dob: false,
});

const travelerTable = reactive({
  isLoading: false,
  addTraveler: false,
  processing: false,
  columns: [
    {
      text: 'First Name',
      value: 'first_name',
    },
    {
      text: 'Last Name',
      value: 'last_name',
    },
    {
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Date of Birth',
      value: 'dob',
    },
    {
      text: 'Gender',
      value: 'gender',
    },
    {
      text: 'Relation',
      value: 'relation',
    },
    {
      text: 'Emirates ID Number',
      value: 'emirates_id_number',
    },
    {
      text: 'Passport Number',
      value: 'passport',
    },
    {
      text: 'UAE Resident',
      value: 'uae_resident',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const submitTraveler = isValid => {
  if (!isValid) return;

  if (travelerForm.id) {
    editTraveler(isValid);
  } else {
    addTravelMember(isValid);
  }
};
const showErrors = errors => {
  Object.keys(errors).forEach(function (key) {
    notification.error({
      title: errors[key],
      position: 'top',
      timeout: 10000,
    });
  });
};
const addTravelMember = isValid => {
  if (!isValid) return;

  travelerForm.name = travelerForm.first_name;
  travelerForm.post(route('travelers.store'), {
    preserveScroll: true,
    onBefore: () => {
      travelerForm.processing = true;
    },
    onSuccess: () => {
      notification.success({
        title: 'Member Added',
        position: 'top',
      });
      onLoadAvailablePlansData();
    },
    onError: errors => {
      showErrors(errors);
    },
    onFinish: () => {
      travelerTable.addTraveler = false;
      travelerForm.processing = false;
      travelerForm.first_name = '';
      travelerForm.last_name = '';
      travelerForm.name = '';
      travelerForm.dob = '';
      travelerForm.nationality_id = '';
      travelerForm.relation_code = '';
      travelerForm.uae_resident = null;
      travelerForm.passport = null;
      travelerForm.emirates_id_number = '';
      travelerForm.id = null;
      travelerForm.gender = null;
      travelerForm.reset();
    },
  });
};

const onAddTraveler = () => {
  travelerForm.reset();
  travelerForm.first_name = '';
  travelerForm.last_name = '';
  travelerForm.name = '';
  travelerForm.dob = '';
  travelerForm.nationality_id = '';
  travelerForm.relation_code = '';
  travelerForm.uae_resident = null;
  travelerForm.passport = null;
  travelerForm.emirates_id_number = '';
  travelerForm.id = null;
  travelerTable.addTraveler = true;
  travelerForm.gender = null;
};

const travelerName = ref('');

const onEditTraveler = traveler => {
  travelerName.value = `Traveler ${traveler.index}`;
  travelerForm.id = traveler.id;
  travelerForm.first_name = traveler.first_name;
  travelerForm.last_name = traveler.last_name;
  travelerForm.name = traveler.first_name + ' ' + traveler.last_name ?? '';
  travelerForm.dob = traveler.dob;
  travelerForm.gender = traveler.gender;
  travelerForm.relation_code = traveler.relation_code;
  travelerForm.nationality_id = traveler.nationality_id;
  travelerForm.uae_resident = traveler.uae_resident;
  travelerForm.emirates_id_number = traveler.emirates_id_number;
  travelerForm.passport = traveler.passport;
  travelerTable.addTraveler = true;
};

const editTraveler = isValid => {
  if (!isValid) return;
  travelerForm.name = travelerForm.first_name;
  travelerForm.put(route('travelers.update', travelerForm.id), {
    preserveScroll: true,
    onBefore: () => {
      travelerForm.processing = true;
    },
    onError: errors => {
      showErrors(errors);
    },
    onSuccess: () => {
      notification.success({
        title: 'Member Updated',
        position: 'top',
      });
      onLoadAvailablePlansData();
    },
    onFinish: () => {
      travelerTable.addTraveler = false;
      travelerForm.processing = false;
      travelerForm.first_name = '';
      travelerForm.last_name = '';
      travelerForm.name = '';
      travelerForm.dob = '';
      travelerForm.nationality_id = '';
      travelerForm.relation_code = '';
      travelerForm.id = null;
      travelerForm.gender = null;
      travelerForm.reset();
    },
  });
};

const deleteTraveler = id => {
  router.delete(route('travelers.destroy', id), {
    preserveScroll: true,
    onBefore: () => {
      travelerTable.processing = true;
    },
    onSuccess: () => {
      notification.success({
        title: 'Member Deleted',
        position: 'top',
      });
      onLoadAvailablePlansData();
    },
    onFinish: () => {
      travelerTable.processing = false;
      confirmModal.show = false;
    },
  });
};

const confirmSendOCBEmailNB = () => {
  processingOCBEmailNB.value = true;
  axios
    .post(`/quotes/travel/${page.props.quote.uuid}/send-email-ocb-nb`, {
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

const confirmModal = reactive({
  show: false,
  title: 'Delete',
  message: 'Are you sure you want to delete this?',
  onConfirm: () => {
    confirmModal.show = false;
  },
});

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
    page.props.permissions.notProductionApproval,
  editMode: false,
  modelType: page.props.modelType,
  quote_id: page.props.quote.id,
});

const cancelPolicyFrom = () => {
  policyDetails.editMode = false;
};

const submitPolicyDetails = isValid => {
  if (!isValid) return;
  policyDetails
    .transform(data => ({
      quote_policy_number: data.policy_number,
      quote_policy_start_date: data.policy_start_date,
      quote_policy_expiry_date: data.policy_expiry_date,
      quote_policy_issuance_date: data.policy_issuance_date,
      quote_premium: data.premium,
      modelType: data.modelType,
      quote_id: data.quote_id,
      isInertia: true,
    }))
    .post(`/quotes/${page.props.modelType}/update-quote-policy`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Policy Details Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        policyDetails.editMode = false;
      },
    });
};

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const memberDataDocs = membersDetail => {
  return membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);
};

const sendPolicyToClient = () => {
  if (confirm('Are you sure you want to send documents to customer?')) {
    let quoteType = page.props.modelType;
    let quoteUuId = page.props.quote.uuid;
    let url =
      '/quotes/' + quoteType + '/' + quoteUuId + '/send-policy-documents';
    axios.post(url).then(response => {
      if (response.status == 200) {
        notification.success({
          title: 'Documents Sent',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Documents Sending Failed',
          position: 'top',
        });
      }
    });
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
    .post(route('manualPlanToggle', { quoteType: 'travel' }), {
      modelType: 'Travel',
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

const onExportPlans = () => {
  if (selectedPlans.value.length < 2 || selectedPlans.value.length > 5) {
    notification.error({
      title: 'Please select 2 to 5 plans to download PDF.',
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
      '/api/v1/quotes/travel/export-plans-pdf',
      {
        plan_ids: planIds,
        quote_uuid: page.props.quote.uuid,
        modelType: 'travel',
        quoteType: 'travel',
        hasAdultAndSeniorMember:
          availableSeniorPlansTable?.data?.length > 0 &&
          availablePlansTable?.data?.length > 0
            ? true
            : false,
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

const onLoadAvailablePlansData = async () => {
  let data = {
    jsonData: true,
  };
  let url = `/quotes/travel/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      availablePlansTable.data = res.data.normalPlans;
      availableSeniorPlansTable.data = res.data.seniorPlans;

      normalPlansIds.ids = res.data.normalPlans.map(plan => plan.id);
      seniorPlansIds.ids = res.data.seniorPlans.map(plan => plan.id);
    })
    .catch(err => {
      console.log(err);
    });
};

const selectedPlanType = ref(null);

const updateSelectedPlan = async selectedPlanData => {
  let data = {
    plan_id: selectedPlanData.plan.id,
  };

  data.planType = selectedPlanData.extraDetails?.planType;
  if (selectedPlanData.extraDetails?.selectedPlansIds.length > 0) {
    for (
      let i = 0;
      i < selectedPlanData.extraDetails?.selectedPlansIds.length;
      i++
    ) {
      if (
        selectedPlanData.extraDetails?.planType == 'normalPlans' &&
        selectedPlanData.extraDetails?.seniorPlansIds.includes(
          selectedPlanData.extraDetails?.selectedPlansIds[i],
        )
      ) {
        data.plan_id = selectedPlanData.plan.id;
        data.selected_plan_id =
          selectedPlanData.extraDetails?.selectedPlansIds[i];
      }

      if (
        selectedPlanData.extraDetails?.planType == 'seniorPlans' &&
        selectedPlanData.extraDetails?.normalPlansIds.includes(
          selectedPlanData.extraDetails?.selectedPlansIds[i],
        )
      ) {
        data.selected_plan_id = selectedPlanData.plan.id;
        data.plan_id = selectedPlanData.extraDetails?.selectedPlansIds[i];
      }
    }
  } else {
    data.plan_id = selectedPlanData.plan.id;
  }

  axios
    .post(
      `/personal-quotes/${selectedPlanData.quoteType}/${page.props.quote.uuid}/update-selected-plan`,
      data,
    )
    .then(res => {
      let premium = 0;
      if (res.data.plan.planProcessValue[0]) {
        premium = res.data.plan.planProcessValue[0].totalPremium;
      }
      let selectedPlan = {
        id: selectedPlanData.plan.id,
        providerName: selectedPlanData.plan.providerName,
        planName: selectedPlanData.plan.name,
      };

      if (res.data.plan.planProcessValue[0]) {
        selectedPlan.premium = premium.toFixed(2);
      }
      handlePlanSelected(selectedPlan);
    })
    .catch(err => {
      console.log(err);
      isLoading.value = false;
      notification.error({
        title: err?.response?.data?.message ?? 'something went wrong',
        position: 'top',
      });
    });
};

const onLoadAvailablePlansDataAndPlanDetails = async selectedPlanData => {
  // In case of update made in selected plan, we need to call the update plan api to make the required changes according to selected plan
  if (
    selectedPlanData.extraDetails?.selectedPlansIds.includes(
      parseInt(selectedPlanData.plan.id),
    )
  ) {
    await updateSelectedPlan(selectedPlanData);
  }
  getPlanDetails(planDetails.value.id);
  await onLoadAvailablePlansData();
};

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
      value: 'status',
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

const availablePlansTable = reactive({
  data: [],
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
      text: 'Insurer Quote Number',
      value: 'insurerQuoteId',
    },
    {
      text: 'Travel Type',
      value: 'travelType',
    },
    {
      text: 'Price',
      value: 'actualPremium',
    },
    {
      text: 'Total Price',
      value: 'premiumWithVat',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const availableSeniorPlansTable = reactive({
  data: [],
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
      text: 'Insurer Quote Number',
      value: 'insurerQuoteId',
    },
    {
      text: 'Travel Type',
      value: 'travelType',
    },
    {
      text: 'Price',
      value: 'actualPremium',
    },
    {
      text: 'Total Price',
      value: 'premiumWithVat',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

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
  modelType: 'Travel',
  parentType: 'Travel',
  quoteType: 8,
  title: null,
  description: null,
  due_date: '',
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.title = null;
  activityForm.description = null;
  activityForm.due_date = null;
  activityForm.assignee_id = null;
  activityForm.status = null;
  activityForm.activity_id = null;
  activityForm.uuid = null;
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

const date = ref(new Date());
const format = date => {
  const day = date.getDate();
  const month = date.getMonth() + 1;
  const year = date.getFullYear();
  const hours = date.getHours();
  const minutes = date.getMinutes();

  return `${day}/${month}/${year} ${hours}:${minutes} `;
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
    let date = new Date(activityForm.due_date);
    date =
      date.toISOString().split('T')[0] +
      ' ' +
      date.toTimeString().split(' ')[0];
    activityForm.due_date = date;
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
    let date = new Date(activityForm.due_date);
    date =
      date.toISOString().split('T')[0] +
      ' ' +
      date.toTimeString().split(' ')[0];
    activityForm.due_date = date;
    activityForm.post(route('activities.create.activity'), {
      preserveScroll: true,
      onSuccess: () => {
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

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'travel',
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

// selected tab

const planDetails = ref(null);

const getPlanDetails = id => {
  try {
    axios
      .get(`/quotes/travel/${page.props.quote.uuid}/plan_details/${id}`)
      .then(res => {
        planDetails.value = res.data;
        modals.planDetails = true;
      })
      .catch(err => {
        notification.error({
          title: 'Error',
          message: 'Plan Details Not Found',
          position: 'top',
        });
        console.log(err);
      });
  } catch (err) {
    console.log(err);
    notification.error({
      title: 'Error',
      message: 'Something went wrong',
      position: 'top',
    });
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

const getAddonVat = item => {
  let addonVat = 0;
  item.addons.forEach(addon => {
    addon.addonOptions.forEach(option => {
      if (option.isSelected && option.price != 0) {
        addonVat += parseInt(option.price) + option.vat;
      }
    });
  });
  return addonVat;
};

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

  insured_first_name: page.props.quote.insured_first_name || '',
  insured_last_name: page.props.quote.insured_last_name || '',
  emirates_id_number: page.props.quote.emirates_id_number || null,
  emirates_id_expiry_date: page.props.quote.emirates_id_expiry_date || null,

  entity_id: page.props.quote.entity_id ?? null,
  trade_license_no: page.props.quote.trade_license_no ?? null,
  company_name: page.props.quote.company_name ?? null,
  company_address: page.props.quote.company_address ?? null,
  entity_type_code: page.props.quote.entity_type_code ?? 'Parent',
  industry_type_code: page.props.quote.industry_type_code ?? null,
  passport_number: page.props.quote.passport_number ?? null,
  emirate_of_registration_id:
    page.props.quote.emirate_of_registration_id ?? null,
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
  if (page.props.message) {
    notification.success({
      title: page.props.message,
      position: 'top',
    });
  }
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const isEmbeddedProduct = code => {
  return code.includes('TRA-CAR');
};

const prefillPlanId = ref(page.props.quote.prefill_plan_id);
const selectedPlanIds = computed(() => {
  return page.props.payments.length > 0
    ? page.props.payments.map(plan => plan.plan_id)
    : [];
});

const selectedProviderPlan = ref({
  id: page.props.quote.plan_id,
  planName: page.props.ecomDetails.planName,
  providerName: page.props.ecomDetails.providerName,
  premium: page.props.ecomDetails.premium,
});

const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest'],
  });
};

const genderList = [
  { value: 'M', label: 'Male' },
  { value: 'F', label: 'Female' },
];
const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);

const handleSelectionChange = (tableType, selectedItems) => {
  if (tableType === 'adult' && selectedSeniorPlans.value.length > 0) {
    modals.mixInquiryConfirm = true;
    selectedAdultPlans.value = [];
  } else if (tableType === 'senior' && selectedAdultPlans.value.length > 0) {
    modals.mixInquiryConfirm = true;
    selectedSeniorPlans.value = [];
  } else {
    if (tableType === 'adult') {
      selectedAdultPlans.value = selectedItems;
      selectedPlans.value = selectedItems;
    } else if (tableType === 'senior') {
      selectedSeniorPlans.value = selectedItems;
      selectedPlans.value = selectedItems;
    }
  }
};
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
const [AddMemberButtonTemplate, AddMemButtonReuseTemplate] =
  createReusableTemplate();
const [EditMemberButtonTemplate, EditMemberButtonReuseTemplate] =
  createReusableTemplate();
const [DeleteMemberButtonTemplate, DeleteMemberButtonReuseTemplate] =
  createReusableTemplate();
const getupdateDocumentValidate = event => {
  updateDocumentValidate.show = event;
  page.props.quote.is_documents_valid = event;
};

const updateDocumentValidate = reactive({
  show: false,
  title: 'Update',
  message: 'Are all documents correct?',
  processing: false,
  onConfirm: () => {
    updateDocumentValidate.show = false;
  },
});

const documentValidate = async val => {
  updateDocumentValidate.processing = true;
  if (page.props.quoteDocuments?.length < 1) {
    notification.info({
      title: 'Please upload the documents file first',
      position: 'top',
    });
    updateDocumentValidate.processing = false;
    updateDocumentValidate.show = false;
    return false;
  }
  let data = { is_documents_valid: val };
  await axios
    .post(
      `/quotes/travel/${page.props.quote.uuid}/update-validate-documents`,
      data,
    )
    .then(res => {
      if (res.status == 200) {
        notification.success({
          title: 'Document validity status update successfully.',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Documents validity status updated failed',
          position: 'top',
        });
      }
      updateDocumentValidate.processing = false;
      updateDocumentValidate.show = false;
    })
    .catch(err => {
      notification.error({
        title: 'Documents validity status updated failed',
        position: 'top',
      });
      updateDocumentValidate.processing = false;
      updateDocumentValidate.show = false;
    });
};
const getGenderDisplay = val => {
  switch (val) {
    case 'M':
    case 'male':
      return 'Male';
    case 'F':
    case 'female':
      return 'Female';
    default:
      return '';
  }
};

const onAddUpdate = () => {
  selectedProviderPlan.value.id = null;
  selectedProviderPlan.value.planName = '';
  selectedProviderPlan.value.providerName = '';
  selectedProviderPlan.value.premium = '';
};

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return page.props.quote.quote_status_id == quoteStatusEnum.PolicyBooked;
  }
  return (
    page.props.quote.quote_status_id == quoteStatusEnum.TransactionApproved
  );
});
</script>

<template>
  <div>
    <Head title="Travel Detail" />
    <div
      class="flex justify-between items-center flex-wrap gap-2"
      v-if="readOnlyMode.isDisable === true"
    >
      <h2 class="text-xl font-semibold">
        Travel Detail
        <span
          class="inline-flex items-center rounded-md bg-yellow-300 px-2 py-1 text-xs font-medium text-yellow-900 ring-1 ring-inset ring-yellow-300/10"
          v-if="isEmbeddedProduct(quote.code)"
        >
          {{ 'Car Embedded Product' }}
        </span>
      </h2>
      <div class="flex gap-2">
        <Link
          v-if="quote?.insly_id"
          :href="`/legacy-policy/${quote.insly_id}`"
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
          v-if="canSendOcbEmail"
          class="mr-2"
          size="sm"
          color="#ff5e00"
          @click.prevent="openSendOCBConfirmNB"
        >
          Send NB OCB To Customer
        </x-button>
        <x-button
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
          v-if="permissions.canNotApprovePayments"
        >
          Duplicate Lead
        </x-button>
        <Link :href="route('travel.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Travel List </x-button>
        </Link>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link v-if="!isDisabled" :href="route('travel.edit', quote.uuid)">
            <x-button size="sm" tag="div">Edit</x-button>
          </Link>
          <x-button v-else :disabled="isDisabled" size="sm" tag="div"
            >Edit</x-button
          >
        </LeadEditBtnTemplate>

        <x-tooltip
          v-if="lockLeadSectionsDetails.lead_details"
          position="bottom"
        >
          <LeadEditBtnReuseTemplate
            v-if="permissions.canEditQuote"
            :isDisabled="true"
          />
          <template #tooltip
            >This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'</template
          >
        </x-tooltip>
        <template v-else>
          <LeadEditBtnReuseTemplate v-if="permissions.canEditQuote" />
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

    <div class="p-4 rounded shadow mt-6 mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center flex-wrap gap-2"></div>
        </template>
        <template #body>
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div
                class="grid sm:grid-cols-2"
                v-for="field in travelFields"
                :key="field"
              >
                <dt v-if="field.title == 'Ref-ID'">
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      {{ field.title }}
                    </label>
                    <template #tooltip> Reference ID </template>
                  </x-tooltip>
                </dt>
                <dt v-else-if="field.title == 'Ref-ID'">
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      {{ field.title }}
                    </label>
                    <template #tooltip> Reference ID </template>
                  </x-tooltip>
                </dt>
                <div
                  class="grid sm:grid-cols-2"
                  v-else-if="
                    hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering]) &&
                    field.title == 'ID'
                  "
                >
                  <dt class="font-medium uppercase">{{ field.title }}</dt>
                  <dd>{{ field?.value }}</dd>
                </div>
                <dt v-else class="font-medium uppercase">{{ field.title }}</dt>
                <dd>
                  {{
                    field.title == 'Advisor'
                      ? quote.api_issuance_status ==
                        policyIssuanceEnum.POLICY_ISSUANCE_API_STATUS_YES_ID
                        ? policyIssuanceEnum.API_POLICY_ISSUANCE_AUTOMATION_USER_LABEL
                        : field?.value
                      : field?.value
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium uppercase">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
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
                <dt>
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
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                    >
                      TRAVELING WHERE
                    </label>
                    <template #tooltip> Traveling Where</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium uppercase">
                  {{
                    quote.direction_code != null
                      ? quote.direction_code
                      : quote?.currently_located_in_id_text ==
                            enums.travelQuoteEnum.LOCATION_UAE_TEXT &&
                          quote?.region_cover_for_id !=
                            enums.travelQuoteEnum.REGION_COVER_ID_UAE
                        ? enums.travelQuoteEnum.TRAVEL_UAE_OUTBOUND
                        : quote?.destination_id_text ==
                              enums.travelQuoteEnum
                                .LOCATION_UNITED_ARAB_EMIRATES_TEXT ||
                            quote?.region_cover_for_id ==
                              enums.travelQuoteEnum.REGION_COVER_ID_UAE
                          ? enums.travelQuoteEnum.TRAVEL_UAE_INBOUND
                          : ''
                  }}
                </dt>
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
                <dt class="font-medium">
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
                </dt>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt>
                  <label class="font-medium text-gray-800 text-sm">
                    DEPARTING FROM
                  </label>
                </dt>
                <dt class="font-medium uppercase">
                  {{
                    quote.departure_country_text != null
                      ? quote.departure_country_text
                      : ''
                  }}
                </dt>
              </div>

              <div
                v-if="
                  enums.travelQuoteEnum.TRAVEL_UAE_INBOUND ==
                    quote.direction_code || quote?.region_cover_for_id == 3
                "
                class="grid sm:grid-cols-2"
              >
                <dt>
                  <label
                    class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                  >
                    ARRIVED AT UAE
                  </label>
                </dt>
                <dt class="font-medium">
                  {{
                    quote.has_arrived_uae == 1 ||
                    quote.currently_located_in_id_text ==
                      enums.travelQuoteEnum.LOCATION_UAE_TEXT
                      ? 'Yes'
                      : 'No'
                  }}
                </dt>
              </div>
              <div v-else class="grid sm:grid-cols-2">
                <dt>
                  <label
                    class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                  >
                    ARRIVED AT DESTINATION
                  </label>
                </dt>
                <dt class="font-medium">
                  {{
                    quote.has_arrived_destination == 1 ||
                    quote.currently_located_in_id_text ==
                      enums.travelQuoteEnum.LOCATION_OUTSIDE_UAE
                      ? 'Yes'
                      : 'No'
                  }}
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                    >
                      DAYS COVERS
                    </label>
                    <template #tooltip>Days Covers</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium">{{ quote.days_cover_for }}</dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                    >
                      TRAVEL START DATE
                    </label>
                    <template #tooltip> Travel Start Date</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium">
                  {{
                    quote.start_date
                      ? quote.start_date
                      : quote.policy_start_date
                  }}
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                    >
                      TRAVEL END DATE
                    </label>
                    <template #tooltip> Travel End Date</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium">{{ quote.end_date }}</dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                    >
                      TRAVEL COVERAGE
                    </label>
                    <template #tooltip> Travel Coverage</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium">
                  {{
                    quote.source == $page.props.leadSource.RENEWAL_UPLOAD
                      ? enums.travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP
                      : quote.coverage_code != null
                        ? quote.coverage_code
                        : quote.days_cover_for <= 92
                          ? enums.travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP
                          : enums.travelQuoteEnum.COVERAGE_CODE_ANNUAL_TRIP +
                            '/' +
                            enums.travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP
                  }}
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip placement="bottom">
                    <label
                      class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                    >
                      REGION COVERAGE
                    </label>
                    <template #tooltip>Region Cover</template>
                  </x-tooltip>
                </dt>
                <dt class="font-medium">
                  {{ quote.region_cover_for_id_text }}
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRAVEL DESTINATION(S)</dt>
                <dt class="font-medium">
                  <span
                    v-for="(item, index) in travelDestinations"
                    :key="item.id"
                  >
                    {{ item?.destination?.country_name
                    }}<span v-if="index < travelDestinations?.length - 1"
                      >,
                    </span>
                  </span>
                </dt>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ quote.transaction_approved_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURER API STATUS</dt>
                <dd>{{ quote.insurer_api_status }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">API ISSUANCE STATUS</dt>
                <dd>{{ quote.api_issuance_status }}</dd>
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
          <div class="flex mb-3 justify-end">
            <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
              KYC - Complete
            </x-tag>
            <x-tag color="amber" v-else> KYC - Pending </x-tag>
          </div>
          <div
            class="grid sm:grid-cols-2"
            v-if="quoteRequest.child || quoteRequest.parent"
          >
            <template v-if="quoteRequest.child">
              <dt>
                <x-tooltip placement="bottom">
                  <label
                    class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                  >
                    CHILD REF ID
                  </label>
                  <template #tooltip
                    >Navigation key from parent to child in data
                    hierarchy.</template
                  >
                </x-tooltip>
              </dt>
              <dt class="font-medium">
                <a
                  :href="'/quotes/travel/' + quoteRequest.child.uuid"
                  target="_blank"
                  class="text-primary-600"
                >
                  {{ quoteRequest.child?.code }}
                </a>
              </dt>
            </template>
            <template v-if="quoteRequest.parent">
              <dt>
                <x-tooltip placement="bottom">
                  <label
                    class="font-medium text-gray-800 text-sm decoration-dotted decoration-primary-700"
                  >
                    PARENT REF ID
                  </label>
                  <template #tooltip>Parent Ref Id</template>
                </x-tooltip>
              </dt>
              <dt class="font-medium">
                <a
                  :href="'/quotes/travel/' + quoteRequest.parent.uuid"
                  target="_blank"
                  class="text-primary-600"
                >
                  {{ quoteRequest.parent.code }}
                </a>
              </dt>
            </template>
          </div>

          <x-form @submit="updateProfileDetails" :auto-focus="false">
            <div class="text-sm">
              <dl
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Individual
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4"
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
                  <dd>{{ quote.nationality_id_text }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">DATE OF BIRTH</dt>
                  <dd>{{ quote.dob }}</dd>
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
                  <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                  <dd>{{ quote.receive_marketing_updates ? 'Yes' : 'No' }}</dd>
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
                <RiskRatingScoreDetails :quote="quote" :modelType="'Travel'" />
              </dl>
              <dl
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Entity
                "
                class="grid md:grid-cols-2 gap-x-6 gap-y-4"
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
                      v-if="readOnlyMode.isDisable === true"
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

    <div
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      class="p-4 rounded shadow mb-6 bg-white"
    >
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex flex-wrap gap-4 justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Member Details
              <x-tag size="sm">{{ travelers.length || 0 }}</x-tag>
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <AddMemberButtonTemplate v-slot="{ isDisabled }">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="onAddTraveler"
              :disabled="isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Add Member
            </x-button>
          </AddMemberButtonTemplate>
          <div class="flex flex-wrap gap-3 mb-3 justify-end">
            <x-tooltip
              v-if="lockLeadSectionsDetails.member_details"
              position="bottom"
            >
              <AddMemButtonReuseTemplate :isDisabled="true" />
              <template #tooltip>
                This lead is now locked as the policy has been booked. If
                changes are needed such midterm addition of member, go to 'Send
                Update', select 'Add Update', and choose 'Endorsement Financial'
              </template>
            </x-tooltip>
            <AddMemButtonReuseTemplate v-else />
          </div>

          <EditMemberButtonTemplate v-slot="{ isDisabled, item }">
            <x-button
              size="xs"
              color="primary"
              @click.prevent="onEditTraveler(item)"
              outlined
              :disabled="isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Edit
            </x-button>
          </EditMemberButtonTemplate>

          <DeleteMemberButtonTemplate v-slot="{ isDisabled, item }">
            <x-button
              size="xs"
              color="error"
              @click.prevent="
                confirmModal.onConfirm = () => deleteTraveler(item.id);
                confirmModal.show = true;
              "
              outlined
              :disabled="isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Delete
            </x-button>
          </DeleteMemberButtonTemplate>

          <DataTable
            table-class-name="tablefixed compact"
            :headers="travelerTable.columns"
            :items="travelers || []"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="travelers.length < 15"
            show-index
          >
            <template #item-index="{ index, code }">
              <div>{{ code ?? 'Member ' + index }}</div>
            </template>
            <template #item-dob="{ dob }">
              {{ dateFormat(dob).value }}
            </template>

            <template #item-relation="{ relation }">
              {{ relation?.text }}
            </template>
            <template #item-gender="{ gender }">
              {{ genderText(gender) }}
            </template>
            <template #item-nationality="{ nationality }">
              {{ nationality?.text }}
            </template>

            <template #item-action="item">
              <div class="flex gap-2">
                <x-tooltip
                  v-if="lockLeadSectionsDetails.member_details"
                  position="bottom"
                >
                  <EditMemberButtonReuseTemplate
                    :isDisabled="true"
                    :item="item"
                  />
                  <template #tooltip>
                    This lead is now locked as the policy has been booked. If
                    changes are needed such midterm deletion of member or
                    marital status change, go to 'Send Update', select 'Add
                    Update', and choose 'Endorsement Financial'
                  </template>
                </x-tooltip>
                <EditMemberButtonReuseTemplate v-else :item="item" />

                <x-tooltip
                  v-if="lockLeadSectionsDetails.member_details"
                  position="bottom"
                >
                  <DeleteMemberButtonReuseTemplate
                    :isDisabled="true"
                    :item="item"
                  />
                  <template #tooltip>
                    This lead is now locked as the policy has been booked. If
                    changes are needed such midterm deletion of member or
                    marital status change, go to 'Send Update', select 'Add
                    Update', and choose 'Endorsement Financial'
                  </template>
                </x-tooltip>
                <DeleteMemberButtonReuseTemplate v-else :item="item" />
              </div>
            </template>
          </DataTable>
        </template>
      </Collapsible>
      <x-modal
        v-model="travelerTable.addTraveler"
        size="lg"
        :title="`${travelerForm.id ? 'Edit' : 'Add'} Member`"
        show-close
        backdrop
        is-form
        @submit="submitTraveler"
      >
        <div class="grid md:grid-cols-2 gap-4">
          <x-input
            v-model="travelerForm.first_name"
            label="Member Name*"
            placeholder="Member Name"
            :rules="[isRequired]"
            :hasError="travelerForm.errors.first_name"
          />
          <ComboBox
            v-model="travelerForm.nationality_id"
            label="Nationality"
            :options="nationalityOptions"
            placeholder="Select Nationality"
            :single="true"
            :hasError="travelerFieldReq.nationality"
          />
          <DatePicker
            v-model="travelerForm.dob"
            label="Date of Birth*"
            :hasError="travelerFieldReq.dob"
            :rules="[isRequired]"
          />
          <x-select
            v-model="travelerForm.relation_code"
            label="Relation"
            :options="memberRelationOptions"
            placeholder="Select Relation"
            class="w-full"
          />
          <x-input
            v-model="travelerForm.emirates_id_number"
            label="Emirates ID Number"
            placeholder="Emirates ID Number"
          />
          <x-input
            v-model="travelerForm.passport"
            label="Passport Number"
            placeholder="Passport Number"
          />
          <x-field label="Gender*">
            <x-select
              v-model="travelerForm.gender"
              placeholder="Gender"
              :options="genderList"
              :rules="[isRequired]"
              class="w-full"
            />
          </x-field>
        </div>
        <template #secondary-action>
          <x-button
            ghost
            tabindex="-1"
            size="sm"
            @click.prevent="travelerTable.addTraveler = false"
          >
            Cancel
          </x-button>
        </template>
        <template #primary-action>
          <x-button
            size="sm"
            color="emerald"
            :loading="travelerForm.processing"
            type="submit"
          >
            {{ travelerForm.id ? 'Update' : 'Save' }}
          </x-button>
        </template>
      </x-modal>
    </div>

    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="page.props.modelType"
      :expanded="sectionExpanded"
    />

    <x-modal
      v-model="confirmModal.show"
      :title="`${confirmModal.title}`"
      show-close
      backdrop
    >
      <p>{{ confirmModal.message }}</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="confirmModal.show = false">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="confirmModal.onConfirm"
            :loading="confirmModal.processing"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>

    <CustomerAdditionalContacts
      quoteType="Travel"
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
      modelType="Travel"
      :quote="quote"
      :insly-id="quote?.insly_id"
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
                <x-field label="STATUS">
                  <x-select
                    v-model="leadStatusForm.leadStatus"
                    :options="leadStatusOptions"
                    :disabled="
                      allowStatusUpdate || lockLeadSectionsDetails.lead_status
                    "
                    placeholder="Lead Status"
                    class="w-full"
                    filterable
                  />
                </x-field>
                <x-field label="NOTES">
                  <x-textarea
                    v-model="leadStatusForm.notes"
                    type="text"
                    placeholder="Lead Notes"
                    class="w-full"
                    :disabled="
                      allowStatusUpdate || lockLeadSectionsDetails.lead_status
                    "
                  />
                </x-field>
              </div>
            </div>
            <div class="w-full md:w-2/3">
              <x-field
                label="LOST REASON"
                v-if="leadStatusForm.leadStatus == quoteStatusEnum.Lost"
              >
                <x-select
                  v-model="leadStatusForm.lostReason"
                  :options="lostReasonsOptions"
                  placeholder="Lost Reason is required"
                  class="w-full"
                  :error="leadStatusForm.errors.lostReason"
                  :disabled="lockLeadSectionsDetails.lead_status"
                />
              </x-field>
              <x-field label="Transaction Type">
                <x-input
                  type="text"
                  v-model="quote.transaction_type_text"
                  class="w-full"
                  :disabled="true"
                />
              </x-field>
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
              position="bottom"
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
            <h3 class="font-semibold text-primary-800 text-lg">
              E-COM Details
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PRICE</dt>
                <dd>{{ selectedProviderPlan.premium ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AUTHORISED AT</dt>
                <dd>{{ ecomDetails.paidAt ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAID AT</dt>
                <dd>{{ ecomDetails.paidAtPayment ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT STATUS</dt>
                <dd>{{ ecomDetails.paymentStatus ?? 'N/A' }}</dd>
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
          <div class="flex justify-between items-center flex-wrap gap-2">
            <div>
              <h6
                v-if="
                  aboveAgeMembers > 0 && availablePlansTable.data.length > 0
                "
                class="font-semibold text-primary-600 text-ms mb-1"
              >
                Travel plans for {{ travelers.length - aboveAgeMembers }} member
                age 0-64
              </h6>
            </div>
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
                v-if="
                  availablePlansTable.data.length > 0 ||
                  availableSeniorPlansTable.data.length > 0
                "
                size="sm"
                color="orange"
                class="mr-2"
                @click.prevent="
                  onCopyText(ecomTravelInsuranceQuoteUrl + quote.uuid)
                "
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
            <DataTable
              v-model:items-selected="selectedPlans"
              table-class-name="tablefixed"
              :headers="availablePlansTable.columns"
              :items="availablePlansTable.data || []"
              border-cell
              hide-rows-per-page
              :rows-per-page="15"
              :hide-footer="availablePlansTable.data.length < 15"
            >
              <template #item-providerName="item">
                <p class="text-primary-600 uppercase">
                  {{ item.providerName }}
                </p>
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
                <span class="text-primary-600 uppercase">{{ item.name }}</span>
              </template>
              <template #item-actualPremium="item">
                {{ parseFloat(item.actualPremium).toFixed(2) }}
              </template>
              <template #item-premiumWithVat="item">
                {{
                  parseFloat(
                    item.discountPremium + item.vat + getAddonVat(item),
                  ).toFixed(2)
                }}
              </template>
              <template #item-action="item">
                <div class="flex gap-2">
                  <x-button
                    size="xs"
                    color="error"
                    outlined
                    @click.prevent="
                      selectedPlanType = 'normalPlans';
                      getPlanDetails(item.id);
                    "
                  >
                    View
                  </x-button>

                  <span>
                    <SelectPlan
                      v-if="!selectedPlanIds.includes(item.id)"
                      :disabled="isEmbeddedProduct"
                      @update:selectedPlanChanged="handlePlanSelected"
                      :plan="item"
                      :quoteType="modelType"
                      :uuid="quote.uuid"
                      :extraDetails="{
                        normalPlansIds: normalPlansIds.ids,
                        seniorPlansIds: seniorPlansIds.ids,
                        selectedPlansIds: selectedPlanIds,
                        planType: 'normalPlans',
                      }"
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

          <div v-if="aboveAgeMembers > 0" class="mt-5">
            <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
              <h6 class="font-semibold text-primary-600 text-ms mb-1">
                Travel plans for {{ aboveAgeMembers }} member age 65 and above
              </h6>
            </div>
            <div>
              <DataTable
                v-model:items-selected="selectedPlans"
                table-class-name="tablefixed"
                :headers="availableSeniorPlansTable.columns"
                :items="availableSeniorPlansTable.data || []"
                border-cell
                hide-rows-per-page
                :rows-per-page="15"
                :hide-footer="availableSeniorPlansTable.data.length < 15"
              >
                <template #item-providerName="item">
                  <span class="text-primary-600 uppercase">{{
                    item.providerName
                  }}</span>
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
                  <span class="text-primary-600 uppercase">{{
                    item.name
                  }}</span>
                </template>
                <template #item-actualPremium="item">
                  {{ parseFloat(item.actualPremium).toFixed(2) }}
                </template>
                <template #item-premiumWithVat="item">
                  {{
                    parseFloat(
                      item.discountPremium + item.vat + getAddonVat(item),
                    ).toFixed(2)
                  }}
                </template>
                <template #item-action="item">
                  <div class="flex gap-2">
                    <x-button
                      size="xs"
                      color="error"
                      outlined
                      @click.prevent="
                        selectedPlanType = 'seniorPlans';
                        getPlanDetails(item.id);
                      "
                    >
                      View
                    </x-button>
                    <span>
                      <SelectPlan
                        v-if="!selectedPlanIds.includes(item.id)"
                        @update:selectedPlanChanged="handlePlanSelected"
                        :disabled="isEmbeddedProduct"
                        :plan="item"
                        :quoteType="modelType"
                        :uuid="quote.uuid"
                        :extraDetails="{
                          normalPlansIds: normalPlansIds.ids,
                          seniorPlansIds: seniorPlansIds.ids,
                          selectedPlansIds: selectedPlanIds,
                          planType: 'seniorPlans',
                        }"
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
              :quote="quote"
              :quoteType="modelType"
              :extraDetails="{
                normalPlansIds: normalPlansIds.ids,
                seniorPlansIds: seniorPlansIds.ids,
                selectedPlansIds: selectedPlanIds,
                planType: selectedPlanType,
              }"
              :access="access"
              @onLoadAvailablePlansData="onLoadAvailablePlansDataAndPlanDetails"
            />
          </x-modal>
        </template>
      </Collapsible>
    </div>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      quoteType="Travel"
      :payments="payments"
    />
    <PaymentTableNew
      v-if="isNewPaymentStructure"
      quoteType="Travel"
      :payments="payments"
      :paymentDocument="paymentDocument"
      :proformaPayment="
        payments.find(
          item =>
            item.payment_methods_code ===
            page.props.paymentMethodsEnum.ProformaPaymentRequest,
        )
      "
      :quoteRequest="quoteRequest"
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

    <PaymentTable
      v-else
      :payments="payments"
      :can="permissions"
      :isBetaUser="isBetaUser"
      :quoteRequest="quoteRequest"
      :paymentMethods="paymentMethods"
      :insuranceProviders="insuranceProviders"
      :quote="quote"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="ecomTravelInsuranceQuoteUrl + quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
      :expanded="sectionExpanded"
    />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      modelType="travel"
      :expanded="sectionExpanded"
      :payments="payments"
      :quoteStatusEnum="enums.quoteStatusEnum"
      :policyIssuanceStatus="policyIssuanceStatus"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="page.props.quoteDocuments || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :expanded="sectionExpanded"
      :docUploadURL="docUploadURL"
      quoteType="Travel"
      :sendPolicy="
        displaySendPolicyButton &&
        permissions.notProductionApproval &&
        permissions.isQuoteDocumentEnabled
      "
      @sendPolicyToClient="sendPolicyToClient"
      @verifyDocuments="getupdateDocumentValidate(true)"
      :bookPolicyDetails="bookPolicyDetails"
    />

    <BookPolicy
      v-if="
        canAny([
          permissionEnum.VIEW_INSLY_BOOK_POLICY,
          permissionEnum.SEND_INSLY_BOOK_POLICY,
          permissionsEnum.VIEW_ALL_LEADS,
        ])
      "
      :quote="quote"
      quoteType="travel"
      :modelClass="modelClass"
      :bookPolicyDetails="bookPolicyDetails"
      :payments="payments"
      :expanded="sectionExpanded"
      :isAmlClearedForQuote="isAmlClearedForQuote"
    />

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
          <div class="my-4 flex justify-end">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="addActivity"
              v-if="readOnlyMode.isDisable === true"
            >
              Add Activity
            </x-button>
          </div>
          <!-- <x-divider class="my-4" /> -->
          <DataTable
            table-class-name="compact"
            :headers="activityTable"
            :items="activities"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="activities.length < 15"
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
              <div class="space-x-4">
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
                  :disabled="item.status === 1"
                  outlined
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
        v-model="updateDocumentValidate.show"
        title="Are all documents correct?"
        show-close
        backdrop
      >
        <p>
          Note: By clicking 'Yes,' you confirm that all submitted documents are
          accurate and valid. Failure to verify will be considered a breach of
          the Code of Conduct (COC).
        </p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="updateDocumentValidate.show = false"
            >
              No
            </x-button>
            <x-button
              size="sm"
              color="green"
              @click.prevent="documentValidate(quote.is_documents_valid)"
              :loading="updateDocumentValidate.processing"
            >
              Yes
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
          <x-field label="Description">
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

          <DatePicker
            :format="format"
            v-model="activityForm.due_date"
            label="Due Date"
            :rules="[isRequired]"
            class="w-full"
            withTime
          />
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
    </div>

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

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="quote"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <AuditLogs
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
      :quoteType="$page.props.modelType"
      :expanded="sectionExpanded"
    />

    <ApiLogs
      v-if="can(permissionEnum.API_LOG_VIEW)"
      :type="modelClass"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <x-modal
      v-model="modals.mixInquiryConfirm"
      title="SORRY!"
      show-close
      backdrop
    >
      <p>
        Please choose quotes from the same age group for a correct comparison.
      </p>
      <template #actions>
        <div class="text-center space-x-4">
          <x-button
            size="sm"
            color="emerald"
            @click.prevent="modals.mixInquiryConfirm = false"
            v-if="readOnlyMode.isDisable === true"
          >
            Okay, got it!
          </x-button>
        </div>
      </template>
    </x-modal>
    <CustomerChatLogs
      :customerName="quote?.first_name + ' ' + quote?.last_name"
      :quoteId="quote.uuid"
      :quoteType="'TRAVEL'"
      :expanded="sectionExpanded"
    />

    <lead-raw-data
      :modelType="'Travel'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>
<style>
.border-inner tr td {
  padding: 8px !important;
  font-size: 10px !important;
}
</style>
