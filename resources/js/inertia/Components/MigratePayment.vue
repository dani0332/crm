<script setup>

const props = defineProps({
    quoteType: String,
    quoteId: Number,
    paymentCode: String,    
});

const notification = useNotifications('toast');
const isLoading = ref(false);

const migratePayment = () => {
    
    isLoading.value = true;

    let data = {
        'model_type' : props.quoteType,
        'quote_id' : props.quoteId,
        'payment_code' : props.paymentCode,        
    }
    
    axios.post(`/payments/${props.quoteType}/migrate-payment`, data)
        .then(res => {
            isLoading.value = false;
            if(res.data.error){
                notification.error({
                    title:  res.data.error,
                    position: 'top',
                });
                return;
            } else {
                notification.success({
                    title:  res.data.message,
                    position: 'top',
                });
            }           
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
    <div class="p-4 rounded shadow mb-6 bg-white flex justify-between items-center">
        <div>
            <h3 class="font-semibold text-primary-800 text-lg">
                Migrate Payment
            </h3>
        </div>
        <div>
            <x-button
                size="sm"
                color="orange"
                :loading="isLoading"        
                @click.prevent="showConfirmation()"
            >
                Migrate Payment
            </x-button>
        </div>
    </div>
</template>