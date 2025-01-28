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
  isEpLoading: {
    type: Boolean,
    default: false,
  },
});

const propsDataReactive = ref(props.data);
const documentsReactive = ref([]);
const paymentStatusEnum = page.props.paymentStatusEnum;
const permissionsEnum = page.props.permissionsEnum;
const embeddedProductEnum = page.props.embeddedProductEnum;
const modals = reactive({
  cancelPayment: false,
  viewDocuments: false,
  addDocument: false,
});

const { isRequired, isEmail, isNumberOrDecimal, isMobileNo } = useRules();

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

const syncDocumentLoader = ref(false);
const downloadLoader = ref(false);
const sendDocumentLoader = ref(false);
const viewDocumentLoader = ref(false);
const downloadDocumentLoader = ref(false);
const addDocumentLoader = ref(false);
const sendDocumentForm = useForm({
  quoteId: props.quote.id,
  modelType: props.modelType,
  isInertia: true,
});

const addDocumentForm = useForm({
  epId: null,
  type: null,
  title: null,
  file: null,
});

const syncDocument = id => {
  syncDocumentLoader.value = true;
  sendDocumentForm
    .transform(data => ({
      ...data,
      epId: id,
    }))
    .post('/embedded-products/sync-document', {
      preserveScroll: true,
      responseType: 'blob', // Ensure this is correctly set
      onSuccess: response => {
        syncDocumentLoader.value = false;
      },
      onError: () => {
        syncDocumentLoader.value = false;
      },
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

const downloadFile = download => {
  const save = document.createElement('a');
  if (typeof save.download !== 'undefined') {
    // if the download attribute is supported, save.download will return empty string, if not supported, it will return undefined
    // if you are using helper method, such as isNone in ember, you can also do isNone(save.download)
    save.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/embedded-products/download/force?path=' +
      download.path;
    save.target = '_blank';
    save.download = download.name;
    save.dispatchEvent(new MouseEvent('click'));
  } else {
    window.location.href =
      window.location.protocol +
      '//' +
      window.location.host +
      '/embedded-products/download/force?path=' +
      download.path; // so that it opens new tab for IE11
  }

  downloadLoader.value = true;
  setTimeout(() => {
    downloadLoader.value = false;
  }, 1300);
};

const viewDocument = id => {
  viewDocumentLoader.value = true;

  axios
    .post(
      '/embedded-products/get-documents',
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
      documentsReactive.value = response.data;
      modals.viewDocuments = true;
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      viewDocumentLoader.value = false;
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
      text: 'Payment Captured date',
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

const epDocuments = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Document Type',
      value: 'document_type',
    },
    {
      text: 'Document Number',
      value: 'document_number',
    },
    {
      text: 'Actions',
      value: 'actions',
      width: 100,
    },
  ],
});

const getBlog = file => useObjectUrl(file);

const ppDoc = str => {
  const doc = JSON.parse(str);
  return doc[0]?.path !== '' ? usePage().props.cdnPath + doc[0]?.path : '';
};

const { copy, copied } = useClipboard();

const onCopyText = () => {
  let providerCode = props.quote.plan_provider_code;
  if (
    props.modelType.toLowerCase() ==
    page.props.quoteTypeCodeEnum.Bike.toLowerCase()
  ) {
    providerCode = props.quote.car_plan?.insurance_provider?.code;
  }

  let paymentLink =
    page.props.epLink +
    '/' +
    props.modelType.toLowerCase() +
    '-insurance/quote/' +
    props.quote.uuid +
    '/payment?planId=' +
    props.quote.plan_id +
    '&providerCode=' +
    providerCode;
  copy(paymentLink);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};

const getFirstPriceWithTransaction = prices => {
  return prices.find(
    price => price.transactions && price.transactions.length > 0,
  );
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
        if (
          price.id !== ep.id &&
          price.transactions.length > 0 &&
          price.transactions[0].is_selected !== false
        ) {
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

  if (removeIdFromSelection.length > 0) {
    removeIdFromSelection.forEach(id => {
      const indexToRemove = selectedEp.value.indexOf(id);
      if (indexToRemove !== -1) {
        selectedEp.value.splice(indexToRemove, 1);
      }
    });
  }

  let data = {
    quote_uuid: props.quote.uuid,
    id: id,
    modelType: props.modelType,
  };
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
const canAny = permissions => useCanAny(permissions);
const can = permission => useCan(permission);
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const addEpDocument = id => {
  modals.viewDocuments = false;
  modals.addDocument = true;
  addDocumentForm.epId = id;
};

const resetAddForm = () => {
  addDocumentForm.epId = null;
  addDocumentForm.title = null;
  addDocumentForm.type = null;
  addDocumentForm.file = null;
};

const uploadEpDocument = event => {
  addDocumentForm.file = event.files;
};

const cancelEpDocument = () => {
  modals.addDocument = false;
  resetAddForm();
  modals.viewDocuments = true;
};

const onAddDocumentSubmit = event => {
  if (!addDocumentForm.title || !addDocumentForm.type) {
    return;
  }

  if (addDocumentForm.file && addDocumentForm.file.length == 0) {
    notification.error({
      title: 'Document upload failed, invalid file selected',
      position: 'top',
    });
    return;
  }

  const epId = addDocumentForm.epId;
  let url = '/embedded-products/upload-quote-document';
  addDocumentLoader.value = true;

  return new Promise((resolve, reject) => {
    addDocumentForm
      .transform(data => ({
        ...data,
        quoteId: props.quote.id,
        modelType: props.modelType,
      }))
      .post(url, {
        preserveScroll: true,
        preserveState: true,
        onError: errors => {
          addDocumentForm.setError(errors.error);
          notification.error({
            title: 'File upload failed',
            position: 'top',
          });
          reject(errors);
        },
        onSuccess: data => {
          resetAddForm();
          modals.addDocument = false;
          modals.viewDocuments = true;
          viewDocument(epId);
        },
        onFinish: () => {
          addDocumentLoader.value = false;
        },
      });
  });
};
</script>

<template>
  <x-accordion
    v-if="
      canAny([
        permissionsEnum.EMBEDDED_PRODUCT_VIEW,
        permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL,
      ])
    "
    show-icon
  >
    <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-4 justify-between items-center">
        <h3 class="font-semibold text-primary-800 text-lg">
          Embedded Products
          <x-tag size="sm">{{ propsDataReactive.length || 0 }}</x-tag>
        </h3>
        <div style="margin-right: 50px">
          <x-button
            v-if="selectedEp.length > 0"
            size="sm"
            @click.stop="onCopyText()"
          >
            Copy Payment Link
          </x-button>
        </div>
      </div>
      <template #content>
        <x-divider class="mb-4 mt-2" />
        <DataTable
          table-class-name="tablefixed"
          :headers="epTable.columns"
          :items="propsDataReactive || []"
          border-cell
          hide-rows-per-page
          hide-footer
          :loading="isEpLoading"
        >
          <template #item-code="{ short_code }">
            {{ short_code + '-' + props.code }}
          </template>

          <template #item-prices="{ prices }">
            <div v-if="prices.length > 0" class="flex gap-3">
              <div v-for="(priceItem, index) in prices" :key="index">
                <x-tag v-if="priceItem.transactions.length" color="primary">
                  <x-checkbox
                    v-model="priceItem.transactions[0].is_selected"
                    @change="toggleProduct(priceItem, $event)"
                    color="primary"
                    :disabled="
                      priceItem.transactions[0]?.payment_status_id ==
                        paymentStatusEnum.AUTHORISED ||
                      priceItem.transactions[0]?.payment_status_id ==
                        paymentStatusEnum.CAPTURED ||
                      priceItem.transactions[0]?.payment_status_id ==
                        paymentStatusEnum.PARTIAL_CAPTURED
                    "
                  />
                  {{
                    (
                      parseFloat(priceItem.price) +
                      (priceItem.price * 5) / 100
                    ).toFixed(2)
                  }}
                </x-tag>
              </div>
            </div>
          </template>

          <template #item-payment_status="{ prices }">
            {{
              paymentStatus(
                getFirstPriceWithTransaction(prices)?.transactions[0]
                  ?.payment_status_id,
              )
            }}
          </template>

          <template #item-updated_at="item">
            <span
              v-if="
                getFirstPriceWithTransaction(item.prices)?.transactions[0]
                  ?.payment_status_id == paymentStatusEnum.CAPTURED
              "
            >
              <span v-if="item.short_code == embeddedProductEnum.TRAVEL">
                {{
                  getFirstPriceWithTransaction(item.prices)?.transactions[0]
                    ?.travel_annual_payments?.captured_at
                }}
              </span>
              <span v-else>
                {{
                  getFirstPriceWithTransaction(item.prices)?.transactions[0]
                    ?.payments[0]?.captured_at
                }}
              </span>
            </span>
            <span v-else> - </span>
          </template>

          <template #item-actions="item">
            <div
              class="flex flex-col gap-1"
              v-if="readOnlyMode.isDisable === true"
            >
              <x-button
                size="xs"
                color="emerald"
                v-if="item.sync_document_button"
                :disabled="!item.sync_document_button"
                :loading="syncDocumentLoader"
                @click.prevent="syncDocument(item.id)"
              >
                Sync Documents from Provider
              </x-button>
              <x-button
                size="xs"
                color="emerald"
                :disabled="
                  !item.send_document_button ||
                  item.short_code == embeddedProductEnum.COURIER
                "
                :loading="sendDocumentLoader"
                @click.prevent="sendDcoument(item.id)"
              >
                Send Documents
              </x-button>
              <x-button
                size="xs"
                color="#ff5e00"
                :disabled="item.short_code == embeddedProductEnum.COURIER"
                :loading="viewDocumentLoader"
                @click.prevent="viewDocument(item.id)"
              >
                View Documents
              </x-button>
              <x-button
                v-if="can(permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL)"
                size="xs"
                color="#ff5e00"
                :disabled="!item.can_cancel_payment"
                @click.prevent="cancelPaymentForm(item)"
              >
                Cancel Payments
              </x-button>
            </div>
          </template>
        </DataTable>
        <x-modal
          v-if="can(permissionsEnum.EMBEDDED_PRODUCT_PAYMENT_CANCEL)"
          title="Cancel Payment"
          v-model="modals.cancelPayment"
          size="md"
          show-close
          backdrop
          is-form
          @submit="onActivitySubmit"
        >
          <div class="grid gap-4">
            <x-input
              v-model="paymentForm.amount"
              label="Amount"
              :rules="[isRequired, isNumberOrDecimal]"
              class="w-full"
            />

            <x-textarea
              v-model="paymentForm.reason"
              label="Reason"
              maxlength="250"
              :adjust-to-text="false"
              class="w-full"
            />
          </div>

          <template #secondary-action>
            <x-button
              size="sm"
              ghost
              tabindex="-1"
              @click.prevent="modals.cancelPayment = false"
            >
              Cancel
            </x-button>
          </template>
          <template #primary-action>
            <x-button
              size="sm"
              color="emerald"
              :loading="paymentForm.processing"
              type="submit"
            >
              Cancel Payment
            </x-button>
          </template>
        </x-modal>
        <x-modal v-model="modals.viewDocuments" size="lg" show-close backdrop>
          <template #header>
            <div class="px-6 py-4 bg-gray-100">
              EP - {{ documentsReactive.ep.display_name }} - Documents
            </div>
          </template>

          <template #footer>
            <div class="mt-2 mb-5 text-center hidden">
              <x-button
                size="xs"
                class="border-0 shadow-none"
                style="box-shadow: none"
                @click.prevent="addEpDocument(documentsReactive.ep.id)"
                v-if="documentsReactive.can_add_document"
              >
                <span
                  class="bg-primary color-white leading-5 mr-1 rounded-full h-[20px] w-[20px] inline-block"
                  >+</span
                >
                Click to add document(s)
              </x-button>
            </div>
          </template>

          <DataTable
            :headers="epDocuments.columns"
            :items="documentsReactive.documents || []"
            border-cell
            hide-rows-per-page
            hide-footer
            :loading="viewDocumentLoader"
          >
            <template #item-actions="item">
              <div class="flex flex-row gap-3">
                <x-button
                  size="xs"
                  color="primary"
                  outlined
                  :href="item.url"
                  target="_blank"
                >
                  View
                </x-button>

                <x-button
                  size="xs"
                  color="emerald"
                  outlined
                  :loading="downloadLoader"
                  @click.prevent="downloadFile(item)"
                >
                  Download
                </x-button>
              </div>
            </template>
          </DataTable>
        </x-modal>

        <x-modal
          v-model="modals.addDocument"
          size="md"
          show-close
          backdrop
          is-form
          @submit="onAddDocumentSubmit"
        >
          <template #header>
            <div class="px-6 py-4 bg-gray-100">Add Document</div>
          </template>

          <div class="grid gap-4">
            <x-input
              v-model="addDocumentForm.type"
              label="Document Type"
              :rules="[isRequired]"
              class="w-full"
            />

            <x-input
              v-model="addDocumentForm.title"
              label="Document serial number / title"
              :rules="[isRequired]"
              class="w-full"
            />

            <div
              v-if="addDocumentForm.file"
              class="relative bg-primary-50 rounded-md text-center flex flex-col gap-4 items-center border border-primary-300 ease-linear transition-all duration-150 p-4"
            >
              <div>
                {{ addDocumentForm.file[0].file.name }}
              </div>

              <x-button
                @click="addDocumentForm.file = null"
                size="xs"
                color="error"
              >
                Remove
              </x-button>
            </div>

            <Dropzone
              v-else
              @change="uploadEpDocument($event)"
              :maxSize="documentsReactive.document_type?.max_size"
              :accept="documentsReactive.document_type?.accepted_files"
            />
          </div>

          <template #secondary-action>
            <x-button
              ghost
              tabindex="-1"
              @click="cancelEpDocument()"
              :disabled="addDocumentLoader"
            >
              Cancel
            </x-button>
          </template>
          <template #primary-action>
            <x-button
              color="primary"
              type="submit"
              :loading="addDocumentLoader"
            >
              Save
            </x-button>
          </template>
        </x-modal>
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
