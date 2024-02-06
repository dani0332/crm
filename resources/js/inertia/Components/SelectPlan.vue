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

    isLoading.value = true;

    let data = {
        'plan_id' : props.plan.id
    }

    if(props.quoteType.toLocaleLowerCase() == 'health') {
        data.copay_id = props.plan.selectedCopayId;
    }


    axios.post(`/personal-quotes/${props.quoteType}/${props.uuid}/update-selected-plan`, data)
        .then(res => {
            isLoading.value = false;
            let premium = 0;
            console.log(props.quoteType.toLowerCase())
            switch (props.quoteType.toLowerCase()) {
                case 'travel':
                    premium = res.data.plan.planProcessValue[0].totalPremium
                    break;
                case 'car':
                    premium = res.data.plan.planProcessValue.totalPremium
                    break;
                case 'health' :                    
                    premium = (props.plan?.actualPremium + (props.plan?.policyFee || 0) + (props.plan?.basmah || 0) + props.plan?.vat)
                    break;
                default:
                    break;
            }    
            
            console.log('BEFORE OMMIT', {
                id: props.plan.id,
                providerName: props.plan.providerName,
                planName: props.plan.name,
                premium: premium.toFixed(2)
            });
            emit('update:selectedPlanChanged', {
                id: props.plan.id,
                providerName: props.plan.providerName,
                planName: props.plan.name,
                premium: premium.toFixed(2)
            });            
            notification.success({
                    title: "Selected plan updated",
                    position: 'top',
            });
        })
        .catch(err => {           
            console.log(err)
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