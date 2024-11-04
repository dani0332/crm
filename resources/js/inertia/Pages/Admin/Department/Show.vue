<script setup>
const props = defineProps({
  department: Object,
  teams: Object,
  permission: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
let teams = ref([]);
const departmentTeams = computed(() => {
  if (props.department && props.department?.teams) {
    return props.department?.teams.map(x => x.team.name).toString();
  } else return null;
});

console.log('teams', teams.value);
</script>
<template>
  <Head title="departments Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Departments Detail</h2>
    <div class="space-x-3">
      <Link :href="route('departments.index')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Department List
        </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.DEPARTMENT_UPDATE)"
        :href="route('departments.edit', props.department.id)"
      >
        <x-button size="sm" color="primary" tag="div">
          Edit Department
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ department.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Teams</dt>
          <dd class="break-words flex flex-wrap gap-1">
            <template v-if="departmentTeams">
              <x-tag
                size="sm"
                color="success"
                v-for="team in departmentTeams.split(',')"
                :key="team"
                class="text-xs"
              >
                {{ team }}
              </x-tag>
            </template>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>
            {{
              department.created_at
                ? department.created_at.split('T')[0]
                : 'N/A'
            }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>
            {{
              department.updated_at
                ? department.updated_at.split('T')[0]
                : 'N/A'
            }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\Department'"
    :id="$page.props.department.id"
    :quoteType="'Department'"
  />
</template>
