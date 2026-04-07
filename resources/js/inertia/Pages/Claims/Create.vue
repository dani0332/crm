<script setup>
import Form from './Form.vue';

defineProps({
  dropdowns: { type: Object, default: null },
});
</script>

<template>
  <div>
    <Head title="Create Claim Lead" />

    <Form :claim="null" :dropdowns="dropdowns" :isEdit="false" />
  </div>
</template>
