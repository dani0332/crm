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
  selectedCategory: {
    type: Object,
    required: true
  },
  indicativePrice: {
    type: Object,
    required: true
  },
  quoteType: {
    type: String,
    required: true
  }
})

const page = usePage();
const notification = useToast();

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const state = reactive({
  isEdit: false,
});

const planDetailsForm = useForm({
  price_with_vat: props.indicativePrice?.price_with_vat || 0,
  price_without_vat: props.indicativePrice?.price_without_vat || 0,
  total_price: props.indicativePrice?.total_price || 0,
	insurer_quote_number: props.indicativePrice?.insurer_quote_number || '',
  insurance_provider_id: props.indicativePrice?.insurance_provider_id || '',
  send_update_log_id: props.sendUpdateLog.id,
  uuid: props.sendUpdateLog.uuid,
});

const insuranceProvidersOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
})

const updatePriceWithVat = () => {
  if (planDetailsForm.price_with_vat != "") {
    let price = parseFloat(planDetailsForm.price_with_vat);
    planDetailsForm.total_price = ((price / 100) * 5) + price;
  } else if (planDetailsForm.price_without_vat != "") {
    let price = parseFloat(planDetailsForm.price_without_vat);
    planDetailsForm.total_price = ((price / 100) * 5) + price;
  }
}

const onUpdate = () => {
  planDetailsForm.post(
    route('send-update-logs.save-price-details'),
    {
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
    },
  );
}
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Plan Details
          </h3>
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
                  v-model="planDetailsForm.price_without_vat"
                  :disabled="!state.isEdit || quoteType != quoteTypeCodeEnum.Life && quoteType != quoteTypeCodeEnum.Business"
                  :error="planDetailsForm.errors.price_without_vat"
                  placeholder="Enter price (VAT not applicable)"
                  type="number"
                  min="0"
                />
              </dd>
            </div>

            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<template v-if="selectedCategory.subCategory.slug !== 'CPD'">
								<dt class="font-bold text-right mr-10">
									<x-tooltip position="left">
										<span>Provider name</span>
										<template #tooltip>
											Name of the insurance company this insurance policy will be issued from.
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
                  v-model="planDetailsForm.price_with_vat"
                  :rules="quoteType == quoteTypeCodeEnum.Life ? [] : [isRequired]"
                  :disabled="!state.isEdit || quoteType == quoteTypeCodeEnum.Life && quoteType != quoteTypeCodeEnum.Business"
                  :error="planDetailsForm.errors.price_with_vat"
                  placeholder="Enter price (VAT applicable)"
                  type="number"
                  min="0"
                  @change="updatePriceWithVat"
                />
              </dd>
            </div>

						<!-- Quote number -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<template v-if="selectedCategory.subCategory.slug !== 'CPD'">
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
                    min="0"
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
