<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AdvancedConfig from './AdvancedConfig.vue';

const props = defineProps({
  configurations: Array,
  allVersions: Array,
  selectedVersion: Number,
  isCurrentVersion: Boolean,
});

const page = usePage();

const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

// Function to change version
const changeVersion = version => {
  window.location.href = route('admin.private-client-config.advanced', {
    version,
  });
};
</script>

<template>
  <Head title="Private Client Configuration - Advanced" />

  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">
      Private Client Configuration - Advanced
    </h2>
    <Link
      :href="route('admin.private-client-config.show')"
      class="text-blue-600 hover:text-blue-800 text-sm"
    >
      ← Back to Simple View
    </Link>
  </div>

  <!-- Version selector -->
  <div
    v-if="hasAnyRole([rolesEnum.Engineering])"
    class="bg-gray-50 p-4 rounded-md my-4"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <span class="font-medium">Global Version:</span>
        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">{{
          selectedVersion || 'None'
        }}</span>
      </div>
      <div class="flex items-center">
        <span class="mr-2">Select Version:</span>
        <x-select
          :modelValue="selectedVersion"
          :options="allVersions.map(v => ({ value: v, label: `Version ${v}` }))"
          @update:modelValue="changeVersion"
          class="w-40"
        />
      </div>
    </div>
  </div>

  <x-divider class="my-4" />

  <!-- Advanced Configuration Component -->
  <AdvancedConfig
    :configurations="configurations"
    :allVersions="allVersions"
    :selectedVersion="selectedVersion"
    :isCurrentVersion="isCurrentVersion"
  />
</template>

<style scoped>
/* Add any additional styling if needed */
</style>
