<script setup>
const page = usePage();
const notification = useToast();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },  
  quoteType: String,
  insuranceProviders: Object
});

const { isRequired } = useRules();

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const planDetailsForm = useForm({
  
  insurance_provider_id: props.quote?.insurance_provider_id ?? null,
  price_vat_applicable : props.quote?.price_vat_applicable ?? null,// price vat applicable
  price_vat_not_applicable: props.quote?.price_vat_not_applicable ?? null,//price vat not applicable  
  price_with_vat: props.quote?.price_with_vat ?? null,
  //price_without_vat: null,
  insurer_quote_number: props.quote?.insurer_quote_number ?? null,

});

const insuranceProviderOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const isProviderEmpty = ref(false);

const submitPlanDetailsForm = isValid => {

  if(!planDetailsForm.insurance_provider_id) isProviderEmpty.value = true;
  else isProviderEmpty.value = false;

  if(!isValid) return;

  console.log(props.quote?.code, isValid, "LLLK");

  let url = `/personal-quotes/${props.quoteType}/${props.quote?.code}/save-plan-details`;

  planDetailsForm.setError([]);

  console.log(url, "URL");

  planDetailsForm.post(url, {
    preserveScroll: true,
    onError: errors => {

      console.log(errors);

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
    },
  });
}

const updatePriceWithVat = () => {  
  
  if(planDetailsForm.price_vat_applicable != "") {
    let price = parseFloat(planDetailsForm.price_vat_applicable);
    planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  }

  else if(planDetailsForm.price_vat_not_applicable != "") {
    let price = parseFloat(planDetailsForm.price_vat_not_applicable);
    planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  }

}

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white" v-show="true">
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
            :rules="props.quoteType == quoteTypeCodeEnum.Life ? [] : [isRequired]"
            :disabled="props.quoteType == quoteTypeCodeEnum.Life && props.quoteType != quoteTypeCodeEnum.Business"
            :error="planDetailsForm.errors.price_vat_applicable"
            label="Price (VAT Applicable)"
            class="w-full"
            type="number"
            @change="updatePriceWithVat"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="planDetailsForm.price_vat_not_applicable"
            :error="planDetailsForm.errors.price_vat_not_applicable"
            :disabled="props.quoteType != quoteTypeCodeEnum.Life && props.quoteType != quoteTypeCodeEnum.Business"
            type="number"
            label="Price (VAT not applicable)"
            class="w-full"
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
      
    
      <div class="text-right space-x-4 mt-12" >
        <x-button
          color="#26B99A"
          type="submit"
          size="sm"          
          >Save</x-button
        >        
      </div>

    </x-form>
  </div>
</template>

