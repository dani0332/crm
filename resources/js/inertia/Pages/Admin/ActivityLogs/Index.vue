<script setup>
import { Head } from '@inertiajs/vue3';
import ActivityLogDetailModal from './ActivityLogDetailModal.vue';

const props = defineProps({
  activityLogs: Object,
  users: Array,
  eventOptions: Array,
  filters: Object,
});

const page = usePage();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const filters = reactive({
  user_id: props.filters?.user_id || '',
  event: props.filters?.event || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  page: 1,
});

const loader = reactive({
  table: false,
});

const modals = reactive({
  detailModal: false,
});

const selectedLog = ref(null);

const tableHeader = [
  { text: 'USER', value: 'causer.name' },
  { text: 'EVENT', value: 'event' },
  { text: 'DESCRIPTION', value: 'description' },
  { text: 'CREATED AT', value: 'created_at', sortable: true },
  { text: 'ACTION', value: 'action' },
];

const userOptions = computed(() => {
  return props.users.map(user => ({
    label: `${user.name} (${user.email})`,
    value: user.id,
  }));
});

const getEventTagColor = event => {
  if (!event) return 'secondary';

  const eventColors = {
    accessed: 'info',
    created: 'success',
    updated: 'primary',
    deleted: 'error',
  };

  return eventColors[event.toLowerCase()] || 'secondary';
};

const onReset = () => {
  filters.user_id = '';
  filters.event = '';
  filters.date_from = '';
  filters.date_to = '';
  filters.page = 1;

  router.visit(route('admin.activity-logs.index'), {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const onSubmit = isValid => {
  if (isValid) {
    filters.page = 1;

    // Include only provided filters (all optional)
    const searchFilters = {};
    if (filters.user_id) searchFilters.user_id = filters.user_id;
    if (filters.event) searchFilters.event = filters.event;
    if (filters.date_from) searchFilters.date_from = filters.date_from;
    if (filters.date_to) searchFilters.date_to = filters.date_to;

    router.visit(route('admin.activity-logs.index'), {
      method: 'get',
      data: searchFilters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

const showDetailModal = log => {
  // Check if log exists and has required data
  if (!log || !log.id) {
    console.error('Invalid log data:', log);
    return;
  }

  // Use data directly from the row - all relationships are already loaded
  // Ensure all fields are properly set
  selectedLog.value = {
    id: log.id,
    log_name: log.log_name,
    description: log.description,
    subject_type: log.subject_type,
    subject_id: log.subject_id,
    event: log.event,
    causer_type: log.causer_type,
    causer_id: log.causer_id,
    causer: log.causer,
    subject: log.subject,
    properties: log.properties,
    batch_uuid: log.batch_uuid,
    url: log.url,
    feature: log.feature,
    ip_address: log.ip_address,
    user_agent: log.user_agent,
    code: log.code,
    created_at: log.created_at,
    updated_at: log.updated_at,
  };
  modals.detailModal = true;
};

const closeDetailModal = () => {
  modals.detailModal = false;
  // Small delay to allow modal animation to complete
  setTimeout(() => {
    selectedLog.value = null;
  }, 300);
};

const hasFiltersApplied = computed(() => {
  // Always show data, filters are optional
  return true;
});
</script>

<template>
  <Head title="Activity Logs" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Activity Logs</h2>
  </div>
  <x-divider class="my-4" />

  <!-- Filters -->
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-select
        label="USER"
        :modelValue="filters.user_id"
        :options="userOptions"
        class="w-full"
        filterable
        placeholder="Select user (optional)"
        @update:modelValue="val => (filters.user_id = val)"
      />
      <x-select
        label="EVENT"
        :modelValue="filters.event"
        :options="props.eventOptions || []"
        class="w-full"
        filterable
        placeholder="Select event (optional)"
        @update:modelValue="val => (filters.event = val)"
      />
      <DatePicker
        v-model="filters.date_from"
        label="DATE FROM"
        placeholder="Select start date (optional)"
        size="sm"
        model-type="yyyy-MM-dd"
        class="w-full"
      />
      <DatePicker
        v-model="filters.date_to"
        label="DATE TO"
        placeholder="Select end date (optional)"
        size="sm"
        model-type="yyyy-MM-dd"
        class="w-full"
      />
    </div>
    <div class="flex justify-end gap-3 mt-4">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>

  <!-- Table -->
  <div class="mt-4">
    <DataTable
      table-class-name="mt-4"
      :loading="loader.table"
      :headers="tableHeader"
      :items="props.activityLogs?.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-causer.name="{ causer }">
        {{ causer?.name || 'System' }}
      </template>

      <template #item-event="{ event }">
        <x-tag size="xs" :color="getEventTagColor(event)">
          {{ event ? event.toUpperCase() : '-' }}
        </x-tag>
      </template>

      <template #item-description="{ description }">
        <div :title="description">
          {{ description || '-' }}
        </div>
      </template>

      <template #item-created_at="{ created_at }">
        {{ dateFormat(created_at) }}
      </template>

      <template #item-action="item">
        <x-button
          size="xs"
          color="primary"
          outlined
          @click.prevent="showDetailModal(item)"
        >
          View
        </x-button>
      </template>
    </DataTable>

    <Pagination
      v-if="props.activityLogs"
      :links="{
        next: props.activityLogs.next_page_url,
        prev: props.activityLogs.prev_page_url,
        current: props.activityLogs.current_page,
        from: props.activityLogs.from,
        to: props.activityLogs.to,
      }"
    />
  </div>

  <!-- Detail Modal -->
  <ActivityLogDetailModal
    v-model="modals.detailModal"
    :log="selectedLog"
    @close="closeDetailModal"
  />
</template>

