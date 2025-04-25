<script setup>
const props = defineProps({
  role: Object,
  rolePermissions: Object,
  permission: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const permissions = computed(() => {
  if (props.rolePermissions.length)
    return props.rolePermissions.map(x => x.name).toString();
  else return null;
});
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
          <dd>{{ role.created_at ? role.created_at.split('T')[0] : 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>{{ role.updated_at ? role.updated_at.split('T')[0] : 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\Role'"
    :quoteType="'Role'"
    :id="$page.props.role.id"
  />
</template>
