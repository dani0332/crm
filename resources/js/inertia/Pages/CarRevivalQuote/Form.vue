<script setup>
import QuoteForm from '@/inertia/Pages/CarShared/QuoteForm.vue';

defineProps({
  quote: Object,
});
</script>

<template>
  <QuoteForm dynamic_route="revival" :quote="quote" />
</template>
