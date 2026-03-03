<script setup>
import { onMounted } from 'vue';

const props = defineProps({
  configurations: Object,
  nationalities: Array,
  quoteTypes: Array,
  filters: Object,
});

const params = useUrlSearchParams('history');

const loader = ref({
  table: false,
});

const isSearching = ref(false);
let isSearchOperation = false;

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

// Initialize filters directly from props with explicit type conversion
const filters = reactive({
  quote_type_id: props.filters?.quote_type_id
    ? +props.filters.quote_type_id
    : '',
  nationality_id: props.filters?.nationality_id
    ? +props.filters.nationality_id
    : '',
  created_at: props.filters?.created_at ?? '',
  created_at_end: props.filters?.created_at_end ?? '',
  status: props.filters?.status ?? '',
  should_skip_sic: props.filters?.should_skip_sic ?? '',
  page: 1,
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Quote Type', value: 'quote_type.text' },
  { text: 'Nationality', value: 'nationality.text' },
  { text: 'No. of Assigned Advisors', value: 'users' },
  { text: 'Should Skip SIC', value: 'should_skip_sic' },
  { text: 'Status', value: 'activated_at' },
  { text: 'Created Date', value: 'created_at' },
  { text: 'Actions', value: 'actions' },
];

const quoteTypeOptions = computed(() => {
  return props.quoteTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const sicOptions = [
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No' },
];

// Track Inertia events
onMounted(() => {
  router.on('start', () => {
    if (isSearchOperation) {
      isSearching.value = true;
    }
  });

  router.on('finish', () => {
    isSearching.value = false;
    isSearchOperation = false;
  });
});

function onSubmit(isValid) {
  filters.page = 1;
  isSearchOperation = true;

  // Format date values if needed
  if (filters.created_at) filters.created_at = filters.created_at.split('T')[0];
  if (filters.created_at_end)
    filters.created_at_end = filters.created_at_end.split('T')[0];

  router.visit(route('admin.nationality-allocation-config.index'), {
    method: 'get',
    data: filters,
    preserveState: true,
    preserveScroll: true,
    onBefore: () => {
      loader.table = true;
    },
    onFinish: () => {
      loader.table = false;
    },
  });
}

function onReset() {
  isSearchOperation = true;

  router.visit(route('admin.nationality-allocation-config.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const showDeleteModal = ref(false);
const deleteAction = useForm({
  id: null,
});

function confirmDelete(id) {
  deleteAction.id = id;
  showDeleteModal.value = true;
}

function onConfirmDelete() {
  deleteAction.delete(
    route('admin.nationality-allocation-config.destroy', deleteAction.id),
    {
      onFinish: () => {
        showDeleteModal.value = false;
      },
    },
  );
}
</script>

<template>
  <Head title="GBP Eligible Nationalities" />
  <div>
    <h2 class="text-xl font-semibold mb-1">GBP Eligible Nationalities</h2>
    <span class="text-sm">Select nationalities AND/OR predefined groups that qualify for GBP Routing</span>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <x-field label="Effective Date"></x-field>
        
    </div>
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <x-field label="Predefined Group Selection"></x-field>
        
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="GBP Nationality">
        <x-select
          v-model="filters.nationality_id"
          :options="[]"
          placeholder="Select Nationality"
          filterable
        />
      </x-field>
    </div>
    <div class="flex justify-end gap-3">
      <x-button
        size="sm"
        color="#ff5e00"
        type="submit"
        :loading="isSearching"
        :disabled="isSearching"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>
