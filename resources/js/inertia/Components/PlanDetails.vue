<script setup>
const page = usePage();
const notification = useToast();
const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  quoteType: String,
  insuranceProviders: Object,
  vatPrice: {
    type: Number,
    default: 0,
  },
  isAddUpdate: {
    type: Boolean,
    default: false,
  },
});

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const planDetailsForm = useForm({
  insurance_provider_id: props.quote?.insurance_provider_id ?? null,
  price_vat_applicable: props.quote?.price_vat_applicable ?? 0, // price vat applicable
  price_vat_not_applicable: props.quote?.price_vat_not_applicable ?? 0, //price vat not applicable
  price_with_vat: props.quote.price_with_vat
    ? useFormatPrice(props.quote.price_with_vat, true)
    : 0,
  insurer_quote_number: props.quote?.insurer_quote_number ?? null,
});

const insuranceProviderOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider?.id ? provider.id : provider?.value ? provider.value : null,
    label: provider?.text
      ? provider.text
      : provider?.label
        ? provider.label
        : null,
  }));
});

const isProviderEmpty = ref(false);
const formProcessing = ref(false);

const rules = {
  isNumber: v => !isNaN(Number(v)) || 'Amount must be a valid number',
  conditionalRequired: v => {
    const vatApplicable = planDetailsForm.price_vat_applicable;
    const vatNotApplicable = planDetailsForm.price_vat_not_applicable;

    if (props.quoteType == quoteTypeCodeEnum.Business) {
      return vatApplicable || vatNotApplicable
        ? true
        : 'Either Price (VAT Applicable) or Price (VAT Not Applicable) should be entered';
    } else if (props.quoteType == quoteTypeCodeEnum.Life) {
      return vatNotApplicable ? true : 'Price (VAT not applicable) is required';
    } else {
      return vatApplicable ? true : 'Price (VAT Applicable) is required';
    }
  },
  isNegative: v => (Number(v) < 0 ? 'Amount must be a positive number' : true),
  lengthCheck: v => {
    const pattern = /^\d{1,7}(\.\d{1,2})?$/;
    if (v == null || v == '') return true;
    return (
      pattern.test(v) || 'Invalid number. Max 7 digits and 2 decimals allowed.'
    );
  },
};

const submitPlanDetailsForm = isValid => {
  if (!planDetailsForm.insurance_provider_id) {
    isProviderEmpty.value = true;
    return;
  } else isProviderEmpty.value = false;

  if (!isValid) return;

  let url = `/personal-quotes/${props.quoteType}/${props.quote?.code}/save-plan-details`;

  formProcessing.value = true;

  planDetailsForm.post(url, {
    preserveScroll: true,
    onError: errors => {
      formProcessing.value = false;
      Object.keys(errors).forEach(function (key) {
        planDetailsForm.setError(key, errors[key]);
      });
      if (errors.error) {
        notification.error({
          title: errors.error,
          position: 'top',
        });
      }
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
    onSuccess: () => {
      formProcessing.value = false;
      notification.success({
        title: 'Plan details saved',
        position: 'top',
      });
    },
  });
};

const updatePriceWithVat = () => {
  planDetailsForm.price_with_vat = 0;

  let priceVatApp = parseFloat(
    planDetailsForm.price_vat_applicable !== null &&
      planDetailsForm.price_vat_applicable !== ''
      ? planDetailsForm.price_vat_applicable
      : 0,
  );

  let priceVatNotApp = parseFloat(
    planDetailsForm.price_vat_not_applicable !== null &&
      planDetailsForm.price_vat_not_applicable !== ''
      ? planDetailsForm.price_vat_not_applicable
      : 0,
  );

  if (props.quoteType == quoteTypeCodeEnum.Business) {
    //let priceVatApp = parseFloat( (planDetailsForm.price_vat_applicable ! ?? 0.00) );
    //let priceVatNotApp = parseFloat(planDetailsForm.price_vat_not_applicable ?? 0.00);
    let totalPrice = parseFloat(
      priceVatApp + priceVatNotApp + (priceVatApp / 100) * props.vatPrice,
    );

    planDetailsForm.price_with_vat = useFormatPrice(totalPrice, true);
  } else {
    if (priceVatApp) {
      let price = parseFloat(planDetailsForm.price_vat_applicable);
      let priceWithVAT = (price / 100) * props.vatPrice + price;
      planDetailsForm.price_with_vat = useFormatPrice(priceWithVAT, true);
    }

    if (priceVatNotApp) {
      let priceWithVAT = parseFloat(planDetailsForm.price_vat_not_applicable);
      planDetailsForm.price_with_vat = useFormatPrice(priceWithVAT, true);
    }
  }
};

const hasRole = role => useHasRole(role);
const can = permission => useCan(permission);

const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);
});
const [SavePlanDetailsButtonTemplate, SavePlanDetailsButtonReuseTemplate] =
  createReusableTemplate();

const disableIfPolicyFailedAndNoBookingFailedEditPermission = computed(() => {
  let isPolicyBookingFailed =
    props.quote.quote_status_id ==
    page.props.quoteStatusEnum.POLICY_BOOKING_FAILED;
  if (isPolicyBookingFailed) {
    let hasBookingFailedEditPermission = can(
      permissionEnum.BOOKING_FAILED_EDIT,
    );
    if (!hasBookingFailedEditPermission) {
      return true;
    }
    return false;
  }
  return false;
});

watch(
  () => props.isAddUpdate,
  () => {
    planDetailsForm.insurance_provider_id = null;
    planDetailsForm.price_vat_applicable = null;
    planDetailsForm.price_vat_not_applicable = null;
    planDetailsForm.price_with_vat = null;
    planDetailsForm.insurer_quote_number = null;
  },
);
</script>

<template>
  <div
    class="p-4 rounded shadow mb-6 bg-white"
    v-if="can(permissionEnum.PLAN_DETAILS_ADD)"
  >
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Plan Details</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <x-form @submit="submitPlanDetailsForm" :auto-focus="false">
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/5">
          <ComboBox
            :single="true"
            :hasError="isProviderEmpty"
            v-model="planDetailsForm.insurance_provider_id"
            placeholder="Insurance Provider"
            :options="insuranceProviderOptions"
            label="Insurance Provider"
            class="w-full uppercase"
            :disabled="page.props.lockLeadSectionsDetails.plan_details"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.price_vat_applicable"
            :rules="
              props.quoteType == quoteTypeCodeEnum.Life
                ? []
                : [
                    rules.conditionalRequired,
                    rules.isNumber,
                    rules.isNegative,
                    rules.lengthCheck,
                  ]
            "
            :disabled="
              (props.quoteType == quoteTypeCodeEnum.Life &&
                props.quoteType != quoteTypeCodeEnum.Business) ||
              page.props.lockLeadSectionsDetails.plan_details
            "
            label="Price (VAT Applicable)"
            class="w-full uppercase"
            type="text"
            @change="updatePriceWithVat"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.price_vat_not_applicable"
            :rules="
              props.quoteType == quoteTypeCodeEnum.Life ||
              props.quoteType == quoteTypeCodeEnum.Business
                ? [
                    rules.conditionalRequired,
                    rules.isNumber,
                    rules.isNegative,
                    rules.lengthCheck,
                  ]
                : []
            "
            :error="planDetailsForm.errors.price_vat_not_applicable"
            :disabled="
              (props.quoteType != quoteTypeCodeEnum.Life &&
                props.quoteType != quoteTypeCodeEnum.Business) ||
              page.props.lockLeadSectionsDetails.plan_details
            "
            type="text"
            label="Price (VAT not applicable)"
            class="w-full uppercase"
            @change="updatePriceWithVat"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            :disabled="true"
            v-model="planDetailsForm.price_with_vat"
            :error="planDetailsForm.errors.price_with_vat"
            type="text"
            label="Total Price"
            class="w-full uppercase"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.insurer_quote_number"
            :error="planDetailsForm.errors.insurer_quote_number"
            type="text"
            label="Insurer Quote Number"
            class="w-full uppercase"
            :disabled="page.props.lockLeadSectionsDetails.plan_details"
          />
        </div>
      </div>

      <SavePlanDetailsButtonTemplate v-slot="{ isDisabled }">
        <x-button
          class="mt-4"
          color="#26B99A"
          type="submit"
          size="sm"
          :loading="formProcessing"
          :disabled="
            isDisabled || disableIfPolicyFailedAndNoBookingFailedEditPermission
          "
          v-if="readOnlyMode.isDisable === true"
        >
          Save
        </x-button>
      </SavePlanDetailsButtonTemplate>

      <div class="flex mb-3 justify-end">
        <x-tooltip
          v-if="page.props.lockLeadSectionsDetails.plan_details"
          placement="bottom"
        >
          <SavePlanDetailsButtonReuseTemplate :isDisabled="true" />
          <template #tooltip>
            This lead is now locked as the policy has been booked. If changes
            are needed, go to 'Send Update', select 'Add Update', and choose
            'Correction of Policy'
          </template>
        </x-tooltip>
        <SavePlanDetailsButtonReuseTemplate v-else />
      </div>
    </x-form>
  </div>
</template>
