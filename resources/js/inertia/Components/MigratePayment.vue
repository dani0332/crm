<script setup>

const props = defineProps({
    quoteType: String,
    quoteId: Number,
    paymentCode: String
})


const notification = useNotifications('toast');
const isLoading = ref(false);

const migratePayment = () => {
    
    isLoading.value = true;

    let data = {
        'model_type' : props.quoteType,
        'quote_id' : props.quoteId,
        'payment_code' : props.paymentCode
    }
    
    axios.post(`/payments/${props.quoteType}/migrate-payment`, data)
        .then(res => {
            isLoading.value = false;
            //console.log("RESP=="+JSON.stringify(res.data));
            notification.success({
                    title: "Payment Migrated Successfully!",
                    position: 'top',
            });
            setTimeout(() => {
                location.reload();
            }, 500);            
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

const showConfirmation = () => {
    if (confirm("Are you sure you want to migrate the payment?")) {
        migratePayment();
    }
};
</script>

<template>
    <div style="float:right; padding:20px;">
        <x-button
            size="sm"
            color="orange"
            :loading="isLoading"        
            @click.prevent="showConfirmation()"
        >
            Migrate Payment
        </x-button>
    </div>
</template>