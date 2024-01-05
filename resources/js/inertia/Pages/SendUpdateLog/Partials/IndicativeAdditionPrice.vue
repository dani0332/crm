<script setup>
const { isRequired } = useRules();

const state = reactive({
  isEdit: false,
});

const additionalPriceForm = useForm({
  price_with_vat: '',
  price_without_vat: '',
  total_price: '0.0',
	provider_name: '',
	insurer_quote_number: ''
});

const showProviderAndQuoteNumber = computed(() => {
	return false;
})

const onUpdate = () => {

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

						<!-- price without VAT -->
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
                  v-model="additionalPriceForm.price_without_vat"
                  placeholder="Enter price (VAT not applicable)"
                  :disabled="!state.isEdit"
                />
              </dd>
            </div>

						<!-- provider name -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<template v-if="showProviderAndQuoteNumber">
								<dt class="font-bold text-right mr-10">
									<x-tooltip position="left">
										<span>Provider name</span>
										<template #tooltip>
											Name of the insurance company this insurance policy will be issued from.
										</template>
									</x-tooltip>
								</dt>
								<dd>
									<x-input
										v-model="additionalPriceForm.price_without_vat"
										placeholder="Enter price (VAT not applicable)"
										:disabled="!state.isEdit"
									/>
								</dd>
							</template>
							<template v-else>	
								<dt class="font-bold text-right mr-10"></dt>
								<dd></dd>
							</template>
            </div>

						<!-- price with VAT -->
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
                  v-model="additionalPriceForm.price_with_vat"
                  placeholder="Enter price (VAT applicable)"
                  :disabled="!state.isEdit"
                />
              </dd>
            </div>

						<!-- Quote number -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<template v-if="showProviderAndQuoteNumber">
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
										v-model="additionalPriceForm.price_with_vat"
										placeholder="Enter price (VAT applicable)"
										:disabled="!state.isEdit"
									/>
								</dd>
							</template>
							<template v-else>	
								<dt class="font-bold text-right mr-10"></dt>
								<dd></dd>
							</template>
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
              <dd>{{ additionalPriceForm.total_price }}</dd>
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
