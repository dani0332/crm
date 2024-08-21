<script setup>
const page = usePage();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  modelType: String,
});

const providerPriceForm = useForm({
  price_vat: null,
  price_non_vat: null,
  insurer_quote_number: null,
});

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Plan Details</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <x-form @submit="submitpolicyForm" :auto-focus="false">
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/5">
          <x-input
            v-model="providerPriceForm.price_vat"
            :error="providerPriceForm.errors.price_vat"
            label="Price (VAT Applicable)"
            class="w-full"
            type="number"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="providerPriceForm.price_non_vat"
            :error="providerPriceForm.errors.price_non_vat"
            type="number"
            label="Price (VAT not applicable)"
            class="w-full"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            :disabled="true"
            type="number"
            label="Total Price"
            class="w-full"
          />
        </div>

        <div class="w-full md:w-1/5">
          <x-input
            v-model="providerPriceForm.insurer_quote_number"
            :error="providerPriceForm.errors.insurer_quote_number"
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
