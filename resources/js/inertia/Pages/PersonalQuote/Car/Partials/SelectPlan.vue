<script setup>

const props = defineProps({
    plan: Object,
    quoteType: String,
    uuid: String
})


const notification = useNotifications('toast');
const isLoading = ref(false);

const updateSelectedPlan = () => {
   
    isLoading.value = true;
  axios.post(`/personal-quotes/${props.quoteType}/${props.uuid}/update-selected-plan/${props.plan.id}`)
        .then(res => {
            isLoading.value = false;
            console.log(res.data);
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
        size="xs"
        color="success"
        outlined
        :loading="isLoading"
        @click.prevent="updateSelectedPlan(item)"
    >
        Select Plan
    </x-button>
</template>