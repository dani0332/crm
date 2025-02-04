<script setup>
const props = defineProps({
  users: Object,
});

const page = usePage();
const params = useUrlSearchParams('history');
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const authUserId = page.props.auth.user.id;
const impersonatingUser = page.props.impersonatingUser;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const filters = reactive({
  email: '',
  name: '',
  page: 1,
});

const loader = reactive({
  table: false,
});

const tableHeader = [
  { text: 'Ref-ID', value: 'id' },
  { text: 'NAME', value: 'name' },
  { text: 'EMAIL', value: 'email' },
  { text: 'ROLES', value: 'roles', width: 100 },
  { text: 'PRIMARY TEAM NAME', value: 'teamName' },
  { text: 'ACTIVE', value: 'is_active' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
];

const onReset = () => {
  router.visit(route('users.index'), {
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
    router.visit(route('users.index'), {
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
  // // Update filters based on URL parameters
  // Object.keys(filters).forEach(key => {
  //   const paramValue = urlParams.get(key);

  //   if (paramValue !== null) {
  //     filters[key] = paramValue;
  //   }
  // });
});
</script>
<template>
  <Head title="Users List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Users List</h2>
    <div class="space-x-3">
      <Link
        v-if="can(permissionsEnum.UsersCreate)"
        :href="route('users.create')"
      >
        <x-button size="sm" color="#ff5e00" tag="div"> Create User </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
      <x-field label="EMAIL" required>
        <x-input v-model="filters.email" class="w-full" type="email" />
      </x-field>
      <x-field label="NAME" required>
        <x-input class="w-full" v-model="filters.name" />
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
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.users.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-id="{ id }">
      <Link
        :href="route('users.show', id)"
        class="text-primary-500 hover:underline"
      >
        {{ id }}
      </Link>
    </template>
    <template #item-name="item">
      {{ item.name }}
      <x-tooltip
        placement="top"
        v-if="
          can(permissionsEnum.ENABLE_IMPERSONATION) &&
          !impersonatingUser &&
          authUserId !== item.id
        "
      >
        <a :href="route('login-as.id.login', item.id)">
          <x-icon icon="loginAs" class="text-success-600 ml-2" />
        </a>
        <template #tooltip> Login As {{ item.name }} </template>
      </x-tooltip>
    </template>
    <template #item-email="item">
      <Link
        :href="route('users.show', item.id)"
        class="text-primary-500 hover:underline"
      >
        {{ item.email }}
      </Link>
    </template>
    <template #item-created_at="{ created_at }">
      <span>
        {{ created_at ? dateFormat(created_at) : 'N/A' }}
      </span>
    </template>
    <template #item-updated_at="{ updated_at }">
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
    </template>
    <template #item-roles="item">
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
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.users.next_page_url,
      prev: props.users.prev_page_url,
      current: props.users.current_page,
      from: props.users.from,
      to: props.users.to,
    }"
  />
</template>
