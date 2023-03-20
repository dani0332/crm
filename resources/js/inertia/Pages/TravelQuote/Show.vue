<script setup>
import axios from 'axios';
import { useNotifications } from '@indielayer/ui';
import { computed, ref, reactive, onMounted } from 'vue';
import { useDateFormat, useClipboard } from '@vueuse/core';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';
import { Head, usePage, router, useForm, Link } from '@inertiajs/vue3';

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
  quoteDocuments: Object,
  displaySendPolicyButton: Boolean,
  documentTypes: Object,
  cdnPath: String,
  memberCategories: Array,
  emailStatuses: Array,
  isAdmin: Boolean,
  listQuotePlans: Array,
  activities: Array,
  customerAdditionalContacts: Array,
  ecomTravelInsuranceQuoteUrl: String,
  fieldsToDisplay: Object,
  quotes: Array,
});

const page = usePage();

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY');

const notification = useNotifications('toast');

const rules = {
  isRequired: v => !!v || 'This field is required',
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

const memberActionEdit = ref(false),
  activityActionEdit = ref(false),
  selectedPlan = ref(null),
  selectedPlansPdf = ref([]),
  exportLoader = ref(false),
  contactLoader = ref(false),
  historyLoading = ref(false),
  lostReasonId = ref(
    page.props.lostReasons.find(
      reason => reason.text === page.props.quote.lost_reason,
    )?.id || null,
  );

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

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });

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
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
  planDetails: false,
});

const leadStatusForm = useForm({
  modelType: 'Travel',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quote.notes || null,
  trans_code: page.props.quote.transapp_code || null,
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
    `/quotes/Travel/${page.props.quote.id}/update-lead-status`,
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

const travelerForm = useForm({
  dob: '',
  travel_quote_request_id: page.props.quote.id,
});

const travelerTable = reactive({
  isLoading: false,
  addTraveler: false,
  processing: false,
  columns: [
    {
      text: 'Name',
      value: 'name',
    },
    {
      text: 'DOB',
      value: 'dob',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Updated At',
      value: 'updated_at',
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

const addTravelMember = isValid => {
  if (!isValid) return;

  travelerForm.post('/travelers', {
    preserveScroll: true,
    onBefore: () => {
      travelerTable.processing = true;
    },
    onSuccess: () => {
      notification.success({
        title: 'Traveler Added',
        position: 'top',
      });
    },
    onFinish: () => {
      travelerTable.addTraveler = false;
      travelerTable.processing = false;
      travelerForm.dob = '';
      travelerForm.id = null;
      travelerForm.reset();
    },
  });
};

const onAddTraveler = () => {
  travelerForm.reset();
  travelerForm.dob = '';
  travelerForm.id = null;
  travelerTable.addTraveler = true;
};

const onEditTraveler = traveler => {
  travelerForm.dob = traveler.dob;
  travelerForm.id = traveler.id;
  travelerTable.addTraveler = true;
};

const editTraveler = isValid => {
  if (!isValid) return;

  travelerForm.put(`/travelers/${travelerForm.id}`, {
    preserveScroll: true,
    onBefore: () => {
      travelerTable.processing = true;
    },
    onSuccess: () => {
      notification.success({
        title: 'Traveler Updated',
        position: 'top',
      });
    },
    onFinish: () => {
      travelerTable.addTraveler = false;
      travelerTable.processing = false;
      travelerForm.dob = '';
      travelerForm.id = null;
      travelerForm.reset();
    },
  });
};

const deleteTraveler = id => {
  router.delete(`/travelers/${id}`, {
    preserveScroll: true,
    onBefore: () => {
      travelerTable.processing = true;
    },
    onSuccess: () => {
      notification.success({
        title: 'Traveler Deleted',
        position: 'top',
      });
    },
    onFinish: () => {
      travelerTable.processing = false;
      confirmModal.show = false;
    },
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

const policyDetailRules = {
  policy_number: v => {
    if (v) {
      return (
        v.length <= 50 || 'Policy Number should be less than 50 characters'
      );
    }
    return true;
  },
  policy_start_date: v => {
    if (v) {
      const date = new Date(v);
      return !isNaN(date.getTime());
    }
    return true;
  },
  renewal_expiry_date: v => {
    if (v) {
      const date = new Date(v);
      if (policyDetails.policy_start_date) {
        const startDate = new Date(policyDetails.policy_start_date);
        if (startDate >= date) {
          return 'Expiry date should be greater than Start Date';
        }
      }
      return !isNaN(date.getTime());
    }
    return true;
  },
  premium: v => {
    if (v) {
      const premium = parseFloat(v);
      if (premium < 0 || isNaN(premium)) {
        return 'Premium should be greater than 0';
      }
    }
    return true;
  },
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
  renewal_expiry_date: dateToYMD(page.props.quote.renewal_expiry_date) || '',
  policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
  quote_status_id: page.props.quote.quote_status_id,
  canEdit:
    page.props.quote.quote_status_id ==
      page.props.enums.quoteStatusEnum.TransactionApproved &&
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
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

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
      text: 'Travel Type',
      value: 'travelType',
    },
    {
      text: 'Actual Premium',
      value: 'actualPremium',
    },
    {
      text: 'Premium with VAT',
      value: 'discountPremium',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

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
  modelType: 'Travel',
  parentType: 'Travel',
  quoteType: 3,
  title: null,
  description: null,
  due_date: ref(new Date()),
  assignee_id: null,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.reset(
    'title',
    'description',
    'due_date',
    'assignee_id',
    'status',
    'activity_id',
    'uuid',
  );
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivityStatusUpdate = id => {
  activityForm.activity_id = id;
  activityForm.post(`/activities/updateStatus`, {
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

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

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
  quote_type: 'travel',
});

const addAdditionalContact = () => {
  additionalContact.additional_contact_type = null;
  additionalContact.additional_contact_val = null;
  modals.addContact = true;
};

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
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.addContact = false;
      },
      onError: err => {
        const firstError = Object.values(err)[0];
        notification.error({
          title: firstError,
          position: 'top',
        });
      },
    });
};

const additionalContactDelete = id => {
  modals.contactDeleteConfirm = true;
  confirmDeleteData.contact = id;
};

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
      quote_type: 'travel',
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Additional Contact Primary',
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

const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=travel&recordId=${page.props.quote.id}`,
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

const selectedTab = ref('general_info');
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

onMounted(() => {});
</script>
<template>
  <div>
    <Head title="Travel Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Travel Detail</h2>
      <div class="flex gap-2">
        <x-button
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
          v-if="permissions.canNotApprovePayments"
        >
          Duplicate Lead
        </x-button>

        <Link href="/quotes/travel" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Travel List </x-button>
        </Link>

        <Link :href="`${quote.uuid}/edit`">
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div
            class="grid sm:grid-cols-2"
            v-for="field in fieldsToDisplay"
            :key="field"
          >
            <dt class="font-medium">{{ field.title.toUpperCase() }}</dt>
            <dd>{{ field.value }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="
                quote.quote_status_id ==
                enums.quoteStatusEnum.transactionApproved
              "
              placeholder="Lead Status"
              class="w-full"
            />
            <x-textarea
              v-model="leadStatusForm.notes"
              type="text"
              label="Notes"
              placeholder="Lead Notes"
              class="w-full"
              :disabled="
                quote.quote_status_id ==
                enums.quoteStatusEnum.transactionApproved
              "
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
        <div class="w-full md:w-2/3">
          <x-input
            v-if="
              leadStatusForm.leadStatus ==
              enums.quoteStatusEnum.transactionApproved
            "
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
            :options="lostReasonsOptions"
            placeholder="Lost Reason is required"
            class="w-full"
            :error="leadStatusForm.errors.lostReason"
          />
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
            <dt class="font-medium">PREMIUM</dt>
            <dd>{{ ecomDetails.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ dateFormat(ecomDetails.paidAt).value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ ecomDetails.paymentStatus }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ ecomDetails.planName }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Travelers
          <x-tag size="sm">{{ travelers.length || 0 }}</x-tag>
        </h3>
        <div class="flex flex-wrap gap-3">
          <x-button size="sm" color="primary" @click.prevent="onAddTraveler">
            Add Member
          </x-button>
        </div>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="travelerTable.columns"
        :items="travelers || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        show-index
      >
        <template #item-name="item"> Traveler {{ item.index }} </template>
        <template #item-dob="{ dob }"> {{ dateFormat(dob).value }} </template>

        <template #item-created_at="{ created_at }">
          {{ dateFormat(created_at).value }}
        </template>

        <template #item-updated_at="{ updated_at }">
          {{ dateFormat(updated_at).value }}
        </template>

        <template #item-action="item">
          <div class="flex gap-2 pr-2">
            <x-button
              size="xs"
              color="primary"
              @click.prevent="onEditTraveler(item)"
              outlined
            >
              Edit
            </x-button>
            <x-button
              size="xs"
              color="emerald"
              @click.prevent="
                confirmModal.onConfirm = () => deleteTraveler(item.id);
                confirmModal.show = true;
              "
              outlined
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <x-modal
        v-model="travelerTable.addTraveler"
        size="lg"
        show-close
        backdrop
      >
        <template #header>
          <i class="fa fa-user"></i>
          {{ travelerForm.id ? 'Edit Member' : 'New Member' }}
        </template>
        <x-form @submit="submitTraveler" :auto-focus="false">
          <x-input
            label="Name"
            placeholder="Name"
            value="Member"
            readonly
            disabled
            class="w-full"
          />
          <DatePicker
            v-model="travelerForm.dob"
            label="Date of Birth"
            input-classes="w-full "
            :rules="[rules.isRequired]"
          />
          <div class="flex justify-end">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              type="submit"
              :loading="travelerTable.processing"
            >
              Save
            </x-button>
          </div>
        </x-form>
      </x-modal>
    </div>

    <x-modal v-model="confirmModal.show" show-close backdrop>
      <template #header> {{ confirmModal.title }} </template>
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

    <div class="p-4 rounded shadow mb-6 bg-white">
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
              :rules="[rules.isRequired, policyDetailRules.policy_number]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <DatePicker
              v-model="policyDetails.policy_issuance_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Issuance Date"
              :rules="[rules.isRequired]"
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <DatePicker
              v-model="policyDetails.policy_start_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Start Date"
              :rules="[rules.isRequired, policyDetailRules.policy_start_date]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <DatePicker
              v-model="policyDetails.renewal_expiry_date"
              :disabled="!policyDetails.editMode"
              type="date"
              label="Expiry Date"
              :rules="[rules.isRequired, policyDetailRules.renewal_expiry_date]"
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
              :rules="[rules.isRequired, policyDetailRules.premium]"
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
    </div>

    <div
      class="p-4 rounded shadow mb-6 bg-white"
      v-if="permissions.isQuoteDocumentEnabled"
    >
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Documents
          <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
        </h3>
        <div class="flex gap-2">
          <x-button
            @click.prevent="modals.doc = true"
            size="sm"
            color="orange"
            v-if="
              permissions.canNotEditPayments &&
              permissions.notProductionApproval
            "
          >
            Upload Documents
          </x-button>
          <x-button
            size="sm"
            color="red"
            v-if="displaySendPolicyButton && permissions.notProductionApproval"
            @click="sendPolicyToClient"
          >
            Send Policy
          </x-button>
          <x-button
            size="sm"
            color="green"
            v-if="
              enums.paymentStatusEnum.AUTHORISED == quote.payment_status_id &&
              permissions.notProductionApproval
            "
          >
            Copy Upload Link
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
          :members="memberDataDocs(travelers)"
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
      <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Available Plans</h3>
        <x-button
          v-if="listQuotePlans.length > 0 && permissions.canNotApprovePayments"
          size="sm"
          color="orange"
          @click.prevent="onCopyText(ecomTravelInsuranceQuoteUrl + quote.uuid)"
        >
          Copy Link
        </x-button>
      </div>

      <div v-if="listQuotePlans.length > 0">
        <DataTable
          table-class-name="tablefixed compact"
          :headers="availablePlansTable.columns"
          :items="listQuotePlans || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
        >
          <template #item-providerName="item">
            <span class="text-primary-600">{{
              item.providerName?.toUpperCase()
            }}</span>
          </template>
          <template #item-name="item">
            <span class="text-primary-600">{{ item.name?.toUpperCase() }}</span>
          </template>
          <template #item-discountPremium="item">
            <span class="text-primary-600">{{
              item.discountPremium + item.vat
            }}</span>
          </template>
          <template #item-action="item">
            <div>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="getPlanDetails(item.id)"
              >
                View
              </x-button>
            </div>
          </template>
        </DataTable>
      </div>
      <div v-else>
        <p
          class="text-center text-primary-600"
          v-if="typeof listQuotePlans == 'string'"
        >
          {{ listQuotePlans?.toUpperCase() }}
        </p>
      </div>

      <x-modal v-model="modals.planDetails" size="xl" show-close backdrop>
        <template #header> Plan Details </template>
        <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
          <x-tab-group
            v-model="selectedTab"
            class="w-full"
            variant="block"
            grow
          >
            <x-tab value="general_info" label="General Info">
              <div
                class="flex flex-wrap gap-4 justify-between items-center mb-4 mt-4"
              >
                <div class="w-full md:w-1/2">
                  <p class="text-primary-600">
                    Provider Code:
                    <span class="text-black">{{
                      planDetails.providerCode
                    }}</span>
                  </p>
                  <p class="text-primary-600">
                    Provider Name:
                    <span class="text-black">{{
                      planDetails.providerName
                    }}</span>
                  </p>
                  <p class="text-primary-600">
                    Travel Type:
                    <span class="text-black">{{ planDetails.travelType }}</span>
                  </p>
                  <p class="text-primary-600">
                    Actual Premium:
                    <span class="text-black">{{
                      planDetails.actualPremium
                    }}</span>
                  </p>
                  <p class="text-primary-600">
                    Discount Premium:
                    <span class="text-black">{{
                      planDetails.discountPremium
                    }}</span>
                  </p>
                </div>
              </div>
            </x-tab>
            <x-tab value="members" label="Members">
              <div
                v-for="(member, index) in planDetails.listQuotePlansMembers"
                class="gap-4 justify-between items-center mb-4 mt-4"
              >
                <p class="">Member {{ index + 1 }}</p>

                <div class="grid grid-cols-2 gap-2">
                  <span>Premium: {{ member.premium }}</span>
                  <span>DOB: {{ member.dob }}</span>
                </div>
              </div>
            </x-tab>
            <x-tab value="inclusions" label="Inclusions">
              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead class="">
                  <tr>
                    <th class="px-6 py-3" scope="col">Features & Benefits</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsFeatures"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>

              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">Travel Inconvenience Cover</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitstravelInconvenienceCover"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>

              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">Emergency Medical Cover</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsemergencyMedicalCover"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>

              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">Included in the plan</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsInclusions"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>
            </x-tab>
            <x-tab value="exclusions" label="Exclusions">
              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">Exclusions</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsExclusions"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>
            </x-tab>
            <x-tab value="covid" label="COVID-19 Cover">
              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">COVID-19 Cover</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsCovid19"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">{{ feature.text }}</td>
                    <td class="px-4 py-2">{{ feature.value }}</td>
                  </tr>
                </tbody>
              </table>
            </x-tab>

            <x-tab value="" label="Policy Details">
              <table cellpadding="3" cellspacing="3" class="table-auto">
                <thead>
                  <tr>
                    <th class="px-4 py-2">Policy Details</th>
                    <th class="px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="feature in planDetails.listQuotePlanBenefitsPolicyDetails"
                    :key="feature.id"
                  >
                    <td class="px-4 py-2">
                      <a
                        :href="feature.link"
                        target="_blank"
                        title="click to open"
                        >📃 {{ feature.text }}</a
                      >
                    </td>
                  </tr>
                </tbody>
              </table>
            </x-tab>
          </x-tab-group>
        </div>
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
              :rules="[rules.isRequired]"
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
              :rules="[rules.isRequired]"
              placeholder="Select Assignee"
              class="w-full"
            />

            <DatePicker
              v-model="activityForm.due_date"
              label="Due Date"
              :rules="[rules.isRequired]"
              :enable-time-picker="true"
              :is-24="true"
              class="w-full"
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
          @click.prevent="addAdditionalContact"
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
          <div class="space-x-4">
            <x-button
              size="xs"
              color="emerald"
              outlined
              @click.prevent="additionalContactPrimary(item)"
            >
              Make Primary
            </x-button>
            <x-button
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
              :rules="[rules.isRequired]"
              placeholder="Select Type"
              class="w-full"
            />

            <x-input
              v-model="additionalContact.additional_contact_val"
              label="Value"
              :rules="[rules.isRequired]"
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Email Status</h3>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="emailStatusesTableColumns"
        :items="emailStatuses || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
      >
        <template #item-email_status="item">
          <span class="text-primary-600">{{
            item.email_status.toUpperCase()
          }}</span>
        </template>
        <template #item-reason="item">
          <span class="text-primary-600">{{ item.reason.toUpperCase() }}</span>
        </template>
      </DataTable>
    </div>

    <AuditLogs :type="'App\\Models\\TravelQuote'" :id="$page.props.quote.id" />
  </div>
</template>
