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

const changeVersion = version => {
  window.location.href = route('admin.private-client-config.show', {
    version,
  });
};
</script>

<template>
  <Head title="Private Client Configuration - Advanced" />

  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">Private Client Configuration</h2>
  </div>

  <AdvancedConfig
    :configurations="configurations"
    :allVersions="allVersions"
    :selectedVersion="selectedVersion"
    :isCurrentVersion="isCurrentVersion"
    :quoteTypes="quoteTypes"
    :quoteTypeCodeEnum="quoteTypeCodeEnum"
  />
</template>
