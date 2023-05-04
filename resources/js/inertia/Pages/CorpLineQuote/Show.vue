<script setup>
defineProps({
  quote: Object,
  genderOptions: Object,
  assignedGMType: String,
  allowedDuplicateLOB: Array,
  quoteDetails: Object,
  customerAdditionalContacts: Array,
  enums: Object,
  activities: Array,
  advisors: Array,
  permissions: Object,
});

const { isRequired } = useRules();

const page = usePage();

const notification = useNotifications('toast');

const { copy, copied } = useClipboard();

const rules = {
  isRequired: v => !!v || 'This field is required',
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

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
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
});

const leadDuplicateForm = useForm({
  lob_team: [],
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  let data = {
    modelType: 'business',
    parentType: 'business',
    entityId: page.props.quote.id,
    entityCode: page.props.quote.code,
    entityUId: page.props.quote.uuid,
    lob_team: leadDuplicateForm.lob_team,
  };
  axios
    .post('/quotes/createDuplicate', data)
    .then(res => {
      modals.duplicate = false;
      notification.success('Lead duplicated successfully');
      router.visit('/quotes/business');
    })
    .catch(err => {
      notification.error('Something went wrong');
    });
};

// Lead Status

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Business',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quoteDetails.notes || null,
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quote.lost_reason || null,
});

const onLeadStatus = () => {
  let data = {
    modelType: 'Business',
    leadId: leadStatusForm.leadId,
    quote_uuid: leadStatusForm.quote_uuid,
    assigned_to_user_id: leadStatusForm.assigned_to_user_id,
    leadStatus: leadStatusForm.leadStatus,
    notes: leadStatusForm.notes,
    trans_code: leadStatusForm.trans_code,
    lostReason: leadStatusForm.lostReason,
  };
  axios
    .post(`/quotes/Business/${page.props.quote.id}/update-lead-status`, data)
    .then(res => {
      console.log(res);
      notification.success({
        title: 'Lead Status Updated',
        position: 'top',
      });
    })
    .catch(err => {
      console.log(err);
    });
};

// Lead History

const historyData = ref(null),
  activityActionEdit = ref(false),
  assignLead = ref(null),
  isDisabled = ref(false),
  historyLoading = ref(false);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=business&recordId=${page.props.quote.id}`,
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

//activities

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

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
  modelType: 'Business',
  parentType: 'Business',
  quoteType: 5,
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
    let date = new Date(activityForm.due_date);
    date =
      date.toISOString().split('T')[0] +
      ' ' +
      date.toTimeString().split(' ')[0];
    activityForm.due_date = date;
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
    let date = new Date(activityForm.due_date);
    date =
      date.toISOString().split('T')[0] +
      ' ' +
      date.toTimeString().split(' ')[0];
    activityForm.due_date = date;
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



const onAssignLead = () => {
  if (!assignLead.value) {
    notification.error({
      title: 'Please select a lead',
      position: 'top',
    });
    return;
  }
  router.post(
    `/quotes/business/manualLeadAssign`,
    {
      modelType: 'Business',
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

onMounted(() => {});
</script>
<template>
  <div>
    <Head title="Business Quote Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Business Quote Detail</h2>
      <div class="flex gap-2">
        <x-button
          v-if="allowedDuplicateLOB"
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
        >
          Duplicate Lead
        </x-button>

        <Link href="/quotes/business" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Business Quote List
          </x-button>
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
            :rules="[rules.isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
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
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ID</dt>
            <dd>{{ quote.id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote.code }}</dd>
          </div>
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
            <dd>{{ quote.company_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor_id_text }}</dd>
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
            <dt class="font-medium">PREMIUM</dt>
            <dd>{{ quote.premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NUMBER OF EMPLOYEES</dt>
            <dd>{{ quote.number_of_employees }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BUSINESS INSURANCE TYPE</dt>
            <dd>Group Medical</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BRIEF DETAILS</dt>
            <dd>{{ quote.brief_details }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL EXPIRY DATE</dt>
            <dd>{{ quote.renewal_expiry_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PARENT CDB ID</dt>
            <dd>{{ quote.parent_duplicate_quote_id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL IMPORT CODE</dt>
            <dd>{{ quote.renewal_import_code }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
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
    <div class="p-4 rounded shadow mb-6 bg-white">
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

    <!-- Additional Contact -->
    <customerAdditionalContacts quoteType="Business" :customerId="quote.customer_id" :quoteId="quote.id"  :contacts="customerAdditionalContacts" />

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

    <AuditLogs
      :type="'App\\Models\\BusinessQuote'"
      :id="$page.props.quote.id"
    />
  </div>
</template>
