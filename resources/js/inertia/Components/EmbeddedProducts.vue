<script setup>
const notification = useNotifications('toast');

const page = usePage();
const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  paymentLink: {
    type: String,
    default: '',
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
  paymentStatusEnum: {
    type: Array,
    default: () => [],
  },
});

const propsDataReactive = ref(props.data);
const paymentStatusEnum = page.props.paymentStatusEnum;
const permissionsEnum = page.props.permissionsEnum;
const modals = reactive({
  cancelPayment: false,
});

const { isRequired, isEmail, isNumber, isMobileNo } = useRules();

const cancelPaymentForm = item => {
  paymentForm.reset();
  paymentForm.embedded_id = item.id;
  paymentForm.quote_id = props.quote.id;
  paymentForm.uuid = props.quote.uuid;
  modals.cancelPayment = true;
};
const paymentForm = useForm({
  reason: null,
  amount: null,
  modelType: props.modelType,
  embedded_id: null,
  quote_id: null,
  processing: false,
});

const downloadLoader = ref(false);
const sendDocumentLoader = ref(false);
const sendDocumentForm = useForm({
  quoteId: props.quote.id,
  modelType: props.modelType,
  isInertia: true,
});

const downloadDcoument = id => {
  downloadLoader.value = true;
  axios
    .post(
      '/embedded-products/download-document',
      {
        quoteId: props.quote.id,
        modelType: props.modelType,
        epId: id,
        isInertia: true,
      },
      {
        responseType: 'json',
      },
    )
    .then(response => {
      const link = document.createElement('a');
      let fileName = response.data.name;
      link.href = response.data.data;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      notification.success({
        title: 'Certificate Downloaded',
        position: 'top',
      });
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      downloadLoader.value = false;
    });
};
const sendDcoument = id => {
  sendDocumentLoader.value = true;
  sendDocumentForm
    .transform(data => ({
      ...data,
      epId: id,
    }))
    .post('/embedded-products/send-document', {
      preserveScroll: true,
      onSuccess: () => {
        sendDocumentLoader.value = false;
      },
      onError: () => {
        sendDocumentLoader.value = false;
      },
    });
};
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const selectedItems = ref([]);
const selectedEp = ref([]);

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
const checkTransactionExist = item => {
  for (let price of item.prices) {
    for (let transaction of price.transactions) {
      const paymentStatusDate = transaction.payment_status_date;
      if(paymentStatusDate) {
        var timeStart = new Date(paymentStatusDate);
        var timeEnd = new Date();
        var timeDifferenceInMiliseconds = timeEnd.getTime() - timeStart.getTime();
        if ((transaction.payment_status_id == 6 || transaction.payment_status_id == 4) && timeDifferenceInMiliseconds <= 259200000) {
          return false;
        }
      }
    }
  }
  return true;
};

const { copy, copied } = useClipboard();

const onCopyText = () => {

let paymentLink = page.props.epLink + '/car-insurance/quote/'+props.quote.uuid+'/payment?planId='+props.quote.plan_id+'&providerCode='+props.quote.plan_provider_code;
  copy(paymentLink);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const paymentStatus = id => {
  const enums = paymentStatusEnum || {};
  const item = Object.keys(enums).find(key => enums[key] === id);
  return item ? item : 'N/A';
};

const toggleProduct = (ep, event) => {

  let removeIdFromSelection = [];
  propsDataReactive.value?.forEach(item => {
    if (item.id === ep.embedded_product_id) {
      item.prices.forEach(price => {
        if (price.id !== ep.id && price.transactions[0].is_selected !== false) {
          price.transactions[0].is_selected = false;
          removeIdFromSelection.push(price.id);
        }
      });
    }
  });

  let id = ep.id;
  if (event.target.checked) {
    selectedEp.value.push(id);
  } else {
    removeIdFromSelection.push(id);
  }

  if(removeIdFromSelection.length > 0) {
    removeIdFromSelection.forEach(id => {
      const indexToRemove = selectedEp.value.indexOf(id);
      if (indexToRemove !== -1) {
        selectedEp.value.splice(indexToRemove, 1);
      }
    });
  }

  console.log(selectedEp);

  let data = { quote_uuid: props.quote.uuid, id: id,modelType:props.modelType };
  let requestUrl = '/quotes/' + props.modelType + '/toggle-product';
  axios
    .post(requestUrl, data)
    .then(res => {
      notification.success('Updated');
    })
    .catch(err => {
      notification.error('Something went wrong');
    });
};
const onActivitySubmit = isValid => {
  if (!isValid) return;
  const method = 'post';
  const url = '/quotes/cancel-payment';
  paymentForm.processing = true;
  axios
    .post(url, paymentForm)
    .then(res => {
        modals.cancelPayment = false;
      notification.success('Processed');
    })
    .catch(err => {
      if (err.response.data) {
        notification.error(err.response.data[0]);
      } else {
        notification.error('Something went wrong');
      }
    })
    .finally(() => {
      paymentForm.processing = false;
    });
};
const hasAnyRole = roles => useHasAnyRole(roles);
</script>

<template>
  <x-accordion v-if="useCanAny([permissionsEnum.EMBEDDED_PRODUCT_VIEW, permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL])" show-icon>

    <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">

      <div class="flex flex-wrap gap-4 justify-between items-center">
        <h3 class="font-semibold text-primary-800 text-lg">
          Embedded Products <x-tag size="sm">{{ propsDataReactive.length || 0 }}</x-tag>
        </h3>
        <div style="margin-right:50px;">
          <x-button v-if="selectedEp.length > 0" size="sm" @click.stop="onCopyText()">
            Copy Payment Link
          </x-button>
        </div>
      </div>
      <template #content>
        <x-divider class="mb-4 mt-2 mt-1" />
        <DataTable table-class-name="tablefixed" :headers="epTable.columns" :items="propsDataReactive || []" border-cell
          hide-rows-per-page hide-footer>
          <template #item-code="{ short_code }">
            {{ short_code + '-' + props.code }}
          </template>

          <template #item-prices="{ prices }">

            <div v-if="prices.length > 0" class="flex gap-3">
              <x-tag color="primary" v-for="(priceItem, index) in prices" :key="index">
                <x-checkbox v-model="priceItem.transactions[0].is_selected"
                  @change="toggleProduct(priceItem, $event)" color="primary" :disabled="priceItem.transactions[0]?.payment_status_id == paymentStatusEnum.AUTHORISED
                    || priceItem.transactions[0]?.payment_status_id == paymentStatusEnum.CAPTURED
                    || priceItem.transactions[0]?.payment_status_id == paymentStatusEnum.PARTIAL_CAPTURED" />
                {{ (parseFloat(priceItem.price) + (priceItem.price * 5) / 100).toFixed(2) }}
              </x-tag>
            </div>

          </template>

          <template #item-payment_status="{ prices }">
            {{ paymentStatus(prices[0]?.transactions[0]?.payment_status_id) }}
          </template>

          <template #item-updated_at="{ updated_at }">
            {{ dateFormat(updated_at) }}
          </template>

          <template #item-actions="item">
            <div class="flex flex-col gap-1">
              <x-button size="xs" color="emerald" :disabled="!item.send_document_button" :loading="sendDocumentLoader"
                @click.prevent="sendDcoument(item.id)">
                Send Documents
              </x-button>
              <x-button v-if="item.canGenerateCerticate" size="xs" color="#ff5e00"
                :disabled="!item.send_document_button" :loading="downloadLoader"
                @click.prevent="downloadDcoument(item.id)">
                Download Certificate
              </x-button>
              <x-button size="xs" color="primary" :href="ppDoc(item.company_documents)" target="_blank"
                :disabled="ppDoc(item.company_documents) === ''">
                Download Product Wordings
              </x-button>
              <x-button v-if="useCan(permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL)" size="xs" color="#ff5e00"
                :disabled="checkTransactionExist(item)" @click.prevent="cancelPaymentForm(item)">
                Cancel Payments
              </x-button>
            </div>
          </template>
        </DataTable>
        <x-modal v-if="useCan(permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL)" title="Cancel Payment" v-model="modals.cancelPayment" size="md"
          show-close backdrop is-form @submit="onActivitySubmit">
            <div class="grid gap-4">
              <x-input v-model="paymentForm.amount" label="Amount" :rules="[isRequired, isNumber]" class="w-full" />

              <x-textarea v-model="paymentForm.reason" label="Reason" maxlength="250" :adjust-to-text="false"
                class="w-full" />
            </div>

            <template #secondary-action>
              <x-button size="sm" ghost tabindex="-1" @click.prevent="modals.cancelPayment = false">
                Cancel
              </x-button>
            </template>
            <template #primary-action>
              <x-button size="sm" color="emerald" :loading="paymentForm.processing" type="submit">
                Cancel Payment
              </x-button>
            </template>
        </x-modal>
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
