<script setup>
// Component Props and State Initialization
const props = defineProps({
  activities: Object,
  advisors: Object,
  cannotUseAssignee: Boolean,
  totalActivities: Number,
});

// Vue Composition API
const page = usePage();
const selectedOption = ref('');
const customStartDate = ref(null);
const customEndDate = ref(null);
const activityLoader = ref(false);
const activityActionEdit = ref(false);
const isOverDue = ref(false);
const rules = {
  isRequired: v => !!v || 'This field is required',
};
const notification = useNotifications('toast');

let params = useUrlSearchParams('history');

const getLink = (quote_uuid, quote_type_id) =>
  buildCdbidLink(quote_uuid, quote_type_id);

const activityForm = useForm({
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props.auth.user.id,
  status: null,
  activity_id: null,
});
const modals = reactive({
  activity: false,
  activityConfirm: false,
});

const filters = reactive({
  assignee_id: '',
  status: '',
  due_date_time_start: '',
  due_date_time_end: '',
  page: 1,
  isCustom: false,
  redirect: false,
});

const loader = reactive({
  table: false,
  export: false,
});

const activityTable = [
  { text: 'REF ID', value: 'cdbid' },
  { text: 'Client Name', value: 'client_name' },
  { text: 'Lead Status', value: 'quote_status.text' },
  { text: 'Title', value: 'title' },
  { text: 'Followup Date', value: 'due_date' },
  { text: 'Assigned To', value: 'assignee.name' },
  { text: 'Done', value: 'status', width: 60, align: 'center' },
  { text: 'Action', value: 'action' },
];

// Filter Functions
function filterActivities(isValid) {
  if (!isValid) {
    return;
  }

  if (isOverDue.value) {
    if (filters.status == '1') {
      filters.due_date_end = '1/1/1970';
    }
  }

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit('/activities', {
    method: 'get',
    data: {
      ...filters,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

function resetFilters() {
  router.visit('/activities', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key] ?? value;
    } else {
      filters[key] = isNaN(parseInt(params[key]))
        ? params[key]
        : parseInt(params[key]);
    }
  }

  if (filters.redirect) {
    resetDates('tweek');
  } else {
    resetDates('today');
  }
}

function resetDates(option) {
  const today = new Date();
  let startDate, endDate;
  if (isOverDue.value) {
    filters.status = '';
  }
  isOverDue.value = false;
  selectedOption.value = option;
  if (option == 'today') {
    startDate = endDate = useDateFormat(today, 'DD-MM-YYYY');
  } else if (option == 'tomorrow') {
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    startDate = endDate = useDateFormat(tomorrow, 'DD-MM-YYYY');
  } else if (option == 'tweek') {
    const firstDayOfWeek = new Date(
      today.setDate(today.getDate() - today.getDay() + 1),
    );
    const lastDayOfWeek = new Date(
      today.setDate(today.getDate() - today.getDay() + 7),
    );
    startDate = useDateFormat(firstDayOfWeek, 'DD-MM-YYYY');
    endDate = useDateFormat(lastDayOfWeek, 'DD-MM-YYYY');
  } else if (option == 'tmonth') {
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(
      today.getFullYear(),
      today.getMonth() + 1,
      0,
    );
    startDate = useDateFormat(firstDayOfMonth, 'DD-MM-YYYY');
    endDate = useDateFormat(lastDayOfMonth, 'DD-MM-YYYY');
  } else if (option == 'overdue') {
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    filters.status = '0';
    isOverDue.value = false;

    startDate = useDateFormat('01/01/1970', 'DD-MM-YYYY');
    endDate = useDateFormat(yesterday, 'DD-MM-YYYY');
  } else if (option === 'custom') {
    // Handle the custom option by setting the custom start and end dates
    selectedOption.value = option;
    customStartDate.value = null; // Clear previously selected dates
    customEndDate.value = null;
    filters.isCustom = true;
  }
  if (option != 'custom') {
    filters.due_date_time_start = startDate.value;
    filters.due_date_time_end = endDate.value;

    filterActivities(1); // Call the filterActivities function
  }
}
function applyCustomDates() {
  if (customStartDate.value && customEndDate.value) {
    filters.due_date_time_start = customStartDate.value;
    filters.due_date_time_end = customEndDate.value;
    filterActivities(1); // Call the filterActivities function
  }
}

// CRUD Functions
const confirmDelete = id => {
  modals.activityConfirm = true;
  deleteData.activity_id = id;
};
const deleteData = reactive({
  activity_id: null,
});
const onDeleteConfirmation = () => {
  router.delete(`/activities/v2/${deleteData.activity_id}/`, {
    preserveScroll: true,
    onSuccess: () => {
      modals.activityConfirm = false;
      notification.success({
        title: 'Activity Deleted',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.activityConfirm = false;
    },
  });
};
const onStatusUpdate = id => {
  activityLoader.value = true;
  activityForm.activity_id = id;
  activityForm.patch(`/activities/v2/${id}/update-status`, {
    preserveScroll: true,

    onSuccess: () => {
      activityLoader.value = false;
      notification.success({
        title: 'Lead Activity Done',
        position: 'top',
      });
    },
  });
};
const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};
const onEdit = activity => {
  activityActionEdit.value = true;
  modals.activity = true;
  activityForm.activity_id = activity.id;
  activityForm.title = activity.title;
  activityForm.description = activity.description;
  activityForm.due_date = activity.due_date
    ? activity.due_date.split(' ')[0].split('-').reverse().join('-') +
      'T' +
      activity.due_date.split(' ')[1]
    : null;
  activityForm.assignee_id = activity.assignee_id;
};
const onSubmit = isValid => {
  if (!isValid) return;
  let url = `/activities/v2/`;
  let method = `post`;
  if (activityActionEdit.value) {
    url += activityForm.activity_id;
    method = 'patch';
  }
  activityForm.submit(method, url, {
    preserveScroll: true,
    onSuccess: () => {
      activityForm.reset();
      notification.success({
        title: 'Activity saved',
        position: 'top',
      });
    },
    onError: errors => {
      //console.error('Form submission error:', errors);
      notification.error({
        title: 'Activity save error',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.activity = false;
    },
  });
};

// Component hooks
watch(() => filters, { deep: true, immediate: true });

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Activities" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Search Activity</h2>
      <div class="space-x-3">
        <x-button size="sm" color="orange" @click.prevent="addActivity">
          Create Activity
        </x-button>
      </div>
    </div>

    <x-divider class="my-4" />

    <x-form @submit="filterActivities" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
        <ComboBox
          v-model="filters.assignee_id"
          label="Assigned To"
          placeholder="Select Assigned To"
          :disabled="cannotUseAssignee"
          :options="[
            { value: null, label: 'All' },
            // Loop through advisorArray to generate options
            ...advisors.map(advisor => ({
              value: advisor.id,
              label: advisor.name,
            })),
          ]"
          :single="true"
        />
        <ComboBox
          v-model="filters.status"
          label="Status"
          placeholder="Select Activity Status"
          :options="[
            { value: '1', label: 'Done' },
            { value: '0', label: 'Pending' },
          ]"
          :single="true"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>
    <x-divider class="my-2" />
    <div class="flex justify-between items-center mb-4 mt-1">
      <h2 class="text-left text-xl font-bold">Activities</h2>
      <div class="flex gap-3">
        <x-button
          size="sm"
          :color="selectedOption === 'overdue' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('overdue')"
        >
          Overdue
        </x-button>
        <x-button
          size="sm"
          :color="selectedOption === 'today' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('today')"
        >
          Today
        </x-button>
        <x-button
          size="sm"
          :color="selectedOption === 'tomorrow' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('tomorrow')"
        >
          Tomorrow
        </x-button>
        <x-button
          size="sm"
          :color="selectedOption === 'tweek' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('tweek')"
        >
          This Week
        </x-button>
        <x-button
          size="sm"
          :color="selectedOption === 'tmonth' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('tmonth')"
        >
          This Month
        </x-button>
        <x-button
          size="sm"
          :color="selectedOption === 'custom' ? 'primary' : 'secondary'"
          @click.prevent="resetDates('custom')"
        >
          Custom
        </x-button>
      </div>
    </div>
    <!-- Custom Date Range Picker -->
    <div v-if="selectedOption === 'custom'">
      <div class="flex gap-3">
        <x-input
          v-model="customStartDate"
          label="Start Date"
          type="datetime-local"
          class="w-full"
        />
        <x-input
          v-model="customEndDate"
          label="End Date"
          type="datetime-local"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex gap-3">
          <x-button size="sm" color="primary" @click="applyCustomDates"
            >Apply</x-button
          >
        </div>
      </div>
      <x-divider class="my-2" />
    </div>
    <p class="text-md font-semibold text-gray-700">
      Total Activities: {{ totalActivities }}
    </p>
    <x-divider class="my-2" />
    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="activityTable"
      :items="activities.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-title="{ title }">
        <div class="w-40 whitespace-normal">{{ title }}</div>
      </template>

      <template #item-client_name="{ client_name }">
        <div class="w-32 whitespace-normal">{{ client_name }}</div>
      </template>

      <template #item-cdbid="item">
        <SanitizeHtml
          v-if="(item.quote_uuid, item.quote_type_id)"
          :html="getLink(item.quote_uuid, item.quote_type_id)"
          class="text-primary-500 hover:underline"
          :key="item.quote_uuid"
        />
      </template>

      <template #item-due_date="item">
        <td
          :class="{
            'bg-red-500': item.is_overdue === true,
            rounded: item.is_overdue === true,
            'flex items-center justify-center w-full h-full': true,
          }"
        >
          {{ item.due_date }}
        </td>
      </template>

      <template #item-status="{ status, id }">
        <x-checkbox
          :key="id"
          color="emerald"
          size="xl"
          :modelValue="status === 1"
          :disabled="status === 1"
          @change="onStatusUpdate(id)"
          :loading="activityLoader"
        />
      </template>
      <template #item-action="item">
        <div class="space-x-4">
          <x-button
            size="xs"
            color="primary"
            outlined
            :disabled="item.status === 1"
            @click.prevent="onEdit(item)"
          >
            Edit
          </x-button>

          <x-button
            v-if="item.user_id && item.user_id != null"
            size="xs"
            color="error"
            :disabled="item.status === 1"
            outlined
            @click.prevent="confirmDelete(item.id)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>
    <Pagination
      :links="{
        next: activities.next_page_url,
        prev: activities.prev_page_url,
        current: activities.current_page,
        from: activities.from,
        to: activities.to,
      }"
    />

    <x-modal
      v-model="modals.activity"
      size="lg"
      :title="`${activityActionEdit ? 'Edit' : 'Add'} Activity`"
      show-close
      backdrop
      is-form
      @submit="onSubmit"
    >
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
        <div v-if="activityActionEdit">
          <x-select
            v-model="activityForm.assignee_id"
            label="Assignee*"
            :options="[
              ...advisors.map(advisor => ({
                value: advisor.id,
                label: advisor.name,
              })),
            ]"
            :rules="[rules.isRequired]"
            :disabled="cannotUseAssignee"
            placeholder="Select Assignee"
            class="w-full"
          />
        </div>
        <x-input
          v-model="activityForm.due_date"
          label="Due Date*"
          type="datetime-local"
          :rules="[rules.isRequired]"
          class="w-full"
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
            @click.prevent="onDeleteConfirmation"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
