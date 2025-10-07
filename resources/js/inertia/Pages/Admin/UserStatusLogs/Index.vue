<script setup>
const props = defineProps({
  statusLogs: Object,
  availableStatuses: Array,
  usersWithLogs: Array,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const { isRequired, maxDateRangeArray } = useRules();

const filters = reactive({
  user_id: '',
  status: '',
  date_range: [],
  page: 1,
});

const loader = reactive({
  table: false,
});

const tableHeader = [
  { text: 'USER', value: 'user.name' },
  { text: 'EMAIL', value: 'user.email' },
  { text: 'STATUS', value: 'status' },
  { text: 'STATUS CHANGED AT', value: 'status_changed_at' },
];

const statusOptions = computed(() => {
  return props.availableStatuses.map(status => ({
    label: status.label,
    value: status.value,
  }));
});

const userOptions = computed(() => {
  return props.usersWithLogs.map(user => ({
    label: `${user.name} (${user.email})`,
    value: user.id,
  }));
});

const hasRequiredFilters = computed(() => {
  return (
    filters.user_id && filters.date_range && filters.date_range.length === 2
  );
});

const onReset = () => {
  router.visit(route('admin.user-status-logs.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const onSubmit = isValid => {
  if (isValid) {
    if (
      !filters.user_id ||
      !filters.date_range ||
      filters.date_range.length !== 2
    ) {
      return;
    }

    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' ||
          filters[key] === null ||
          filters[key] === undefined ||
          (Array.isArray(filters[key]) && filters[key].length === 0)) &&
        delete filters[key],
    );

    router.visit(route('admin.user-status-logs.index'), {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

const getStatusTagColor = statusDisplay => {
  if (!statusDisplay) return 'error';

  const statusColors = {
    online: 'success',
    offline: 'error',
    unavailable: 'warning',
    sick: 'info',
    'on leave': 'primary',
    'manual offline': 'secondary',
  };

  return statusColors[statusDisplay.toLowerCase()] || 'secondary';
};

const updateFilter = (field, val) =>
  (filters[field] = !val || filters[field] === val ? null : val);
</script>
<template>
  <Head title="User Status Logs" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">User Status Logs</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <x-select
        label="USER"
        :modelValue="filters.user_id"
        :options="userOptions"
        class="w-full"
        filterable
        placeholder="Select user"
        clearable
        @update:modelValue="val => updateFilter('user_id', val)"
        :rules="[isRequired]"
        required
      />
      <x-select
        label="STATUS"
        :modelValue="filters.status"
        :options="statusOptions"
        class="w-full"
        filterable
        placeholder="Select status"
        clearable
        @update:modelValue="val => updateFilter('status', val)"
      />
      <DatePicker
        v-model="filters.date_range"
        label="DATE RANGE"
        placeholder="Select date range (max 7 days)"
        range
        size="sm"
        model-type="yyyy-MM-dd"
        class="w-full"
        :rules="[isRequired, maxDateRangeArray(7)]"
        required
      />
    </div>
    <div class="flex justify-end gap-3">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>

  <div>
    <DataTable
      table-class-name="mt-4"
      :loading="loader.table"
      :headers="tableHeader"
      :items="props.statusLogs.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-status="{ status_display }">
        <x-tag size="sm" :color="getStatusTagColor(status_display)">
          {{ status_display || 'Unknown' }}
        </x-tag>
      </template>
    </DataTable>
    <Pagination
      :links="{
        next: props.statusLogs.next_page_url,
        prev: props.statusLogs.prev_page_url,
        current: props.statusLogs.current_page,
        from: props.statusLogs.from,
        to: props.statusLogs.to,
      }"
    />
  </div>
</template>
