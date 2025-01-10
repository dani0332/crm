<script setup>
import LazyDocumentUploader from '../HealthQuote/Partials/DocumentUploader.vue';
import LazyCreatePlan from '../HealthQuote/Partials/CreatePlan.vue';
import LazyAvailablePlan from '../HealthQuote/Partials/AvailablePlans.vue';
const props = defineProps({
  quote: Object,
  customerTypeEnum: Object,
  genderOptions: Object,
  customerAdditionalContactsData: Array,
  memberCategories: Array,
  membersDetail: Array,
  memberRelations: Array,
  nationalities: Array,
  leadStatuses: Array,
  quoteType: String,
  ecomDetails: Object,
  sendPolicy: Boolean,
  coPayment: Object,
  storageUrl: String,
  quoteType: String,
  paymentTooltipEnum: Object,
  quoteRequest: Object,
  documentTypes: Object,
  paymentMethods: Object,
  isNewPaymentStructure: Boolean,
  isAmlClearedForPayment: Boolean,
  payments: Array,
  quoteDocuments: Array,
  activities: Array,
  advisors: Array,
  insuranceProviders: Array,
  healthPlanTypes: Array,
  quoteNotes: Object,
  noteDocumentType: Array,
  allowedDuplicateLOB: Array,
  cdnPath: String,
  lockLeadSectionsDetails: Object,
  hashCollapsibleStatuses: Boolean,
  ecomHealthInsuranceQuoteUrl: String,
  bookPolicyDetails: Array,
  paymentDocument: Array,
});

const page = usePage();

const showPlans = ref(!props.hashCollapsibleStatuses);

const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const { copy, copied } = useClipboard();

const paymentStatusEnum = page.props.paymentStatusEnum;
const notification = useToast();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

const { isRequired, isEmail, isNumber, isMobileNo } = useRules();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MMM-YYYY').value : '-';

const onCopyText = text => {
  copy(text);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};
const isManualPlansCount = ref(0);

const assignSubteam = ref(page.props.quote.health_team_type || ''),
  assignLead = ref(null),
  memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlans = ref([]),
  exportLoader = ref(false),
  toggleLoader = ref(false),
  historyLoading = ref(false),
  isDisabled = ref(false);

//activities
const activityTable = [
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Title', value: 'title' },
  { text: 'Client Name', value: 'client_name' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
  { text: 'Action', value: 'action' },
];

const cleanObj = obj => useCleanObj(obj);

const activityForm = useForm({
  entityUId: page.props.quote.uuid,
  entityId: page.props.quote.id,
  modelType: 'Health',
  parentType: 'Health',
  quoteType: 3,
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

const checkPlanType = id => {
  return page.props.healthPlanTypes.find(type => type.id === id)?.text;
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

const activityDelete = id => {
  modals.activityConfirm = true;
  confirmDeleteData.activity = id;
};

const activityDeleteConfirmed = () => {
  router.post(
    `/activities/${confirmDeleteData.activity}/delete`,
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

//doc
const quoteDocumentsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Document Type',
      value: 'document_type_text',
    },
    {
      text: 'Document Name',
      value: 'original_name',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Created By',
      value: 'created_by_name',
    },
  ],
});

const onAddMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
  memberForm.nationality_id = page.props.quote.nationality_id;
};

const memberFieldReq = reactive({
  nationality: false,
  dob: false,
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const memberForm = useForm({
  id: null,
  gender: null,
  dob: null,
  nationality_id: page.props.membersDetail.length
    ? null
    : page.props.quote.nationality_id,
  salary_band_id: null,
  emirate_of_your_visa_id: page.props.membersDetail.length
    ? null
    : page.props.quote.emirate_of_your_visa_id,
  member_category_id: null,
  quote_request_id: page.props.quote.id,
  update_lead_against_member: null,
  first_name: null,
  last_name: null,
  relation_code: null,
  quote_type: page.props.modelType,
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
  customer_member_id: null,
  quoteId: page.props.quote.uuid,
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});
const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});
const memberCategoriesOptions = computed(() => {
  return page.props.memberCategories.map(cat => ({
    value: cat.id,
    label: cat.text,
  }));
});
const memberRelationOptions = computed(() => {
  return page.props.memberRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
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
  planFilters: false,
});

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const genderSelect = computed(() => {
  return Object.keys(page.props.genderOptions).map(status => ({
    value: status,
    label: page.props.genderOptions[status],
  }));
});

const memberDataDocs = membersDetail => {
  const data = membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);

  return data;
};

const salaryBandsOptions = computed(() => {
  return page.props.salaryBands.map(sal => ({
    value: sal.id,
    label: sal.text,
  }));
});

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Member Name',
      value: 'first_name',
    },
    {
      text: 'Gender',
      value: 'gender',
    },
    {
      text: 'DOB',
      value: 'dob',
    },
    {
      text: 'Relation',
      value: 'relation',
    },
    {
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Emirate of Visa',
      value: 'emirate',
    },
    {
      text: 'Member Category',
      value: 'member_category_id',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const fixedValue = number => {
  if (number == Math.floor(number)) {
    return number.toLocaleString();
  } else {
    return number.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
};
const selectedProviderPlan = ref({
  id: page.props.quote.plan_id,
  planName: page.props.quote.health_plan_name_text,
  providerName: page.props.quote.plan_provider_name_text,
  premium: page.props.ecomDetails.priceWithVAT,
  planType: checkPlanType(page.props.quote.plan_type_id),
});

const handlePlanSelected = plan => {
  //se.value = plan.id;
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  selectedProviderPlan.value.planType = plan.planType;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'ecomDetails', 'coPayment'],
  });
};

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const confirmDeleteDoc = () => {
  quoteDocumentsTable.isLoading = true;
  router.post(
    `/documents/delete`,
    {
      docName: confirmDeleteData.docs,
      quoteId: page.props.quote.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        quoteDocumentsTable.isLoading = false;
        notification.error({
          title: 'File Deleted',
          position: 'top',
        });
      },
    },
  );
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
  emirate_of_registration_id:
    page.props.quote.emirate_of_registration_id ?? null,
});

const initialEditCategoryId = ref(null);
const previouslySelectedCategoryId = ref(null);

function onEditMember(data) {
  memberActionEdit.value = true;
  modals.member = true;

  memberForm.id = data.id;
  memberForm.gender = data.gender;
  memberForm.dob = data.dob;
  memberForm.nationality_id = data.nationality_id;
  memberForm.emirate_of_your_visa_id = data.emirate_of_your_visa_id;
  memberForm.member_category_id = data.member_category_id;
  memberForm.salary_band_id = data.salary_band_id;
  memberForm.first_name = data.first_name;
  memberForm.last_name = data.last_name;
  memberForm.relation_code = data.relation_code;
  memberForm.update_lead_against_member = data.index === 1;

  // set initialEditCategoryId to member_category_id when any member is edited
  initialEditCategoryId.value = data.member_category_id;

  // set previouslySelectedCategoryId for the refernece of initialEditCategoryId
  previouslySelectedCategoryId.value = initialEditCategoryId.value;
}

const membersDetailsUpdated = ref(false);

const onMemberSubmit = isValid => {
  if (memberForm.nationality_id == null) {
    memberFieldReq.nationality = true;
  } else {
    memberFieldReq.nationality = false;
  }
  if (memberForm.dob == null) {
    memberFieldReq.dob = true;
  } else {
    memberFieldReq.dob = false;
  }

  if (!isValid) return;
  if (memberActionEdit.value) {
    memberForm.put(`/health-quote-update-member`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Updated',
          position: 'top',
        });
        memberForm.reset();
        onLoadAvailablePlansData();
        // location.reload();
      },
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
        membersDetailsUpdated.value = true;
      },
    });
  } else {
    memberForm.post(`/health-quote-add-member`, {
      // new mavonic endpoint
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Added',
          position: 'top',
        });
        onLoadAvailablePlansData();
        // location.reload();
      },
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
        membersDetailsUpdated.value = true;
      },
    });
  }
};

const onRecieveMembersDetailsReview = () => {
  membersDetailsUpdated.value = false;
};

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Health/${page.props.quote.id}/update-lead-status`,
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

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Health',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=health&recordId=${page.props.quote.id}`,
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
    },
    {
      text: 'Plan Name',
      value: 'name',
    },
    {
      text: 'Plan Type',
      value: 'planTypeId',
      sortable: true,
    },
    {
      text: 'Network Provider',
      value: 'eligibilityName',
    },
    {
      text: 'CO-PAY/CO-INSURANCE',
      value: 'copayName',
      width: 100,
    },
    {
      text: 'Price',
      value: 'actualPremium',
      sortable: true,
    },
    {
      text: 'Basmah',
      value: 'basmah',
    },
    {
      text: 'Policy Fee (if applicable)',
      value: 'policyFee',
    },
    {
      text: 'Total Indicative Price (with VAT)',
      value: 'total',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const onLoadAvailablePlansData = async () => {
  let data = {
    jsonData: true,
  };
  let url = `/quotes/health/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      plansTable.data = res.data.length > 0 ? res?.data[0] : [];
      getSmallestCopayRateAsDefaultValue();
      plansTable.data.forEach(plan => {
        if (plan.isManualPlan) {
          isManualPlansCount.value++;
        }

        if (plan.id === selectedPlan.value?.id && !plan.needPriceUpdate) {
          selectedPlan.value.needPriceUpdate = false;
        }
      });

      setTimeout(() => {
        onPlanFiltersSubmit();
      }, 800);
    })
    .catch(err => {
      console.log(err);
    });
};

const planClicked = plan => {
  selectedPlan.value = plan;
  modals.plan = true;
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

  let addOns = {};

  selectedPlans.value.map(plan => {
    let copayIdToBeAdded = plan.selectedCopayId;
    plan.coPayments.forEach(element => {
      if (element.id == copayIdToBeAdded) {
        addOns[plan.id] = { coPayment: element };
      }
    });
  });

  axios
    .post(
      '/api/v1/quotes/health/export-plans-pdf',
      {
        plan_ids: planIds,
        quote_uuid: page.props.quote.uuid,
        addons: addOns,
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

const onTogglePlans = toggle => {
  toggleLoader.value = true;

  const planIds = useArrayUnique(
    selectedPlans.value.map(p => {
      return p.id;
    }),
  ).value;

  axios
    .post(route('manualPlanToggle', { quoteType: 'Health' }), {
      modelType: 'Health',
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

const onCreatePlan = () => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['plansTable.data'],
    onStart: () => {
      modals.createPlan = false;
    },
    onFinish: () => {
      notification.success({
        title: 'Plan Created',
        position: 'top',
      });
      location.reload();
    },
  });
};

const onPlanError = data => {
  modals.createPlan = false;
  notification.error({
    title: data ?? 'Plan Creation Failed',
    position: 'top',
  });
};

const planFilters = reactive({
  insurer: [],
  network: [],
  manual_plan: null,
  current_online: null,
  plan_types: [],
});
const planFiltersCount = ref(0);
const options = reactive({
  network: [],
  loading: false,
});
watch(
  () => planFilters?.insurer,
  value => {
    if (value) {
      options.loading = true;
      const ids = planFilters.insurer.map(item => {
        return item;
      });
      let url = `/insurance-provider-networks?insuranceProviderId=${ids.toString()}`;
      axios
        .get(url)
        .then(res => {
          if (res.data.length > 0) {
            options.network = res.data;
          } else {
            options.network = [];
          }
        })
        .catch(err => {
          console.log(err);
        })
        .finally(() => {
          options.loading = false;
        });
    }
  },
);

const listQuotePlansFiltered = ref([]);

watchEffect(() => {
  listQuotePlansFiltered.value = plansTable.data
    .slice()
    .sort((a, b) => Number(!b.isHidden) - Number(!a.isHidden));
});

const onPlanFiltersSubmit = () => {
  const filters = cleanObj(planFilters);
  planFiltersCount.value = Object.keys(filters).length;
  listQuotePlansFiltered.value = plansTable.data.filter(plan => {
    let isManualPlan = planFilters.manual_plan;
    let isCurrentlyOnline = planFilters.current_online;
    let network = planFilters.network;
    let insurerIds =
      planFilters.insurer?.map(item => {
        return item;
      }) || [];
    let manualMatch = false;
    let insurerMatch = false;
    let networkMatch = false;
    let onlineMatch = false;
    let planTypeMatch = false;
    if (isManualPlan != null) {
      manualMatch = plan.isManualPlan == isManualPlan;
    } else {
      manualMatch = true;
    }
    if (isCurrentlyOnline != null) {
      onlineMatch = !plan.isHidden == isCurrentlyOnline;
    } else {
      onlineMatch = true;
    }
    if (planFilters.plan_types && planFilters.plan_types.length > 0) {
      planTypeMatch = planFilters.plan_types.includes(plan.planTypeId);
    } else {
      planTypeMatch = true;
    }
    if (insurerIds?.length > 0) {
      insurerMatch = insurerIds.includes(plan.providerId);
    } else {
      insurerMatch = true;
    }
    if (network?.length > 0) {
      networkMatch = network.includes(plan.eligibilityName);
    } else {
      networkMatch = true;
    }
    return (
      manualMatch &&
      insurerMatch &&
      networkMatch &&
      onlineMatch &&
      planTypeMatch
    );
  });
  modals.planFilters = false;
  planDataTable.value.updatePage(1);
};

const onPlanFiltersReset = () => {
  planFilters.insurer = [];
  planFilters.network = [];
  planFilters.manual_plan = null;
  planFilters.current_online = null;
  listQuotePlansFiltered.value = plansTable.data;
  modals.planFilters = false;
  planFiltersCount.value = 0;
  planDataTable.value.updatePage(1);
};

const isMounted = ref(false);

const selectedCoPay = reactive({
  id: null,
  premium: null,
  vat: null,
  planId: null,
});

const getSmallestCopayRateAsDefaultValue = () => {
  let smallestCopayValue = 0;
  let defaultCopayId = 0;
  let smallestCopayVAT = 0;
  let smallestCopayLoadingPrice = 0;
  plansTable.data.forEach(element => {
    defaultCopayId = element.selectedCopayId;
    element.ratesPerCopay?.forEach(function callback(value, index) {
      if (
        element.selectedCopayId &&
        defaultCopayId == value.healthPlanCoPaymentId
      ) {
        smallestCopayValue = Number(value.premium);
        smallestCopayVAT = Number(value.vat);
        smallestCopayLoadingPrice = Number(
          value.loadingPrice ? value.loadingPrice : 0,
        );
        defaultCopayId = element.selectedCopayId;
      } else if (
        element.selectedCopayId == undefined ||
        element.selectedCopayId == null
      ) {
        if (index == 0) {
          smallestCopayValue = Number(value.premium);
          smallestCopayVAT = Number(value.vat);
          smallestCopayLoadingPrice = Number(
            value.loadingPrice ? value.loadingPrice : 0,
          );
          defaultCopayId = value.healthPlanCoPaymentId;
        } else if (value.premium < smallestCopayValue) {
          smallestCopayValue = Number(value.premium);
          smallestCopayVAT = Number(value.vat);
          smallestCopayLoadingPrice = Number(
            value.loadingPrice ? value.loadingPrice : 0,
          );
          defaultCopayId = value.healthPlanCoPaymentId;
        }
      }
    });

    element.memberPremiumBreakdown?.forEach(
      function callback(breakDown, index) {
        breakDown.ratesPerCopay?.forEach(function callback(ratePerCopay) {
          if (ratePerCopay.notifyAgent) {
            element.needPriceUpdate = true;
          }
        });
      },
    );

    if (isMounted.value && selectedCoPay.planId == element.id) {
      element.actualPremium = selectedCoPay.premium;
      if (smallestCopayLoadingPrice != 0) {
        element.vat = Number(
          (selectedCoPay.premium + smallestCopayLoadingPrice) * 0.05,
        );
      } else {
        element.vat = selectedCoPay.vat;
      }
      element.selectedCopayId = selectedCoPay.id;
      element.loadingPrice = smallestCopayLoadingPrice;
    } else {
      element.selectedCopayId = defaultCopayId;
      element.actualPremium = smallestCopayValue;
      element.vat = smallestCopayVAT;
      element.loadingPrice = smallestCopayLoadingPrice;
    }
    element.coPayments.forEach(function callback(value, index) {
      if (value.id == element.selectedCopayId) {
        element.copayName = value.text;
      }
    });
  });
};

const onSelectedCopay = data => {
  selectedCoPay.id = data.id;
  selectedCoPay.premium = Number(data.premium);
  selectedCoPay.vat = Number(data.vat);
  selectedCoPay.planId = data.planId;
  getSmallestCopayRateAsDefaultValue();
};

const onMarkPlanAsManual = (plan, loadingPrice) => {
  listQuotePlansFiltered.value = listQuotePlansFiltered.value.map(element => {
    if (element.id == plan.id) {
      element.isManualPlan = true;
      // LOADING PRICE UPDTAE
      let vat =
        (element.actualPremium +
          (element.policyFee || 0) +
          (element.basmah || 0) +
          (loadingPrice || 0)) *
        0.05;
      element.loadingPrice = Number(loadingPrice);
      element.vat = Number(vat);
    }
    return element;
  });
  onLoadAvailablePlansData();
};

onMounted(() => {
  onLoadAvailablePlansData();
  const isHealthAdvisor = page.props.advisors.find(
    a => a.id == page.props.quote.advisor_id,
  ) || { id: null };
  if (isHealthAdvisor) assignLead.value = isHealthAdvisor.id;
  isMounted.value = true;
});
const onAssignLead = () => {
  if (!assignLead.value) {
    notification.error({
      title: 'Please select a lead',
      position: 'top',
    });
    return;
  }
  router.post(
    route('manualLeadAssign', { quoteType: 'Health' }),
    {
      modelType: 'Health',
      entityId: page.props.quote.id,
      assigned_to_id_new: assignLead.value,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        isDisabled.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Lead Assigned',
          position: 'top',
        });
      },
      onFinish: () => {
        isDisabled.value = false;
      },
    },
  );
};
const onTeamAssign = () => {
  if (!assignSubteam.value) {
    notification.error({
      title: 'Please select a subteam',
      position: 'top',
    });
    return;
  }
  router.post(
    route('healthTeamAssign'),
    {
      modelType: 'Health',
      entityId: page.props.quote.id,
      assign_team: assignSubteam.value,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        isDisabled.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Team Assigned',
          position: 'top',
        });
      },
      onFinish: () => {
        isDisabled.value = false;
      },
    },
  );
};
const subTeamOptions = [
  { value: 'RM-NB', label: 'RM-NB' },
  { value: 'RM-SPEED', label: 'RM-SPEED' },
  { value: 'EBP', label: 'EBP' },
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];
const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};
const leadDuplicateForm = useForm({
  modelType: 'health',
  parentType: 'health',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

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

// Only copied from health quote show not sure if it is needed
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
</script>

<template>
  <div>
    <Head title="Health Revival Detail" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Health Revival Detail</h2>
      </template>
      <template #default>
        <LeadNotes
          :documentType="props?.noteDocumentType"
          :notes="quoteNotes"
          modelType="Health"
          :quote="props.quote"
          :cdn="cdnPath"
        />

        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>
        <Link :href="route('health-revival-quotes-list')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Health Revival List
          </x-button>
        </Link>

        <Link :href="route('health-revival-quotes-edit', quote.uuid)">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </template>
    </StickyHeader>

    <x-divider class="my-4" />
    <div
      v-if="
        !hasAnyRole([
          rolesEnum.EBPAdvisor,
          rolesEnum.HealthAdvisor,
          rolesEnum.RMAdvisor,
        ]) || hasRole(rolesEnum.SuperManagerLeadAllocation)
      "
      class="p-4 rounded shadow mb-6 bg-primary-50/50 saad"
    >
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div>
            <h3 class="font-semibold text-primary-800 text-lg">
              Assign Team & Advisor
            </h3>
          </div>
        </template>

        <template #body>
          <x-divider class="my-4" />
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-1/2 flex gap-2 items-end">
              <x-select
                v-model="assignSubteam"
                label="Assign Subteam"
                :options="subTeamOptions"
                placeholder="Select Subteam"
                class="w-auto flex-1 !mb-2"
              />
              <div>
                <x-button
                  color="orange"
                  size="sm"
                  class="mb-2"
                  @click.prevent="onTeamAssign"
                  :loading="isDisabled"
                >
                  Assign Team
                </x-button>
              </div>
            </div>
            <div
              v-if="!hasRole(rolesEnum.HealthWCUAdvisor)"
              class="w-full md:w-1/2 flex gap-2 items-end"
            >
              <ComboBox
                v-model="assignLead"
                label="Assign Lead"
                :options="advisorOptions"
                placeholder="Select Lead"
                class="w-auto flex-1 mt-1"
                :single="true"
              />
              <div>
                <x-button
                  color="orange"
                  size="sm"
                  class="mb-2"
                  @click.prevent="onAssignLead"
                  :loading="isDisabled"
                >
                  Assign
                </x-button>
              </div>
            </div>
          </div>
        </template>
      </Collapsible>
    </div>

    <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
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
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div
            v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
            class="grid sm:grid-cols-2"
          >
            <dt class="font-medium">ID</dt>
            <dd>{{ props.quote.id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Ref-ID
                </label>
                <template #tooltip> Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ props.quote.code }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CUSTOMER TYPE</dt>
            <dd>{{ props.quote.customer_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ props.quote.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUBTEAM</dt>
            <dd>{{ props.quote.health_team_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ props.quote.advisor_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ props.quote.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ props.quote.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Parent Ref-ID
                </label>
                <template #tooltip> Parent Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ props.quote.parent_duplicate_quote_id }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS ECOMMERCE</dt>
            <dd>{{ props.quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS EBP RENEWAL</dt>
            <dd>{{ props.quote.is_ebp_renewal ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ props.quote.lost_reason }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ props.quote.device }}</dd>
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
            <dt class="font-medium">
              FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?
            </dt>
            <dd>{{ props.quote.cover_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ props.quote.currently_insured_with_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF PLAN</dt>
            <dd>{{ checkPlanType(props.quoteRequest.health_plan_type_id) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ dateFormat(props.quote.next_followup_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ props.quote.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADDITIONAL NOTES</dt>
            <dd>{{ props.quote.additional_notes }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ENQUIRY COUNT</dt>
            <dd>{{ props.quote.enquiry_count }}</dd>
          </div>
        </dl>
      </div>
    </div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          {{
            quote.customer_type == page.props.customerTypeEnum.Individual
              ? 'Customer'
              : 'Entity '
          }}
          Profile
        </h3>
        <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
          KYC - Complete
        </x-tag>
        <x-tag color="amber" v-else> KYC - Pending </x-tag>
      </div>
      <x-divider class="mb-4 mt-1" />
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
              <dt class="font-medium">EMIRATE OF VISA</dt>
              <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">GENDER</dt>
              <dd>{{ genderText(quote.gender).value }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MARITAL STATUS</dt>
              <dd>{{ quote.marital_status_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">SALARY BAND</dt>
              <dd>{{ quote.salary_band_id_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MEMBER CATEGORY</dt>
              <dd>{{ quote.member_category_id_text }}</dd>
            </div>
            <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
          </dl>
          <dl
            v-if="quote.customer_type === page.props.customerTypeEnum.Entity"
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
    </div>

    <div
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      class="p-4 rounded shadow mb-6 bg-white"
    >
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Member Details
          <x-tag size="sm">{{ membersDetail.length || 0 }}</x-tag>
        </h3>
        <x-button @click.prevent="onAddMemberModal" size="sm" color="orange">
          Add Member
        </x-button>
      </div>

      <DataTable
        table-class-name="tablefixed compact"
        :headers="memberDetailsTable.columns"
        :items="membersDetail || []"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-first_name="{ first_name, last_name }">
          {{ first_name + ' ' + (last_name == null ? '' : last_name) }}
        </template>
        <template #item-gender="{ gender }">
          {{ genderText(gender).value }}
        </template>
        <template #item-dob="{ dob }">
          {{ dateFormat(dob) }}
        </template>
        <template #item-relation="{ relation }">
          {{ relation?.text }}
        </template>
        <template #item-nationality="{ nationality }">
          {{ nationality?.text }}
        </template>
        <template #item-emirate="{ emirate }">
          {{ emirate?.text }}
        </template>
        <template #item-member_category_id="{ member_category_id }">
          {{ memberCategoryText(member_category_id).value }}
        </template>
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

      <x-modal v-model="modals.member" size="lg" show-close backdrop>
        <template #header>
          {{ memberActionEdit ? 'Edit' : 'Add' }} Member
        </template>

        <x-form @submit="onMemberSubmit" :auto-focus="false">
          <div
            v-if="isManualPlansCount > 0"
            class="bg-red-100 border border-red-400 text-red-700 rounded-b px-4 py-3 shadow-md mb-4"
            role="alert"
          >
            <div class="flex">
              <div class="py-1">
                <svg
                  class="fill-current h-6 w-6 text-read-900 mr-4"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"
                  />
                </svg>
              </div>
              <div>
                <p class="font-bold">ALERT! Manual Plan(s) exists.</p>
                <p class="text-sm">
                  Please revist all manual plan(s) and update the per member
                  price
                </p>
              </div>
            </div>
          </div>
          <div class="grid md:grid-cols-2 gap-4 md:pb-16">
            <input type="hidden" :value="memberForm.id" />
            <x-input
              maxLength="60"
              v-model="memberForm.first_name"
              label="First Name"
              placeholder="First Name"
              :rules="[isRequired]"
            />
            <x-input
              maxLength="60"
              v-model="memberForm.last_name"
              label="Last Name"
              placeholder="Last Name"
              :rules="[isRequired]"
            />
            <ComboBox
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalityOptions"
              placeholder="Select Nationality"
              :single="true"
              :hasError="memberFieldReq.nationality"
            />

            <x-select
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa*"
              :options="emiratesOptions"
              :rules="[isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender*"
              :options="genderSelect"
              :rules="[isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />
            <DatePicker
              v-model="memberForm.dob"
              label="DOB*"
              :max-date="new Date()"
              :rules="[isRequired]"
              :hasError="memberFieldReq.dob"
            />
            <x-select
              v-model="memberForm.member_category_id"
              label="Member Category*"
              :options="memberCategoriesOptions"
              :rules="[isRequired]"
              placeholder="Select Member Category"
              class="w-full"
            />
            <x-select
              v-model="memberForm.relation_code"
              label="Relation"
              :options="memberRelationOptions"
              placeholder="Select Relation"
              class="w-full"
            />
            <x-select
              v-model="memberForm.salary_band_id"
              label="Salary Band"
              :options="salaryBandsOptions"
              placeholder="Select Salary Band"
              class="w-full"
            />
          </div>

          <div class="flex justify-end gap-3">
            <x-button size="sm" @click.prevent="modals.member = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="memberForm.processing"
              type="submit"
              class="px-6"
            >
              {{ memberActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>

      <!-- <x-modal v-model="modals.memberConfirm" show-close backdrop>
        <template #header> Delete Member Detail </template>
        <div
          v-if="isManualPlansCount > 0"
          class="w-full bg-red-100 border border-red-400 text-red-700 rounded-b px-4 py-3 shadow-md mb-4"
          role="alert"
        >
          <div class="flex">
            <div class="py-1">
              <svg
                class="fill-current h-6 w-6 text-read-900 mr-4"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
              >
                <path
                  d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"
                />
              </svg>
            </div>
            <div>
              <p class="font-bold">ALERT! Manual Plan(s) exists.</p>
              <p class="text-sm">
                Please revist all manual plan(s) and update the per member price
              </p>
            </div>
          </div>
        </div>
        <p>Are you sure you want to delete this?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.memberConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              @click.prevent="memberDeleteConfirmed"
              :loading="memberForm.processing"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal> -->
    </div>

    <CustomerAdditionalContacts
      :quoteType="quoteType"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContactsData"
      :quoteEmail="quote.email"
      :quoteMobile="quote.mobile_no"
    />

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Health"
      :quote="quote"
      :insly-id="quote?.insly_id"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-50">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="quote.quote_status_id == 15"
              placeholder="Lead Status"
              class="w-full"
            />
            <x-textarea
              v-model="leadStatusForm.notes"
              type="text"
              label="Notes"
              placeholder="Lead Notes"
              class="w-full"
              :disabled="quote.quote_status_id == 15"
            />
          </div>
        </div>
        <div class="w-full md:w-50">
          <div class="flex flex-col gap-4">
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
            <x-field class="" label="Transaction Type">
              <x-input
                type="text"
                :value="quote.transaction_type_text"
                class="w-full"
                :disabled="true"
              />
            </x-field>
          </div>
        </div>
      </div>
      <x-divider class="mb-1 mt-10" />
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

  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">E-COM Details</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PLAN NAME</dt>
          <dd>{{ selectedProviderPlan.planName }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PROVIDER NAME</dt>
          <dd>{{ selectedProviderPlan.providerName }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PAYMENT STATUS</dt>
          <dd>{{ quote.payment_status_text }}</dd>
        </div>
        <div
          class="grid sm:grid-cols-2"
          v-if="
            page.props.quote.payment_status_id == paymentStatusEnum.DECLINED
          "
        >
          <dt class="font-medium">REASON</dt>
          <dd>{{ mainPayment?.payment_status_message }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PAID AT</dt>
          <dd>{{ ecomDetails.paidAt }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NETWORK</dt>
          <dd>{{ ecomDetails.network }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">TOTAL PRICE (with VAT)</dt>
          <dd>{{ fixedValue(selectedProviderPlan.premium) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CO-PAY / CO-INSURANCE</dt>
          <dd>{{ coPayment ? coPayment.text : 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>
  <!-- plans -->

  <div class="p-4 rounded shadow mb-6 bg-white">
    <TheCollapsible v-model:expanded="showPlans">
      <template #header>
        <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">
            Available Plans
            <x-tag size="sm">{{ listQuotePlansFiltered.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4"></x-divider>
        <div class="flex flex-wrap gap-3 justify-end my-2">
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
            @click.prevent="onExportPlans"
            :loading="exportLoader"
          >
            Download PDF
          </x-button>

          <x-button
            v-if="plansTable.data.length > 0"
            size="sm"
            color="orange"
            @click.prevent="
              onCopyText(ecomHealthInsuranceQuoteUrl + quote.uuid)
            "
          >
            Copy Link
          </x-button>
          <x-badge
            size="sm"
            color="error"
            outlined
            animated
            :show="planFiltersCount > 0"
          >
            <x-button
              v-if="plansTable.data.length > 0"
              size="sm"
              color="primary"
              @click.prevent="modals.planFilters = true"
            >
              Filters
            </x-button>
            <template #content> {{ planFiltersCount }} </template>
          </x-badge>

          <x-button
            v-if="
              hasAnyRole([
                rolesEnum.BetaUser,
                rolesEnum.RMAdvisor,
                rolesEnum.HealthManager,
              ])
            "
            size="sm"
            color="emerald"
            @click.prevent="modals.createPlan = true"
          >
            Add Plan
          </x-button>
        </div>
        <DataTable
          ref="planDataTable"
          v-model:items-selected="selectedPlans"
          table-class-name="tablefixed compact"
          :headers="plansTable.columns"
          :items="listQuotePlansFiltered || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          class="flex-wrap"
          :sort-by="'actualPremium'"
          :sort-type="'asc'"
          :hide-footer="listQuotePlansFiltered.length < 15"
        >
          <template #item-copayName="item">
            <span class="copay-max">{{ item.copayName }}</span>
          </template>
          <template #item-planTypeId="item">
            <span class="copay-max">{{ item.plan_type }}</span>
          </template>
          <template
            #item-providerName="{ providerName, isManualPlan, isHidden }"
          >
            <p>{{ providerName }}</p>
            <div class="flex gap-1">
              <x-tag
                v-if="isManualPlan"
                size="xs"
                color="primary"
                class="mt-0.5 text-[10px]"
              >
                Manual Plan
              </x-tag>
              <x-tag
                v-if="isHidden"
                size="xs"
                color="error"
                class="mt-0.5 text-[10px]"
              >
                Hidden
              </x-tag>
              <x-tag
                v-if="!isHidden"
                size="xs"
                color="success"
                class="mt-0.5 text-[10px]"
              >
                Currently Online
              </x-tag>
            </div>
          </template>
          <template
            #item-total="{
              actualPremium,
              policyFee,
              basmah,
              vat,
              loadingPrice,
            }"
          >
            {{
              fixedValue(
                actualPremium +
                  (policyFee || 0) +
                  (basmah || 0) +
                  vat +
                  (loadingPrice || 0),
              )
            }}
          </template>
          <template #item-action="item">
            <div class="flex gap-2 pr-2">
              <!-- put here -->
              <!-- don't remove this commented code anyone please -->
              <template
                v-if="
                  (item.isManualPlan && membersDetailsUpdated) ||
                  item.needPriceUpdate
                "
              >
                <!-- always false temporarily -->
                <x-tooltip position="top" class="arrow-b">
                  <x-badge
                    size="xs"
                    color="error"
                    outlined
                    offset-x="-8"
                    offset-y="-10"
                  >
                    <x-button
                      size="xs"
                      color="primary"
                      outlined
                      @click.prevent="planClicked(item)"
                    >
                      View
                    </x-button>
                    <template #content>!</template>
                  </x-badge>
                  <template #tooltip>
                    Price outdated! <br />
                    Please update
                  </template>
                </x-tooltip>
              </template>
              <template v-else>
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  @click.prevent="planClicked(item)"
                >
                  View
                </x-button>
              </template>
              <x-button
                size="xs"
                color="emerald"
                outlined
                @click.prevent="
                  onCopyText(
                    ecomHealthInsuranceQuoteUrl +
                      quote.uuid +
                      `/payment/?providerCode=${item.providerCode}&planId=${item.id}&selectedCopayId=${item.selectedCopayId}`,
                  )
                "
              >
                Copy
              </x-button>

              <span>
                <SelectPlan
                  v-if="selectedProviderPlan.id != item.id"
                  @update:selectedPlanChanged="handlePlanSelected"
                  :plan="item"
                  :quoteType="quoteType"
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

        <LazyAvailablePlan
          v-model="modals.plan"
          :plan="selectedPlan"
          :genders="genderOptions"
          :members="membersDetail"
          :memberCategories="memberCategories"
          :memebersDetailsChanged="membersDetailsUpdated"
          @copay-update="onSelectedCopay"
          @onLoadAvailablePlansData="onLoadAvailablePlansData"
          @membersDetailsReviewed="onRecieveMembersDetailsReview"
          @markPlanAsManual="onMarkPlanAsManual"
        />

        <LazyCreatePlan
          v-model="modals.createPlan"
          :uuid="quote.uuid"
          :members="membersDetail"
          :genders="genderOptions"
          @success="onCreatePlan"
          @error="onPlanError"
        />

        <x-modal v-model="modals.planFilters" size="lg" show-close backdrop>
          <template #header> Filters </template>

          <div class="grid sm:grid-cols-2 gap-4 py-8 min-h-[18rem]">
            <ComboBox
              v-model="planFilters.insurer"
              label="Insurer"
              :options="insuranceProviders"
              :loading="planFilters.processing"
              select-all
              deselect-all
            />
            <ComboBox
              v-model="planFilters.network"
              :label="
                planFilters.insurer?.length == 0
                  ? 'Network (please select insurer first)'
                  : 'Network'
              "
              :options="options.network"
              :disabled="planFilters.insurer?.length == 0"
              select-all
              deselect-all
            />
            <div>
              <x-tooltip position="right" class="arrow-l">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600 mb-0.5"
                >
                  Manual Plan
                </label>
                <template #tooltip>Manually Added Plans</template>
              </x-tooltip>
              <x-select
                v-model="planFilters.manual_plan"
                :options="[
                  { value: '', label: 'All' },
                  { value: '1', label: 'Yes' },
                  { value: '0', label: 'No' },
                ]"
                class="w-full"
              />
            </div>

            <div>
              <x-tooltip position="right" class="arrow-l">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600 mb-0.5"
                >
                  Currently Online
                </label>
                <template #tooltip> Plans that are Currently Online </template>
              </x-tooltip>
              <x-select
                v-model="planFilters.current_online"
                :options="[
                  { value: '', label: 'All' },
                  { value: '1', label: 'Yes' },
                  { value: '0', label: 'No' },
                ]"
                class="w-full"
              />
            </div>

            <ComboBox
              v-model="planFilters.plan_types"
              :label="'Plan Type'"
              :options="planTypes"
              :disabled="planFilters.plan_types?.length == 0"
              select-all
              deselect-all
            />
          </div>

          <div class="flex justify-end gap-3 mb-4">
            <x-button
              size="sm"
              color="#ff5e00"
              type="submit"
              @click="onPlanFiltersSubmit"
            >
              Apply
            </x-button>
            <x-button
              size="sm"
              color="primary"
              @click.prevent="onPlanFiltersReset"
            >
              Reset
            </x-button>
          </div>
        </x-modal>
      </template>
    </TheCollapsible>
  </div>

  <!-- payments -->

  <PaymentTableNew
    v-if="isNewPaymentStructure"
    quoteType="Health"
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
    :paymentStatusEnum="paymentStatusEnum"
    :paymentTooltipEnum="paymentTooltipEnum"
    :paymentMethods="
      paymentMethods.map(pm => {
        return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
      })
    "
    :storageUrl="storageUrl"
    :eCommercePrice="ecomDetails.priceWithVAT ? ecomDetails.priceWithVAT : 0"
    :eCommercePriceWithLP="
      ecomDetails.priceWithLP ? ecomDetails.priceWithLP : 0
    "
    :bookPolicyDetails="bookPolicyDetails"
  />

  <!-- QuoteDocuments -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Documents
        <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
      </h3>
      <div class="flex gap-2">
        <Link
          v-if="quote?.insly_id && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
          :href="`/legacy-policy/${quote.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <x-button @click.prevent="modals.doc = true" size="sm" color="primary">
          Upload Documents
        </x-button>
        <x-button
          size="sm"
          color="red"
          v-if="sendPolicy"
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
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="modals.doc" size="xl" show-close backdrop>
      <template #header> Upload Documents </template>
      <LazyDocumentUploader
        :members="memberDataDocs(membersDetail)"
        :doc-types="documentTypes"
        :docs="quoteDocuments || []"
        :cdn="cdnPath"
      />
    </x-modal>
    <x-modal v-model="modals.docConfirm" show-close backdrop>
      <template #header> Delete Document </template>
      <p>Are you sure you want to delete this document?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.docConfirm = false">
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
  </div>

  <!-- Lead activities -->

  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Lead Activities
        <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
      </h3>
      <x-button size="sm" color="orange" @click.prevent="addActivity">
        Add Activity
      </x-button>
    </div>
    <x-divider class="my-4" />

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
          >
            Edit
          </x-button>
          <x-button
            size="xs"
            color="error"
            :disabled="item.status === 1"
            outlined
            @click.prevent="activityDelete(item.id)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>
    <x-modal v-model="modals.activity" size="lg" show-close backdrop>
      <template #header>
        {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
      </template>

      <x-form @submit="onActivitySubmit" :auto-focus="false">
        <div class="grid gap-4">
          <x-input
            v-model="activityForm.title"
            label="Title"
            :rules="[isRequired]"
            class="w-full"
          />

          <x-textarea
            v-model="activityForm.description"
            label="Description"
            :adjust-to-text="false"
            class="w-full"
          />

          <x-select
            v-model="activityForm.assignee_id"
            label="Assignee"
            :options="advisorOptions"
            :rules="[isRequired]"
            placeholder="Select Assignee"
            class="w-full"
          />

          <date-picker
            v-model="activityForm.due_date"
            label="Due Date"
            :rules="[isRequired]"
            class="w-full"
            withTime
            :timezone="'UTC'"
          />
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
  </div>
  <!-- Lead History -->

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

  <CustomerChatLogs
    :customerName="quote?.first_name + ' ' + quote?.last_name"
    :quoteId="quote.uuid"
    :quoteType="'HEALTH'"
  />
</template>
