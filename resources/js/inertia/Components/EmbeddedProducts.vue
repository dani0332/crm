<script setup>
const notification = useNotifications('toast');
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
  modelType: {
    type: String,
    default: '',
  },
  quote: {
    type: Object,
    default: {},
  },
});

const sendDocumentForm = useForm({
  quoteId: props.quote.id,
  modelType: props.modelType,
  isInertia: true,
});

const sendDcoument = id => {
  sendDocumentForm
    .transform(data => ({
      ...data,
      epId: id,
    }))
    .post('/embedded-products/send-document', {
      preserveScroll: true,
      onSuccess: () => {},
      onError: () => {},
    });
};
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
          <x-button
            size="xs"
            color="emerald"
            @click.prevent="sendDcoument(item.id)"
          >
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
            Download Product Wordings
          </x-button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
