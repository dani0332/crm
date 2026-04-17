<script setup>
import { calculateAge } from '../Composables/utilities';
import { useHealthQuoteFlags } from '../Composables/useHealthQuoteFlags';

const props = defineProps({
  membersDetail: {
    type: Array,
    required: true,
  },
  quote: {
    type: Object,
    required: true,
  },
  isManualPlansCount: {
    type: Number,
    default: 0,
  },
  isLocked: {
    type: Boolean,
    default: false,
  },
  readOnlyMode: {
    type: Object,
    default: () => ({ isDisable: true }),
  },
  lockLeadSectionsDetails: {
    type: Object,
    default: () => ({}),
  },
  nationalities: {
    type: Array,
    default: () => ([]),
  },
  memberCategories: {
    type: Array,
    default: () => ([]),
  },
  memberRelations: {
    type: Array,
    default: () => ([]),
  },
  emirates: {
    type: Array,
    default: () => ([]),
  },
  salaryBands: {
    type: Array,
    default: () => ([]),
  },
  genderOptions: {
    type: Array,
    default: () => ([]),
  },
  maritalStatusOptions: {
    type: Array,
    default: () => ([]),
  },
  visaCategoryOptions: {
    type: Array,
    default: () => ([]),
  },
  includePolicyHolder: {
    type: Boolean,
    default: false,
  },
  healthInsureCode: {
    type: String,
    default: '',
  },
  policyHolderCode: {
    type: String,
    default: '',
  },
  quoteForm: {
    type: Object,
    default: () => ({}),
  },
  coverForId: {
    type: Number,
    default: 0,
  },
});

const HEALTH_PEC_YES = 1;
const HEALTH_PEC_NO = 2;
const MAX_ALLOWED_INSURED_MEMBERS = 8;
const MIN_AGE_YEARS_FOR_PRINCIPAL_ACTION = 18;
const MAX_INSURED_FOR_SELF_OTHER_OR_DOMESTIC_HELPER = 1;
const isCreate = computed(() => {
  return route().current().includes('create');
});

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const isView = computed(() => {
  return !isCreate.value && !isEdit.value;
});

const emit = defineEmits(['memberUpdated', 'loadAvailablePlans']);

const page = usePage();
const notification = useToast();

const {
  isDomesticHelper,
  isSelf_Me,
  isSelf_Other,
  isFamily_Me,
  isFamily_Other,
  isSelfAndFamily_Me,
  isSelfAndFamily_Other,
} = useHealthQuoteFlags({
  getCoverForId: () => props.coverForId,
  getInsureCode: () => props.healthInsureCode,
  getPolicyHolderCode: () => props.policyHolderCode,
});

const modals = reactive({
  member: false,
  memberConfirm: false,
  memberPrincipal: false,
});

const confirmDeleteData = reactive({
  member: null,
});

const confirmPrincipalData = reactive({
  member: null,
});

const memberActionEdit = ref(false);
const memberPecValidationError = ref('');

/** In-memory members when on create page (no API calls). */
const localMembers = ref([]);

const loadLocalMembers = () => {
  localMembers.value = props.membersDetail.map(m => ({
    ...m,
    pec: m.is_pec_marked == 1 ? HEALTH_PEC_YES : HEALTH_PEC_NO,
  }));
};

onMounted(() => {
  if(!isCreate.value) {
    loadLocalMembers();
  }
});

// Watch for changes in membersDetail prop and update localMembers
watch(
  () => props.membersDetail,
  (newMembers) => {
    if (isView.value) {
      loadLocalMembers();
    }
  },
  { deep: true }
);

const genderText = gender =>
  computed(() => {
    return props.genderOptions.find(option => option.value === gender)?.label;
  });

const relationText = relationCode =>
  computed(() => {
    return props.memberRelations.find(relation => relation.value === relationCode)?.label;
  });

const nationalityText = nationalityId =>
  computed(() => {
    return props.nationalities.find(nationality => nationality.value === nationalityId)?.label;
  });
  
const emirateText = emirateId =>
  computed(() => {
    return props.emirates.find(emirate => emirate.value === emirateId)?.label;
  });

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return props.memberCategories.find(category => category.value === memberCategoryId)?.label;
  });

const visaCategoryText = visaCategoryId =>
  computed(() => {
    return props.visaCategoryOptions.find(option => option.value === visaCategoryId)?.label;
  });

const maritalStatusText = maritalStatusId =>
  computed(() => {
    return props.maritalStatusOptions.find(option => option.value === maritalStatusId)?.label;
  });

const salaryBandText = salaryBandId =>
  computed(() => {
    return props.salaryBands.find(option => option.value === salaryBandId)?.label;
  });

const dateFormat = date => {
  if (!date) return '-';
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}-${month}-${year}`;
};

// Form validation rules
const isRequired = value => {
  if (!value) return 'This field is required';
  return true;
};

// Reusable templates
const [AddMemberButtonTemplate, AddMemButtonReuseTemplate] =
  createReusableTemplate();
const [EditMemberButtonTemplate, EditMemberButtonReuseTemplate] =
  createReusableTemplate();
const [DeleteMemberButtonTemplate, DeleteMemberButtonReuseTemplate] =
  createReusableTemplate();
const [PrincipalMemberButtonTemplate, PrincipalMemberButtonReuseTemplate] =
  createReusableTemplate();

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Member Name',
      value: 'first_name',
    },
    {
      text: 'Policy PEC Flag',
      value: 'is_pec_marked',
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
      text: 'Visa Category',
      value: 'visa_category_id',
    },
    {
      text: 'Marital Status',
      value: 'marital_status_id',
    },
    {
      text: 'Salary',
      value: 'salary_band_id',
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
  nationality_id: props.membersDetail.length
    ? null
    : props.quote?.nationality_id,
  salary_band_id: null,
  emirate_of_your_visa_id: props.membersDetail.length
    ? null
    : props.quote?.emirate_of_your_visa_id,
  member_category_id: null,
  quote_request_id: props.quote?.id,
  update_lead_against_member: null,
  first_name: null,
  last_name: null,
  relation_code: null,
  pec: null,
  customer_member_id: null,
  marital_status_id: null,
  visa_category_id: null,
  is_principal: null,
  is_policy_holder: null,
  is_insured: 0,
  customer_id: props.quote?.customer_id,
  quoteId: props.quote?.uuid,
});

const memberHealthRegulationAuthority = computed(() => {
  return memberForm.emirate_of_your_visa_id === page.props.emirateEnum.ABU_DHABI
    ? 'DoH'
    : 'DHA';
});

const memberPecErrorMessage = computed(() => {
  return `Please confirm the member's health declaration to proceed, as required under ${memberHealthRegulationAuthority.value} regulations.`;
});

const refreshPlansForm = useForm({
  quoteId: props.quote?.uuid,
});

const submitForm = (form, method, url, { successTitle, onSuccess, onFinish } = {}) => {
  form[method](url, {
    preserveScroll: true,
    onSuccess: response => {
      const hasError = response?.props?.flash?.error;
      if (!hasError) {
        if (successTitle) {
          notification.success({ title: successTitle, position: 'top' });
        }
        onSuccess?.();
      }
    },
    onError: errors => {
      notification.error({
        title: errors.error || 'Data not updated',
        position: 'top',
      });
    },
    onFinish,
  });
};

const onRefreshPlans = () => {
  refreshPlansForm.post(route('health.refresh-plans'), {
    preserveScroll: true,
    onSuccess: () => emit('loadAvailablePlans'),
    onError: () => {
      notification.error({ title: 'Failed to refresh plans', position: 'top' });
    },
  });
};

const getPrincipalEmirateOfYourVisaId = () => {
  return localMembers.value.find(m => m.is_principal === 1)?.emirate_of_your_visa_id;
};

function updateMemberForm(data) {

  memberForm.id = data.id;
  memberForm.gender = data.gender;
  memberForm.dob = data.dob;
  memberForm.nationality_id = data.nationality_id;
  memberForm.salary_band_id = data.salary_band_id;
  memberForm.emirate_of_your_visa_id = getPrincipalEmirateOfYourVisaId();
  memberForm.member_category_id = data.member_category_id;
  memberForm.first_name = data.first_name;
  memberForm.last_name = data.last_name;
  memberForm.relation_code = data.relation_code;
  memberForm.pec = data.is_pec_marked == 1 ? HEALTH_PEC_YES : HEALTH_PEC_NO;
  memberForm.is_principal = data.is_principal;
  memberForm.is_policy_holder = data.is_policy_holder;
  memberForm.is_insured = data.is_insured;
  memberForm.marital_status_id = data.marital_status_id;
  memberForm.visa_category_id = data.visa_category_id;
}

function buildMemberFromForm() {
  return {
    ...memberForm,
    id: memberForm.id ?? `temp-${Date.now()}`,
    is_pec_marked: memberForm.pec === HEALTH_PEC_YES,
  };
}

const onAddMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
  memberForm.nationality_id = null;
  memberForm.is_principal = localMembers.value.length === 0 ? 1 : 0;
  memberForm.is_policy_holder = 0;
  memberForm.is_insured = 1;
  memberForm.emirate_of_your_visa_id = getPrincipalEmirateOfYourVisaId();
};

function onEditMember(data) {
  memberActionEdit.value = true;
  modals.member = true;
  updateMemberForm(data);
}

function syncPrincipalToQuoteForm(member) {
  if (member.is_principal === 1 && (isSelf_Other.value || isFamily_Me.value ||
    (props.includePolicyHolder == 0 && (isFamily_Other.value || isSelfAndFamily_Other.value)))) {
    props.quoteForm.dob = member.dob;
    props.quoteForm.gender = member.gender;
    props.quoteForm.marital_status_id = member.marital_status_id;
    props.quoteForm.nationality_id = member.nationality_id;
    props.quoteForm.emirate_of_your_visa_id = member.emirate_of_your_visa_id;
    props.quoteForm.pec = member.pec;
  }
}

function syncPolicyHolderToQuoteForm(member) {
  if (member.is_policy_holder === 1) {
    props.quoteForm.first_name = member.first_name;
    props.quoteForm.last_name = member.last_name;
    props.quoteForm.gender = member.gender;
    props.quoteForm.dob = member.dob;
    props.quoteForm.nationality_id = member.nationality_id;
    props.quoteForm.salary_band_id = member.salary_band_id;
    props.quoteForm.emirate_of_your_visa_id = member.emirate_of_your_visa_id;
    props.quoteForm.member_category_id = member.member_category_id;
    props.quoteForm.marital_status_id = member.marital_status_id;
    props.quoteForm.visa_category_id = member.visa_category_id;
    props.quoteForm.pec = member.pec;
  }
}

const onMemberSubmit = isValid => {
  memberPecValidationError.value = '';

  if (memberForm.pec == null || memberForm.pec == '') {
    memberPecValidationError.value = memberPecErrorMessage.value;
  }

  if (
    memberPecValidationError.value
  ) {
    notification.error({
      title: 'Please fill all required fields',
      position: 'top',
    });
    return;
  }

  if (!isValid) return;

  if (isCreate.value || isEdit.value) {
    const member = buildMemberFromForm();
    if (memberActionEdit.value) {
      const index = localMembers.value.findIndex(m => m.id === memberForm.id);
      if (index !== -1) {
        localMembers.value[index] = member;
        
        syncPolicyHolderToQuoteForm(member);
        syncPrincipalToQuoteForm(member);
        syncEmiratesToMembers();
      }
      notification.success({
        title: 'Member Updated',
        position: 'top',
      });
    } else {
      localMembers.value.push(member);
      
      syncPrincipalToQuoteForm(member);
      
      notification.success({
        title: 'Member Added',
        position: 'top',
      });
    }
    memberForm.reset();
    modals.member = false;
    return;
  }

  const [method, url, successTitle] = memberActionEdit.value
    ? ['put', '/health-quote-update-member', 'Member Updated']
    : ['post', '/health-quote-add-member', 'Member Added'];

  submitForm(memberForm, method, url, {
    successTitle,
    onSuccess: () => {
      memberForm.reset();
      emit('memberUpdated');
    },
    onFinish: () => {
      modals.member = false;
    },
  });
};

const memberDelete = id => {
  modals.memberConfirm = true;
  confirmDeleteData.member = id;
  memberForm.id = id;
};

const memberDeleteConfirmed = () => {
  if (isCreate.value || isEdit.value) {
    localMembers.value = localMembers.value.filter(
      m => m.id !== confirmDeleteData.member,
    );
    notification.success({
      title: 'Member Deleted',
      position: 'top',
    });
    modals.memberConfirm = false;
    emit('memberUpdated');
    return;
  }

  submitForm(memberForm, 'post', '/health-quote-delete-member', {
    successTitle: 'Member Deleted',
    onSuccess: () => emit('memberUpdated'),
    onFinish: () => {
      modals.memberConfirm = false;
    },
  });
};

const memberPrincipal = data => {
  updateMemberForm(data);
  memberForm.is_principal = 1;
  if(makeActionName.value === 'policyholder') {
    memberForm.is_policy_holder = 1;
    memberForm.relation_code = null;
  } else {
    memberForm.is_policy_holder = 0;
  }

  modals.memberPrincipal = true;
  confirmPrincipalData.member = data.id;
};

const syncEmiratesToMembers = () => {
  const principalMember = localMembers.value.find(m => m.is_principal === 1);
  if (principalMember) {
    localMembers.value = localMembers.value.map(m => {
      if (m.is_insured === 1) {
        return { ...m, emirate_of_your_visa_id: principalMember.emirate_of_your_visa_id };
      }
      return m;
    });
  }
};

const memberPrincipalConfirmed = () => {
  if (isCreate.value || isEdit.value) {

    const targetId = confirmPrincipalData.member;
    localMembers.value = localMembers.value.map(m => {
      const principalWhenInsured = m.id === targetId ? 1 : 0;
      const isPrincipal = m.is_insured == 1 ? principalWhenInsured : m.is_principal;

      const policyHolderWhenInsured = m.id === targetId && makeActionName.value === 'policyholder' ? 1 : 0;
      const isPolicyHolder = m.is_insured == 1 ? policyHolderWhenInsured : m.is_policy_holder;

      return { ...m, is_principal: isPrincipal, is_policy_holder: isPolicyHolder };
    });
    
    const newPrincipalMember = localMembers.value.find(m => m.id === targetId);
    if (newPrincipalMember) {

      if (makeActionName.value === 'policyholder') {
        syncPolicyHolderToQuoteForm(newPrincipalMember);
        syncPrincipalToQuoteForm(newPrincipalMember);
      } else {
        syncPrincipalToQuoteForm(newPrincipalMember);
      }
    }
    
    notification.success({
      title: `${memberForm.first_name} ${memberForm.last_name} has been made ${makeActionName.value === 'policyholder' ? 'Policyholder' : 'Principal'}`,
      position: 'top',
    });
    modals.memberPrincipal = false;
    return;
  }

  memberForm.put(`/health-quote-update-member`, {
    preserveScroll: true,
    onSuccess: response => {
      const flash_messages = response.props.flash;
      if (!flash_messages.error) {
        notification.success({
          title: `${memberForm.first_name} ${memberForm.last_name} has been made ${makeActionName.value === 'policyholder' ? 'Policyholder' : 'Principal'}`,
          position: 'top',
        });
        emit('memberUpdated');
      }
    },
    onError: errors => {
      notification.error({
        title: errors.error || 'Some error occurred while processing request',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.memberPrincipal = false;
    },
  });
};

const modalTitle = (action) => {
  let title = `${action ? 'Edit' : 'Add'} Member`;

  if(localMembers.value.length === 0) {
    title += ' (Principal)';
  }

  return title;
};

const makeActionName = computed(() => {
  return isSelf_Me.value || isSelfAndFamily_Me.value ||
    (props.includePolicyHolder == 1 && (isFamily_Other.value || isSelfAndFamily_Other.value)) ? 'policyholder' : 'principal';
});

const localMembersFiltered = computed(() => {
  return localMembers.value.filter(m => !((m.is_policy_holder == 1 && m.is_insured == 0) || (m.is_third_party_payer == 1)));
});

const ADD_MEMBER_VIEW_MODE_TOOLTIP =
  'To add more members, go to Edit and update the details under \'Who would the customer like to insure?\' and \'Who will be the policyholder?\'';

/**
 * @param {boolean|undefined} isDisabledFromTemplate True when ReuseTemplate passes :isDisabled (e.g. locked member details).
 */
function isHealthAddMemberButtonDisabled(isDisabledFromTemplate) {
  const filteredCount = localMembersFiltered.value.length;

  return Boolean(
    isDisabledFromTemplate
    || props.isLocked
    || isSelf_Me.value
    || (isSelf_Other.value && filteredCount === MAX_INSURED_FOR_SELF_OTHER_OR_DOMESTIC_HELPER)
    || (isDomesticHelper.value && filteredCount === MAX_INSURED_FOR_SELF_OTHER_OR_DOMESTIC_HELPER)
    || filteredCount >= MAX_ALLOWED_INSURED_MEMBERS
    || (
      !isView.value
      && (!props.coverForId || !props.healthInsureCode || !props.policyHolderCode)
    )
  );
}

function shouldShowHealthAddMemberViewModeTooltip(isDisabledFromTemplate) {
  if (localMembersFiltered.value.length >= MAX_ALLOWED_INSURED_MEMBERS) {
    return false;
  }

  return isHealthAddMemberButtonDisabled(isDisabledFromTemplate);
}

function buildPrincipalMemberDataFromQuoteForm() {
  return {
    id: `temp-${Date.now()}`,
    first_name: props.quoteForm.first_name || null,
    last_name: props.quoteForm.last_name || null,
    gender: props.quoteForm.gender || null,
    dob: props.quoteForm.dob || null,
    nationality_id: props.quoteForm.nationality_id || null,
    salary_band_id: props.quoteForm.salary_band_id || null,
    emirate_of_your_visa_id: props.quoteForm.emirate_of_your_visa_id || null,
    member_category_id: props.quoteForm.member_category_id || null,
    marital_status_id: props.quoteForm.marital_status_id || null,
    visa_category_id: props.quoteForm.visa_category_id || null,
    is_pec_marked: props.quoteForm.pec === HEALTH_PEC_YES,
    pec: props.quoteForm.pec || null,
    is_principal: 1,
    is_policy_holder: 1,
    is_insured: 1,
    relation_code: null,
    quote_request_id: props.quote.id,
  };
}

function syncMembersWhenFamilyOtherWithInsuredPh(memberData) {
  localMembers.value = localMembers.value.filter(
    m => !(m.is_policy_holder == 1 && m.is_insured == 0),
  );

  const existingInsuredPh = localMembers.value.find(
    m => m.is_policy_holder == 1 && m.is_insured == 1,
  );

  if (existingInsuredPh) {
    return;
  }

  localMembers.value = localMembers.value.map(m => ({
    ...m,
    is_principal: 0,
    is_policy_holder: 0,
  }));

  localMembers.value.push(memberData);
}

function syncMembersWhenFamilyOtherWithoutInsuredPh() {
  localMembers.value = localMembers.value.filter(
    m => !(m.is_policy_holder == 1 && m.is_insured == 1),
  );

  let principalAssigned = false;
  localMembers.value = localMembers.value.map(m => {
    if (m.is_insured != 1) {
      return { ...m, is_principal: 0 };
    }
    if (!principalAssigned) {
      principalAssigned = true;
      return { ...m, is_principal: 1 };
    }
    return { ...m, is_principal: 0 };
  });

  const newPrincipal = localMembers.value.find(m => m.is_principal === 1);
  if (newPrincipal) {
    syncPrincipalToQuoteForm(newPrincipal);
  }
}

function syncMembersForFamilyOtherFlows(memberData) {
  if (props.includePolicyHolder == 1) {
    syncMembersWhenFamilyOtherWithInsuredPh(memberData);

    return;
  }

  syncMembersWhenFamilyOtherWithoutInsuredPh();
}

watch(
  () => [
    isSelf_Me.value,
    isSelf_Other.value,
    isFamily_Me.value,
    isFamily_Other.value,
    isSelfAndFamily_Me.value,
    isSelfAndFamily_Other.value,
    props.includePolicyHolder,
    props.coverForId,
  ],
  () => {
    if (!isCreate.value && !isEdit.value) {
      return;
    }

    const memberData = buildPrincipalMemberDataFromQuoteForm();

    if (isFamily_Other.value || isSelfAndFamily_Other.value) {
      syncMembersForFamilyOtherFlows(memberData);

      return;
    }

    if (isSelf_Me.value || isSelfAndFamily_Me.value) {
      localMembers.value = [memberData];

      return;
    }

    localMembers.value = [];
  },
  { immediate: true },
);

watch(
  () => [
    props.quoteForm.first_name,
    props.quoteForm.last_name,
    props.quoteForm.gender,
    props.quoteForm.dob,
    props.quoteForm.nationality_id,
    props.quoteForm.salary_band_id,
    props.quoteForm.emirate_of_your_visa_id,
    props.quoteForm.member_category_id,
    props.quoteForm.marital_status_id,
    props.quoteForm.visa_category_id,
    props.quoteForm.pec,
  ],
  () => {
    if ((isCreate.value || isEdit.value) && localMembers.value.length > 0
      && (isSelf_Me.value || isSelfAndFamily_Me.value ||
        (props.includePolicyHolder == 1 && (isFamily_Other.value || isSelfAndFamily_Other.value)))
    ) {
      const principalMember = localMembers.value.find(m => m.is_principal === 1);
      if (principalMember && principalMember.is_policy_holder === 1) {
        principalMember.first_name = props.quoteForm.first_name || null;
        principalMember.last_name = props.quoteForm.last_name || null;
        principalMember.gender = props.quoteForm.gender || null;
        principalMember.dob = props.quoteForm.dob || null;
        principalMember.nationality_id = props.quoteForm.nationality_id || null;
        principalMember.salary_band_id = props.quoteForm.salary_band_id || null;
        principalMember.emirate_of_your_visa_id = props.quoteForm.emirate_of_your_visa_id || null;
        principalMember.member_category_id = props.quoteForm.member_category_id || null;
        principalMember.marital_status_id = props.quoteForm.marital_status_id || null;
        principalMember.visa_category_id = props.quoteForm.visa_category_id || null;
        principalMember.is_pec_marked = props.quoteForm.pec === HEALTH_PEC_YES;
        principalMember.pec = props.quoteForm.pec || null;
      }

      syncEmiratesToMembers();
    }
  },
  { deep: true }
);

// Expose localMembers so parent component can access it
defineExpose({
  localMembers,
});
</script>

<template>
  <x-accordion show-icon>
    <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
      <h3 class="font-semibold text-primary-800 text-lg">
        Insured Member Details
        <x-tag size="sm">{{ localMembersFiltered.length || 0 }}</x-tag>
      </h3>
      <template #content>
        <x-divider class="mb-4 mt-1" />
        <AddMemberButtonTemplate v-slot="{ isDisabled }">
          <template v-if="readOnlyMode.isDisable === true">
            <x-tooltip
              v-if="shouldShowHealthAddMemberViewModeTooltip(isDisabled)"
              position="bottom"
            >
              <x-button
                @click.prevent="onAddMemberModal"
                size="sm"
                color="orange"
                :disabled="isHealthAddMemberButtonDisabled(isDisabled)"
              >
                Add Member
              </x-button>
              <template #tooltip>
                {{ ADD_MEMBER_VIEW_MODE_TOOLTIP }}
              </template>
            </x-tooltip>
            <x-button
              v-else
              @click.prevent="onAddMemberModal"
              size="sm"
              color="orange"
              :disabled="isHealthAddMemberButtonDisabled(isDisabled)"
            >
              Add Member
            </x-button>
          </template>
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
          <x-tooltip
            v-else-if="shouldShowHealthAddMemberViewModeTooltip(false)"
            position="bottom"
          >
            <AddMemButtonReuseTemplate />
            <template #tooltip>
              {{ ADD_MEMBER_VIEW_MODE_TOOLTIP }}
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
            :disabled="isDisabled || isLocked || (!isView && (isSelf_Me || item.is_policy_holder === 1))"
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
            :disabled="isDisabled || isLocked"
            v-if="readOnlyMode.isDisable === true && !item.is_principal"
          >
            Delete
          </x-button>
        </DeleteMemberButtonTemplate>
        <PrincipalMemberButtonTemplate v-slot="{ isDisabled, item }">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click.prevent="memberPrincipal(item)"
            v-if="! (item.is_principal || calculateAge(item.dob) < MIN_AGE_YEARS_FOR_PRINCIPAL_ACTION)"
            :disabled="isLocked"
          >
            Make {{ makeActionName === 'policyholder' ? 'Policyholder' : 'Principal' }}
          </x-button>
        </PrincipalMemberButtonTemplate>
        <DataTable
          table-class-name="tablefixed overflow-auto"
          :headers="memberDetailsTable.columns"
          :items="localMembersFiltered || []"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-first_name="{ first_name, last_name, is_principal, is_policy_holder }">
            {{ (first_name ?? '') + ' ' + (last_name ?? '') }}
            {{ is_policy_holder === 1 ? '(Policyholder)' : is_principal === 1 ? '(Principal)' : '' }}
          </template>

          <template #item-is_pec_marked="{ is_pec_marked }">
            <div class="text-center">
              <x-tag size="sm" :color="is_pec_marked ? 'error' : 'success'">
                {{ is_pec_marked ? 'Yes' : 'No' }}
              </x-tag>
            </div>
          </template>

          <template #item-gender="{ gender }">
            {{ genderText(gender).value }}
          </template>

          <template #item-dob="{ dob }">
            {{ dateFormat(dob) }}
          </template>

          <template #item-relation="{ is_policy_holder, relation_code }">
            {{ is_policy_holder === 1 ? 'Self' : relationText(relation_code).value }}
          </template>

          <template #item-nationality="{ nationality_id }">
            {{ nationalityText(nationality_id).value }}
          </template>

          <template #item-emirate="{ is_principal, emirate_of_your_visa_id }">
            {{ is_principal !== 1 ? 'N/A' : emirateText(emirate_of_your_visa_id).value }}
          </template>

          <template #item-member_category_id="{ member_category_id }">
            {{ memberCategoryText(member_category_id).value }}
          </template>

          <template #item-visa_category_id="{ visa_category_id }">
            {{ visaCategoryText(visa_category_id).value }}
          </template>

          <template #item-marital_status_id="{ marital_status_id }">
            {{ maritalStatusText(marital_status_id).value }}
          </template>

          <template #item-salary_band_id="{ salary_band_id }">
            {{ salaryBandText(salary_band_id).value }}
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
                v-if="lockLeadSectionsDetails.member_details"
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

              <x-tooltip
                v-if="lockLeadSectionsDetails.member_details"
                position="left"
                align="center"
                class="yoyo-tip"
              >
                <PrincipalMemberButtonReuseTemplate
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
              <PrincipalMemberButtonReuseTemplate v-else :item="item" />
            </div>
          </template>
        </DataTable>

        <div class="mt-2 flex justify-end">
          <x-button
              @click.prevent="onRefreshPlans"
              size="sm"
              color="emerald"
              :disabled="refreshPlansForm.processing || isLocked || props.quote?.is_quote_revisable == 0"
              :loading="refreshPlansForm.processing"
              v-if="isView"
            >
              View Quote
          </x-button>
        </div>
        <div v-if="isView && props.quote?.is_quote_revisable == 1" class="mx-2 text-right mt-2">*Add all members to view the final quote under Available Plans Section.</div>

        <x-modal
          v-model="modals.member"
          size="lg"
          :title="modalTitle(memberActionEdit)"
          show-close
          backdrop
          is-form
          persistent
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
          <div class="grid md:grid-cols-2 gap-4">
            <input type="hidden" :value="memberForm.id" />
            <x-input
              required
              maxLength="60"
              v-model="memberForm.first_name"
              label="Insured First Name"
              placeholder="Insured First Name"
              :rules="[isRequired]"
            />
            
            <x-input
              required
              maxLength="60"
              v-model="memberForm.last_name"
              label="Insured Last Name"
              placeholder="Insured Last Name"
              :rules="[isRequired]"
            />

            <DatePicker
              required
              v-model="memberForm.dob"
              label="Date of Birth"
              :max-date="new Date()"
              :rules="[isRequired]"
            />

            <x-select
              required
              v-model="memberForm.gender"
              label="Gender"
              :options="genderOptions"
              :rules="[isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />

            <x-select
              v-model="memberForm.marital_status_id"
              label="Marital Status"
              :options="maritalStatusOptions"
              placeholder="Select Marital Status"
              filterable
              filterPlaceholder="Filter Marital Status...."
              required
              :rules="[isRequired]"
            />

            <x-select
              required
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalities"
              placeholder="Select Nationality"
              filterable
              filterPlaceholder="Filter Nationality...."
              :rules="[isRequired]"
            />

            <x-select
              v-if="memberForm.is_principal == 1"
              required
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa"
              :options="emirates"
              :rules="[isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.member_category_id"
              label="Member Category"
              required
              :options="memberCategories"
              :rules="[isRequired]"
              placeholder="Select Member Category"
              class="w-full"
            />

            <x-select
              v-model="memberForm.salary_band_id"
              label="Salary"
              :options="salaryBands"
              placeholder="Select Salary Band"
              class="w-full"
              :rules="[isRequired]"
              required
            />

            <x-select
              required
              v-model="memberForm.visa_category_id"
              label="Visa Category"
              :options="visaCategoryOptions"
              placeholder="Select Visa Category"
              class="w-full"
              :rules="[isRequired]"
            />

            <x-select
              v-if="memberForm.is_policy_holder != 1"
              v-model="memberForm.relation_code"
              label="Relationship with Policyholder"
              :options="memberRelations"
              placeholder="Select Relation"
              class="w-full"
              :rules="[isRequired]"
              required
            />
            
          </div>

          <div class="md:col-span-2" data-member-pec-field>
            <div class="mb-3">
              <ToolTip
                title="Does the member need to declare any chronic or pre-existing medical conditions, pregnancy, plans to conceive, or fertility treatment?"
                tooltip="Any ongoing or past health issues that may or may not require regular treatment or medical attention."
                class="w-full"
              />
            </div>
            <div>
              <x-form-group v-model="memberForm.pec">
                <x-radio :value="HEALTH_PEC_YES" label="Yes" />
                <x-radio :value="HEALTH_PEC_NO" label="No" />
              </x-form-group>
              <div
                v-if="memberForm.pec === HEALTH_PEC_YES"
                class="mt-2 text-sm text-orange-600 border border-orange-200 bg-orange-50 rounded-md p-2"
              >
                <b>Please note:</b> Declaring a health condition doesn't mean it's automatically covered. It helps us assess eligibility. Premiums shown next are indicative. Your advisor will confirm coverage details for any pre-existing conditions.
              </div>
              <div
                v-if="memberPecValidationError"
                class="mt-2 text-sm text-red-600 border border-red-200 bg-red-50 rounded-md p-2"
              >
                {{ memberPecValidationError }}
              </div>
            </div>
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

        <x-modal
          v-model="modals.memberPrincipal"
          :title="`Confirm ${makeActionName === 'policyholder' ? 'Policyholder' : 'Principal'} Member`"
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
          <p>Are you sure you want to make this member {{ makeActionName === 'policyholder' ? 'Policyholder' : 'Principal' }}?</p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.memberPrincipal = false"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="error"
                @click.prevent="memberPrincipalConfirmed"
                :loading="memberForm.processing"
              >
                Confirm
              </x-button>
            </div>
          </template>
        </x-modal>
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
