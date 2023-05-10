<script setup>
import EditForm from "@/inertia/Pages/CarShared/EditForm.vue";

defineProps({
    quote: Object
});

</script>

<template>
    <EditForm
        dynamic_route="car-revival"
        :quote="quote"
    />
</template>
