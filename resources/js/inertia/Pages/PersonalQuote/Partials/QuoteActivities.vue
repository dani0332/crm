<script setup>
defineProps({
  quote: Object,
  activities: Array,
  can: Object,
  advisors: Object,
  quoteType: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const notification = useNotifications('toast');
const compareDueDate = useCompareDueDate;

const page = usePage();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const activityLoader = ref(false);
const modals = reactive({
  activity: false,
  activityConfirm: false,
});

const activityActionEdit = ref(false);

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.roles[0].name
      ? advisor.name + ' - ' + advisor.roles[0]?.name
      : advisor.name,
  }));
});

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
  uuid: page.props.quote.uuid,
  quote_id: page.props.quote.id,
  quote_type: page.props.quoteType,
  title: null,
  description: null,
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
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
    onFinish: () => {
      modals.activity = false;
    },
  });
};

const confirmDelete = id => {
  modals.activityConfirm = true;
  deleteData.activity_id = id;
};

const deleteData = reactive({
  activity_id: null,
});

const onDeleteConfirmation = () => {
  router.delete(`/activities/v2/${deleteData.activity_id}/`, {
    quote_uuid: page.props.quote.uuid,
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
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            New Lead Activity
            <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="mb-4 flex justify-end">
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
          <template #item-assignee="item">
            {{ item?.assignee?.name || item?.assignee }}
          </template>
          <template #item-status="{ status, id }">
            <x-checkbox
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
                v-if="readOnlyMode.isDisable === true"
              >
                Edit
              </x-button>

              <x-button
                size="xs"
                color="error"
                :disabled="item.status === 1"
                outlined
                @click.prevent="confirmDelete(item.id)"
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
    <x-modal
      v-model="modals.activity"
      size="lg"
      :title="`${activityActionEdit ? 'Edit' : 'Add'} Lead Activity`"
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
          label="Description*"
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
