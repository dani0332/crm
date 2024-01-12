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

const planDetailsForm = useForm({
  price_vat_applicable: 0,
  price_vat_not_applicable: 0,
  price_with_vat: 0,
	provider_name: '',
	insurer_quote_number: '',
  insurance_provider_id: '',
  send_update_log_id: props.sendUpdateLog.id,
  id: props.sendUpdateLog.reportable_id,
  quote_type_id: props.sendUpdateLog.quote_type_id
});

const insuranceProviderOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const showProviderAndQuoteNumber = computed(() => {
	return false;
});

watch(
  () => planDetailsForm.price_vat_applicable,
  (newValue) => {
    let price = parseFloat(newValue);
    planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  },
  { deep: true }
)

watch(
  () => planDetailsForm.price_vat_not_applicable,
  (newValue) => {
    let price = parseFloat(newValue);
    planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  },
  { deep: true }
)

const updateTotalPrice = () => {
  // if (planDetailsForm.price_vat_applicable != "") {
  //   let price = parseFloat(planDetailsForm.price_vat_applicable);
  //   planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  // } else if (planDetailsForm.price_vat_not_applicable != "") {
  //   let price = parseFloat(planDetailsForm.price_vat_not_applicable);
  //   planDetailsForm.price_with_vat = ((price / 100) * 5) + price;
  // }
}

const onUpdate = () => {
  planDetailsForm.post(
    route('send-update-logs.save-plan-details'),
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
              Plan Details
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
                  v-model="planDetailsForm.price_vat_not_applicable"
                  placeholder="Enter price (VAT not applicable)"
                  :disabled="!state.isEdit"
                  type="number"
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
                  <ComboBox
                    :single="true"
                    v-model="planDetailsForm.insurance_provider_id"                          
                    placeholder="Insurance Provider"
                    :options="insuranceProviderOptions"
                    class="w-full"
                  />
								</dd>
							</template>
							<template v-else>	
								<dt class="font-bold text-right mr-10"></dt>
								<dd></dd>
							</template>
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
                  v-model="planDetailsForm.price_vat_applicable"
                  placeholder="Enter price (VAT applicable)"
                  :disabled="!state.isEdit"
                  type="number"
                  @change="updateTotalPrice"
                />
              </dd>
            </div>

						<!-- Quote number -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<template v-if="selectedType.slug !== 'COPD' && selectedType.slug !== 'EF'">
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
										v-model="planDetailsForm.insurer_quote_number"
										placeholder="Enter Insurer Quote Number"
										:disabled="!state.isEdit"
                    type="number"
                    :error="false"
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
              <dd>{{ planDetailsForm.price_with_vat }}</dd>
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
