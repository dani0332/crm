<script setup>
import {reactive} from "vue";
import QuoteShow from "@/inertia/Pages/CarShared/QuoteShow.vue";

const page = usePage();
const notification = useNotifications('toast');

const loader = reactive({
    table: false,
    export: false,
});

defineProps({
    dynamic_route: String,
    leadStatuses: Array,
    advisors: Array,
});
</script>

<template>
    <QuoteShow
        :dynamic_route="car-revival"
        :lead-statuses="leadStatuses"
        :advisors="advisors"
    >

    </QuoteShow>
</template>
