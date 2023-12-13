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
                title:  "Something went wrong",
                position: 'top',
            });
        });
}
</script>

<template>
    <x-button
        v-show="false"
        size="xs"
        color="success"
        outlined
        :loading="isLoading"
        @click.prevent="updateSelectedPlan(item)"
    >
        Select
    </x-button>
</template>