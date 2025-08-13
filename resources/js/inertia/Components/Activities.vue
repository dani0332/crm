<script setup>
const props = defineProps({
  activities: Array,
  quote: Object,
  quoteType: String,
  modelType: String,
  advisors: Array,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  readOnlyMode: {
    type: Object,
    required: false,
    default: () => ({ isDisable: false }),
  },
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();

const activityActionEdit = ref(false);
const compareDueDate = useCompareDueDate;

const activityForm = useForm({
  entityUId: props.quote.uuid,
  entityId: props.quote.id,
  modelType: props.modelType,
  parentType: props.modelType || null,
  quoteType: props.quoteType,
  title: null,
  description: '',
  due_date: null,
  assignee_id: page.props?.auth?.user?.id,
  status: null,
  activity_id: null,
  uuid: null,
});

const modals = reactive({
  activity: false,
  activityConfirm: false,
});

const confirmDeleteData = reactive({
  activity: null,
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

const advisorOptions = computed(() => {
  return props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const addActivity = () => {
  activityForm.reset();
  modals.activity = true;
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
      quote_uuid: props.quote.uuid,
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
  activityForm.due_date = useformatDateTimeForPicker(data.due_date);
  activityForm.assignee_id = data.assignee_id;
  activityForm.status = data.status;
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
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
          <template #item-due_date="item">
            <template
              v-if="compareDueDate(item.due_date) && item.is_cold === 0"
            >
              <x-tooltip placement="top">
                <p
                  :class="
                    compareDueDate(item.due_date) && item.is_cold === 0
                      ? 'bg-error-300 rounded p-1'
                      : ''
                  "
                >
                  {{ item.due_date }}
                </p>
                <template #tooltip>
                  <span>Pending overdue Task, please complete immediately</span>
                </template>
              </x-tooltip>
            </template>
            <span v-else>{{ item.due_date }}</span>
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

          <template #item-assignee="{ assignee }">
            <span>{{ assignee.name }}</span>
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
                :disabled="item.status === 1"
                size="xs"
                color="error"
                outlined
                @click.prevent="activityDelete(item.id)"
                :key="item.user_id"
                v-if="
                  readOnlyMode.isDisable === true &&
                  item.user_id &&
                  item.user_id != null
                "
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
    persistent
    @submit="onActivitySubmit"
  >
    <div class="grid gap-4">
      <x-input
        v-model="activityForm.title"
        label="Title"
        :rules="[isRequired]"
        class="w-full"
        required
      />

      <x-textarea
        v-model="activityForm.description"
        label="Description"
        :adjust-to-text="false"
        class="w-full"
        :rules="[isRequired]"
        required
      />

      <x-select
        v-model="activityForm.assignee_id"
        label="Assignee"
        :options="advisorOptions"
        :rules="[isRequired]"
        placeholder="Select Assignee"
        class="w-full"
        required
      />

      <date-picker
        v-model="activityForm.due_date"
        label="Due Date"
        :rules="[isRequired]"
        class="w-full"
        withTime
        :timezone="'UTC'"
        required
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
</template>
