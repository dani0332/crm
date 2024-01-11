<script setup>

const props = defineProps({
    plan: Object,
    quoteType: String,
    uuid: String
})


const notification = useNotifications('toast');
const isLoading = ref(false);

const emit = defineEmits(['update:updatePlanId']);

const updateSelectedPlan = () => {

    isLoading.value = true;
    axios.post(`/personal-quotes/${props.quoteType}/${props.uuid}/update-selected-plan/${props.plan.id}`)
        .then(res => {
            isLoading.value = false;
            emit('update:updatePlanId', props.plan.id);            
            notification.success({
                    title: "Selected plan updated",
                    position: 'top',
            });
        })
        .catch(err => {           
            isLoading.value = false;
            notification.error({
                title:  err.response.data.message ?? 'something went wrong',
                position: 'top',
            });
        });
}
</script>

<template>
    <x-button
        size="xs"
        color="success"
        outlined
        :loading="isLoading"
        v-if="props.plan.actualPremium > 0"
        @click.prevent="updateSelectedPlan()"
    >
        Select
    </x-button>
</template>