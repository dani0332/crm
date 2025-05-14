<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  uuid: String,
  insuranceProviders: Array,
  currencies: Array,
  lifeRiders: Array,
  plan: Object,
  modelValue: Boolean,
});

const notification = useNotifications('toast');

const { isRequired } = useRules();

const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const validatePriceRange = value => {
  if (!value) return true;
  const price = parseFloat(value);
  if ((createForm.isManualPlan && price < 1) || price > 100000000) {
    return 'Value must be between 1 and 100,000,000';
  }
  return true;
};

const validatePolicyTerm = value => {
  if (!value) return true;
  const policyTerm = parseFloat(value);
  if (policyTerm < 1 || policyTerm > 100) {
    return 'Value must be between 1 to 100';
  }
  return true;
};

const riders = props.lifeRiders.map(rider => ({
  riderId: rider.id,
  active: 0,
  price: 0,
  coverValue: 0,
  text: rider.text,
}));

const ridersData = ref(riders);
const page = usePage();
const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const active = ref(false);

const paymentTerms = [
  { value: 1, label: 'Monthly' },
  { value: 3, label: 'Quarterly' },
  { value: 6, label: 'Semi-Annually' },
  { value: 12, label: 'Annually' },
];

const availableInsuranceProviders = computed(() => {
  return props.insuranceProviders;
});

let errorMessage = null;
let alreadyQuoted = ref(null);

const createForm = reactive({
  providerId: null,
  planId: null,
  loading: false,
  isUW: 0,
  currency: null,
  paymentTerm: null,
  sumAssured: null,
  policyTerm: null,
  actualPremium: null,
  insurerQuoteNo: null,
  isVariant: true,
  update: false,
  getQuoteLoading: false,
});

// Ensure numeric fields are never negative
watch(
  () => createForm.sumAssured,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.sumAssured = 0;
    }
  },
);

watch(
  () => createForm.policyTerm,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.policyTerm = 0;
    }
  },
);

watch(
  () => createForm.actualPremium,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.actualPremium = 0;
    }
  },
);

// Watch for negative values in riders
watch(
  () => ridersData.value,
  newRiders => {
    newRiders.forEach(rider => {
      if (Number(rider.price) < 0) rider.price = 0;
      if (Number(rider.coverValue) < 0) rider.coverValue = 0;
    });
  },
  { deep: true },
);

watch(
  () => props.plan,
  newVal => {
    createForm.providerId = props.plan?.providerId;
    createForm.planId = props.plan?.planId;
    createForm.currency = props.plan?.currency;
    createForm.sumAssured = props.plan?.sumInsured
      ? Math.max(0, Number(props.plan.sumInsured))
      : null;
    createForm.policyTerm = props.plan?.policyTerm
      ? Math.max(0, Number(props.plan.policyTerm))
      : null;
    createForm.paymentTerm = props.plan?.paymentTerm;
    createForm.actualPremium = null;
    if (!props.plan?.isApi) {
      createForm.actualPremium = props.plan?.actualPremium
        ? Math.max(0, Number(props.plan.actualPremium))
        : null;
    }
    createForm.insurerQuoteNo = null;
    errorMessage = null;

    // Handle rider data on plan change
    if (props.plan?.riders && props.plan.riders.length > 0) {
      console.log('Watch - Using riders from plan:', props.plan.riders);
      ridersData.value = props.plan.riders.map(rider => ({
        riderId: rider.id,
        active: rider.active ?? 0,
        price: Math.max(0, parseFloat(rider.price) || 0),
        coverValue: Math.max(0, parseFloat(rider.coverValue) || 0),
        text: rider.text,
      }));
    } else {
      console.log('Watch - No riders in plan, using lifeRiders');
      ridersData.value = props.lifeRiders.map(rider => ({
        riderId: rider.id,
        active: 0,
        price: 0,
        coverValue: 0,
        text: rider.text,
      }));
    }
    console.log('Watch - Final rider data:', ridersData.value);
  },
  { deep: true },
);

const getQuote = () => {
  createForm.getQuoteLoading = true;
  
  if(!validatePolicyTerm(createForm.policyTerm)) {
    return;
  }
  axios
    .post(`/personal-quotes/get-life-provider-plan`, {
      data: {
        quoteUID: props.uuid,
        planId: props.plan.planId,
        providerCode: props.plan.providerCode,
        isIndividualLoading: true,
        planData: {
          currency: createForm.currency,
          sumAssured: parseFloat(createForm.sumAssured).toFixed(2),
          policyTerm: createForm.policyTerm,
          paymentTerm: createForm.paymentTerm,
          riders: ridersData.value,
        },
        lang: 'en',
      },
    })
    .then(res => {
      if (res.data.providerPlan.message) {
        errorMessage = res.data.providerPlan.message;
        return;
      }
      if (res.data) {
        createForm.actualPremium = Math.max(
          0,
          Number(res.data.providerPlan.plan.actualPremium),
        );
        errorMessage = null;
      }
    })
    .catch(err => {
      errorMessage = err.response.data.message;
    })
    .finally(() => {
      createForm.getQuoteLoading = false;
    });
};

const onSubmit = isValid => {
  if (!isValid) {
    return;
  }
  createForm.loading = true;
  createForm.sumAssured = parseFloat(createForm.sumAssured).toFixed(2);
  createForm.actualPremium = parseFloat(createForm.actualPremium).toFixed(2);
  createForm.riders = ridersData.value;

  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: createForm,
    })
    .then(res => {
      if (res?.data?.msg) {
        alreadyQuoted = res.data.msg;
        return;
      }

      notification.success({
        title: res.data.message,
        position: 'top',
      });

      setTimeout(() => {
        location.reload();
      }, 2000);
    })
    .catch(err => {
      emit('error');

      const alreadyQuotedMessage = 'This plan detail is already quoted';
      if (
        err?.response?.data?.message &&
        err?.response?.data?.message.includes(alreadyQuotedMessage)
      ) {
        alreadyQuoted = 'This plan detail is already quoted';
        return;
      }

      notification.error({
        title: res.data.msg,
        position: 'top',
      });
    })
    .finally(() => {
      createForm.loading = false;
    });
};

// Add onMounted hook to load rider data when component is mounted
onMounted(() => {
  if (props.plan) {
    console.log('Plan data:', props.plan);

    // Initialize form data from plan
    createForm.providerId = props.plan.providerId;
    createForm.planId = props.plan.planId;
    createForm.currency = props.plan.currency;
    createForm.sumAssured = props.plan.sumInsured
      ? Math.max(0, Number(props.plan.sumInsured))
      : null;
    createForm.policyTerm = props.plan.policyTerm
      ? Math.max(0, Number(props.plan.policyTerm))
      : null;
    createForm.paymentTerm = props.plan.paymentTerm;
    createForm.actualPremium = null;
    if (!props.plan.isApi) {
      createForm.actualPremium = props.plan.actualPremium
        ? Math.max(0, Number(props.plan.actualPremium))
        : null;
    }

    // Handle rider data initialization
    if (
      props.plan.riders &&
      Array.isArray(props.plan.riders) &&
      props.plan.riders.length > 0
    ) {
      console.log('Using riders from plan:', JSON.stringify(props.plan.riders));
      ridersData.value = props.plan.riders.map(rider => {
        const mappedRider = {
          riderId: rider.id,
          active: rider.active ?? 0,
          price: Math.max(0, parseFloat(rider.price) || 0),
          coverValue: Math.max(0, parseFloat(rider.coverValue) || 0),
          text: rider.text || rider.name,
        };
        console.log('Mapped rider:', mappedRider);
        return mappedRider;
      });
    } else {
      console.log(
        'No riders in plan, using lifeRiders:',
        JSON.stringify(props.lifeRiders),
      );
      ridersData.value = props.lifeRiders.map(rider => {
        const mappedRider = {
          riderId: rider.id,
          active: 0,
          price: 0,
          coverValue: 0,
          text: rider.text || rider.name,
        };
        console.log('Mapped lifeRider:', mappedRider);
        return mappedRider;
      });
    }

    console.log('Final rider data:', JSON.stringify(ridersData.value));
  }
});

// Add validation for non-negative numbers
const isNonNegative = value => {
  if (value === null || value === undefined || value === '') return true;
  return parseFloat(value) >= 0 || 'Value must be non-negative';
};
</script>

<template>
  <x-modal
    v-model="shown"
    size="lg"
    title="Add Variant"
    show-close
    backdrop
    is-form
    @submit="onSubmit"
  >
    <div class="mx-auto p-6 bg-white rounded-lg">
      <h2
        class="bg-gray-100 text-gray-700 font-semibold text-center rounded-lg px-6 py-3 -mt-4 mb-2"
      >
        {{ props.plan.planName }}
      </h2>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Insurance Provider <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.providerId"
            :options="
              availableInsuranceProviders.map((insuranceProver, index) => ({
                value: insuranceProver.id,
                label: insuranceProver.text,
              }))
            "
            :rules="[isRequired]"
            placeholder=""
            class="w-full"
            disabled
          />
        </div>
        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Plan <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.planId"
            placeholder="Select Plan"
            class="w-full"
            :options="[
              {
                value: plan.planId,
                label: plan.planName,
              },
            ]"
            :rules="[isRequired]"
            disabled
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-gray-700 mb-1"
              >Currency <span class="text-red-500">*</span></label
            >
            <x-select
              v-model="createForm.currency"
              placeholder="AED"
              class="w-full"
              :options="
                props.currencies?.map(currency => ({
                  value: currency.text,
                  label: currency.text,
                }))
              "
              :rules="[isRequired]"
            />
          </div>
          <div>
            <label class="block font-medium text-gray-700 mb-1"
              >Sum Assured <span class="text-red-500">*</span></label
            >
            <x-input
              v-model="createForm.sumAssured"
              placeholder="Enter Sum Assured"
              :rules="[isRequired, isNonNegative, validatePriceRange]"
              class="w-full"
              type="number"
              min="0"
              step="any"
              @keydown="
                e => (e.key === 'e' || e.key === '-') && e.preventDefault()
              "
            />
          </div>
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Policy Term <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.policyTerm"
            placeholder="Enter Policy Term"
            :rules="[isRequired, isNonNegative, validatePolicyTerm]"
            class="w-full"
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Payment Terms <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.paymentTerm"
            placeholder="Select Payment Terms"
            class="w-full"
            :options="paymentTerms"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Price (VAT not applicable)
            <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.actualPremium"
            placeholder="Enter Price"
            :rules="[isRequired, isNonNegative, validatePriceRange]"
            class="w-full"
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            :disabled="plan.isApi"
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Insurer Quote Number</label
          >
          <x-input
            v-model="createForm.insurerQuoteNo"
            placeholder="Enter Insurer Quote Number"
            class="w-full"
            :disabled="plan.isApi"
          />
        </div>
        <div
          v-if="errorMessage"
          class="flex items-center justify-center text-red-500 font-medium"
        >
          <span>{{ errorMessage }}</span>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold bg-gray-100 p-4 rounded-md text-gray-700">
          RIDERS
        </h3>

        <div class="grid grid-cols-6 items-center gap-4 p-2 border-b">
          <span class="text-gray-700 col-span-2">Life Cover</span>
          <span class="text-gray-700">Included</span>
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.sumAssured"
            disabled
          />

          <x-toggle color="emerald" size="lg" disabled />
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.actualPremium"
            disabled
          />
        </div>
        <div
          class="grid grid-cols-6 items-center gap-4 p-2 border-b"
          v-for="(rider, index) in ridersData"
          :key="rider.riderId"
        >
          <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
          <span class="text-gray-700">{{
            rider.active ? 'Included' : 'Optional'
          }}</span>
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            :disabled="!rider.active"
            class="w-full h-10 p-2 rounded-md"
            v-model="rider.coverValue"
          />
          <x-toggle v-model="rider.active" color="success" size="lg" />
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            class="w-full h-10 p-2 rounded-md"
            :disabled="!rider.active || !props.plan.isManualPlan"
            v-model="rider.price"
          />
        </div>
      </div>
    </div>

    <template #actions>
      <div class="flex justify-between w-full">
        <!-- Left side: Text -->
        <div class="flex justify-start">
          <p class="text-red-500">{{ alreadyQuoted }}</p>
        </div>

        <!-- Right side: Button -->
        <div class="flex justify-end">
          <x-button class="mr-2" @click="shown = false"> Cancel </x-button>
          <x-button
            @click="getQuote"
            v-if="!createForm.actualPremium && plan.isApi"
            color="emerald"
            :loading="createForm.getQuoteLoading"
          >
            Get Quote
          </x-button>

          <x-button
            v-else
            type="submit"
            color="emerald"
            :loading="createForm.loading"
          >
            Save
          </x-button>
        </div>
      </div>
    </template>
  </x-modal>
</template>
