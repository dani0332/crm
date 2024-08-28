<script setup>
const props = defineProps({
  tiers: Object,
});

const params = useUrlSearchParams('history');

const loader = ref({
  table: false,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

const filters = reactive({
  name: '',
  min_price: '',
  max_price: '',
  created_at: '',
  created_at_end: '',
  page: 1,
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Created Date', value: 'created_at' },
  { text: 'Tier Name', value: 'name' },
  { text: 'Min.price', value: 'min_price' },
  { text: 'Max.price', value: 'max_price' },
  { text: 'Cost Per Lead', value: 'cost_per_lead' },
  { text: 'Is Ecommerce', value: 'can_handle_ecommerce' },
  { text: 'Null Value?', value: 'can_handle_null_value' },
  { text: 'Is TPL?', value: 'can_handle_tpl' },
  { text: 'Renewal (TPL_RENEWALS)?', value: 'is_tpl_renewals' },
  { text: 'IsActive', value: 'is_active' },
  { text: 'Actions', value: 'actions' },
];

function onSubmit(isValid) {
  filters.page = 1;
  if (filters.created_at) filters.created_at = filters.created_at.split('T')[0];
  if (filters.created_at_end)
    filters.created_at_end = filters.created_at_end.split('T')[0];
  router.visit(route('tiers.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('tiers.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const showDeleteModal = ref(false),
  deleteAction = useForm({
    id: null,
  }),
  onConfirmDelete = () => {
    deleteAction.delete(route('tiers.destroy', deleteAction.id), {
      onFinish: () => {
        showDeleteModal.value = false;
      },
    });
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
</script>
<template>
  <Head title="Tier List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Tier List</h2>
    <div class="space-x-3">
      <Link :href="route('tiers.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Tier </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
      <x-field label="Created Start Date">
        <DatePicker
          v-model="filters.created_at"
          placeholder="Created Start Date"
        />
      </x-field>
      <x-field label="Created End Date">
        <DatePicker
          v-model="filters.created_at_end"
          placeholder="Created End Date"
        />
      </x-field>
      <x-field label="Tire Name">
        <x-input v-model="filters.name" type="text" class="w-full" />
      </x-field>
      <x-field label="Min Price">
        <x-input v-model="filters.min_price" type="number" class="w-full" />
      </x-field>
      <x-field label="Max Price">
        <x-input v-model="filters.max_price" type="number" class="w-full" />
      </x-field>
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
    :headers="tableHeader"
    :items="tiers.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('tiers.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>

    <template #item-created_at="{ created_at }">
      {{ dateFormat(created_at) }}
    </template>

    <template #item-can_handle_ecommerce="{ can_handle_ecommerce }">
      <div class="text-center">
        <x-tag size="sm" :color="can_handle_ecommerce ? 'success' : 'error'">
          {{ can_handle_ecommerce ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
    <template #item-can_handle_tpl="{ can_handle_tpl }">
      <div class="text-center">
        <x-tag size="sm" :color="can_handle_tpl ? 'success' : 'error'">
          {{ can_handle_tpl ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
    <template #item-can_handle_null_value="{ can_handle_null_value }">
      <div class="text-center">
        <x-tag size="sm" :color="can_handle_null_value ? 'success' : 'error'">
          {{ can_handle_null_value ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
    <template #item-is_tpl_renewals="{ is_tpl_renewals }">
      <div class="text-center">
        <x-tag size="sm" :color="is_tpl_renewals ? 'success' : 'error'">
          {{ is_tpl_renewals ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>

    <template #item-is_active="{ is_active }">
      <div class="text-center">
        <x-tag size="sm" :color="is_active ? 'success' : 'error'">
          {{ is_active ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>

    <template #item-actions="{ id }">
      <div class="flex gap-1.5 justify-end">
        <Link :href="route('tiers.show', id)">
          <x-button tag="div" size="xs" outlined> View </x-button>
        </Link>
        <Link :href="route('tiers.edit', id)">
          <x-button color="primary" size="xs" outlined> Edit </x-button>
        </Link>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: tiers.next_page_url,
      prev: tiers.prev_page_url,
      current: tiers.current_page,
      from: tiers.from,
      to: tiers.to,
    }"
  />

  <x-modal
    v-model="showDeleteModal"
    size="md"
    title="Delete Resource"
    show-close
    backdrop
  >
    <p>Are you sure you want to delete selected resource?</p>
    <template #actions>
      <div class="text-right space-x-4">
        <x-button size="sm" ghost @click.prevent="showDeleteModal = false">
          Cancel
        </x-button>
        <x-button
          size="sm"
          color="error"
          :loading="deleteAction.processing"
          @click.prevent="onConfirmDelete"
        >
          Delete
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
