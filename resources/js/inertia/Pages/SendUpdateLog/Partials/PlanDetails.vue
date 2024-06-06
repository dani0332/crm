<script setup>
const { isRequired } = useRules();

const props = defineProps({
  updateLogOptions: {
    type: Object,
    required: true,
  },
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  quoteType: {
    type: String,
    required: true,
  },
  isUpdateBooked: {
    type: Boolean,
    required: true,
  },
});

const page = usePage();
const notification = useToast();

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const sendUpdateEnums = page.props.sendUpdateStatusEnum;

const state = reactive({
  isEdit: false,
});

const planDetailsForm = useForm({
  price_with_vat: props.sendUpdateLog?.price_with_vat || null,
  price_without_vat: props.sendUpdateLog?.price_without_vat || null,
  total_price: props.sendUpdateLog?.total_price || null,
  insurer_quote_number: props.sendUpdateLog?.insurer_quote_number || null,
  insurance_provider_id: props.sendUpdateLog?.insurance_provider_id || null,
  id: props.sendUpdateLog?.id,
});

const isIndicativeAdditionalPrice = computed(() => {
  let hasRestrictedSubType = false;
  props.updateLogOptions?.forEach(option => {
    if (
      [sendUpdateEnums.MDOM, sendUpdateEnums.MDOV, sendUpdateEnums.MPC, sendUpdateEnums.ED, sendUpdateEnums.DM].includes(option.slug) &&
      props.sendUpdateLog.option_id === option.value
    ) {
      hasRestrictedSubType = true;
    }
  });
  return (
    props.sendUpdateLog.category.code === 'EF' && !hasRestrictedSubType
  );
});

const isPlanDetails = computed(() => {
  return props.sendUpdateLog.category.code === 'CPD';
});

const insuranceProvidersOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const roundDecimal = value => {
  return value ? parseFloat(value.toFixed(2)) : '';
};

const updatePriceWithVat = () => {
  let totalPrice = 0;
  const priceWithVat = parseFloat(planDetailsForm.price_with_vat);
  const priceWithoutVat = parseFloat(planDetailsForm.price_without_vat);

  if (priceWithVat && priceWithoutVat) {
    totalPrice = (priceWithVat / 100) * 5 + priceWithVat + priceWithoutVat;
  } else if (priceWithVat) {
    totalPrice = (priceWithVat / 100) * 5 + priceWithVat;
  } else if (priceWithoutVat) {
    totalPrice = priceWithoutVat;
  }

  planDetailsForm.total_price = roundDecimal(totalPrice);
  planDetailsForm.price_with_vat = roundDecimal(priceWithVat);
  planDetailsForm.price_without_vat = roundDecimal(priceWithoutVat);
};

const onUpdate = () => {
  if (!planDetailsForm.price_with_vat && !planDetailsForm.price_without_vat) {
    notification.error({
      title: 'Please enter price.',
      position: 'top',
    });
    planDetailsForm.total_price = null;
    return;
  }
  planDetailsForm.post(route('send-update.save-price-details'), {
    preserverScroll: true,
    onSuccess: ({ props }) => {
      notification.success({
        title: 'The request has been updated',
        position: 'top',
      });
      state.isEdit = false;
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
};

const onKeyPress = event => {
  if (event.key === 'e' || event.key === 'E') {
    event.preventDefault();
  }
};

const onCancel = () => {
  state.isEdit = false;
  planDetailsForm.price_with_vat = props.sendUpdateLog?.price_with_vat || null;
  planDetailsForm.price_without_vat =
    props.sendUpdateLog?.price_without_vat || null;
  planDetailsForm.total_price = props.sendUpdateLog?.total_price || null;
};
</script>

<template>
  <div
    class="p-4 rounded shadow mb-6 bg-white"
    v-if="isPlanDetails || isIndicativeAdditionalPrice"
  >
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <x-tooltip position="left" v-if="!isPlanDetails">
            <label
              class="font-semibold text-primary-800 text-lg underline decoration-dotted decoration-primary-700"
            >
              Indicative Additional Price
            </label>
            <template #tooltip>
              Refers to an estimated cost that may be added to the policy.
              Please check with the policy schedule or insurance provider for
              the most accurate and up-to-date pricing.
            </template>
          </x-tooltip>
          <h3 v-else class="text-lg font-semibold text-primary-800 capitalize">
            Plan Details
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-y-4">
            <!-- price VAT not applicable -->
            <div class="grid sm:grid-cols-2 gap-2">
              <dt>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    PRICE (VAT NOT APPLICABLE)
                  </label>
                  <template #tooltip>
                    Enter the quoted price that VAT is not applicable. Remember,
                    VAT is exempt for Life Insurance policies.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <x-input
                  v-model="planDetailsForm.price_without_vat"
                  :disabled="
                    !state.isEdit ||
                    (quoteType != quoteTypeCodeEnum.Life &&
                      quoteType != quoteTypeCodeEnum.Business)
                  "
                  :error="planDetailsForm.errors.price_without_vat"
                  placeholder="Enter price (VAT not applicable)"
                  type="number"
                  min="0"
                  @change="updatePriceWithVat"
                  @keypress="onKeyPress"
                  class="w-full"
                />
              </dd>
            </div>

            <div class="grid sm:grid-cols-2 gap-2">
              <template
                v-if="
                  isPlanDetails && props.sendUpdateLog.category.code !== sendUpdateEnums.CPD
                "
              >
                <dt>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      Provider name
                    </label>
                    <template #tooltip>
                      Name of the insurance company this insurance policy will
                      be issued from.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-select
                    v-model="planDetailsForm.insurance_provider_id"
                    placeholder="Insurance Provider"
                    :options="insuranceProvidersOptions"
                    class="w-1/2"
                    :disabled="!state.isEdit"
                  />
                </dd>
              </template>
              <template v-else>
                <dt class="font-bold text-right mr-10"></dt>
                <dd></dd>
              </template>
            </div>

            <!-- price VAT applicable -->
            <div class="grid sm:grid-cols-2 gap-2">
              <dt>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    PRICE (VAT APPLICABLE)
                  </label>
                  <template #tooltip>
                    Please enter the quoted price without including Value Added
                    Tax (VAT). VAT will be calculated separately.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <x-input
                  v-model="planDetailsForm.price_with_vat"
                  :rules="
                    quoteType == quoteTypeCodeEnum.Life
                      ? []
                      : [isRequired, amount]
                  "
                  :disabled="
                    !state.isEdit ||
                    (quoteType == quoteTypeCodeEnum.Life &&
                      quoteType != quoteTypeCodeEnum.Business)
                  "
                  :error="planDetailsForm.errors.price_with_vat"
                  placeholder="Enter price (VAT applicable)"
                  type="number"
                  min="0"
                  @change="updatePriceWithVat"
                  @keypress="onKeyPress"
                  class="w-full"
                />
              </dd>
            </div>

            <!-- Quote number -->
            <div class="grid sm:grid-cols-2 gap-2">
              <template
                v-if="
                  isPlanDetails && props.sendUpdateLog.category.code !== sendUpdateEnums.CPD
                "
              >
                <dt>
                  <x-tooltip position="left">
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      Quote number
                    </label>
                    <template #tooltip>
                      Refers to the unique identifier associated with the
                      initial quote provided by the insurer.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    v-model="planDetailsForm.insurer_quote_number"
                    placeholder="Enter Insurer Quote Number"
                    :disabled="!state.isEdit"
                    type="number"
                    min="0"
                  />
                </dd>
              </template>
              <template v-else>
                <dt class="font-bold"></dt>
                <dd></dd>
              </template>
            </div>

            <!-- Total price -->
            <div class="grid sm:grid-cols-2 gap-2">
              <dt>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    TOTAL PRICE
                  </label>
                  <template #tooltip>
                    The entire amount due before any potential discounts.
                  </template>
                </x-tooltip>
              </dt>
              <dd>{{ planDetailsForm.total_price }}</dd>
            </div>
          </dl>
        </div>
        <div class="flex justify-end gap-2">
          <x-button size="sm" @click="state.isEdit = true" v-if="!state.isEdit">
            Edit
          </x-button>
          <template v-else>
            <x-button
              size="sm"
              color="orange"
              @click="onCancel"
              :loading="planDetailsForm.processing"
              :disabled="planDetailsForm.processing"
              >Cancel</x-button
            >
            <x-button
              size="sm"
              color="primary"
              @click="onUpdate"
              :loading="planDetailsForm.processing"
              :disabled="planDetailsForm.processing"
              >Update</x-button
            >
          </template>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
