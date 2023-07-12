<script setup>
import LazyDocumentUploader from './Partials/DocumentUploader.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import PaymentTable from './Partials/PaymentTable.vue';

defineProps({
  quote: Object,
  leadStatuses: Array,
  ecomDetails: Object,
  membersDetail: Array,
  memberCategories: Array,
  salaryBands: Array,
  nationalities: Array,
  emirates: Array,
  advisors: Array,
  listQuotePlans: Array,
  quoteDocuments: Object,
  documentTypes: Object,
  cdnPath: String,
  ecomHealthInsuranceQuoteUrl: String,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  quoteStatusEnum: Object,
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
  paymentMethods: Object,
  sendPolicy: Boolean,
});

const page = usePage();

const notification = useToast();
const hasRole = role => useHasRole(role);

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const fixedValue = number => {
  if (number == Math.floor(number)) {
    return number.toLocaleString();
  } else {
    return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
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
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
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

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const confirmData = reactive({
  contactPrimary: null,
});

const assignSubteam = ref(page.props.quote.health_team_type || ''),
  assignLead = ref(null),
  memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlans = ref([]),
  exportLoader = ref(false),
  toggleLoader = ref(false),
  contactLoader = ref(false),
  historyLoading = ref(false),
  isDisabled = ref(false);

const { copy, copied } = useClipboard();

const { isRequired, isEmail, isNumber, isMobile } = useRules();

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
  { value: 'RM-NB', label: 'RM-NB' },
  { value: 'RM-Speed', label: 'RM-Speed' },
  { value: 'EBP', label: 'EBP' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
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

const memberCategoriesOptions = computed(() => {
  return page.props.memberCategories.map(cat => ({
    value: cat.id,
    label: cat.text,
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
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
  leadStatusForm.post(
    `/quotes/Health/${page.props.quote.id}/update-lead-status`,
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

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Gender',
      value: 'gender',
    },
    {
      text: 'DOB',
      value: 'dob',
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
  health_quote_request_id: page.props.quote.id,
  update_lead_against_member: null,
});

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
  memberForm.update_lead_against_member = data.index === 1;
}

const onAddMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
};

const memberFieldReq = reactive({
  nationality: false,
  dob: false,
});
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
    memberForm.put(`/members/${memberForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Updated',
          position: 'top',
        });
        memberForm.reset();
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  } else {
    memberForm.post(`/members`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  }
};

const memberDelete = id => {
  modals.memberConfirm = true;
  confirmDeleteData.member = id;
};

const memberDeleteConfirmed = () => {
  memberForm.delete(`/members/${confirmDeleteData.member}`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Member Deleted',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.memberConfirm = false;
    },
  });
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
const plansTable = reactive({
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
      text: 'Network Provider',
      value: 'eligibilityName',
    },
    {
      text: 'Base Premium',
      value: 'actualPremium',
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
      text: 'Total Indicative Premium (with VAT)',
      value: 'total',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const planClicked = plan => {
  selectedPlan.value = plan;
  modals.plan = true;
};

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
      '/api/v1/quotes/health/export-plans-pdf',
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

const onCreatePlan = () => {
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['listQuotePlans'],
    onStart: () => {
      modals.createPlan = false;
    },
    onFinish: () => {
      notification.success({
        title: 'Plan Created',
        position: 'top',
      });
    },
  });
};

const onPlanError = () => {
  modals.createPlan = false;
  notification.error({
    title: 'Plan Creation Failed',
    position: 'top',
  });
};

// quoteDocuments

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
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Title', value: 'title' },
  { text: 'Client Name', value: 'client_name' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee' },
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
  renewal_expiry_date: dateToYMD(page.props.quote.renewal_expiry_date) || '',
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
      quote_policy_expiry_date: data.renewal_expiry_date,
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

onMounted(() => {
  const isHealthAdvisor = page.props.advisors.find(
    a => a.id == page.props.quote.advisor_id,
  );
  if (isHealthAdvisor) assignLead.value = isHealthAdvisor.id;
});
</script>
<template>
  <div>
    <Head title="Health Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Health Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link :href="route('health.index')" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Health List </x-button>
        </Link>

        <Link :href="route('health.edit', quote.uuid)">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
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

    <x-divider class="my-4" />
    <div
      v-if="!$page.props.can.isAdvisor"
      class="p-4 rounded shadow mb-6 bg-primary-50/50 saad"
    >
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2 flex gap-2 items-end">
          <x-select
            v-model="assignSubteam"
            label="Assign Subteam"
            :options="subTeamOptions"
            placeholder="Select Subteam"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onTeamAssign"
              :loading="isDisabled"
            >
              Assign Team
            </x-button>
          </div>
        </div>
        <div
          v-if="!hasRole($page.props.rolesEnum.HealthWCUAdvisor)"
          class="w-full md:w-1/2 flex gap-2 items-end"
        >
          <x-select
            v-model="assignLead"
            label="Assign Lead"
            :options="advisorOptions"
            placeholder="Select Lead"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onAssignLead"
              :loading="isDisabled"
            >
              Assign
            </x-button>
          </div>
        </div>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div v-if="hasRole($page.props.rolesEnum.Engineering)" class="grid sm:grid-cols-2">
            <dt class="font-medium">ID</dt>
            <dd>{{ quote.id }}</dd>
        </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote.code }}</dd>
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
            <dt class="font-medium">PARENT CDB ID</dt>
            <dd>{{ quote.parent_duplicate_quote_id }}</dd>
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
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Profile</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quote.marital_status_id_text }}</dd>
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
            <dt class="font-medium">EMIRATE OF VISA</dt>
            <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MEMBER CATEGORY</dt>
            <dd>{{ quote.member_category_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SALARY BAND</dt>
            <dd>{{ quote.salary_band_id_text }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
            <dd>{{ quote.plan_id }}</dd>
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
            <dt class="font-medium">Additional Notes</dt>
            <dd>{{ quote.additional_notes }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ dateFormat(quote.previous_policy_expiry_date) }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
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
        show-index
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-index="{ index }">
          <div>Member {{ index }}</div>
        </template>
        <template #item-gender="{ gender }">
          {{ genderText(gender).value }}
        </template>
        <template #item-dob="{ dob }">
          {{ dateFormat(dob) }}
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
          <div class="grid md:grid-cols-2 gap-4">
            <input type="hidden" :value="memberForm.id" />

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
              label="Emirate of Visa"
              :options="emiratesOptions"
              :rules="[isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender"
              :options="genderSelect"
              :rules="[isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />

            <DatePicker
              v-model="memberForm.dob"
              label="DOB"
              :hasError="memberFieldReq.dob"
            />

            <x-select
              v-model="memberForm.member_category_id"
              label="Member Category"
              :options="memberCategoriesOptions"
              :rules="[isRequired]"
              placeholder="Select Member Category"
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

          <div class="text-right space-x-4 mt-8">
            <x-button size="sm" @click.prevent="modals.member = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="memberForm.processing"
              type="submit"
            >
              {{ memberActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>

      <x-modal v-model="modals.memberConfirm" show-close backdrop>
        <template #header> Delete Member Detail </template>
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
    </div>

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-2/3">
          <x-textarea
            v-model="leadStatusForm.notes"
            type="text"
            label="Notes"
            placeholder="Lead Notes"
            class="w-full"
            :disabled="quote.quote_status_id == 15"
          />
        </div>
        <div class="w-full md:w-1/3">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="quote.quote_status_id == 15"
              placeholder="Lead Status"
              class="w-full"
            />
            <x-input
              v-if="leadStatusForm.leadStatus == 15"
              v-model="leadStatusForm.trans_code"
              label="TransApp Code"
              placeholder="TransApp Code is required"
              class="w-full"
              :error="leadStatusForm.errors.trans_code"
            />
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
          </div>

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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">E-COM Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PLAN NAME</dt>
            <dd>{{ ecomDetails.planName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ ecomDetails.providerName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ ecomDetails.paymentStatus }}</dd>
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
            <dd>{{ fixedValue(ecomDetails.priceWithVAT) }}</dd>
        </div>
        </dl>
      </div>
    </div>

    <PaymentTable
      v-if="isBetaUser"
      :payments="payments"
      :can="can"
      :isBetaUser="isBetaUser"
      :quoteRequest="quoteRequest"
      :paymentMethods="paymentMethods"
      :quote="quote"
    />

    <!-- <div class="p-4 rounded shadow mb-6 bg-white" v-if="isQuoteDocumentEnabled">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <x-form @submit="submitPolicyDetails" :auto-focus="false">
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyDetails.policy_number"
              :disabled="!policyDetails.editMode"
              label="Policy Number"
              :rules="[isRequired, policyDetailRules.policy_number]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyDetails.policy_issuance_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Issuance Date"
              :rules="[isRequired]"
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyDetails.policy_start_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Start Date"
              :rules="[isRequired, policyDetailRules.policy_start_date]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyDetails.renewal_expiry_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Expiry Date"
              :rules="[isRequired, policyDetailRules.renewal_expiry_date]"
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyDetails.premium"
              :disabled="!policyDetails.editMode"
              label="Premium"
              :rules="[isRequired, policyDetailRules.premium]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2"></div>
        </div>

        <div class="text-right space-x-4 mt-12" v-if="policyDetails.canEdit">
          <x-button
            color="#007bff"
            size="sm"
            v-show="policyDetails.editMode"
            @click.prevent="cancelPolicyFrom"
            >Cancel</x-button
          >
          <x-button
            color="#26B99A"
            type="submit"
            size="sm"
            v-show="policyDetails.editMode"
            >Update</x-button
          >
          <x-button
            color="#007bff"
            size="sm"
            type="submit"
            v-show="!policyDetails.editMode"
            @click.prevent="policyDetails.editMode = true"
            >Edit</x-button
          >
        </div>
      </x-form>
    </div> -->

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Available Plans
          <x-tag size="sm">{{ listQuotePlans.length || 0 }}</x-tag>
        </h3>
        <div class="flex flex-wrap gap-3">
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
            v-if="listQuotePlans.length > 0"
            size="sm"
            color="orange"
            @click.prevent="
              onCopyText(ecomHealthInsuranceQuoteUrl + quote.uuid)
            "
          >
            Copy Link
          </x-button>

          <x-button
            size="sm"
            color="primary"
            @click.prevent="modals.createPlan = true"
          >
            Add Plan
          </x-button>
        </div>
      </div>
      <DataTable
        v-model:items-selected="selectedPlans"
        table-class-name="tablefixed compact"
        :headers="plansTable.columns"
        :items="listQuotePlans || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="listQuotePlans.length < 15"
      >
        <template #item-providerName="{ providerName, isManualPlan, isHidden }">
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
          </div>
        </template>
        <template #item-total="{ actualPremium, vat, basmah }">
          {{ fixedValue(actualPremium + (vat || 0) + (basmah || 0)) }}
        </template>
        <template #item-action="item">
          <div class="flex gap-2 pr-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="planClicked(item)"
            >
              View
            </x-button>
            <x-button
              size="xs"
              color="emerald"
              outlined
              @click.prevent="
                onCopyText(
                  ecomHealthInsuranceQuoteUrl +
                    quote.uuid +
                    `/payment/?providerCode=${item.providerCode}_${item.planCode}&planId=${item.id}`,
                )
              "
            >
              Copy
            </x-button>
          </div>
        </template>
      </DataTable>

      <x-modal v-model="modals.plan" size="xl" show-close backdrop>
        <template #header>
          {{ selectedPlan.providerName }} - {{ selectedPlan.name }}
        </template>
        <LazyAvailablePlan :plan="selectedPlan" :genders="genderOptions" />
      </x-modal>

      <x-modal v-model="modals.createPlan" size="xl" show-close backdrop>
        <template #header> Create Heath Quote </template>
        <LazyCreatePlan
          :uuid="quote.uuid"
          :members="membersDetail"
          :genders="genderOptions"
          @success="onCreatePlan"
          @error="onPlanError"
        />
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Documents
          <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
        </h3>
        <div class="flex gap-2">
          <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
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
    </div>

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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Customer Additional Contacts
          <x-tag size="sm">{{ customerAdditionalContacts.length || 0 }}</x-tag>
        </h3>
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
          >
            Make Primary
          </x-button>
        </template>
      </DataTable>

      <x-modal v-model="modals.addContact" size="lg" show-close backdrop>
        <template #header> Add Additional Contacts </template>

        <x-form @submit="onAdditionalContactSubmit" :auto-focus="false">
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

    <AuditLogs :type="'App\\Models\\HealthQuote'" :id="$page.props.quote.id" />
  </div>
</template>
