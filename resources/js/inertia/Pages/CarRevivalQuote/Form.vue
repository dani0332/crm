<script setup>
import QuoteForm from "@/inertia/Pages/CarShared/QuoteForm.vue";

defineProps({
    quote: Object
});

</script>

<template>
    <QuoteForm
        dynamic_route="carrevival"
        :quote="quote"
    />
</template>
