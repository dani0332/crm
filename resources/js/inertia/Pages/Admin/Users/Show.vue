<script setup>
const props = defineProps({
  user: Object,
  teamName: String,
  subTeamName: String,
  additionalTeamNames: String,
  managerName: String,
  productName: String,
});

const user = ref(props.user);
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const userRoles = computed(() => {
  if (props.user && props.user?.roles.length > 0) {
    console.log(props.user?.roles.map(x => x.name));
    return props.user.roles.map(x => x.name).toString();
  } else return null;
});
</script>
<template>
  <Head title="User Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Users Detail</h2>
    <div class="space-x-3">
      <Link :href="route('users.index')">
        <x-button size="sm" color="#ff5e00" tag="div"> User List </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.UsersCreate)"
        :href="route('users.edit', props.user.id)"
      >
        <x-button size="sm" color="primary" tag="div"> Edit User </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ user?.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">EMAIL</dt>
          <dd>{{ user?.email ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">MOBILE NUMBER</dt>
          <dd>{{ user?.mobile_no ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LANDLINE NUMBER</dt>
          <dd>{{ user?.landline_no ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ROLES</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="userRoles">
              <x-tag
                size="sm"
                color="success"
                v-for="role in userRoles.split(',')"
                :key="role"
                class="text-xs"
              >
                {{ role }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PRODUCTS</dt>
          <dd>{{ productName ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">TEAMS</dt>
          <dd>{{ teamName ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">SUB TEAMS</dt>
          <dd>{{ subTeamName ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LOB VISIBILITY TEAM</dt>
          <dd>{{ additionalTeamNames ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ACTIVE</dt>
          <dd>
            <x-tag size="sm" :color="user.is_active ? 'success' : 'error'">
              {{ user.is_active ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>{{ user.created_at ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>{{ user.updated_at ?? 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\User'"
    :id="$page.props.user.id"
  />
</template>