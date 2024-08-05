<script setup>
const props = defineProps({
  quoteType: String,
  quoteId: Number,
  paymentCode: String,
  totalPrice: Number,
  totalPaidPrice: Number,
});

const updatePriceModal = ref(false);
const notification = useNotifications('toast');
const isLoading = ref(false);
const newTotalPrice = ref(props.totalPrice);

const rules = {
  isRequired: v => !!v || 'This field is required',
  isTotalAmountLess: v => {
    if (newTotalPrice.value > props.totalPaidPrice) {
      return true;
    } else {
      return (
        'The entered amount should be greater than ' +
        props.totalPaidPrice +
        ' AED'
      );
    }
  },
  verifyDecimalPlaces: v => {
    let regex = /^\d+(\.\d{1,2})?$/;
    if (!regex.test(v)) {
      return 'The entered amount should have no more than 2 decimal places.';
    } else {
      return true;
    }
  },
};

const submitTotalPrice = isValid => {
  if (!isValid) return;
  isLoading.value = true;

  let data = {
    model_type: props.quoteType,
    quote_id: props.quoteId,
    payment_code: props.paymentCode,
    total_price: newTotalPrice.value,
  };

  axios
    .post(`/payments/${props.quoteType}/update-total-price`, data)
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
      updatePriceModal.value = false;

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

const showPriceModel = () => {
  updatePriceModal.value = true;
};
</script>

<template>
  <div class="flex justify-between items-center" style="margin-left: auto">
    <x-tooltip>
      <x-button
        size="sm"
        color="orange"
        @click.prevent="showPriceModel()"
        class="ml-auto"
      >
        <span class="border-b border-dotted">Update Total Price</span>
      </x-button>
      <template #tooltip>
        <span
          >Click this if you need to modify the total price to collect
          additional payments.</span
        >
      </template>
    </x-tooltip>
    <x-modal
      v-model="updatePriceModal"
      size="lg"
      title="Update Total Price"
      show-close
      backdrop
      is-form
      @submit="submitTotalPrice"
    >
      <div class="w-full grid md:grid-cols-1 gap-3">
        <div>
          Total Price
          <x-field class="w-full">
            <x-input
              class="w-full"
              v-model="newTotalPrice"
              :rules="[
                rules.isRequired,
                rules.isTotalAmountLess,
                rules.verifyDecimalPlaces,
              ]"
            />
          </x-field>
        </div>
      </div>
      <template #secondary-action>
        <x-button ghost tabindex="-1" @click.prevent="updatePriceModal = false">
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          color="emerald"
          type="submit"
          tabindex="0"
          class="focus:outline-black"
          :loading="isLoading"
        >
          Update
        </x-button>
      </template>
    </x-modal>
  </div>
</template>
