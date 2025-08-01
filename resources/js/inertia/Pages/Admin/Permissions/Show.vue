<script setup>
const props = defineProps({
  permission: Object,
  rolesWithPermission: Array,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>
<template>
  <Head title="Permission Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Permission Detail</h2>
    <div class="space-x-3">
      <Link :href="route('permissions.index')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Permissions List
        </x-button>
      </Link>
      <!-- <Link
        v-if="can(permissionsEnum.PermissionEdit)"
        :href="route('permissions.edit', props.permission.id)"
      >
        <x-button size="sm" color="primary" tag="div">
          Edit Permission
        </x-button>
      </Link> -->
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ID</dt>
          <dd>{{ permission.id ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ permission.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">GUARD NAME</dt>
          <dd>{{ permission.guard_name ?? 'web' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>
            {{
              permission.created_at
                ? permission.created_at.split('T')[0]
                : 'N/A'
            }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>
            {{
              permission.updated_at
                ? permission.updated_at.split('T')[0]
                : 'N/A'
            }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ASSIGNED TO ROLES</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="rolesWithPermission && rolesWithPermission.length">
              <x-tag
                size="sm"
                color="success"
                v-for="role in rolesWithPermission"
                :key="role.id"
                class="text-xs"
              >
                {{ role.name }}
              </x-tag>
            </template>
            <template v-else>
              <span>Not assigned to any role</span>
            </template>
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
