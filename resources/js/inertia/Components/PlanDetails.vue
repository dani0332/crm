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
});

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const planDetailsForm = useForm({
  insurance_provider_id: props.quote?.insurance_provider_id ?? null,
  price_vat_applicable: props.quote?.price_vat_applicable ?? null, // price vat applicable
  price_vat_not_applicable: props.quote?.price_vat_not_applicable ?? null, //price vat not applicable
  price_with_vat: props.quote?.price_with_vat ?? null,
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

const rules = {
  isNumber: v => !isNaN(Number(v)) || 'Field must be a number',
  conditionalRequired: v => {
    if (props.quoteType == quoteTypeCodeEnum.Business) {
      if (
        (planDetailsForm.price_vat_applicable !== null &&
          planDetailsForm.price_vat_applicable !== '') ||
        (planDetailsForm.price_vat_not_applicable !== null &&
          planDetailsForm.price_vat_not_applicable !== '')
      ) {
        return true;
      } else {
        return 'Either Price (VAT Applicable) or Price (VAT Not Applicable) should be entered';
      }
    } else if (props.quoteType == quoteTypeCodeEnum.Life) {
      if (
        planDetailsForm.price_vat_not_applicable !== null &&
        planDetailsForm.price_vat_not_applicable !== ''
      ) {
        return true;
      } else {
        return 'Price (VAT not applicable) is required';
      }
    } else {
      if (
        planDetailsForm.price_vat_applicable !== null &&
        planDetailsForm.price_vat_applicable !== ''
      ) {
        return true;
      } else {
        return 'Price (VAT Applicable) is required';
      }
    }
  },
};

const submitPlanDetailsForm = isValid => {
  if (!planDetailsForm.insurance_provider_id) isProviderEmpty.value = true;
  else isProviderEmpty.value = false;

  if (!isValid) return;

  let url = `/personal-quotes/${props.quoteType}/${props.quote?.code}/save-plan-details`;

  planDetailsForm.post(url, {
    preserveScroll: true,
    onError: errors => {
      //planDetailsForm.errors = errors;
      planDetailsForm.setError(errors);

      notification.error({
        title: errors.error || 'Something went wrong',
        position: 'top',
      });
    },
    onSuccess: () => {
      notification.success({
        title: 'Plan details saved',
        position: 'top',
      });

      //reload for payment task for now, should be handled by props update along with hafeez
      setTimeout(() => {
        location.reload();
      }, 500);
    },
  });
};

const updatePriceWithVat = () => {
  planDetailsForm.price_with_vat = '';

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
    console.log('TOTAL', priceVatApp, priceVatNotApp);
    let totalPrice = parseFloat(
      priceVatApp + priceVatNotApp + (priceVatApp / 100) * 5,
    );

    planDetailsForm.price_with_vat = totalPrice.toFixed(2);
  } else {
    if (priceVatApp) {
      let price = parseFloat(planDetailsForm.price_vat_applicable);
      planDetailsForm.price_with_vat = ((price / 100) * 5 + price).toFixed(2);
    }

    if (priceVatNotApp) {
      let price = parseFloat(planDetailsForm.price_vat_not_applicable);
      planDetailsForm.price_with_vat = price.toFixed(2);
    }
  }
};

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
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
            class="w-full"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.price_vat_applicable"
            :rules="
              props.quoteType == quoteTypeCodeEnum.Life
                ? []
                : [rules.conditionalRequired, rules.isNumber]
            "
            :disabled="
              props.quoteType == quoteTypeCodeEnum.Life &&
              props.quoteType != quoteTypeCodeEnum.Business
            "
            label="Price (VAT Applicable)"
            class="w-full"
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
                ? [rules.conditionalRequired, rules.isNumber]
                : []
            "
            :disabled="
              props.quoteType != quoteTypeCodeEnum.Life &&
              props.quoteType != quoteTypeCodeEnum.Business
            "
            type="text"
            label="Price (VAT not applicable)"
            class="w-full"
            @change="updatePriceWithVat"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            :disabled="true"
            v-model="planDetailsForm.price_with_vat"
            :error="planDetailsForm.errors.price_with_vat"
            type="number"
            label="Total Price"
            class="w-full"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.insurer_quote_number"
            :error="planDetailsForm.errors.insurer_quote_number"
            type="number"
            label="Insurer Quote Number"
            class="w-full"
          />
        </div>
      </div>

      <div class="text-right space-x-4 mt-12">
        <x-button color="#26B99A" type="submit" size="sm">Save</x-button>
      </div>
    </x-form>
  </div>
</template>
