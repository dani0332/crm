<script setup>
import { computed } from 'vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';

const props = defineProps({
  quote: Object,
  leadStatuses: Array,
  ecomDetails: Object,
  coPayment: Object,
  membersDetail: Array,
  memberCategories: Array,
  memberRelations: Array,
  salaryBands: Array,
  nationalities: Array,
  emirates: Array,
  advisors: Array,
  teams: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  documentType: Object,
  cdnPath: String,
  ecomHealthInsuranceQuoteUrl: String,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  modelType: String,
  quoteTypeId: Number,
  notProductionApproval: Boolean,
  allowedDuplicateLOB: Array,
  permissions: Object,
  genderOptions: Object,
  isQuoteDocumentEnabled: Boolean,
  isBetaUser: Boolean,
  payments: Array,
  mainPayment: Object,
  quoteRequest: Object,
  can: Object,
  paymentMethods: Object,
  sendPolicy: Boolean,
  insuranceProviders: Array,
  planTypes: Array,
  embeddedProducts: Array,
  healthPlanTypes: Array,
  customerTypeEnum: Object,
  industryType: Object,
  UBORelations: Array,
  UBOsDetails: Array,
  canAddBatchNumber: Boolean,
  paymentLink: String,
  quoteType: String,
  paymentTooltipEnum: Object,
  storageUrl: String,
  bookPolicyDetails: Array,
  isNewPaymentStructure: Boolean,
  amlStatusName: String,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  linkedQuoteDetails: Object,
  lockLeadSectionsDetails: Object,
  clientInquiryLogs: Array,
  hashCollapsibleStatuses: Boolean,
  emailStatuses: Array,
  quoteNotes: Object,
  paymentDocument: Array,
  noteDocumentType: Array,
});
const modelClass = 'App\\Models\\HealthQuote';

const isManualPlansCount = ref(0);

const page = usePage();
const authId = computed(() => page.props.auth.user.id);

let countDays = ref(useDaysSinceStale(props.quoteRequest?.stale_at));
const compareDueDate = useCompareDueDate;
const permissionsEnum = page.props.permissionsEnum;
const canAny = permissions => useCanAny(permissions);
const leadSource = page.props.leadSource;
const can = permission => useCan(permission);

const showPlans = ref(!props.hashCollapsibleStatuses);
const contactLoader = ref(false);

const notification = useToast();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;

const paymentStatusEnum = page.props.paymentStatusEnum;
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const daysSinceStale = date => useDaysSinceStale(date);

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
const confirmData = reactive({
  contactPrimary: null,
});

const allowStatusUpdate = computed(() => {
  if (canAny([permissionEnum.SUPER_LEAD_STATUS_CHANGE])) {
    return (
      page.props.quote.quote_status_id ==
      page.props.quoteStatusEnum.PolicyBooked
    );
  }
  return (
    (props.quote.quote_status_id ==
      page.props.quoteStatusEnum.TransactionApproved ||
      props.quote.quote_status_id == page.props.quoteStatusEnum.Lost) ??
    false
  );
});

const checkPlanType = id => {
  return page.props.healthPlanTypes.find(type => type.id === id)?.text;
};

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
  sendConfirm: false,
});

const leadDuplicateForm = useForm({
  modelType: 'health',
  parentType: 'health',
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

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const cleanObj = obj => useCleanObj(obj);

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

const { copy, copied } = useClipboard();

const { isRequired, isEmail, isNumber, isMobileNo } = useRules();

const onCopyText = text => {
  copy(text);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const subTeamOptions = [
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
  { value: 'PCP', label: 'PCP' },
];

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const genderSelect = computed(() => {
  return Object.keys(page.props.genderOptions).map(status => ({
    value: status,
    label: page.props.genderOptions[status],
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const nationalityOptions = computed(() => {
  return page.props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const industryTypeOptions = computed(() => {
  return page.props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
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

const emiratesOptions = computed(() => {
  return page.props.emirates.map(em => ({
    value: em.id,
    label: em.text,
  }));
});

const salaryBandsOptions = computed(() => {
  return page.props.salaryBands.map(sal => ({
    value: sal.id,
    label: sal.text,
  }));
});

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

const leadStatusForm = useForm({
  modelType: 'Health',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Health/${page.props.quote.id}/update-lead-status`,
    {
      preserveScroll: true,
      preserveState: true,
      onError: errors => {
        notification.error({ title: errors.value, position: 'top' });
      },
      onSuccess: response => {
        const flash_messages = response.props.flash;
        countDays.value = useDaysSinceStale(
          response.props.quoteRequest?.stale_at,
        );
        router.reload({ only: ['quoteRequest'] });
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

const members = ref(page.props.membersDetail);
const computedMembers = computed(() => {
  return members.value.filter(x => !x.is_third_party_payer);
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

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  allowEmpty: v => true || 'This field is required',
  isPhone: v =>
    /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,10}$/im.test(v) ||
    'Phone must be valid',
};

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

const memberDelete = id => {
  modals.memberConfirm = true;
  confirmDeleteData.member = id;
  memberForm.customer_member_id = id;
};

const memberDeleteConfirmed = () => {
  memberForm.post(
    `/health-quote-delete-member`,
    // `/members/${page.props.quote.customer_type}-${page.props.modelType}-${confirmDeleteData.member}`,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Deleted',
          position: 'top',
        });
        onLoadAvailablePlansData();
        // location.reload();
      },
      onFinish: () => {
        modals.memberConfirm = false;
        membersDetailsUpdated.value = true;
      },
    },
  );
};

const onRecieveMembersDetailsReview = () => {
  membersDetailsUpdated.value = false;
};

const memberDataDocs = membersDetail => {
  return membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);
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
      fixed: true,
      width: 400,
    },
    {
      text: 'Plan Name',
      value: 'name',
      fixed: true,
      width: 100,
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
      width: 230,
    },
    {
      text: 'Price',
      value: 'actualPremium',
      sortable: true,
    },
    {
      text: 'Total Indicative Price (with VAT)',
      value: 'total',
      width: 20,
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

      if (selectedPlan.value?.id) {
        let plans = plansTable.data.filter(x => x.id == selectedPlan.value?.id);
        selectedPlan.value = { ...plans[0] };
      }

      setTimeout(() => {
        onPlanFiltersSubmit();
      }, 800);
    })
    .catch(err => {
      notification.error({
        title: 'Error loading plans',
        position: 'top',
      });
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
      notification.error({
        title: 'Error exporting plans',
        position: 'top',
      });
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
    title: 'Plan Creation Failed',
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
          notification.error({
            title: 'Error!',
            position: 'top',
          });
        })
        .finally(() => {
          options.loading = false;
        });
    }
  },
);

const listQuotePlansFiltered = ref([]);

const sortPlans = incommingPlans => {
  incommingPlans = incommingPlans.sort((a, b) => {
    // Convert undefined or falsy `actualPremium` values to 0 for comparison, if needed
    const premiumA = a.actualPremium || 0;
    const premiumB = b.actualPremium || 0;

    return premiumA - premiumB;
  });

  const matchingIndex = incommingPlans.findIndex(
    x => x.id === selectedProviderPlan.value?.id,
  );

  if (matchingIndex > 0) {
    [incommingPlans[0], incommingPlans[matchingIndex]] = [
      incommingPlans[matchingIndex],
      incommingPlans[0],
    ];
  }
  listQuotePlansFiltered.value = [...incommingPlans];
};

watchEffect(() => {
  listQuotePlansFiltered.value = plansTable.data
    .slice()
    .sort((a, b) => Number(!b.isHidden) - Number(!a.isHidden));

  if (
    planFilters?.insurer?.length === 0 ||
    planFilters?.insurer?.length === undefined
  ) {
    sortPlans(listQuotePlansFiltered.value);
  }
});

const computedListQuotePlans = computed(() => {
  return listQuotePlansFiltered.value;
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
  if (planDataTable.value) {
    planDataTable.value.updatePage(1);
  }

  sortPlans(listQuotePlansFiltered.value);
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
        smallestCopayValue = Number(value.discountPremium);
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
          smallestCopayValue = Number(value.discountPremium);
          smallestCopayVAT = Number(value.vat);
          smallestCopayLoadingPrice = Number(
            value.loadingPrice ? value.loadingPrice : 0,
          );
          defaultCopayId = value.healthPlanCoPaymentId;
        } else if (value.discountPremium < smallestCopayValue) {
          smallestCopayValue = Number(value.discountPremium);
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

const onMarkPlanAsManual = plan => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'ecomDetails', 'coPayment'],
  });
  listQuotePlansFiltered.value = listQuotePlansFiltered.value.map(element => {
    if (element.id == plan.id) {
      element.isManualPlan = true;
    }
    return element;
  });
};

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
      text: 'Original Document',
      value: 'doc_name',
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

const documentsTableItems = computed(() => {
  return page.props.quoteDocuments.map(doc => {
    return {
      document_type_text:
        doc.document_type_text.length > 0 ? doc.document_type_text : '',
      doc_name: doc.doc_name,
      original_name: doc.original_name,
      created_at: doc.created_at,
      doc_uuid: doc.doc_uuid,
      doc_url: doc.doc_url,
      created_by: doc.created_by ? doc.created_by.name : '',
      watermarked_doc_url: doc.watermarked_doc_url ?? doc.doc_url,
    };
  });
});

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

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
  modelType: 'Health',
  parentType: 'Health',
  quoteType: 3,
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

// additional contact
const additionalContactTable = [
  { text: 'Type', value: 'key' },
  { text: 'Value', value: 'value' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Action', value: 'action' },
];

const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: page.props.quote.id,
  customer_id: page.props.quote.customer_id,
  quote_type: 'health',
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
      onError: errors => {
        notification.error({
          title: errors.error || 'Data not saved',
          position: 'top',
        });
      },
      onSuccess: () => {
        additionalContact.reset();
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
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

const additionalContactPrimaryConfirmed = () => {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: page.props.quote.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'health',
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

const policyDetails = useForm({
  premium: page.props.quote.premium,
  policy_number: page.props.quote.policy_number || '',
  policy_start_date: dateToYMD(page.props.quote.policy_start_date),
  policy_expiry_date: dateToYMD(page.props.quote.policy_expiry_date) || '',
  policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
  quote_status_id: page.props.quote.quote_status_id,
  canEdit:
    page.props.quote.quote_status_id ==
      page.props.quoteStatusEnum.TransactionApproved &&
    page.props.notProductionApproval,
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
      notification.error({
        title: 'Error!',
        position: 'top',
      });
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
      notification.error({
        title: 'Error linking entity',
        position: 'top',
      });
    });
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  onLoadAvailablePlansData();
  const isHealthAdvisor = page.props.advisors.find(
    a => a.id == page.props.quote.advisor_id,
  ) || { id: null };
  if (isHealthAdvisor) assignLead.value = isHealthAdvisor.id;
  isMounted.value = true;

  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const prefillPlanId = ref(page.props.quote.prefill_plan_id);

const handleChildUpdate = planId => {
  prefillPlanId.value = planId;
};

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);
const getDetailPageRoute = (uuid, quote_type_id) =>
  useGetShowPageRoute(uuid, quote_type_id, null);

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
watch(
  () => page.props.ecomDetails,
  value => {
    selectedProviderPlan.value.premium = value.priceWithVAT;
  },
  { deep: true },
);

watch(
  () => page.props.quote.quote_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      leadStatusForm.leadStatus = newValue;
    }
  },
);
const memberCategorySalaryMapping = {
  'Investor or Partner': 2,
  'Golden visa': 2,
  'Self-employed or Freelancer': 2,
  'Domestic worker': 1,
  'Dependent spouse': 2,
  'Dependent child': 2,
  'Dependent parent': 2,
  'Dependent sibling or Other relatives': 2,
  'Employee with salary AED 4000 and below': 1,
  'Employee with salary above AED 4000': 2,
};

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();
const [AddPlanButtonTemplate, AddPlanButtonReuseTemplate] =
  createReusableTemplate();
const [AddMemberButtonTemplate, AddMemButtonReuseTemplate] =
  createReusableTemplate();
const [EditMemberButtonTemplate, EditMemberButtonReuseTemplate] =
  createReusableTemplate();
const [DeleteMemberButtonTemplate, DeleteMemberButtonReuseTemplate] =
  createReusableTemplate();
const [StatusUpdateButtonTemplate, StatusUpdateButtonReuseTemplate] =
  createReusableTemplate();

const salaryBrandMapping = {
  1: 'AED 4000 and below',
};
const validateEmailSending = () => {
  if (selectedPlans.value.length === 0) {
    modals.sendConfirm = true;
    return;
  }
  const hiddenPlans = selectedPlans.value.filter(plan => plan.isHidden);
  if (hiddenPlans.length > 0) {
    notification.error({
      title: 'You cannot select a hidden plan',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }
  if (selectedPlans.value.length < 6) {
    notification.error({
      title: 'Minimum 6 plans should be selected',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }
  if (selectedPlans.value.length > 6) {
    notification.error({
      title: 'Maximum 6 plans can be selected',
      position: 'top',
    });
    modals.sendConfirm = false;
    return;
  }
  modals.sendConfirm = true;
};
const loader = ref({
  link: false,
});

const isOcaButtonDisabled = ref(false);
const confirmSendEmail = () => {
  loader.value.link = true;
  const first_name = page.props.quote.first_name || '';
  const last_name = page.props.quote.last_name || '';
  axios
    .post(
      `/quotes/health/${page.props.quote.uuid}/send-ocb`,
      {
        quote_type_id: page.props.quoteTypeId,
        quote_id: page.props.quote.id,
        quote_uuid: page.props.quote.uuid,
        quote_cdb_id: page.props.quote.code,
        quote_previous_expiry_date:
          page.props.quote.previous_policy_expiry_date,
        quote_previous_policy_number:
          page.props.quote.previous_quote_policy_number,
        customer_name: `${first_name} ${last_name}`,
        customer_email: page.props.quote.email,
        customer_id: page.props.quote.customer_id,
        advisor_name: page.props.quote.advisor_id_text
          ? page.props.quote.advisor_id_text
          : null,
        advisor_email: page.props.quote.advisor_email
          ? page.props.quote.advisor_email
          : null,
        advisor_mobile_no: page.props.quote.advisor_mobile_no
          ? page.props.quote.advisor_mobile_no
          : null,
        advisor_landline_no: page.props.quote.advisor_landline_no
          ? page.props.quote.advisor_landline_no
          : null,
        selected_plans: selectedPlans.value,
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
      isOcaButtonDisabled.value = true;
      router.reload({
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .catch(error => {
      notification.error({
        title: 'OCB email sending failed, please try again.',
        position: 'top',
      });
    })
    .finally(() => {
      modals.sendConfirm = false;
      loader.value.link = false;
    });
};

watch(
  () => memberForm.member_category_id,
  (newValue, oldValue) => {
    if (newValue) {
      if (
        (!memberActionEdit.value && modals.member) || // Add case
        (memberActionEdit.value &&
          (newValue !== initialEditCategoryId.value ||
            (newValue === initialEditCategoryId.value &&
              newValue !== previouslySelectedCategoryId.value)))
      ) {
        //fetch category text
        const selectedCategory = memberCategoriesOptions.value.find(
          option => option.value === newValue,
        );

        // fetch salary band id based on category text
        const salaryBandId =
          memberCategorySalaryMapping[selectedCategory.label];

        // if quote status is Transaction Approved do not auto-popualte salary band automatically
        if (page.props.quote.quote_status_id != 15) {
          memberForm.salary_band_id = salaryBandId;
        }
        previouslySelectedCategoryId.value = newValue;
      }
    }
  },
  { immediate: true },
);

const doesEmailStatusExist = computed(() => props.emailStatuses.length > 0);

const onAddUpdate = () => {
  selectedProviderPlan.value.id = null;
  selectedProviderPlan.value.planName = '';
  selectedProviderPlan.value.providerName = '';
  selectedProviderPlan.value.premium = '';
};
</script>

<template>
  <div>
    <Head title="Health Detail" />

    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Health Detail</h2>
        <p
          class="bg-red-600 px-2 py-1 rounded text-sm text-white"
          v-if="countDays !== false"
        >
          Stale for {{ countDays }}
        </p>
      </template>

      <template #default v-if="readOnlyMode.isDisable === true">
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
        <LeadNotes
          :documentType="noteDocumentType"
          :notes="quoteNotes"
          :modelType="modelType"
          :quote="quote"
          :cdn="cdnPath"
        />
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>
        <Link :href="route('health.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Health List </x-button>
        </Link>

        <LeadEditBtnTemplate v-slot="{ isDisabled }">
          <Link v-if="!isDisabled" :href="route('health.edit', quote.uuid)">
            <x-button size="sm" tag="div">Edit</x-button>
          </Link>
          <x-button v-else :disabled="isDisabled" size="sm" tag="div">
            Edit
          </x-button>
        </LeadEditBtnTemplate>

        <x-tooltip
          v-if="lockLeadSectionsDetails.lead_details"
          position="bottom"
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
      size="md"
      title="Duplicate Lead"
      show-close
      backdrop
      is-form
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

    <div
      v-if="
        (!$page.props.can.isAdvisor ||
          hasRole(rolesEnum.SuperManagerLeadAllocation)) &&
        !can(permissionsEnum.VIEW_ALL_LEADS)
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
                class="w-auto flex-1"
                hide-footer
                filterable
              />
              <div>
                <x-button
                  color="orange"
                  @click.prevent="onTeamAssign"
                  :loading="isDisabled"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Assign Team
                </x-button>
              </div>
            </div>
            <div class="w-full md:w-1/2 flex gap-2 items-end">
              <x-select
                v-model="assignLead"
                label="Assign Lead"
                :options="advisorOptions"
                placeholder="Select Lead"
                class="w-auto flex-1 mt-1"
                filterable
                hide-footer
              />
              <div>
                <x-button
                  color="orange"
                  :loading="isDisabled"
                  @click.prevent="onAssignLead"
                  v-if="readOnlyMode.isDisable === true"
                >
                  Assign
                </x-button>
              </div>
            </div>
          </div>
        </template>
      </Collapsible>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center flex-wrap gap-2">
            <h3 class="text-xl font-semibold text-primary-800"></h3>
          </div>
        </template>

        <template #body>
          <x-divider class="my-4" />
          <div class="flex gap-2 mb-3 justify-end"></div>
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div
                v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
                class="grid sm:grid-cols-2"
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
                <dd>{{ quote.customer_type }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ quote.created_at }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUBTEAM</dt>
                <dd>{{ quote.health_team_type }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADVISOR</dt>
                <dd>{{ quote.advisor_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ quote.source }}</dd>
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
                <dt class="font-medium">IS ECOMMERCE</dt>
                <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">IS EBP RENEWAL</dt>
                <dd>{{ quote.is_ebp_renewal ? 'Yes' : 'No' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LOST REASON</dt>
                <dd>{{ quote.lost_reason }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DEVICE</dt>
                <dd>{{ quote.device }}</dd>
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
                <dd>{{ quote.cover_for_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                <dd>{{ quote.currently_insured_with_id_text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TYPE OF PLAN</dt>
                <dd>{{ checkPlanType(quoteRequest.health_plan_type_id) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                <dd>{{ dateFormat(quote.next_followup_date) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">DETAILS</dt>
                <dd>{{ quote.details }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDITIONAL NOTES</dt>
                <dd>{{ quote.additional_notes }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ENQUIRY COUNT</dt>
                <dd>{{ quote.enquiry_count }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TRANSACTION APPROVED AT</dt>
                <dd>{{ quote.transaction_approved_at }}</dd>
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
                  ? 'Customer'
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
                v-if="
                  quote.customer_type === page.props.customerTypeEnum.Entity //Here we go
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
      <div class="flex justify-end">
        <x-button
          class="mt-4"
          color="primary"
          size="sm"
          :loading="customerProfileForm.processing"
          @click.prevent="searchByTradeLicense('SubEntity')"
          v-if="readOnlyMode.isDisable === true"
        >
          Search
        </x-button>
      </div>
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
      </template>
    </x-modal>

    <x-accordion
      v-if="quote.customer_type == page.props.customerTypeEnum.Individual"
      show-icon
    >
      <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
        <h3 class="font-semibold text-primary-800 text-lg">
          Member Details
          <x-tag size="sm">{{ membersDetail.length || 0 }}</x-tag>
        </h3>
        <template #content>
          <x-divider class="mb-4 mt-1" />
          <AddMemberButtonTemplate v-slot="{ isDisabled }">
            <x-button
              @click.prevent="onAddMemberModal"
              size="sm"
              color="orange"
              :disabled="isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Add Member
            </x-button>
          </AddMemberButtonTemplate>
          <div class="flex mb-3 justify-end">
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
              outlined
              @click.prevent="onEditMember(item)"
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
              outlined
              @click.prevent="memberDelete(item.id)"
              :disabled="isDisabled"
              v-if="readOnlyMode.isDisable === true"
            >
              Delete
            </x-button>
          </DeleteMemberButtonTemplate>
          <DataTable
            table-class-name="tablefixed overflow-auto"
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
                <x-tooltip
                  v-if="lockLeadSectionsDetails.member_details"
                  position="left"
                  align="center"
                  class="yoyo-tip"
                >
                  <EditMemberButtonReuseTemplate
                    :isDisabled="true"
                    :item="item"
                  />
                  <template #tooltip>
                    <div class="whitespace-normal text-xs">
                      This lead is now locked as the policy has been booked. If
                      changes are needed such midterm deletion of member or
                      marital status change, go to 'Send Update', select 'Add
                      Update', and choose 'Endorsement Financial'
                    </div>
                  </template>
                </x-tooltip>
                <EditMemberButtonReuseTemplate v-else :item="item" />
                <x-tooltip
                  v-if="page.props.lockLeadSectionsDetails.member_details"
                  position="left"
                  align="center"
                  class="yoyo-tip"
                >
                  <DeleteMemberButtonReuseTemplate
                    :isDisabled="true"
                    :item="item"
                  />
                  <template #tooltip>
                    <div class="whitespace-normal text-xs">
                      This lead is now locked as the policy has been booked. If
                      changes are needed such midterm deletion of member or
                      marital status change, go to 'Send Update', select 'Add
                      Update', and choose 'Endorsement Financial'
                    </div>
                  </template>
                </x-tooltip>
                <DeleteMemberButtonReuseTemplate v-else :item="item" />
              </div>
            </template>
          </DataTable>

          <x-modal
            v-model="modals.member"
            size="lg"
            :title="`${memberActionEdit ? 'Edit' : 'Add'} Member`"
            show-close
            backdrop
            is-form
            @submit="onMemberSubmit"
          >
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
                v-model="memberForm.member_category_id"
                label="Member Category*"
                :options="memberCategoriesOptions"
                :rules="[isRequired]"
                placeholder="Select Member Category"
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

            <template #secondary-action>
              <x-button
                size="sm"
                ghost
                tabindex="-1"
                @click="modals.member = false"
              >
                Cancel
              </x-button>
            </template>
            <template #primary-action>
              <x-button
                size="sm"
                color="emerald"
                :loading="memberForm.processing"
                type="submit"
              >
                {{ memberActionEdit ? 'Update' : 'Save' }}
              </x-button>
            </template>
          </x-modal>

          <x-modal
            v-model="modals.memberConfirm"
            title="Delete Member Detail"
            show-close
            backdrop
          >
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
                    Please revist all manual plan(s) and update the per member
                    price
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
          </x-modal>
        </template>
      </x-accordion-item>
    </x-accordion>
    <UBODetails
      v-if="quote.customer_type == page.props.customerTypeEnum.Entity"
      :quote="quote"
      :UBOsDetails="UBOsDetails"
      :nationalities="nationalities"
      :UBORelations="UBORelations"
      :quote_type="page.props.modelType"
      :expanded="sectionExpanded"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex flex-wrap gap-3 justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Customer Additional Contacts
              <x-tag size="sm"
                >{{ customerAdditionalContacts.length || 0 }}
              </x-tag>
            </h3>
          </div>
        </template>

        <template #body>
          <x-divider class="my-4" />
          <div class="flex mb-3 justify-end">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="
                additionalContact.reset();
                modals.addContact = true;
              "
              v-if="readOnlyMode.isDisable === true"
            >
              Add Additional Contacts
            </x-button>
          </div>

          <DataTable
            table-class-name="compact"
            :headers="additionalContactTable"
            :items="customerAdditionalContacts || []"
            border-cell
            hide-rows-per-page
            hide-footer
          >
            <template #item-key="{ key }">
              <span v-if="key === 'email'"> Email Address </span>
              <span v-else> Mobile Number </span>
            </template>

            <template #item-action="item">
              <x-button
                size="xs"
                color="emerald"
                outlined
                @click.prevent="additionalContactPrimary(item)"
                v-if="readOnlyMode.isDisable === true"
              >
                Make Primary
              </x-button>
            </template>
          </DataTable>
        </template>
      </Collapsible>

      <x-modal
        v-model="modals.addContact"
        size="md"
        title="Add Additional Contacts"
        show-close
        backdrop
        is-form
        @submit="onAdditionalContactSubmit"
      >
        <div class="grid gap-4">
          <x-select
            v-model="additionalContact.additional_contact_type"
            label="Type"
            :options="[
              { value: 'email', label: 'Email' },
              { value: 'mobile_no', label: 'Mobile Number' },
            ]"
            :rules="[isRequired]"
            placeholder="Select Type"
            class="w-full"
          />

          <x-input
            v-model="additionalContact.additional_contact_val"
            label="Value"
            :rules="[
              isRequired,
              additionalContact.additional_contact_type === 'email'
                ? isEmail
                : isNumber,
            ]"
            class="w-full"
          />
        </div>

        <template #secondary-action>
          <x-button ghost tabindex="-1" @click="modals.addContact = false">
            Cancel
          </x-button>
        </template>
        <template #primary-action>
          <x-button
            color="emerald"
            type="submit"
            :loading="additionalContact.processing"
          >
            Save
          </x-button>
        </template>
      </x-modal>

      <x-modal
        v-model="modals.contactPrimaryConfirm"
        title="Primary Additional Contact"
        show-close
        backdrop
      >
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
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Health"
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
            <div class="w-full md:w-50">
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
                  :disabled="lockLeadSectionsDetails.lead_status"
                />
                <x-field class="" label="Transaction Type">
                  <x-input
                    type="text"
                    v-model="quote.transaction_type_text"
                    class="w-full"
                    :disabled="true"
                  />
                </x-field>

                <div class="flex flex-col gap-4"></div>
              </div>
            </div>
          </div>
          <x-divider class="mb-1 mt-10" />
          <StatusUpdateButtonTemplate v-slot="{ isDisabled }">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
              :disabled="isDisabled"
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
                <dt class="font-medium">PLAN NAME</dt>
                <dd>{{ selectedProviderPlan.planName ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PROVIDER NAME</dt>
                <dd>{{ selectedProviderPlan.providerName ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAYMENT STATUS</dt>
                <dd>{{ quote.payment_status_text ?? 'N/A' }}</dd>
              </div>
              <div
                class="grid sm:grid-cols-2"
                v-if="
                  page.props.quote.payment_status_id ==
                  paymentStatusEnum.DECLINED
                "
              >
                <dt class="font-medium">REASON</dt>
                <dd>{{ mainPayment?.payment_status_message }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">AUTHORISED AT</dt>
                <dd>{{ quote.paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PAID AT</dt>
                <dd>{{ quote.payment_paid_at ?? 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NETWORK</dt>
                <dd>
                  {{ ecomDetails.network != '' ? ecomDetails.network : 'N/A' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TOTAL PRICE (with VAT)</dt>
                <dd>
                  {{
                    selectedProviderPlan.premium
                      ? fixedValue(selectedProviderPlan.premium)
                      : 'N/A'
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CO-PAY / CO-INSURANCE</dt>
                <dd>{{ coPayment ? coPayment.text : 'N/A' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">PLAN TYPE</dt>
                <dd>{{ selectedProviderPlan.planType ?? 'N/A' }}</dd>
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
              <x-tag size="sm">{{ listQuotePlansFiltered.length || 0 }}</x-tag>
            </h3>
          </div>
        </template>

        <template #body>
          <x-divider class="my-4" />
          <div class="flex flex-wrap gap-3 justify-end mb-3">
            <x-button-group v-if="selectedPlans.length > 0" size="sm">
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
            <x-button
              @click.prevent="validateEmailSending"
              size="sm"
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
                onCopyText(ecomHealthInsuranceQuoteUrl + quote.uuid)
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

            <AddPlanButtonTemplate v-slot="{ isDisabled }">
              <x-button
                v-if="can(permissionsEnum.ADD_MANUAL_HEALTH_PLAN)"
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
              <template #item-copayName="item">
                <p class="copay-max">
                  {{ item.copayName }}
                </p>
              </template>

              <template #item-planTypeId="item">
                <span class="copay-max">{{ item.plan_type }}</span>
              </template>

              <template
                #item-providerName="{ providerName, isManualPlan, isHidden }"
              >
                <p>
                  {{ providerName }}
                </p>
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
                      (item.isManualPlan || membersDetailsUpdated) &&
                      item.needPriceUpdate
                    "
                  >
                    <!-- always false temporarily -->
                    <x-tooltip placement="top">
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
                        <template #content>! </template>
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
          </div>
        </template>
      </Collapsible>
    </div>

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

    <x-modal
      v-model="modals.planFilters"
      size="lg"
      title="Filters"
      show-close
      backdrop
    >
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
          <x-tooltip placement="right">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600 mb-0.5"
            >
              Manual Plan
            </label>
            <template #tooltip> Manually Added Plans </template>
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
          <x-tooltip placement="right">
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

      <template #actions>
        <div class="flex justify-end gap-3">
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
      </template>
    </x-modal>

    <MigratePayment
      v-if="!isNewPaymentStructure"
      :quoteId="quote.id"
      :paymentCode="quote.code"
      quoteType="Health"
      :payments="payments"
    />

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

    <PaymentTable
      v-else
      :payments="payments"
      :can="can"
      :isBetaUser="isBetaUser"
      :quoteRequest="quoteRequest"
      :paymentMethods="paymentMethods"
      :insuranceProviders="insuranceProviders"
      :quote="quote"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="modelType"
      :paymentLink="paymentLink"
      :expanded="sectionExpanded"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
      </div>
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
    </div>
    <x-divider class="my-4" />

    <PolicyDetail
      v-if="permissions.isQuoteDocumentEnabled"
      :quote="quote"
      modelType="health"
      :expanded="sectionExpanded"
      :payments="payments"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="page.props.quoteDocuments || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :expanded="sectionExpanded"
      :docUploadURL="docUploadURL"
      quoteType="Health"
      :sendPolicy="sendPolicy"
      @sendPolicyToClient="sendPolicyToClient"
      :bookPolicyDetails="bookPolicyDetails"
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
      quoteType="health"
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
          <div class="mb-3 flex justify-end">
            <x-button
              size="sm"
              color="orange"
              @click.prevent="addActivity"
              v-if="readOnlyMode.isDisable === true"
            >
              Add Activity
            </x-button>
          </div>
          <DataTable
            table-class-name="compact"
            :headers="activityTable"
            :items="activities"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="activities.length < 15"
          >
            <template #item-due_date="{ due_date }">
              <template v-if="compareDueDate(due_date)">
                <x-tooltip placement="top">
                  <p
                    :class="
                      compareDueDate(due_date) ? 'bg-error-300 rounded p-1' : ''
                    "
                  >
                    {{ due_date }}
                  </p>
                  <template #tooltip>
                    <span
                      >Pending overdue Task, please complete immediately</span
                    >
                  </template>
                </x-tooltip>
              </template>
              <span v-else>{{ due_date }}</span>
            </template>

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
    </div>
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

      <template #secondary-action>
        <x-button
          size="sm"
          ghost
          tabindex="-1"
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

    <ClientInquiryLogs
      v-if="clientInquiryLogs?.length > 0"
      :logs="clientInquiryLogs"
    />

    <CustomerChatLogs
      :customerName="quote?.first_name + ' ' + quote?.last_name"
      :quoteId="quote.uuid"
      :quoteType="'HEALTH'"
      :expanded="sectionExpanded"
    />

    <AuditLogs
      :quoteType="$page.props.modelType"
      :type="modelClass"
      :id="$page.props.quote.id"
      :quoteCode="$page.props.quote.code"
    />

    <ClientInquiryLogs
      v-if="clientInquiryLogs?.length > 0"
      :logs="clientInquiryLogs"
    />

    <lead-raw-data
      :modelType="'Health'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>
