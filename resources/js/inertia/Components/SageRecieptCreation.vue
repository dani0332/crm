<script setup>

const props = defineProps({
    quoteType: String,
    quoteId: Number,
    paymentCode: String,   
});

const updateModal = ref(false);
const notification = useNotifications('toast');
const isLoading = ref(false);

const submitForm = isValid => {  
    if (!isValid) return;
    isLoading.value = true;

    let data = {
        'model_type' : props.quoteType,
        'quote_id' : props.quoteId,
        'payment_code' : props.paymentCode,        
    }
    
    axios.post(`/payments/${props.quoteType}/create-sage-receipts-temp`, data)
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
            updateModal.value = false;    
            
            /*setTimeout(() => {
                location.reload();
            }, 500); */
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

const showModel = () => {
    updateModal.value = true;    
};
</script>

<template>
    <div class="flex justify-between items-center" style="margin-left:auto">
        <x-button
            size="sm"
            color="orange"
            @click.prevent="showModel()"
            class="ml-auto"               
        >
        <span>Create Sage Receipts</span>
        </x-button>
        <x-modal v-model="updateModal" size="xl" show-close backdrop>
            
            <template #header>
                <span class=" ">
                 Create Sage Receipts
                </span>
            </template> 
            
            <x-form @submit="submitForm" :auto-focus="false">
                <div class="w-full grid md:grid-cols-1 gap-3">          
                    <div>
                        Are you sure you want to create Sage Receipts for this payment?                             
                    </div>
                </div>
                <div class="w-full md:col-span-4 flex justify-end"> 
                    <div>
                    <x-button color="emerald" type="submit" tabindex="0" class="focus:outline-black" :loading="isLoading"   >
                        Create Sage Receipts
                    </x-button>
                    </div>          
                </div>            
            </x-form>
        </x-modal>
    </div>
</template>