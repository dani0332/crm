<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  requests: Object,
  filters: Object,
  users: Array,
});

const page = usePage();
const params = useUrlSearchParams('history');
const notification = useToast();
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const filters = reactive({
  user_id: props.filters?.user_id || '',
  quote_type: props.filters?.quote_type || '',
  status: props.filters?.status || '',
  request_type: props.filters?.request_type || '',
  segment: props.filters?.segment || '',
  date: props.filters?.date || null,
  page: 1,
});

const loader = reactive({
  table: false,
});

const expireModal = ref(false);
const selectedRequest = ref(null);

const tableHeader = reactive([
  { text: 'ID', value: 'id', width: 70 },
  { text: 'User', value: 'user.name', width: 200 },
  { text: 'LOB', value: 'quote_type.code', width: 100 },
  { text: 'Count', value: 'requested_count', width: 100 },
  { text: 'Total Cost', value: 'cost_per_lead', width: 120 },
  { text: 'Details', value: 'department.name', width: 140 },
  { text: 'Status', value: 'status_label', width: 100 },
  { text: 'Requested At', value: 'created_at', width: 150 },
  { text: 'Actions', value: 'actions', width: 100 },
]);

const lobOptions = computed(() => [
  { label: 'Car', value: 'Car' },
  { label: 'Health', value: 'Health' },
  { label: 'Car Revival', value: 'CAR_CAT_A' },
]);

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Completed', value: 'completed' },
  { label: 'Processing', value: 'processing' },
  { label: 'Expired', value: 'expired' },
];

const requestTypeOptions = [
  { label: 'Value', value: 'value' },
  { label: 'Volume', value: 'volume' },
];

const onReset = () => {
  router.visit(route('admin.buy-leads.requests.index'), {
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

    const data = { ...filters };
    Object.keys(data).forEach(
      key => (data[key] === '' || data[key] === null) && delete data[key],
    );

    router.visit(route('admin.buy-leads.requests.index'), {
      method: 'get',
      data,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

const showExpireModal = request => {
  selectedRequest.value = request;
  expireModal.value = true;
};

const expireRequest = async () => {
  if (!selectedRequest.value) return;

  loader.table = true;
  try {
    const response = await axios.post(
      route('admin.buy-leads.requests.expire', selectedRequest.value.id),
    );

    notification.success({
      title: response.data.message || 'Request expired successfully',
      position: 'top',
    });

    expireModal.value = false;
    selectedRequest.value = null;

    router.reload({
      preserveScroll: true,
      preserveState: true,
    });
  } catch (error) {
    notification.error({
      title: error.response?.data?.message || 'Failed to expire request',
      position: 'top',
    });
  } finally {
    loader.table = false;
  }
};

const getStatusColor = status => {
  const colors = {
    active: 'success',
    completed: 'primary',
    processing: 'warning',
    expired: 'error',
  };
  return colors[status] || 'secondary';
};

const getTypeColor = type => {
  return type === 'value' ? 'primary' : 'secondary';
};

const calculateTotalCost = request => {
  return Math.round(request.requested_count * request.cost_per_lead);
};

const capitalizeFirstLetter = string => {
  if (!string) return '';
  return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
};
</script>

<template>
  <Head title="Buy Lead Requests" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Buy Lead Requests</h2>
  </div>
  <x-divider class="my-4" />

  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
      <x-select
        label="User"
        v-model="filters.user_id"
        :options="props.users"
        class="w-full"
        filterable
        placeholder="Search and select user"
        clearable
      />
      <x-select
        label="Line of Business"
        v-model="filters.quote_type"
        :options="lobOptions"
        class="w-full"
        filterable
        placeholder="Select LOB"
        clearable
      />
      <x-select
        label="Status"
        v-model="filters.status"
        :options="statusOptions"
        class="w-full"
        filterable
        placeholder="Select Status"
        clearable
      />
      <x-select
        label="Request Type"
        v-model="filters.request_type"
        :options="requestTypeOptions"
        class="w-full"
        placeholder="Select Type"
        clearable
      />
      <DatePicker
        label="Date Range"
        v-model="filters.date"
        range
        placeholder="Select Date Range"
        format="dd-MM-yyyy"
        class="w-full"
      />
    </div>
    <div class="flex justify-end gap-3 mt-4">
      <x-button size="sm" color="#ff5e00" type="submit" :loading="loader.table">
        Search
      </x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>

  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.requests.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <span class="font-mono text-sm">{{ id }}</span>
    </template>

    <template #item-user.name="item">
      <div class="flex flex-col py-1">
        <span class="font-medium text-gray-900">{{
          item.user?.name || 'N/A'
        }}</span>
        <span class="text-xs text-gray-500 mt-0.5">
          {{ item.user?.email || 'No email' }}
        </span>
      </div>
    </template>

    <template #item-quote_type.code="item">
      <x-tag color="primary" size="sm">
        {{
          item.source === 'REVIVAL'
            ? 'Car Revival'
            : item.quote_type?.code || 'N/A'
        }}
      </x-tag>
    </template>

    <template #item-department.name="item">
      <div class="flex flex-wrap gap-1 py-1">
        <x-tag color="primary" size="sm" class="!text-[10px] !px-1.5 !py-0.5">
          {{ item.department?.name || 'N/A' }}
        </x-tag>
        <x-tag
          :color="getTypeColor(item.request_type)"
          size="sm"
          class="!text-[10px] !px-1.5 !py-0.5"
        >
          {{
            item.request_type
              ? item.request_type.charAt(0).toUpperCase() +
                item.request_type.slice(1).toLowerCase()
              : 'N/A'
          }}
        </x-tag>
        <x-tag color="secondary" size="sm" class="!text-[10px] !px-1.5 !py-0.5">
          {{ item.segment_label || 'N/A' }}
        </x-tag>
      </div>
    </template>

    <template #item-requested_count="item">
      <div class="flex flex-col py-1">
        <span class="text-xs text-gray-900 font-medium">
          {{ item.allocated_count }} / {{ item.requested_count }}
        </span>
      </div>
    </template>

    <template #item-cost_per_lead="item">
      <div class="flex flex-col py-1">
        <span class="text-xs text-gray-900 font-medium">
          {{ calculateTotalCost(item) }} AED
        </span>
      </div>
    </template>

    <template #item-status_label="{ status_label }">
      <x-tag
        :color="getStatusColor(status_label)"
        size="sm"
        class="!text-[10px] !px-1.5 !py-0.5"
      >
        {{ status_label?.toUpperCase() || 'N/A' }}
      </x-tag>
    </template>

    <template #item-created_at="{ created_at }">
      <span class="text-xs text-gray-600 whitespace-nowrap">
        {{ created_at ? dateFormat(created_at) : 'N/A' }}
      </span>
    </template>

    <template #item-actions="item">
      <x-button
        v-if="item.can_be_expired"
        size="xs"
        color="error"
        @click.prevent="showExpireModal(item)"
      >
        Expire
      </x-button>
      <span v-else class="text-gray-400 text-xs">-</span>
    </template>
  </DataTable>

  <Pagination
    :links="{
      next: props.requests.next_page_url,
      prev: props.requests.prev_page_url,
      current: props.requests.current_page,
      from: props.requests.from,
      to: props.requests.to,
    }"
  />

  <x-modal
    v-model="expireModal"
    title="Expire Buy Lead Request"
    show-close
    backdrop
    size="md"
  >
    <div class="space-y-4 pb-4">
      <p class="text-gray-700 text-base">
        Are you sure you want to expire this buy lead request?
      </p>
      <div class="bg-gray-50 p-4 rounded-lg space-y-3" v-if="selectedRequest">
        <div class="flex justify-between items-center">
          <span class="font-medium text-gray-700">User:</span>
          <span class="text-gray-900">{{ selectedRequest.user?.name }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="font-medium text-gray-700">LOB:</span>
          <span class="text-gray-900">{{
            selectedRequest.quote_type?.code
          }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="font-medium text-gray-700"
            >Count (Allocated/Requested):</span
          >
          <span class="text-gray-900 font-semibold"
            >{{ selectedRequest.allocated_count }} /
            {{ selectedRequest.requested_count }}</span
          >
        </div>
        <div class="flex justify-between items-center">
          <span class="font-medium text-gray-700">Status:</span>
          <x-tag
            :color="getStatusColor(selectedRequest.status_label)"
            size="sm"
          >
            {{ selectedRequest.status_label?.toUpperCase() }}
          </x-tag>
        </div>
      </div>
      <div class="bg-red-50 border border-red-200 rounded-lg p-3">
        <p class="text-sm text-red-700">
          <strong>Warning:</strong> This action will set the request status to
          expired and set the expiry date to now. The user will no longer
          receive leads from this request.
        </p>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-3 pt-4 px-6 pb-4 border-t">
        <x-button
          size="md"
          color="primary"
          @click.prevent="expireModal = false"
        >
          Cancel
        </x-button>
        <x-button
          size="md"
          color="error"
          @click.prevent="expireRequest"
          :loading="loader.table"
        >
          Expire Request
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
