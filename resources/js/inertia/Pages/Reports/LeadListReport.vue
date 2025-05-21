<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

const { isRequired, maxSelections } = useRules();

const filters = reactive({
  uuid: '',
  advisorAssignedDates: props.defaultFilters.advisorAssignedDates,
  tiers: [],
  leadSources: [],
  teams: [],
  is_ecommerce: '',
  payment_status: [],
  page: 1,
});

const leadSource = computed(() => {
  return Object.keys(props.defaultFilters.leadSource).map(key => ({
    value: key,
    label: props.defaultFilters.leadSource[key],
  }));
});

const teams = computed(() => {
  return Object.keys(props.defaultFilters.teams).map(key => ({
    value: key,
    label: props.defaultFilters.teams[key],
  }));
});

const tiers = computed(() => {
  return Object.keys(props.defaultFilters.tiers).map(key => ({
    value: key,
    label: props.defaultFilters.tiers[key],
  }));
});

const paymentStatus = computed(() => {
  return Object.keys(props.defaultFilters.paymentStatus).map(key => ({
    value: key,
    label: props.defaultFilters.paymentStatus[key],
  }));
});

const onSubmit = isValid => {
  if (!isValid) return;
  filters.page = 1;
  router.visit(route('lead-list-report'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('lead-list-report'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const tableHeader = reactive([
  {
    text: 'LEAD CODE',
    value: 'uuid',
  },
  {
    text: 'Name',
    value: 'first_name',
  },
  {
    text: 'Lead Source',
    value: 'source',
  },
  {
    text: 'Lead Status',
    value: 'quoteStatus',
  },
  {
    text: 'Payment Status',
    value: 'payment_status_id',
  },
  {
    text: 'IS ECOMMERCE',
    value: 'is_ecommerce',
  },
  {
    text: 'ASSIGNED TO',
    value: 'advisor',
  },
  {
    text: 'CREATED AT',
    value: 'created_at',
  },
  {
    text: 'LAST MODIFIED',
    value: 'updated_at',
  },
  {
    text: 'TIER',
    value: 'tier',
  },
  {
    text: 'RECEIVED FROM DEVICE',
    value: 'device',
  },
]);
</script>
<template>
  <Head title="Lead List Report" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Lead List Report
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-input
        label="Search"
        v-model="filters.uuid"
        type="text"
        class="w-full"
        placeholder="Search By Ref i.e CAR-12345678"
      />

      <DatePicker
        label="Advisor Assigned Date"
        required
        v-model="filters.advisorAssignedDates"
        placeholder="Select Start & End Date"
        range
        :max-range="92"
        size="sm"
        model-type="yyyy-MM-dd"
        :rules="[isRequired]"
      />
      <x-select
        label="Tiers"
        v-model="filters.tiers"
        placeholder="Search by Tiers"
        :options="tiers"
        multiple
        :rules="[maxSelections(3)]"
        helper="You can select up to 3 tiers"
        class="w-full"
        filterable
        filterPlaceholder="Filter Tiers...."
        truncate
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="filters.tiers = tiers.map(tier => tier.value)"
            @clear="filters.tiers = []"
          />
        </template>
      </x-select>
      <x-select
        label="Lead Source"
        v-model="filters.leadSources"
        placeholder="Search by Lead Source"
        virtualList
        :virtual-list-item-height="34"
        :virtual-list-overscan="10"
        :options="leadSource"
        multiple
        :rules="[maxSelections(3)]"
        class="w-full"
        filterable
        filterPlaceholder="Filter Lead Source...."
        helper="You can select up to 3 lead sources"
        truncate
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.leadSources = leadSource.map(
                leadSource => leadSource.value,
              )
            "
            @clear="filters.leadSources = []"
          />
        </template>
      </x-select>
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-select
        label="Teams"
        v-model="filters.teams"
        placeholder="Search By Teams"
        :options="teams"
        multiple
        :rules="[maxSelections(3)]"
        class="w-full"
        filterable
        filterPlaceholder="Filter Teams...."
        helper="You can select up to 3 teams"
        truncate
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="filters.teams = teams.map(team => team.value)"
            @clear="filters.teams = []"
          />
        </template>
      </x-select>

      <x-select
        label="Is Ecommerce"
        v-model="filters.is_ecommerce"
        placeholder="Search by Ecommerce"
        :options="[
          { value: 'All', label: 'All' },
          { value: 'Yes', label: 'Yes' },
          { value: 'No', label: 'No' },
        ]"
        class="w-full"
      />
      <x-select
        v-model="filters.payment_status"
        placeholder="Search By Payment Status"
        :options="paymentStatus"
        multiple
        :rules="[maxSelections(3)]"
        helper="You can select up to 3 payment status"
        class="w-full"
        filterable
        filterPlaceholder="Filter Payment Status...."
        truncate
        label="Payment Status"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              filters.payment_status = paymentStatus.map(
                paymentStatus => paymentStatus.value,
              )
            "
            @clear="filters.payment_status = []"
          />
        </template>
      </x-select>
    </div>
    <div class="flex gap-3 justify-end">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loaders.table"
    :headers="tableHeader"
    :items="props.reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-uuid="{ uuid }">
      <Link
        :href="route('car.show', uuid)"
        class="text-primary-500 hover:underline"
      >
        {{ uuid }}
      </Link>
    </template>
    <template #item-is_ecommerce="{ is_ecommerce }">
      <div class="text-center">
        <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
          {{ is_ecommerce ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  />
</template>
