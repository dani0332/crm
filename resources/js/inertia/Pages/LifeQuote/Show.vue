<script setup>
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';

defineProps({
  quote: Object,
  quoteStatuses: Object,
  quoteType: String,
  activities: Array,
  advisors: Array,
  customerAdditionalContacts: Array,
  allowedDuplicateLOB: Array,
  lostReasons: Array,
  quoteStatusEnum: Object,
});
const { isRequired } = useRules();
const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
});

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name ? advisor.name + ' - ' + advisor.roles[0]?.name : advisor.name
  }));
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const historyLoading = ref(false);

// history data
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

const leadDuplicateForm = useForm({
  modelType: 'life',
  parentType: 'life',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});


//activities
const activityActionEdit = ref(false);
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
  quoteType: 2,
  title: null,
  description: null,
  due_date: null,
  assignee_id: null,
  status: null,
  activity_id: null,
  uuid: null,
});


const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
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

const modalsDuplicate = ref(false);
const openDuplicate = () => {
  modalsDuplicate.value = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post('/quotes/createDuplicate', {
    preserveScroll: true,
    onError: function onError(errors) {
      notification.error({
        title: errors[0],
        position: 'top',
      });
    },
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modalsDuplicate.value = false;
    },
  });
};

const quoteStatusOptions = computed(() => {
    return page.props.quoteStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const leadStatusOptions = computed(() => {
    return page.props.quoteStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const allowStatusUpdate = computed(() => {
    return (
        page.props.quote.quote_status_id ==
        page.props.quoteStatusEnum.TransactionApproved
    );
});

const leadStatusForm = useForm({
    modelType: 'Life',
    leadId: page.props.quote.id,
    quote_uuid: page.props.quote.uuid,
    assigned_to_user_id: page.props.quote.advisor_id,
    leadStatus: page.props.quote.quote_status_id || null,
    notes: page.props.quote.life_quote_request_detail?.notes || null,
    trans_code: page.props.quote.transapp_code || null,
    lostReason: page.props.quote.lost_reason_id || null,
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

</script>

<template>
  <div>
    <Head title="Life Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Life Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>
        <Link
          v-if="can(permissionsEnum.LifeQuotesList)"
          href="/quotes/life"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"> Life Quotes </x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.LifeQuotesEdit)"
          :href="`/quotes/life/${quote.uuid}/edit`"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>

    <x-modal v-model="modalsDuplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
        <div class="grid gap-4">
          <x-select
            v-model="leadDuplicateForm.lob_team"
            label="LOBs"
            :options="
              allowedDuplicateLOB.map((lob, index) => ({
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
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ID</dt>
            <dd>{{ quote.id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
              <div>
                  <x-tooltip position="bottom">
                      <label class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700">
                          Ref-ID
                      </label>
                      <template #tooltip> Reference ID </template>
                  </x-tooltip>
              </div>
              <div>{{ quote.code }}</div>
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
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote?.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor?.name }}</dd>
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
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quote.dob }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quote.nationality?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUM INSURED VALUE</dt>
            <dd>{{ quote.sum_insured_value }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.life_quote_request_detail?.next_followup_date }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.life_quote_request_detail?.transapp_code }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.life_quote_request_detail?.lost_reason?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PRICE</dt>
            <dd>{{ quote.premium }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENCY</dt>
            <dd>{{ quote.currency?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PURPOSE OF INSURANCE</dt>
            <dd>{{ quote.purpose_of_insurance?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quote.marital_status?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CHILDREN</dt>
            <dd>{{ quote.childern?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF INSURANCE</dt>
            <dd>{{ quote.insurance_tenure?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TENURE OF COVER</dt>
            <dd>{{ quote.number_of_years?.text }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ quote.gender }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS SMOKER</dt>
            <dd>{{ quote.is_smoker ? 'Yes' : 'No' }}</dd>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">OTHERS INFO</dt>
            <dd>{{ quote.others_info }}</dd>
            <dd></dd>
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
              <div>
                  <x-tooltip position="bottom">
                      <label class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700">
                          Parent Ref-ID
                      </label>
                      <template #tooltip> Parent Reference ID </template>
                  </x-tooltip>
              </div>
              <div>{{ quote.parent_duplicate_quote_id }}</div>
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
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PRICE</dt>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
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
                          :disabled="allowStatusUpdate"
                          placeholder="Lead Status"
                          class="w-full"
                      />
                      <x-textarea
                          v-model="leadStatusForm.notes"
                          type="text"
                          label="Notes"
                          placeholder="Lead Notes"
                          class="w-full"
                          :disabled="allowStatusUpdate"
                      />
                  </div>
              </div>
              <div class="w-full md:w-2/3">
                  <x-input
                      v-if="leadStatusForm.leadStatus == page.props.quoteStatusEnum.TransactionApproved"
                      v-model="leadStatusForm.trans_code"
                      label="TransApp Code"
                      placeholder="TransApp Code is required"
                      class="w-full"
                      :error="leadStatusForm.errors.trans_code"
                  />
                  <x-select
                      v-if="leadStatusForm.leadStatus == page.props.quoteStatusEnum.Lost"
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
          <div class="flex justify-end">
              <x-button
                  class="mt-4"
                  color="emerald"
                  size="sm"
                  :loading="leadStatusForm.processing"
                  @click.prevent="onLeadStatus"
                  :disabled="allowStatusUpdate"
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
        <template #item-advisor="{assignee}">
            {{ assignee?.name }}
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

    <customerAdditionalContacts quoteType="Life" :customerId="quote.customer_id" :quoteId="quote.id"  :contacts="customerAdditionalContacts" />

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
    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />
  </div>
</template>
