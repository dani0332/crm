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
  quoteTypes: Array,
  quoteTypeCodeEnum: Object,
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

  <!-- Advanced Configuration Component -->
  <AdvancedConfig
    :configurations="configurations"
    :allVersions="allVersions"
    :selectedVersion="selectedVersion"
    :isCurrentVersion="isCurrentVersion"
    :quoteTypes="quoteTypes"
    :quoteTypeCodeEnum="quoteTypeCodeEnum"
  />
</template>

<style scoped>
/* Add any additional styling if needed */
</style>
