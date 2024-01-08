<script setup>
const { isRequired } = useRules();

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true
  },
  insuranceProviders: {
    type: Array,
    required: true
  },
  selectedType: {
    type: Object,
    required: true
  }
})

const state = reactive({
  isEdit: false,
});

const additionalPriceForm = useForm({
  price_vat_applicable: 0,
  price_vat_not_applicable: 0,
  price_with_vat: 0,
	provider_name: '',
	insurer_quote_number: '',
  insurance_provider_id: '',
  send_update_log_id: props.sendUpdateLog.id,
});

const insuranceProviderOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

watch(
  () => additionalPriceForm.price_vat_applicable,
  (newValue) => {
    let price = parseFloat(newValue);
    additionalPriceForm.price_with_vat = ((price / 100) * 5) + price;
  },
  { deep: true }
)

watch(
  () => additionalPriceForm.price_vat_not_applicable,
  (newValue) => {
    let price = parseFloat(newValue);
    additionalPriceForm.price_with_vat = ((price / 100) * 5) + price;
  },
  { deep: true }
)

const updateTotalPrice = () => {
  // if (additionalPriceForm.price_vat_applicable != "") {
  //   let price = parseFloat(additionalPriceForm.price_vat_applicable);
  //   additionalPriceForm.price_with_vat = ((price / 100) * 5) + price;
  // } else if (additionalPriceForm.price_vat_not_applicable != "") {
  //   let price = parseFloat(additionalPriceForm.price_vat_not_applicable);
  //   additionalPriceForm.price_with_vat = ((price / 100) * 5) + price;
  // }
}

const onUpdate = () => {
  additionalPriceForm.post(
    route('indicative-additional-price.store'),
    {
      preserverScroll: true,
      onSuccess: ({ props }) => {
        notification.success({
          title: 'The request has been updated',
          position: 'top',
        });
        state.edit = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    },
  );
}
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <x-tooltip position="right">
            <h3 class="font-semibold text-primary-800 text-lg">
              Indicative Additional Price
            </h3>
            <template #tooltip>
              Refers to an estimated cost that may be added to the policy.
              Please check with the policy schedule or insurance provider for
              the most accurate and up-to-date pricing.
            </template>
          </x-tooltip>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-y-4">

						<!-- price VAT not applicable -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>PRICE (VAT NOT APPLICABLE)</span>
                  <template #tooltip>
                    Enter the quoted price that VAT is not applicable. Remember,
                    VAT is exempt for Life Insurance policies.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <x-input
                  v-model="additionalPriceForm.price_vat_not_applicable"
                  placeholder="Enter price (VAT not applicable)"
                  :disabled="!state.isEdit"
                  type="number"
                />
              </dd>
            </div>

            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<!-- <template v-if="showProviderAndQuoteNumber">
								<dt class="font-bold text-right mr-10">
									<x-tooltip position="left">
										<span>Provider name</span>
										<template #tooltip>
											Name of the insurance company this insurance policy will be issued from.
										</template>
									</x-tooltip>
								</dt>
								<dd>
                  <ComboBox
                    :single="true"
                    v-model="additionalPriceForm.insurance_provider_id"                          
                    placeholder="Insurance Provider"
                    :options="insuranceProviderOptions"
                    class="w-full"
                  />
								</dd>
							</template>
							<template v-else>	
              </template> -->
              <dt class="font-bold text-right mr-10"></dt>
              <dd></dd>
            </div>

						<!-- price VAT applicable -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
								<x-tooltip position="left">
                  <span>PRICE (VAT APPLICABLE)</span>
                  <template #tooltip>
                    Please enter the quoted price without including Value Added Tax (VAT). VAT will be calculated separately.
                  </template>
                </x-tooltip>
							</dt>
              <dd>
                <x-input
                  v-model="additionalPriceForm.price_vat_applicable"
                  placeholder="Enter price (VAT applicable)"
                  :disabled="!state.isEdit"
                  type="number"
                  @change="updateTotalPrice"
                />
              </dd>
            </div>

						<!-- Quote number -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<!-- <template v-if="selectedType.slug !== 'COPD' && selectedType.slug !== 'EF'">
								<dt class="font-bold text-right mr-10">
									<x-tooltip position="left">
										<span>Quote number</span>
										<template #tooltip>
											Refers to the unique identifier associated with the initial quote provided by the insurer.
										</template>
									</x-tooltip>
								</dt>
								<dd>
									<x-input
										v-model="additionalPriceForm.insurer_quote_number"
										placeholder="Enter Insurer Quote Number"
										:disabled="!state.isEdit"
                    type="number"
                    :error="false"
									/>
								</dd>
							</template>
							<template v-else>	
              </template> -->
              <dt class="font-bold text-right mr-10"></dt>
              <dd></dd>
						</div>

						<!-- Total price -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
								<x-tooltip position="left">
									<span>TOTAL PRICE</span>
									<template #tooltip>
										The entire amount due before any potential discounts. 
									</template>
								</x-tooltip>
							</dt>
              <dd>{{ additionalPriceForm.price_with_vat }}</dd>
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
              @click="state.isEdit = false"
              :loading="additionalPriceForm.processing"
              :disabled="additionalPriceForm.processing"
              >Cancel</x-button
            >
            <x-button
              size="sm"
              color="primary"
              @click="onUpdate"
              :loading="additionalPriceForm.processing"
              :disabled="additionalPriceForm.processing"
              >Update</x-button
            >
          </template>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
