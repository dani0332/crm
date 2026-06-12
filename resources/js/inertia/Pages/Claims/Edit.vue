<script setup>
import Form from './Form.vue';

const props = defineProps({
  claim: { type: Object, required: true },
  dropdowns: { type: Object, default: null },
});
</script>

<template>
  <div>
    <Head title="Edit Claim" />

    <Form :claim="claim" :dropdowns="dropdowns" :isEdit="true" />
  </div>
</template>
