<script setup>
const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  link: {
    type: String,
    default: '',
  },
  code: {
    type: String,
    default: '',
  },
});
const modals = reactive({
    cancelPayment: false,
});

const cancelPaymentForm = () => {
    paymentForm.reset();
   // activityActionEdit.value = false;
    modals.cancelPayment = true;
};
const paymentForm = useForm({
    reason: null,
    amount: null
});
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const selectedItems = ref([]);

const epTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Product Reference ID',
      value: 'code',
    },
    {
      text: 'Product / Service',
      value: 'display_name',
    },
    {
      text: 'Price with VAT',
      value: 'prices',
    },
    {
      text: 'EP Status',
      value: 'ep_status',
    },
    {
      text: 'Last Updated Date',
      value: 'updated_at',
    },
    {
      text: 'Payment Status',
      value: 'payment_status',
    },
    {
      text: 'Actions',
      value: 'actions',
    },
  ],
});

const ppDoc = str => {
  const doc = JSON.parse(str);
  return doc[0]?.path !== '' ? usePage().props.cdnPath + doc[0]?.path : '';
};

const { copy, copied } = useClipboard();
const notification = useNotifications('toast');

const onCopyText = () => {
  copy(props.link);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const paymentStatus = id => {
  const enums = usePage().props?.enums?.paymentStatusEnum || {};
  const item = Object.keys(enums).find(key => enums[key] === id);
  return item ? item : 'N/A';
};
const onActivitySubmit = isValid => {
    if (!isValid) return;
        paymentForm.post(`/activities/create-activity`, {
            preserveScroll: true,
            onSuccess: () => {
                paymentForm.reset();
                notification.success({
                    title: 'Activity Added',
                    position: 'top',
                });
            },
            onFinish: () => {
                modals.activity = false;
            },
        });

};
const hasAnyRole = roles => useHasAnyRole(roles);
</script>

<template>
  <div
    v-if="
      hasAnyRole([
        $page.props.rolesEnum.Engineering,
        $page.props.rolesEnum.BetaUser,
      ])
    "
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <div class="flex flex-wrap gap-4 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Embedded Products <x-tag size="sm">{{ props.data.length || 0 }}</x-tag>
      </h3>
      <div class="flex flex-wrap gap-3">
        <x-button
          v-if="selectedItems.length > 0"
          size="sm"
          @click.prevent="onCopyText()"
        >
          Copy Payment Link
        </x-button>
      </div>
    </div>

    <DataTable
      v-model:items-selected="selectedItems"
      table-class-name="tablefixed"
      :headers="epTable.columns"
      :items="props.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ short_code }">
        {{ short_code + '-' + props.code }}
      </template>

      <template #item-prices="{ prices }">
        <div v-if="prices.length > 1" class="flex gap-3">
          <x-tooltip
            v-for="item in prices"
            :key="'price_' + item.id"
            position="bottom"
          >
            <x-tag color="primary">
              {{ (parseFloat(item.price) + (item.price * 5) / 100).toFixed(2) }}
            </x-tag>
            <template #tooltip> {{ item.variant }} </template>
          </x-tooltip>
        </div>

        <div v-else>
          <x-tag color="primary">
            {{
              (
                parseFloat(prices[0]?.price) +
                (prices[0]?.price * 5) / 100
              ).toFixed(2)
            }}
          </x-tag>
        </div>
      </template>

      <template #item-ep_status="{ ep_status }"> N/A </template>

      <template #item-payment_status="{ prices }">
        {{ paymentStatus(prices[0]?.transactions[0]?.payment_status_id) }}
      </template>

      <template #item-updated_at="{ updated_at }">
        {{ dateFormat(updated_at) }}
      </template>

      <template #item-actions="item">
        <div class="flex flex-col gap-1">
          <x-button size="xs" color="emerald" disabled>
            Send Documents
          </x-button>
          <x-button size="xs" color="#ff5e00" disabled>
            Download Certificate
          </x-button>
          <x-button
            size="xs"
            color="primary"
            :href="ppDoc(item.company_documents)"
            target="_blank"
            :disabled="ppDoc(item.company_documents) === ''"
          >
            Download Product Wordingss
          </x-button>
            <x-button size="xs" color="#ff5e00" @click.prevent="cancelPaymentForm">
                Cancel Paymentss
            </x-button>
        </div>
      </template>
    </DataTable>
      <x-modal v-model="modals.cancelPayment" size="lg" show-close backdrop>
          <template #header>
              Cancel Payment
          </template>

          <x-form @submit="onActivitySubmit" :auto-focus="false">
              <div class="grid gap-4">
                  <x-input
                      v-model="paymentForm.amount"
                      label="Amount"
                      :rules="[isRequired]"
                      class="w-full"
                  />

                  <x-textarea
                      v-model="paymentForm.reason"
                      label="Reason"
                      :adjust-to-text="false"
                      class="w-full"
                  />



              </div>

              <div class="text-right space-x-4 mt-12">
                  <x-button size="sm" @click.prevent="modals.cancelPayment = false">
                      Cancel
                  </x-button>

                  <x-button
                      size="sm"
                      color="emerald"
                      :loading="paymentForm.processing"
                      type="submit"
                  >
                     Cancel
                  </x-button>
              </div>
          </x-form>
      </x-modal>
  </div>
</template>
