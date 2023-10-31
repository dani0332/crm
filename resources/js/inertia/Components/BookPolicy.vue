<script setup>
const page = usePage();

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
});

const bPForm = useForm({
  insurer_invoice_date: null,
});

const bp = reactive({
  isEditing: false,
});

const bpForm = useForm({
  insurer_invoice_date: '',
  insurer_tax_invoice_number: '',
  insurer_commmission_invoice_number: '',
  commission_vat_not_applicable: '',
  commission_vat_applicable: '',
  commission_percentage: '',
});
const onUpdateBpDetails = () => {
  bpForm.post('/quotes/update-booking-policy', {
    preserveScroll: true,
    onSuccess: () => {
      policyDetailsState.isEditing = false;
    },
  });
};

console.log('quote' + JSON.stringify(props.quote));

const notVatCommission = () => {};

const vatCommission = () => {
  if (bpForm.commission_vat_applicable > 0) {
    bpForm.commission_percentage = (
      (bpForm.commission_vat_applicable / props.quote?.price_without_vat) *
      100
    ).toFixed(2);
  } else {
    bpForm.commission_percentage = '';
  }
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Book Policy</h3>
      <x-divider class="mb-4 mt-1" />
    </div>

    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Booking Date</dt>
          <dd></dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Invoice Description</dt>
          <dd>eee</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Main Class Insurance</dt>
          <dd>{{ props?.quoteType }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Transaction Payment Status</dt>
          <dd>Not Paid</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Sub Class</dt>
          <dd>aaa</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insurer Invoice Date</dt>
          <dd>
            <DatePicker
              v-model="bPForm.insurer_invoice_date"
              type="date"
              placeholder="Expiry Date"
              class="w-full"
              :disabled="!bp.isEditing"
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Broker Invoice Number</dt>
          <dd>aaa</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insurer Tax Invoice Number</dt>
          <dd>
            <x-input
              v-model="bpForm.insurer_tax_invoice_number"
              placeholder="Insurer Tax Invoice Number"
              class="w-full"
              :disabled="!bp.isEditing"
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Discount</dt>
          <dd>aaa</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insurer Commmission Invoice Number</dt>
          <dd>
            <x-input
              v-model="bpForm.insurer_commmission_invoice_number"
              placeholder="Insurer Tax Invoice Number"
              class="w-full"
              :disabled="!bp.isEditing"
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
              @change="notVatCommission"
              placeholder="Commmission VAT NOT APPLICABLE"
              class="w-full"
              :disabled="
                !bp.isEditing || bpForm.commission_vat_applicable !== ''
              "
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">VAT on commission</dt>
          <dd>aaa</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Commmission VAT APPLICABLE</dt>
          <dd>
            <x-input
              v-model="bpForm.commission_vat_applicable"
              @change="vatCommission"
              placeholder="Commmission VAT APPLICABLE"
              class="w-full"
              :disabled="
                !bp.isEditing || bpForm.commission_vat_not_applicable !== ''
              "
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Total commission</dt>
          <dd>aaa</dd>
        </div>
      </dl>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2"></div>
        <div class="w-full md:w-1/2" />
      </div>
      <div class="flex justify-end">
        <x-button
          v-if="bp.isEditing"
          class="mt-4 mr-2"
          color="emerald"
          size="sm"
          :loading="bPForm.processing"
          @click.prevent="bp.isEditing = false"
        >
          Cancel
        </x-button>
        <x-button
          v-if="bp.isEditing"
          class="mt-4 mr-2"
          color="emerald"
          size="sm"
          :loading="bPForm.processing"
          @click.prevent="onUpdateBpDetails"
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
          v-if="props.bPDetails?.sendButton"
        >
          {{ props.bPDetails?.text }}
        </x-button>
      </div>
    </div>
  </div>
</template>
