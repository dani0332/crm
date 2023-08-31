<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, usePage, router, useForm, Link } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';
// import axios from 'axios';

defineProps({
  quote: Object,
  leadStatuses: Array,
  advisors: Array,
  activities: Array,
  customerAdditionalContacts: Array,
  lostReasons: Array,
  quoteStatusEnum: Object,
  modelType: String,
  notProductionApproval: Boolean,
  allowedDuplicateLOB: Array,
  isBetaUser: Boolean,
  can: Object,
});

const page = usePage();

const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
});

const leadDuplicateForm = useForm({
  modelType: 'home',
  parentType: 'home',
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

const confirmDeleteData = reactive({
  activity: null,
  contact: null,
});

const confirmData = reactive({
  contactPrimary: null,
});

const contactLoader = ref(false),
  activityActionEdit = ref(false),
  historyLoading = ref(false);

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Home',
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
    `/quotes/Home/${page.props.quote.id}/update-lead-status`,
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
  modelType: 'Home',
  parentType: 'Home',
  quoteType: 2,
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
  quote_type: 'home',
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
      quote_type: 'home',
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

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=home&recordId=${page.props.quote.id}`,
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
</script>

<template>
  <div>
    <Head title="Home Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Home Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link href="/quotes/home" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Home List </x-button>
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
            <div>{{ quote.code }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Advisor</dt>
            <dd>{{ quote.advisor_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created Date</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Source</dt>
            <dd>{{ quote.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Last Modified Date</dt>
            <dd>{{ quote.updated_at }}</dd>
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
            <div>{{ quote.parent_duplicate_quote_id }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Renewal import code</dt>
            <dd>{{ quote.renewal_import_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Device</dt>
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
            <dt class="font-medium">First Name</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Last Name</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Mobile Number</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Email</dt>
            <dd>{{ quote.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Address</dt>
            <dd>{{ quote.address }}</dd>
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
            <dt class="font-medium">I am</dt>
            <dd>{{ quote.iam_possesion_type_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">I Live In</dt>
            <dd>{{ quote.ilivein_accommodation_type_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Contents</dt>
            <dd>{{ quote.has_contents ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Contents Aed</dt>
            <dd>{{ quote.contents_aed }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Building</dt>
            <dd>{{ quote.has_building ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Building Aed</dt>
            <dd>{{ quote.building_aed }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Has Personal Belongings</dt>
            <dd>{{ quote.has_personal_belongings ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Personal Belongings Aed</dt>
            <dd>{{ quote.personal_belongings_aed }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Currently Insured With</dt>
            <dd>{{ quote.currently_insured_with_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Type Of Plan</dt>
            <dd>{{ quote.plan_id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Next FollowUp Date</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Details</dt>
            <dd>{{ quote.details }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Number</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Price</dt>
            <dd>{{ quote.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TransApp Code</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      :quote="quote"
    />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
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
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/3">
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
              label="Title*"
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
              label="Assignee*"
              :options="advisorOptions"
              :rules="[rules.isRequired]"
              placeholder="Select Assignee"
              class="w-full"
            />

            <DatePicker
              v-model="activityForm.due_date"
              withTime
              :rules="[rules.isRequired]"
              label="Due Date*"
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

    <customerAdditionalContacts
      quoteType="Home"
      :customerId="quote.customer_id"
      :quoteId="quote.id"
      :contacts="customerAdditionalContacts"
    />

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

    <AuditLogs :type="'App\\Models\\HomeQuote'" :id="$page.props.quote.id" />
  </div>
</template>
