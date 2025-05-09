<script setup>
const props = defineProps({
  configuration: Object,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;
</script>

<template>
  <Head title="Configuration Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      Nationality Allocation Configuration Detail
    </h2>
    <div class="flex gap-2">
      <Link :href="route('admin.nationality-allocation-config.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Configuration List
        </x-button>
      </Link>
      <Link
        :href="
          route('admin.nationality-allocation-config.edit', configuration.id)
        "
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ID</dt>
          <dd>{{ configuration.id }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Quote Type</dt>
          <dd>{{ configuration.quote_type.text }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Nationality</dt>
          <dd>{{ configuration.nationality.text }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">SIC</dt>
          <dd>
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                configuration.is_sic_enabled
                  ? 'bg-green-100 text-green-800'
                  : 'bg-gray-100 text-gray-800',
              ]"
            >
              {{ configuration.is_sic_enabled ? 'Enabled' : 'Disabled' }}
            </span>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created By</dt>
          <dd>{{ configuration.created_by?.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated By</dt>
          <dd>{{ configuration.updated_by?.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ dateFormat(configuration.created_at) }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ dateFormat(configuration.updated_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="text-lg font-semibold mb-4">Assigned Users</h3>
    <div v-if="configuration.users.length === 0" class="text-gray-500 italic">
      No users assigned to this configuration.
    </div>
    <div v-else class="grid md:grid-cols-3 gap-2">
      <div
        v-for="user in configuration.users"
        :key="user.id"
        class="p-2 border rounded flex items-center"
      >
        <div
          class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-medium mr-2"
        >
          {{ user.name.charAt(0) }}
        </div>
        <div class="flex-1 overflow-hidden">
          <div class="font-medium truncate">{{ user.name }}</div>
          <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
