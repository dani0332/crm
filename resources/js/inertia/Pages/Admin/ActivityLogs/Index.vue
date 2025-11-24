<script setup>
const props = defineProps({
  activityLogs: Object,
  users: Array,
  logNames: Array,
  features: Array,
  events: Array,
  subjectTypes: Array,
  filters: Object,
});

const page = usePage();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const filters = reactive({
  user_id: props.filters?.user_id || '',
  log_name: props.filters?.log_name || '',
  feature: props.filters?.feature || '',
  event: props.filters?.event || '',
  subject_type: props.filters?.subject_type || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  code: props.filters?.code || '',
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
  { text: 'ID', value: 'id', sortable: true },
  { text: 'USER', value: 'causer.name' },
  { text: 'LOG NAME', value: 'log_name' },
  { text: 'FEATURE', value: 'feature' },
  { text: 'EVENT', value: 'event' },
  { text: 'DESCRIPTION', value: 'description' },
  { text: 'SUBJECT TYPE', value: 'subject_type' },
  { text: 'SUBJECT ID', value: 'subject_id' },
  { text: 'CREATED AT', value: 'created_at', sortable: true },
  { text: 'ACTION', value: 'action' },
];

const userOptions = computed(() => {
  return props.users.map(user => ({
    label: `${user.name} (${user.email})`,
    value: user.id,
  }));
});

const logNameOptions = computed(() => {
  return props.logNames.map(name => ({
    label: name,
    value: name,
  }));
});

const featureOptions = computed(() => {
  return props.features.map(feature => ({
    label: feature,
    value: feature,
  }));
});

const eventOptions = computed(() => {
  return props.events.map(event => ({
    label: event,
    value: event,
  }));
});

const subjectTypeOptions = computed(() => {
  return props.subjectTypes.map(type => ({
    label: type,
    value: type,
  }));
});

const getEventTagColor = event => {
  if (!event) return 'secondary';

  const eventColors = {
    created: 'success',
    updated: 'primary',
    deleted: 'error',
  };

  return eventColors[event.toLowerCase()] || 'secondary';
};

const onReset = () => {
  Object.keys(filters).forEach(key => {
    if (key !== 'page') {
      filters[key] = '';
    }
  });
  filters.page = 1;

  router.visit(route('admin.activity-logs.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const onSubmit = isValid => {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' ||
          filters[key] === null ||
          filters[key] === undefined ||
          (Array.isArray(filters[key]) && filters[key].length === 0)) &&
        delete filters[key],
    );

    router.visit(route('admin.activity-logs.index'), {
      method: 'get',
      data: filters,
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
    id: log.id || null,
    log_name: log.log_name || null,
    description: log.description || null,
    subject_type: log.subject_type || null,
    subject_id: log.subject_id || null,
    event: log.event || null,
    causer_type: log.causer_type || null,
    causer_id: log.causer_id || null,
    causer: log.causer || null,
    subject: log.subject || null,
    properties: log.properties || null,
    batch_uuid: log.batch_uuid || null,
    url: log.url || null,
    feature: log.feature || null,
    ip_address: log.ip_address || null,
    code: log.code || null,
    created_at: log.created_at || null,
    updated_at: log.updated_at || null,
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

const updateFilter = (field, val) =>
  (filters[field] = !val || filters[field] === val ? '' : val);

const formatProperties = properties => {
  if (!properties) return null;
  
  // Handle string properties (JSON string)
  if (typeof properties === 'string') {
    try {
      properties = JSON.parse(properties);
    } catch (e) {
      return null;
    }
  }
  
  // Check if properties have old and attributes structure
  if (properties.old && properties.attributes) {
    const formatted = [];
    const allKeys = new Set([
      ...Object.keys(properties.old || {}),
      ...Object.keys(properties.attributes || {}),
    ]);
    
    allKeys.forEach(key => {
      formatted.push({
        key,
        old: properties.old[key] ?? null,
        new: properties.attributes[key] ?? null,
      });
    });
    
    return formatted;
  }
  
  return null;
};

const formattedProperties = computed(() => {
  if (!selectedLog.value?.properties) return null;
  return formatProperties(selectedLog.value.properties);
});

const hasFiltersApplied = computed(() => {
  return !!(
    filters.user_id ||
    filters.log_name ||
    filters.feature ||
    filters.event ||
    filters.subject_type ||
    filters.date_from ||
    filters.date_to ||
    filters.code
  );
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
    <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <x-select
        label="USER"
        :modelValue="filters.user_id"
        :options="userOptions"
        class="w-full"
        filterable
        placeholder="Select user"
        clearable
        @update:modelValue="val => updateFilter('user_id', val)"
      />
      <x-select
        label="LOG NAME"
        :modelValue="filters.log_name"
        :options="logNameOptions"
        class="w-full"
        filterable
        placeholder="Select log name"
        clearable
        @update:modelValue="val => updateFilter('log_name', val)"
      />
      <x-select
        label="FEATURE"
        :modelValue="filters.feature"
        :options="featureOptions"
        class="w-full"
        filterable
        placeholder="Select feature"
        clearable
        @update:modelValue="val => updateFilter('feature', val)"
      />
      <x-select
        label="EVENT"
        :modelValue="filters.event"
        :options="eventOptions"
        class="w-full"
        filterable
        placeholder="Select event"
        clearable
        @update:modelValue="val => updateFilter('event', val)"
      />
      <x-select
        label="SUBJECT TYPE"
        :modelValue="filters.subject_type"
        :options="subjectTypeOptions"
        class="w-full"
        filterable
        placeholder="Select subject type"
        clearable
        @update:modelValue="val => updateFilter('subject_type', val)"
      />
      <x-input
        v-model="filters.code"
        type="text"
        label="CODE"
        placeholder="Enter code"
        class="w-full"
      />
      <DatePicker
        v-model="filters.date_from"
        label="DATE FROM"
        placeholder="Select start date"
        size="sm"
        model-type="yyyy-MM-dd"
        class="w-full"
      />
      <DatePicker
        v-model="filters.date_to"
        label="DATE TO"
        placeholder="Select end date"
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
    <div
      v-if="!hasFiltersApplied"
      class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center"
    >
      <div class="text-gray-500 text-lg mb-2">
        <i class="fa fa-filter text-4xl mb-4"></i>
      </div>
      <p class="text-gray-700 font-medium mb-1">No filters applied</p>
      <p class="text-gray-500 text-sm">
        Please apply at least one filter to view activity logs.
      </p>
    </div>
    <DataTable
      v-else
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
        <x-tag size="sm" :color="getEventTagColor(event)">
          {{ event ? event.toUpperCase() : '-' }}
        </x-tag>
      </template>

      <template #item-description="{ description }">
        <div class="max-w-md truncate" :title="description">
          {{ description || '-' }}
        </div>
      </template>

      <template #item-subject_type="{ subject_type }">
        <div class="max-w-xs truncate" :title="subject_type">
          {{ subject_type || '-' }}
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
          View Details
        </x-button>
      </template>
    </DataTable>

    <Pagination
      v-if="hasFiltersApplied && props.activityLogs"
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
  <x-modal
    v-model="modals.detailModal"
    :title="`Activity Log Details${selectedLog ? ' - ID: ' + selectedLog.id : ''}`"
    size="xl"
    backdrop
    show-close
    @close="closeDetailModal"
  >
    <template #default>
      <div v-if="!selectedLog" class="flex flex-col items-center justify-center py-12">
        <p class="text-gray-500">No data available</p>
      </div>
      <div v-else class="space-y-4">
        <!-- Basic Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold mb-3">Basic Information</h3>
          <dl class="grid md:grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="font-medium text-gray-700">ID:</dt>
              <dd class="text-gray-900">{{ selectedLog.id }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">User:</dt>
              <dd class="text-gray-900">
                {{ selectedLog.causer?.name || 'System' }}
                <span v-if="selectedLog.causer?.email" class="text-gray-500">
                  ({{ selectedLog.causer.email }})
                </span>
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Log Name:</dt>
              <dd class="text-gray-900">{{ selectedLog.log_name || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Feature:</dt>
              <dd class="text-gray-900">{{ selectedLog.feature || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Event:</dt>
              <dd>
                <x-tag size="sm" :color="getEventTagColor(selectedLog.event)">
                  {{ selectedLog.event ? selectedLog.event.toUpperCase() : '-' }}
                </x-tag>
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Code:</dt>
              <dd class="text-gray-900">{{ selectedLog.code || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">IP Address:</dt>
              <dd class="text-gray-900">{{ selectedLog.ip_address || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Created At:</dt>
              <dd class="text-gray-900">
                {{ dateFormat(selectedLog.created_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Description -->
        <div>
          <h3 class="text-lg font-semibold mb-2">Description</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded">
            {{ selectedLog.description || '-' }}
          </p>
        </div>

        <!-- Subject Information -->
        <div v-if="selectedLog.subject_type" class="bg-blue-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold mb-3">Subject Information</h3>
          <dl class="grid md:grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="font-medium text-gray-700">Subject Type:</dt>
              <dd class="text-gray-900">{{ selectedLog.subject_type }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Subject ID:</dt>
              <dd class="text-gray-900">{{ selectedLog.subject_id || '-' }}</dd>
            </div>
            <div v-if="selectedLog.subject" class="col-span-2">
              <dt class="font-medium text-gray-700 mb-2">Subject Data:</dt>
              <dd>
                <pre
                  class="bg-white p-3 rounded border text-xs overflow-auto max-h-40"
                >{{ JSON.stringify(selectedLog.subject, null, 2) }}</pre>
              </dd>
            </div>
          </dl>
        </div>

        <!-- URL -->
        <div v-if="selectedLog.url">
          <h3 class="text-lg font-semibold mb-2">URL</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded break-all">
            {{ selectedLog.url }}
          </p>
        </div>

        <!-- Properties -->
        <div v-if="selectedLog.properties">
          <h3 class="text-lg font-semibold mb-2">Properties</h3>
          <div v-if="formattedProperties" class="bg-gray-50 p-4 rounded border overflow-auto max-h-96">
            <div class="space-y-3">
              <div
                v-for="item in formattedProperties"
                :key="item.key"
                class="border-b border-gray-200 pb-3 last:border-b-0 last:pb-0"
              >
                <div class="font-semibold text-gray-800 mb-2 capitalize">
                  {{ item.key.replace(/_/g, ' ') }}
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                  <div>
                    <div class="text-xs font-medium text-gray-600 mb-1">Old Value:</div>
                    <div
                      class="bg-red-50 border border-red-200 rounded p-2 text-sm break-words"
                      :class="{
                        'text-gray-400 italic': item.old === null || item.old === '',
                      }"
                    >
                      {{ item.old === null || item.old === '' ? '(empty)' : item.old }}
                    </div>
                  </div>
                  <div>
                    <div class="text-xs font-medium text-gray-600 mb-1">New Value:</div>
                    <div
                      class="bg-green-50 border border-green-200 rounded p-2 text-sm break-words"
                      :class="{
                        'text-gray-400 italic': item.new === null || item.new === '',
                      }"
                    >
                      {{ item.new === null || item.new === '' ? '(empty)' : item.new }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="bg-gray-50 p-4 rounded border overflow-auto max-h-96">
            <pre class="text-xs">{{
              typeof selectedLog.properties === 'string'
                ? selectedLog.properties
                : JSON.stringify(selectedLog.properties, null, 2)
            }}</pre>
          </div>
        </div>

        <!-- Batch UUID -->
        <div v-if="selectedLog.batch_uuid">
          <h3 class="text-lg font-semibold mb-2">Batch UUID</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded font-mono text-sm">
            {{ selectedLog.batch_uuid }}
          </p>
        </div>
      </div>
    </template>
  </x-modal>
</template>

