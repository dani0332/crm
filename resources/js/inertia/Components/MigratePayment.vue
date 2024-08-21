<script setup>
const page = usePage();
const props = defineProps({
  quoteType: String,
  quoteId: Number,
  paymentCode: String,
  payments: Array,
});

const notification = useNotifications('toast');
const isLoading = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
// Check if the component should be visible
const isVisible = computed(() => {
  if (props.payments.length > 5 || props.payments.length === 0) {
    return false;
  }

  if (isSameInsuranceProviderId(props.payments)) {
    return true;
  } else {
    return false;
  }
});

// Check if all insurance_provider_id are the same
const isAllCancelled = allPayments => {
  if (allPayments.length === 0) {
    return false;
  }

  // filter out the 3 cancelled and 11 drafted payments
  let cancelledPayments = allPayments.filter(
    payment =>
      payment.payment_status_id === page.props.paymentStatusEnum.CANCELLED ||
      payment.payment_status_id === page.props.paymentStatusEnum.DRAFT,
  );

  if (cancelledPayments.length === allPayments.length) {
    return true;
  } else {
    return false;
  }
};

// Check if all insurance_provider_id are the same
const isSameInsuranceProviderId = arr => {
  if (arr.length === 0) {
    return false;
  }
  const firstInsuranceProviderId = arr[0].insurance_provider.id;
  for (let i = 1; i < arr.length; i++) {
    if (arr[i].insurance_provider.id !== firstInsuranceProviderId) {
      return false; // If any insurance_provider_id is different, return false
    }
  }
  return true; // If all insurance_provider_id are the same, return true
};
const migratePayment = () => {
  isLoading.value = true;

  let data = {
    model_type: props.quoteType,
    quote_id: props.quoteId,
    payment_code: props.paymentCode,
  };

  axios
    .post(`/payments/${props.quoteType}/migrate-payment`, data)
    .then(res => {
      isLoading.value = false;
      if (res.data.error) {
        notification.error({
          title: res.data.error,
          position: 'top',
        });
        return;
      } else {
        notification.success({
          title: res.data.message,
          position: 'top',
        });
      }
      setTimeout(() => {
        location.reload();
      }, 500);
    })
    .catch(err => {
      console.log(err);
      isLoading.value = false;
      notification.error({
        title: err?.response?.data?.message ?? 'something went wrong',
        position: 'top',
      });
    });
};

const showConfirmation = () => {
  if (confirm('Are you sure you want to migrate the payment?')) {
    migratePayment();
  }
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div
    class="p-4 rounded shadow mb-6 bg-white flex justify-between items-center"
    v-if="isVisible"
  >
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Migrate Payment</h3>
    </div>
    <div>
      <x-button
        size="sm"
        color="orange"
        :loading="isLoading"
        @click.prevent="showConfirmation()"
        v-if="readOnlyMode.isDisable === true"
      >
        Migrate Payment
      </x-button>
    </div>
  </div>
</template>
