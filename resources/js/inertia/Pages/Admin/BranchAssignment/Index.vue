<script setup>
console.log('hello');
const props = defineProps({
  branchAssignments: Object,
  branches: Object,
  advisorList: Object,
});
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const params = useUrlSearchParams('history');

const tableHeader = ref([
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'Current Branches', value: 'current_branches' },
  { text: 'Primary Branch', value: 'primary_branch' },
  { text: 'Effective From', value: 'effective_from' },
  { text: 'Effective To', value: 'effective_to' },
  { text: 'Manage Branches', value: 'actions' },
]);

const loader = reactive({
  table: false,
});

const filters = reactive({
  advisors: '',
  primary_branch: '',
  page: 1,
});

const onReset = () => {
  router.visit(route('branch-assignments.index'), {
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
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('branch-assignments.index'), {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});

const advisorOptions = computed(() => {
  return props.advisorList.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

const branchOptions = computed(() => {
  return props.branches.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

</script>
<template>
  <Head title="Advisor Branch Assignments" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Advisor Branch Assignments</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
    <div class="gap-4">
      <x-select label="Advisor"
      class="w-full"
      multiple
      v-model="filters.advisors"
      filterable
      truncate
      :options="advisorOptions"
      placeholder="Select Advisor"
      />
    </div>
    <div class="gap-4">
      <x-select
        label="Primary Branch"
        class="w-full"
        v-model="filters.primary_branch"
        :options="[{ value: '', label: 'All Branches' }, ...branchOptions]"
        placeholder="Select Branch"
      />
    </div>
    </div>
    <div class="flex justify-end gap-3">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.branchAssignments.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('branch-assignments.show', id)"
      >
        {{ id }}
      </Link>
    </template>
    <template #item-effective_from="{ effective_from }">
      {{ effective_from ? dateFormat(effective_from) : '' }}
    </template>
    <template #item-effective_to="{ effective_to }">
      {{ effective_to ? dateFormat(effective_to) : '' }}
    </template>
    <template #item-actions="{ id }">
    <div class="flex gap-2">
      <Link :href="route('branch-assignments.create', id)">
        <x-button size="sm" color="#1d83bc" tag="div">
          Add Branch
        </x-button>
      </Link>
    </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.branchAssignments.next_page_url,
      prev: props.branchAssignments.prev_page_url,
      current: props.branchAssignments.current_page,
      from: props.branchAssignments.from,
      to: props.branchAssignments.to,
    }"
  />
</template>
