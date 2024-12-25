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
  isEditingBlocked: {
    type: Boolean,
  },
  isEditDisabledForQueuedBooking: Boolean,
});

const page = usePage();
const notification = useToast();
const vat = page.props.vatValue;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const sendUpdateEnums = page.props.sendUpdateStatusEnum;

const state = reactive({
  isEdit: false,
});

const planDetailsForm = useForm({
  price_vat_applicable: props.sendUpdateLog?.price_vat_applicable || null,
  price_vat_not_applicable:
    props.sendUpdateLog?.price_vat_not_applicable || null,
  price_with_vat: props.sendUpdateLog?.price_with_vat || null,
  insurer_quote_number: props.sendUpdateLog?.insurer_quote_number || null,
  insurance_provider_id: props.sendUpdateLog?.insurance_provider_id || null,
  id: props.sendUpdateLog?.id,
});

const isPlanDetails = computed(() => {
  return props.sendUpdateLog.category.code === sendUpdateEnums.CPD;
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
  let priceWithVat = 0;
  const priceVatApplicable = parseFloat(planDetailsForm.price_vat_applicable);
  const priceVatNotApplicable = parseFloat(
    planDetailsForm.price_vat_not_applicable,
  );

  if (priceVatApplicable && priceVatNotApplicable) {
    priceWithVat =
      (priceVatApplicable / 100) * vat +
      priceVatApplicable +
      priceVatNotApplicable;
  } else if (priceVatApplicable) {
    priceWithVat = (priceVatApplicable / 100) * vat + priceVatApplicable;
  } else if (priceVatNotApplicable) {
    priceWithVat = priceVatNotApplicable;
  }

  planDetailsForm.price_with_vat = roundDecimal(priceWithVat);
  planDetailsForm.price_vat_applicable = roundDecimal(priceVatApplicable);
  planDetailsForm.price_vat_not_applicable = roundDecimal(
    priceVatNotApplicable,
  );
};

const onUpdate = () => {
  if (
    !planDetailsForm.price_vat_applicable &&
    !planDetailsForm.price_vat_not_applicable
  ) {
    notification.error({
      title: 'Please enter price.',
      position: 'top',
    });
    planDetailsForm.price_with_vat = null;
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
  const charCode = event.charCode || event.keyCode;
  const char = String.fromCharCode(charCode);
  const regex = /^[0-9.]$/;

  if (!regex.test(char)) {
    event.preventDefault();
  }
};

const onCancel = () => {
  state.isEdit = false;
  planDetailsForm.price_vat_applicable =
    props.sendUpdateLog?.price_vat_applicable || null;
  planDetailsForm.price_vat_not_applicable =
    props.sendUpdateLog?.price_vat_not_applicable || null;
  planDetailsForm.price_with_vat = props.sendUpdateLog?.price_with_vat || null;
};

const onEdit = () => {
  if (props.isUpdateBooked) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });
  } else {
    state.isEdit = true;
  }
};

watch(
  () => props.sendUpdateLog.insurance_provider_id,
  (newValue, oldValue) => {
    planDetailsForm.insurance_provider_id = newValue;
  },
);
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <x-tooltip v-if="!isPlanDetails">
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
                <x-tooltip>
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
                  v-model="planDetailsForm.price_vat_not_applicable"
                  :disabled="
                    !state.isEdit ||
                    (quoteType != quoteTypeCodeEnum.Life &&
                      quoteType != quoteTypeCodeEnum.Business)
                  "
                  :error="planDetailsForm.errors.price_vat_not_applicable"
                  placeholder="Enter price (VAT not applicable)"
                  maxlength="13"
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
                  isPlanDetails &&
                  props.sendUpdateLog.category.code !== sendUpdateEnums.CPD
                "
              >
                <dt>
                  <x-tooltip>
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
                <x-tooltip>
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
                  v-model="planDetailsForm.price_vat_applicable"
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
                  :error="planDetailsForm.errors.price_vat_applicable"
                  placeholder="Enter price (VAT applicable)"
                  maxlength="13"
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
                  isPlanDetails &&
                  props.sendUpdateLog.category.code !== sendUpdateEnums.CPD
                "
              >
                <dt>
                  <x-tooltip>
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
                <x-tooltip>
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
              <dd>{{ planDetailsForm.price_with_vat }}</dd>
            </div>
          </dl>
        </div>
        <div
          class="flex justify-end gap-2"
          v-if="readOnlyMode.isDisable === true"
        >
          <template v-if="!state.isEdit">
            <x-tooltip v-if="props.isEditDisabledForQueuedBooking">
              <x-button
                class="focus:ring-2 focus:ring-black"
                size="sm"
                @click="onEdit"
                :disabled="props.isEditDisabledForQueuedBooking"
              >
                Edit
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  No further action can be taken on Update Booking Queued or
                  Failed status.
                </span>
              </template>
            </x-tooltip>
            <x-button
              v-else
              class="focus:ring-2 focus:ring-black"
              size="sm"
              @click="onEdit"
            >
              Edit
            </x-button>
          </template>

          <template v-else>
            <x-button
              class="focus:ring-2 focus:ring-black"
              size="sm"
              color="orange"
              @click="onCancel"
              :loading="planDetailsForm.processing"
              :disabled="planDetailsForm.processing"
              >Cancel</x-button
            >
            <x-button
              class="focus:ring-2 focus:ring-black"
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
