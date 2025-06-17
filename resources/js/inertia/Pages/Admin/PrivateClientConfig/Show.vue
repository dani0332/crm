<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AdvancedConfig from './AdvancedConfig.vue';

const props = defineProps({
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
  <Head title="Private Client Configuration" />

  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold">Private Client Configuration</h2>
  </div>

  <AdvancedConfig
    :quoteTypes="quoteTypes"
    :quoteTypeCodeEnum="quoteTypeCodeEnum"
  />
</template>
