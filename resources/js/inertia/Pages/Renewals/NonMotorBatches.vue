<script setup>
defineProps({
  batches: Object,
  lobs: Object,
  years: Object,
  months: Object,
});
const { isRequired } = useRules();
const page = usePage();

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const role = [rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.LifeManager];
const roleLeadPool = [rolesEnum.LeadPool];
const hasAnyRole = role => useHasAnyRole(role);

const filters = reactive({
  lob: '',
  year: '',
  batch: '',
  page: 1,
});

function onSubmit(isValid) {
  filters.page = 1;

  Object.keys(filters).forEach(
    key =>
      (filters[key] === '' || filters[key].length === 0) && delete filters[key],
  );

  // /personal-quotes/bike'
  router.visit(route('renewals-batches-nonmotor'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('renewals-batches'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Batch', value: 'renewal_batch' },
  { text: 'Actions', value: 'action', width: 300 },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const quoteTypesOptions = computed(() => {
  const quoteTypesOptions = [
    ...Object.keys(page.props.lobs).map(text => ({
      label: text,
      value: page.props.lobs[text],
    })),
  ];
  return quoteTypesOptions;
});

filters.lob = page.props.lobs.Home;

const yearsOptions = computed(() => {
  const yearsOptions = [
    ...Object.keys(page.props.years).map(text => ({
      label: text,
      value: page.props.years[text],
    })),
  ];
  return yearsOptions;
});

filters.year = new Date().getFullYear();

const monthsOptions = computed(() => {
  const monthsOptions = [
    ...Object.keys(page.props.months).map(text => ({
      label: text,
      value: page.props.months[text],
    })),
  ];
  return monthsOptions;
});

filters.month = new Date().getMonth() + 1;

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key] ?? value;
    } else {
      filters[key] = isNaN(parseInt(params[key]))
        ? params[key]
        : parseInt(params[key]);
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Batches" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Batches</h2>
      <div class="space-x-3 space-y-3"></div>
    </div>

    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-select
            v-model="filters.lob"
            label="Line of business"
            name="lob"
            :options="quoteTypesOptions"
            placeholder="Select line of business"
            required
            class="w-full"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <x-select
            v-model="filters.year"
            label="Year"
            name="year"
            :options="yearsOptions"
            placeholder="Select Year"
            required
            class="w-full"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <x-select
            v-model="filters.month"
            label="Month"
            name="month"
            :options="monthsOptions"
            placeholder="Select Month"
            required
            class="w-full"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <label
            class="font-medium text-gray-800 text-sm decoration-primary-600"
          >
            Renewal Batch
          </label>

          <x-input
            v-model="filters.batch"
            type="search"
            name="batch"
            class="w-full"
            placeholder="Search by Renewal Batch"
          />
        </div>

        <div class="flex justify-between gap-1 mb-4 mt-6">
          <div class="flex justify-self-end gap-3">
            <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
            <x-button size="sm" color="primary" @click.prevent="onReset">
              Reset
            </x-button>
          </div>
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="batches.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-renewal_batch="{ renewal_batch }">
        {{ renewal_batch?.name || batch }}
      </template>

      <template #item-action="{ renewal_batch, quote_type, renewal_batch_id }">
        <x-button
          :href="`/renewals/batches-non-motor/${renewal_batch_id}/${quote_type}/plans-processes`"
          class="text-primary-500 btn-passed mr-2"
          color="primary"
        >
          Fetch Plans
        </x-button>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: batches.next_page_url,
        prev: batches.prev_page_url,
        current: batches.current_page,
        from: batches.from,
        to: batches.to,
      }"
    />
  </div>
</template>
