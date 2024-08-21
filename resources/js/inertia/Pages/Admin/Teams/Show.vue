<script setup>
const props = defineProps({
  team: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>
<template>
  <Head title="Team Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Team Detail</h2>
    <div class="space-x-3">
      <Link :href="route('team.index')">
        <x-button size="sm" color="#ff5e00" tag="div"> Team List </x-button>
      </Link>
      <Link :href="route('team.edit', props.team.id)">
        <x-button size="sm" color="primary" tag="div"> Edit Team </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Team Name</dt>
          <dd>{{ team.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Record Type</dt>
          <dd>{{ team.type ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Parent</dt>
          <dd>{{ team.parent_team_id ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Active</dt>
          <dd>
            <x-tag
              size="sm"
              :color="team.is_active == 'True' ? 'success' : 'error'"
            >
              {{ team.is_active == 'True' ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ team.created_at ? team.created_at.split('T')[0] : 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ team.updated_at ? team.updated_at.split('T')[0] : 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Slabs Count</dt>
          <dd>{{ team.slabs_count ?? 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
