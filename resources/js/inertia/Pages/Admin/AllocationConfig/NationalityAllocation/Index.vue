<script setup>
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

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

const filters = reactive({
  quote_type_id: props.filters?.quote_type_id || '',
  nationality_id: props.filters?.nationality_id || '',
  created_at: props.filters?.created_at || '',
  created_at_end: props.filters?.created_at_end || '',
  page: 1,
});

const tableHeader = [
  { text: 'ID', value: 'id' },
  { text: 'Quote Type', value: 'quote_type.text' },
  { text: 'Nationality', value: 'nationality.text' },
  { text: 'No. of Assigned Users', value: 'users' },
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

function onSubmit(isValid) {
  filters.page = 1;
  if (filters.created_at) filters.created_at = filters.created_at.split('T')[0];
  if (filters.created_at_end)
    filters.created_at_end = filters.created_at_end.split('T')[0];

  router.visit(route('admin.nationality-allocation-config.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
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

function formatUsers(users) {
  return users.map(user => user.name).join(', ');
}

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

// Define a set of good background colors for avatars
const avatarColors = [
  '#1e88e5', // Blue
  '#43a047', // Green
  '#e53935', // Red
  '#5e35b1', // Deep Purple
  '#fb8c00', // Orange
  '#00897b', // Teal
  '#d81b60', // Pink
  '#8e24aa', // Purple
  '#546e7a', // Blue Grey
  '#f4511e', // Deep Orange
];

function getAvatarColor(name) {
  // Generate a consistent hash from the name
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }

  // Use the hash to pick a color from our predefined set
  const index = Math.abs(hash) % avatarColors.length;
  return avatarColors[index];
}

function getUserInitials(name) {
  if (!name) return '?';

  const nameParts = name.split(' ').filter(part => part.length > 0);
  if (nameParts.length === 0) return '?';

  if (nameParts.length === 1) {
    return nameParts[0].charAt(0).toUpperCase();
  }

  return (
    nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)
  ).toUpperCase();
}
</script>

<template>
  <Head title="Nationality Allocation Configurations" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Nationality Allocation Configurations</h2>
    <div class="space-x-3">
      <Link :href="route('admin.nationality-allocation-config.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Create Configuration
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Quote Type">
        <x-select
          v-model="filters.quote_type_id"
          :options="quoteTypeOptions"
          placeholder="Select Quote Type"
          filterable
        />
      </x-field>
      <x-field label="Nationality">
        <x-select
          v-model="filters.nationality_id"
          :options="nationalityOptions"
          placeholder="Select Nationality"
          filterable
        />
      </x-field>
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
    :items="configurations.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
    :loading="loader.table"
  >
    <template #item-id="{ id }">
      <Link
        :href="route('admin.nationality-allocation-config.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>

    <template #item-created_at="{ created_at }">
      {{ dateFormat(created_at) }}
    </template>

    <template #item-users="{ users }">
      <div>
        <div v-if="users.length === 0" class="italic text-sm">
          No assigned users
        </div>
        <div v-else class="text-sm">
          {{ users.length }}
        </div>
      </div>
    </template>

    <template #item-actions="{ id }">
      <div class="flex gap-1.5 justify-end">
        <Link :href="route('admin.nationality-allocation-config.show', id)">
          <x-button tag="div" size="xs" outlined> View </x-button>
        </Link>
        <Link :href="route('admin.nationality-allocation-config.edit', id)">
          <x-button color="primary" size="xs" outlined> Edit </x-button>
        </Link>
        <x-button
          color="error"
          size="xs"
          outlined
          @click.prevent="confirmDelete(id)"
        >
          Delete
        </x-button>
      </div>
    </template>
  </DataTable>

  <Pagination
    v-if="configurations.data && configurations.data.length > 0"
    :links="{
      next: configurations.next_page_url,
      prev: configurations.prev_page_url,
      current: configurations.current_page,
      from: configurations.from,
      to: configurations.to,
      total: configurations.total,
    }"
  />

  <div
    v-else-if="!loader.table"
    class="bg-white p-4 text-center rounded shadow mt-4"
  >
    <p class="text-gray-500">No nationality allocation configurations found.</p>
  </div>

  <x-modal
    v-model="showDeleteModal"
    size="md"
    title="Delete Configuration"
    show-close
    backdrop
  >
    <p>
      Are you sure you want to delete this nationality allocation configuration?
      This cannot be undone.
    </p>
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
