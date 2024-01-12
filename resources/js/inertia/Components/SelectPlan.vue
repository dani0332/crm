<script setup>

const props = defineProps({
    plan: Object,
    quoteType: String,
    uuid: String
})


const notification = useNotifications('toast');
const isLoading = ref(false);

const emit = defineEmits(['update:selectedPlanChanged']);

const updateSelectedPlan = () => {

    console.log('COOOO', props.plan.selectedCopayId);
    console.log('full plan:' , props.plan);
    isLoading.value = true;
    axios.post(`/personal-quotes/${props.quoteType}/${props.uuid}/update-selected-plan/${props.plan.id}`)
        .then(res => {
            console.log('ken:',res.data.plan.planProcessValue.totalPremium);
            isLoading.value = false;
            emit('update:selectedPlanChanged', {
                id: props.plan.id,
                providerName: props.plan.providerName,
                planName: props.plan.name,
                premium: res.data.plan.planProcessValue.totalPremium.toFixed(2)
            });            
            notification.success({
                    title: "Selected plan updated",
                    position: 'top',
            });
        })
        .catch(err => {           
            isLoading.value = false;
            notification.error({
                title:  err?.response?.data?.message ?? 'something went wrong',
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