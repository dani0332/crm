<script setup>
const props = defineProps({
  role: Object,
  rolePermissions: Object,
  permission: Object,
  roleUsers: Array,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;

const permissions = computed(() => {
  if (props.rolePermissions.length)
    return props.rolePermissions.map(x => x.name).toString();
  else return null;
});

// Users section state
const showUsers = ref(false);
const loader = reactive({
  users: false,
});

// Search state
const searchQuery = ref('');

// Sorting state
const sortBy = ref('name');
const sortType = ref('asc');

const userTableHeader = ref([
  { text: 'User ID', value: 'id', sortable: true },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Email', value: 'email', sortable: true },
]);

// Load users data
const loadUsers = async () => {
  loader.users = true;
  showUsers.value = true;
  await router.reload({ only: ['roleUsers'] });
  loader.users = false;
};

// Computed property for users count
const usersCount = computed(() => props.roleUsers?.length || 0);

// Computed property for sorted and filtered users
const sortedUsers = computed(() => {
  if (!props.roleUsers) return [];
  
  let filteredUsers = props.roleUsers;
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filteredUsers = filteredUsers.filter(user => 
      user.name?.toLowerCase().includes(query) ||
      user.email?.toLowerCase().includes(query) ||
      user.id.toString().includes(query)
    );
  }
  
  return [...filteredUsers].sort((a, b) => {
    const modifier = sortType.value === 'desc' ? -1 : 1;
    const aValue = a[sortBy.value];
    const bValue = b[sortBy.value];
    
    // Handle numeric sorting for ID
    if (sortBy.value === 'id') {
      return (Number(aValue) - Number(bValue)) * modifier;
    }
    
    // Handle string sorting for name and email
    if (!aValue) return 1;
    if (!bValue) return -1;
    return aValue.localeCompare(bValue) * modifier;
  });
});

// Handle sort change
const onSort = ({ sortBy: newSortBy, sortType: newSortType }) => {
  sortBy.value = newSortBy;
  sortType.value = newSortType;
};
</script>
<template>
  <Head title="Roles Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Roles Detail</h2>
    <div class="space-x-3">
      <Link :href="route('roles.index')">
        <x-button size="sm" color="#ff5e00" tag="div"> Role List </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.RoleCreate)"
        :href="route('roles.edit', props.role.id)"
      >
        <x-button size="sm" color="primary" tag="div"> Edit Role </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  
  <!-- Role Details Section -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ role.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PERMISSIONS</dt>
          <!-- <dd>{{ permissions ?? 'N/A' }}</dd> -->
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="permissions">
              <x-tag
                size="sm"
                color="success"
                v-for="permission in permissions.split(',')"
                :key="permission"
                class="text-xs"
              >
                {{ permission }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>{{ role.created_at ? dateFormat(role.created_at) : 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>{{ role.updated_at ? dateFormat(role.updated_at) : 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <!-- Users Section -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-primary-800 text-lg">Users with this Role</h3>
          <span v-if="showUsers" class="px-2 py-1 bg-gray-100 rounded-full text-sm text-gray-600">
            {{ usersCount }} {{ usersCount === 1 ? 'user' : 'users' }}
          </span>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-center py-3" v-if="!showUsers">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click="loadUsers"
            :loading="loader.users"
          >
            Load Users
          </x-button>
        </div>
        <div v-else>
          <div class="mb-4">
            <x-input
              v-model="searchQuery"
              placeholder="Search by ID, name or email..."
              class="w-full md:w-64"
              size="sm"
            >
              <template #prefix>
                <i-heroicons-magnifying-glass class="w-4 h-4 text-gray-400" />
              </template>
            </x-input>
          </div>
          <DataTable
            table-class-name="w-full"
            :loading="loader.users"
            :headers="userTableHeader"
            :items="sortedUsers"
            :sort-by="sortBy"
            :sort-type="sortType"
            @sort="onSort"
            border-cell
            :rows-items-count="[10, 25, 50, 100, 0]"
            :rows-per-page="10"
          >
            <template #item-id="{ id }">
              <Link 
                :href="route('users.show', id)"
                class="text-primary-500 hover:underline"
              >
                {{ id }}
              </Link>
            </template>
            <template #item-name="{ name }">
              {{ name || 'N/A' }}
            </template>
            <template #item-email="{ email, id }">
              <Link 
                :href="route('users.show', id)"
                class="text-primary-500 hover:underline"
              >
                {{ email }}
              </Link>
            </template>
            <template #empty>
              <div class="text-center py-4 text-gray-500">
                No users found with this role
              </div>
            </template>
          </DataTable>
        </div>
      </template>
    </Collapsible>
  </div>

  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\Role'"
    :quoteType="'Role'"
    :id="$page.props.role.id"
  />
</template>
