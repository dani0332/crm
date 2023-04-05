<script setup>

import { useNotifications } from '@indielayer/ui';
import { computed, ref, reactive, onMounted } from 'vue';
import { useDateFormat} from '@vueuse/core';

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
  modelType: String,
  quoteDocuments: Object,
  displaySendPolicyButton: Boolean,
  documentTypes: Object,
  cdnPath: String,
  memberCategories: Array,
  isAdmin: Boolean,
  activities: Array,
  customerAdditionalContacts: Array,
  fieldsToDisplay: Object,
  customTitles: Object,
});

const page = usePage();

const dateFormat = date => {
  if (!date) return '';
  return useDateFormat(date, 'DD-MM-YYYY');
};

const notification = useNotifications('toast');

const { isRequired } = useRules();

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
  modelType: 'life',
  parentType: 'life',
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

const travelFields = computed(() => {
  let skipFields = [
    'previous_quote_policy_number',
    'previous_quote_policy_premium',
    'previous_policy_expiry_date',
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
  modelType: 'Life',
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
    `/quotes/Life/${page.props.quote.id}/update-lead-status`,
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
  modelType: 'Life',
  parentType: 'Life',
  quoteType: 4,
  title: null,
  description: null,
  due_date: null,
  assignee_id: null,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.title = null;
  activityForm.description = null;
  activityForm.due_date = ref(new Date());
  activityForm.assignee_id = null;
  activityForm.status = null;
  activityForm.activity_id = null;
  activityForm.uuid = null;
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
  quote_type: 'life',
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
      quote_type: 'life',
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
    `/quotes/getLeadHistory?modelType=life&recordId=${page.props.quote.id}`,
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

onMounted(() => {
  if (page.props.message) {
    notification.success({
      title: page.props.message,
      position: 'top',
    });
  }
});

</script>
<template>
  <div>
    <Head title="Life Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Life Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link href="/quotes/life" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">Life List </x-button>
        </Link>

        <Link
          v-if="permissions.canEditQuote == true"
          :href="`${quote.uuid}/edit`"
        >
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div
            class="grid sm:grid-cols-2"
            v-for="field in travelFields"
            :key="field"
          >
            <dt class="font-medium">{{ field.title.toUpperCase() }}</dt>
            <dd>{{ field.value }}</dd>
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
            <dd>{{ quote.previous_policy_expiry_date }}</dd>
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
              label="STATUS"
              :options="leadStatusOptions"
              :disabled="
                quote.quote_status_id ==
                enums.quoteStatusEnum.TransactionApproved
              "
              placeholder="Lead Status"
              class="w-full"
            />
            <x-textarea
              v-model="leadStatusForm.notes"
              type="text"
              label="NOTES"
              placeholder="Lead Notes"
              class="w-full"
              :disabled="
                quote.quote_status_id ==
                enums.quoteStatusEnum.TransactionApproved
              "
            />
          </div>
        </div>
        <div class="w-full md:w-2/3">
          <x-input
            v-if="
              leadStatusForm.leadStatus ==
              enums.quoteStatusEnum.TransactionApproved
            "
            :disabled="
              quote.quote_status_id == enums.quoteStatusEnum.TransactionApproved
            "
            v-model="leadStatusForm.trans_code"
            label="TRANSAPP CODE"
            placeholder="TransApp Code is required"
            class="w-full"
            :error="leadStatusForm.errors.trans_code"
          />
          <x-select
            v-if="leadStatusForm.leadStatus == enums.quoteStatusEnum.Lost"
            v-model="leadStatusForm.lostReason"
            label="LOST REASON"
            :options="lostReasonsOptions"
            placeholder="Lost Reason is required"
            class="w-full"
            :error="leadStatusForm.errors.lostReason"
          />
        </div>
      </div>
      <div class="flex justify-end">
        <x-button
          class="mt-4"
          color="emerald"
          size="sm"
          :loading="leadStatusForm.processing"
          @click.prevent="onLeadStatus"
          :disabled="
            quote.quote_status_id == enums.quoteStatusEnum.TransactionApproved
          "
        >
          Change Status
        </x-button>
      </div>
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
              :options="leadStatusOptions"
              :rules="[isRequired]"
              placeholder="Select Assignee"
              class="w-full"
            />
            <DatePicker
              :format="format"
              v-model="activityForm.due_date"
              label="Due Date"
              :rules="[isRequired]"
              class="w-full"
              withTime
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
              :rules="[isRequired]"
              placeholder="Select Type"
              class="w-full"
            />

            <x-input
              v-model="additionalContact.additional_contact_val"
              label="Value"
              :rules="[isRequired]"
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

    <AuditLogs :type="'App\\Models\\LifeQuote'" :id="$page.props.quote.id" />
  </div>
</template>
