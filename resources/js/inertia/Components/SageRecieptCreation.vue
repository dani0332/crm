<script setup>

const props = defineProps({
    quoteType: String,
    quoteId: Number,
    paymentCode: String,
    quoteStatusId: Number,
    payments: Array,
});

const page = usePage();
const can = permission => useCan(permission);
const updateModal = ref(false);
const notification = useNotifications('toast');
const isLoading = ref(false);


const validateSageButton = () => {
    const { permissionsEnum, quoteStatusEnum, paymentStatusEnum, paymentMethodsEnum } = page.props;
    const { payments, quoteStatusId } = props;

    console.log(can(permissionsEnum.TEMP_UPDATE_PAYMENT), quoteStatusId, payments.length, payments[0].payment_splits.length);
    console.log('Payment Create date:', payments[0].created_at);

    let [datePart, timePart] = payments[0].created_at.split(' ');
    let [month, day, year] = datePart.split('-');
    let createdAt = new Date(`${year}-${day}-${month}T${timePart}`);

    console.log('Payment Create dateVV:', createdAt);
    let startDate = new Date('2024-04-23T00:00:00');
    let endDate = new Date('2024-08-15T23:59:59');

    const isWithinDateRange = createdAt >= startDate && createdAt <= endDate;
    const isQuoteStatusValid = quoteStatusId === quoteStatusEnum.PolicyIssued || quoteStatusId === quoteStatusEnum.PolicySentToCustomer;
    const isPaymentStatusValid = payments[0].payment_status_id === paymentStatusEnum.PAID || payments[0].payment_status_id === paymentStatusEnum.PARTIALLY_PAID;

    if (can(permissionsEnum.TEMP_UPDATE_PAYMENT) && isQuoteStatusValid && payments.length && isWithinDateRange && isPaymentStatusValid) {
        const paymentSplits = payments[0].payment_splits;

        const totalUnpaidReceipts = paymentSplits.filter(item => 
            (item.sage_reciept_id === '' || item.sage_reciept_id === null) &&
            item.payment_method.code !== paymentMethodsEnum.CreditApproval &&
            (item.payment_status_id === paymentStatusEnum.PAID || item.payment_status_id === paymentStatusEnum.PARTIALLY_PAID)
        );

        console.log('Total Unpaid Receipts:', totalUnpaidReceipts);
        console.log('Number of Total Unpaid Receipts:', totalUnpaidReceipts.length);

        return totalUnpaidReceipts.length > 0;
    }
    return false;
}

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
            v-if = "validateSageButton()"
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