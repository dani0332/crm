<script setup>
const props = defineProps({
  tiers: Object,
  model: Object,
  dropdownSource: Object,
  customTitles: Object,
});

const loader = ref({
  table: false,
});

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
  { text: 'Null Value', value: 'can_handle_null_value' },
  { text: 'Is TPL', value: 'can_handle_tpl' },
  { text: 'Renewal (TPL_RENEWALS)', value: 'is_tpl_renewals' },
  { text: 'IsActive', value: 'is_active' },
];

function onSubmit(isValid) {
  filters.page = 1;
  router.visit(route('tier.index'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onFinish: () => (loader.table = false),
  });
}

function onReset() {
  router.visit(route('tier.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
</script>
<template>
  <Head title="Tier List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Tier List</h2>
    <div class="space-x-3">
      <Link :href="route('tier.create')">
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
    fixed-checkbox
  >
    <!-- <template #item-id="{ id }">
      <Link
        :href="route('users.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template> -->
    <!-- <template #item-email="item">
      <Link
        :href="route('users.show', item.id)"
        class="text-primary-500 hover:underline"
      >
        {{ item.email }}
      </Link>
    </template> -->
    <!-- <template #item-created_at="{ created_at }">
      <span>
        {{ created_at ? dateFormat(created_at) : 'N/A' }}
      </span>
    </template> -->
    <!-- <template #item-updated_at="{ updated_at }">
      <span>
        {{ updated_at ? dateFormat(updated_at) : 'N/A' }}
      </span>
    </template>
    <template #item-is_active="{ is_active }">
      <div class="text-center">
        <x-tag size="sm" :color="is_active ? 'success' : 'error'">
          {{ is_active ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template> -->
    <!-- <template #item-roles="item">
      <div class="break-words flex flex-wrap gap-1" v-if="item.roles">
        <x-tag
          class="text-xs"
          size="sm"
          color="success"
          v-for="role in item.roles.split(',')"
          :key="role"
        >
          {{ role }}
        </x-tag>
      </div>
    </template> -->
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
</template>