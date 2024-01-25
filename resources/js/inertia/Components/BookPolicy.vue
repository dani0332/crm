<script setup>
const page = usePage();
const notification = useNotifications('toast');
const { isRequired } = useRules();
const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  quoteType: {
    type: String,
    default: '',
  },
  bPDetails: {
    type: Array,
    default: [],
  },
  payments: {
    type: Array,
    default: [],
  },
});

const isLoading = ref(false);

const dateToYMD = date => {
  if (date) {
    const [year, month, day] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const bp = reactive({
  isEditing: false,
});

const transactionPaymentStatus = computed(() => {
  if (Number(props?.quote?.price_with_vat) === 0) {
    return 'Not Paid';
  }
  if (Number(props?.quote?.premium) > Number(props?.quote?.price_with_vat)) {
    return 'Partially Paid';
  }
  if (Number(props?.quote?.premium) === Number(props?.quote?.price_with_vat)) {
    return 'Paid';
  }
});
const bpForm = useForm({
  booking_date:
    dateToYMD(props.quote?.policy_booking_date) ||
    new Date().toJSON().slice(0, 10),
  transaction_payment_status: transactionPaymentStatus.value,
  invoice_date: dateToYMD(page.props.payments[0]?.insurer_invoice_date) || '',
  invoice_description: page.props.bPDetails.invoiceDescription || '',
  broker_invoice_number: page.props.bPDetails.brokerInvoiceNo || '',
  insurer_tax_invoice_number: page.props?.payments[0]?.insurer_tax_number || '',
  insurer_commmission_invoice_number:
    page.props?.payments[0]?.insurer_commmission_invoice_number || '',
  commission_vat_not_applicable:
    page.props?.payments[0]?.commission_vat_not_applicable || '',
  commission_vat_applicable:
    page.props?.payments[0]?.commission_vat_applicable || '',
  commission_percentage: page.props?.payments[0]?.commmission_percentage || '',
  vat_on_commission: page.props?.payments[0]?.commission_vat || '',
  total_commission: page.props?.payments[0]?.commission || '',
  payment_code: page.props?.payments[0]?.code,
  discount: page.props?.payments[0]?.discount_value || '',
  model_type: props.quoteType,
  quote_id: page.props.quote.id,
});

const onUpdateBpDetails = isValid => {
  if (isValid) {
    bpForm.post('/quotes/update-booking-policy', {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Book policy details update Successfully',
          position: 'top',
        });

        bp.isEditing = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    });
  } else {
    console.log('Invalid');
  }
};

const modals = reactive({
  sendPolicyConfirm: false,
  isConfirmed: false,
});
const confirmSendPolicy = () => {
  modals.sendPolicyConfirm = true;
};

const submitPolicy = () => {
  isLoading.value = true;
  let url = '/quotes/send-booking-policy';
  let data = {
    send_policy_type: props.bPDetails.sendPolicyType,
    model_type: props?.quoteType,
    quote_id: props?.quote?.id,
  };
  axios
    .post(url, data)
    .then(response => {
      if (response.status == 200) {
        notification.success({
          title: 'Policy Sent Successfully',
          position: 'top',
        });
        location.reload();
        modals.sendPolicyConfirm = false;
      }
    })
    .catch(err => {
      const flash_messages = err.response.data.errors;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      modals.sendPolicyConfirm = false;
      isLoading.value = false;
    });
};

const caculateCommission = () => {
  if (bpForm.commission_vat_applicable > 0) {
    if (Number(props.quote?.price_with_vat > 0)) {
      bpForm.commission_percentage = (
        (bpForm.commission_vat_applicable / props.quote?.price_with_vat) *
        100
      ).toFixed(2);

      bpForm.vat_on_commission = (
        bpForm.commission_percentage * page.props.vat
      ).toFixed(2);
      bpForm.total_commission =
        Number(bpForm.vat_on_commission) +
        Number(bpForm.commission_vat_applicable);
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT APPLICABLE)',
        position: 'top',
      });
    }
  } else if (bpForm.commission_vat_not_applicable > 0) {
    if (Number(props.quote?.price_vat_not_applicable) > 0) {
      bpForm.commission_percentage = (
        (bpForm.commission_vat_not_applicable /
          props.quote?.price_vat_not_applicable) *
        100
      ).toFixed(2);

      bpForm.total_commission = bpForm.commission_vat_not_applicable;
    } else {
      notification.error({
        title: 'Please add Policy Detail Price (VAT NOT APPLICABLE)',
        position: 'top',
      });
    }
  } else {
    bpForm.commission_percentage = '';
    bpForm.vat_on_commission = '';
    bpForm.total_commission = '';
  }
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Book Policy</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <x-form @submit="onUpdateBpDetails" :auto-focus="false">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Booking Date</dt>
            <dd>{{ bpForm.booking_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Invoice Description</dt>
            <dd>{{ bpForm.invoice_description }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Main Class Insurance</dt>
            <dd>{{ props?.quoteType }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Transaction Payment Status</dt>
            <dd>{{ bpForm.transaction_payment_status }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Sub Class</dt>
            <dd></dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Insurer Invoice Date</dt>
            <dd>
              <DatePicker
                v-model="bpForm.invoice_date"
                type="date"
                placeholder="Insurer Invoice Date"
                class="w-full"
                :disabled="!bp.isEditing"
                :rules="[isRequired]"
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Broker Invoice Number</dt>
            <dd>{{ bpForm.broker_invoice_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Insurer Tax Invoice Number</dt>
            <dd>
              <x-input
                v-model="bpForm.insurer_tax_invoice_number"
                placeholder="Insurer Tax Invoice Number"
                class="w-full"
                :disabled="!bp.isEditing"
                :rules="[isRequired]"
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Discount</dt>
            <dd>{{ bpForm.discount }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Insurer Commmission Invoice Number</dt>
            <dd>
              <x-input
                v-model="bpForm.insurer_commmission_invoice_number"
                placeholder="Insurer Tax Invoice Number"
                class="w-full"
                :disabled="!bp.isEditing"
                :rules="[isRequired]"
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Commmission %</dt>
            <dd>{{ bpForm.commission_percentage }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Commmission (VAT NOT APPLICABLE)</dt>
            <dd>
              <x-input
                v-model="bpForm.commission_vat_not_applicable"
                @change="caculateCommission"
                placeholder="Commmission VAT NOT APPLICABLE"
                class="w-full"
                :disabled="
                  !bp.isEditing || bpForm.commission_vat_applicable !== ''
                "
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">VAT on Commission</dt>
            <dd>{{ bpForm.vat_on_commission }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Commmission VAT APPLICABLE</dt>
            <dd>
              <x-input
                v-model="bpForm.commission_vat_applicable"
                @change="caculateCommission"
                placeholder="Commmission VAT APPLICABLE"
                class="w-full"
                :disabled="
                  !bp.isEditing || bpForm.commission_vat_not_applicable !== ''
                "
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Total Commission</dt>
            <dd>{{ bpForm.total_commission }}</dd>
          </div>
        </dl>
        <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
          <div class="w-full md:w-1/2"></div>
          <div class="w-full md:w-1/2" />
        </div>
        <div class="flex justify-end">
          <template
            v-if="
              props.quote.quote_status_id ==
              page.props.quoteStatusEnum.TransactionApproved
            "
          >
            <x-button
              v-if="bp.isEditing"
              class="mt-4 mr-2"
              color="emerald"
              size="sm"
              :loading="bpForm.processing"
              @click.prevent="bp.isEditing = false"
            >
              Cancel
            </x-button>
            <x-button
              v-if="bp.isEditing"
              class="mt-4 mr-2"
              color="emerald"
              size="sm"
              :loading="bpForm.processing"
              type="submit"
            >
              Update
            </x-button>
            <x-button
              v-if="!bp.isEditing && props.bPDetails?.editButton"
              class="mt-4 mr-2"
              color="emerald"
              size="sm"
              @click.prevent="bp.isEditing = true"
            >
              Edit
            </x-button>
            <x-button
              size="sm"
              color="orange"
              class="mt-4"
              @click.prevent="confirmSendPolicy"
              :disabled="bp.isEditing"
              v-if="props.bPDetails?.sendButton"
            >
              {{ props.bPDetails?.text }}
            </x-button></template
          >

          <template
            v-else-if="
              props.quote.quote_status_id ==
              page.props.quoteStatusEnum.PolicyBooked
            "
          >
            <x-tooltip>
              <x-button
                class="mt-4 mr-2"
                size="sm"
                color="emerald"
                :disabled="true"
                >Edit
              </x-button>
              <template #tooltip>
                <span>{{
                  'The Button is not accessable because policy has been booked'
                }}</span>
              </template>
            </x-tooltip>
            <x-tooltip>
              <x-button
                v-if="
                  props.quote.quote_status_id ==
                  page.props.quoteStatusEnum.PolicyBooked
                "
                size="sm"
                class="mt-4 mr-2"
                color="orange"
                :disabled="true"
              >
                {{ props.bPDetails?.text }}
              </x-button>
              <template #tooltip>
                <span>{{
                  'The Button is not accessable because policy has been booked'
                }}</span>
              </template>
            </x-tooltip>
          </template>
          <template v-else>
            <template
              v-if="
                props.quote.quote_status_id ==
                  page.props.quoteStatusEnum.PolicySentToCustomer &&
                props.bPDetails?.editButton
              "
            >
              <x-button
                v-if="bp.isEditing"
                class="mt-4 mr-2"
                color="emerald"
                size="sm"
                :loading="bpForm.processing"
                @click.prevent="bp.isEditing = false"
              >
                Cancel
              </x-button>
              <x-button
                v-if="bp.isEditing"
                class="mt-4 mr-2"
                color="emerald"
                size="sm"
                :loading="bpForm.processing"
                type="submit"
              >
                Update
              </x-button>
              <x-button
                v-if="!bp.isEditing && props.bPDetails?.editButton"
                class="mt-4 mr-2"
                color="emerald"
                size="sm"
                @click.prevent="bp.isEditing = true"
              >
                Edit
              </x-button>
              <x-button
                size="sm"
                color="orange"
                class="mt-4"
                @click.prevent="confirmSendPolicy"
                :disabled="bp.isEditing"
                v-if="props.bPDetails?.sendButton"
              >
                {{ props.bPDetails?.text }}
              </x-button>
            </template>
            <template v-else
              ><x-tooltip>
                <x-button
                  v-if="
                    props.quote.quote_status_id ==
                      page.props.quoteStatusEnum.PolicySentToCustomer &&
                    !props.bPDetails?.editButton
                  "
                  size="sm"
                  class="mt-4 mr-2"
                  color="orange"
                  :disabled="true"
                >
                  {{ props.bPDetails?.text }}
                </x-button>
                <template #tooltip>
                  <span>{{
                    'The Button is not accessable because policy has been sent to customer'
                  }}</span>
                </template>
              </x-tooltip></template
            >
          </template>
        </div>
      </div>
    </x-form>

    <x-modal v-model="modals.sendPolicyConfirm" show-close backdrop>
      <template #header> Send Policy </template>
      <x-checkbox
        v-model="modals.isConfirmed"
        label="I confirm and attest that all the information is correct"
      />
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="modals.sendPolicyConfirm = false"
          >
            Cancel
          </x-button>

          <x-button
            size="sm"
            color="error"
            :disabled="!modals.isConfirmed"
            @click.prevent="submitPolicy"
            :loading="isLoading"
          >
            Confirm
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
