<script setup>
const props = defineProps({
  rules: Object,
});

const params = useUrlSearchParams('history');

const loader = ref({
  table: false,
});

function formatRuleUsers(users) {
  return users.map(user => user.name).join(', ');
}

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

const filters = reactive({
  name: '',
  created_at: '',
  created_at_end: '',
  page: 1,
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Rule Name', value: 'name' },
  { text: 'Rule Type', value: 'rule_type.name' },
  { text: 'Lead Source', value: 'lead_source.name' },
  { text: 'Rule Users', value: 'rule_users' },
  { text: 'Is Active', value: 'is_active' },
  { text: 'Created Date', value: 'created_at' },
  { text: 'Actions', value: 'actions' },
];

function onSubmit(isValid) {
  filters.page = 1;
  if (filters.created_at) filters.created_at = filters.created_at.split('T')[0];
  if (filters.created_at_end)
    filters.created_at_end = filters.created_at_end.split('T')[0];
  router.visit(route('rule.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('rule.index'), {
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
    deleteAction.delete(route('rule.destroy', deleteAction.id), {
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
  <Head title="Rules List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Rules List</h2>
    <div class="space-x-3">
      <Link :href="route('rule.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Rule </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
      <x-field label="Created Date Start">
        <DatePicker
          v-model="filters.created_at"
          placeholder="Created Date Start"
        />
      </x-field>
      <x-field label="Created Date End">
        <DatePicker
          v-model="filters.created_at_end"
          placeholder="Created Date End"
        />
      </x-field>
      <x-field label="Rule Name">
        <x-input v-model="filters.name" type="text" class="w-full" />
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
    :items="rules.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('rule.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>

    <template #item-created_at="{ created_at }">
      {{ dateFormat(created_at) }}
    </template>

    <template #item-rule_users="{ rule_users }">
      {{ formatRuleUsers(rule_users) }}
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
        <Link :href="route('rule.show', id)">
          <x-button tag="div" size="xs" outlined> View </x-button>
        </Link>
        <Link :href="route('rule.edit', id)">
          <x-button color="primary" size="xs" outlined> Edit </x-button>
        </Link>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: rules.next_page_url,
      prev: rules.prev_page_url,
      current: rules.current_page,
      from: rules.from,
      to: rules.to,
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
