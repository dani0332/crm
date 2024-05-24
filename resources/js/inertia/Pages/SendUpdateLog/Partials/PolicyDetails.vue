<script setup>
const { isRequired } = useRules();

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  selectedCategory: {
    type: Object,
    required: true,
  },
	quote: {
    type: Object,
    required: true,
  },
});

const state = reactive({
  isEdit: false,
});

const page = usePage();
const notification = useToast();

const issuanceStatusOptions = computed(() => {
  return page.props.issuanceStatuses.map(status => {
    return { label: status.text, value: status.id };
  })
})

const isEndorsementFinancial = computed(() => {
  return props.selectedCategory.subCategory.slug === 'EF' && props.selectedCategory.subCategory.option.slug === 'PPE'
});

const isCIR = computed(() => {
  return props.selectedCategory?.subCategory.slug === 'CIR';
});

const isCPD = computed(() => {
  return props.selectedCategory?.subCategory.slug === 'CPD';
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY');

const policyDetailsForm = useForm({
	first_name: props.sendUpdateLog?.first_name || props.quote?.first_name || null,
	last_name: props.sendUpdateLog?.last_name || props.quote?.last_name || null,
	provider_name: props.sendUpdateLog?.provider_name || props.quote?.plan?.insurance_provider?.name || null,
	plan_name: props.sendUpdateLog?.plan_name || props.quote?.plan?.name || null,
	policy_number: props.sendUpdateLog?.policy_number || props.quote?.policy_number || null,
	issuance_date: props.sendUpdateLog?.issuance_date || props.quote?.policy_issuance_date || null,
	start_date: props.sendUpdateLog?.start_date || props.quote?.policy_start_date || null,
	expiry_date: props.sendUpdateLog?.expiry_date || props.quote?.renewal_expiry_date || null,
	insurer_quote_number: props.sendUpdateLog?.insurer_quote_number || props.quote?.insurer_quote_number || null,
	issuance_status_id: props.sendUpdateLog?.issuance_status_id || props.quote?.policy_issuance_status_id || null,
	id: props.sendUpdateLog.id,
})

const onUpdate = () => {
  policyDetailsForm.post(
    route('send-update-logs.save-policy-details'),
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
          <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-y-4">
            <!-- First name -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>First Name</span>
                  <template #tooltip>
                    This field captures the policyholder's first name, representing the primary contact person associated with the policy.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
								<x-input
									v-if="isCPD"
                  v-model="policyDetailsForm.first_name"
									:disabled="!state.isEdit"
								/>
								<span v-else>{{ policyDetailsForm.first_name }}</span>
							</dd>
            </div>

            <!-- Last name -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<dt class="font-bold text-right mr-10">
								<x-tooltip position="left">
									<span>Last Name</span>
									<template #tooltip>
										Records the policyholder's surname or family name.
									</template>
								</x-tooltip>
							</dt>
							<dd>
								<x-input
									v-if="isCPD"
                  v-model="policyDetailsForm.last_name"
									:disabled="!state.isEdit"
								/>
								<span v-else>{{ policyDetailsForm.last_name }}</span>
							</dd>
            </div>

            <!-- Provider Name -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Provider Name</span>
                  <template #tooltip>
                    Name of the insurance company responsible for the coverage.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
								<x-input
									v-if="isCPD"
									:disabled="!state.isEdit"
								/>
								<span v-else>{{ policyDetailsForm.provider_name }}</span>
							</dd>
            </div>

            <!-- Plan Name -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
							<dt class="font-bold text-right mr-10">
								<x-tooltip position="left">
									<span>Plan Name</span>
									<template #tooltip>
										Identifies the specific coverage or insurance plan offered by the provider.
									</template>
								</x-tooltip>
							</dt>
							<dd>
								<x-input
									v-if="isCPD"
									:disabled="!state.isEdit"
								/>
								<span v-else>{{ policyDetailsForm.plan_name }}</span>
							</dd>
            </div>

            <!-- Policy Number -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Policy Number</span>
                  <template #tooltip>
                    The unique Insurance policy number for the chosen insurance plan offered by the provider.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
								<x-input
									v-if="isCPD || isCIR"
									:disabled="!state.isEdit"
									v-model="policyDetailsForm.policy_number"
									type="number"
                  placeholder="Enter policy number"
								/>
								<span v-else>{{ policyDetailsForm.policy_number }}</span>
							</dd>
            </div>

						<!-- Issuance Date -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Issuance Date</span>
                  <template #tooltip>
                    Signifies the date when the insurance policy was officially issued.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <DatePicker
                  v-if="isCPD"
                  v-model="policyDetailsForm.issuance_date"
                  name="issuance_date"
                  :disabled="!state.isEdit"
                  placeholder="dd-mm-yyyy"
                  class="w-1/2"
                />
								<span v-else>{{ policyDetailsForm.issuance_date }}</span>
							</dd>
            </div>

						<!-- Start Date -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Start Date</span>
                  <template #tooltip>
                    Signifies the date when the insurance policy was officially issued.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <DatePicker
                  v-if="isCPD || isCIR"
                  v-model="policyDetailsForm.start_date"
                  name="start_date"
                  :disabled="!state.isEdit"
                  placeholder="dd-mm-yyyy"
                  class="w-[69%]"
                />
								<span v-else>{{ policyDetailsForm.start_date }}</span>
							</dd>
            </div>

						<!-- Expiry Date -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Expiry Date</span>
                  <template #tooltip>
                    Signifies the date when the insurance policy was officially issued.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
                <DatePicker
                  v-model="policyDetailsForm.expiry_date"
                  name="expiry_date"
                  :disabled="!state.isEdit"
                  placeholder="dd-mm-yyyy"
                  class="w-1/2"
                />
							</dd>
            </div>

						<!-- Insurer Quote Number -->
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Insurer Quote Number</span>
                  <template #tooltip>
                    Signifies the date when the insurance policy was officially issued.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
								<span>{{ policyDetailsForm.insurer_quote_number }}</span>
							</dd>
            </div>

						<!-- Issuance Status -->
            <div class="grid sm:grid-cols-2 ml-[-250px]">
              <dt class="font-bold text-right mr-10">
                <x-tooltip position="left">
                  <span>Issuance Status</span>
                  <template #tooltip>
                    Signifies the date when the insurance policy was officially issued.
                  </template>
                </x-tooltip>
              </dt>
              <dd>
								<x-select
									v-if="!isCPD"
									:disabled="!state.isEdit"
                  :options="issuanceStatusOptions"
                  v-model="policyDetailsForm.issuance_status_id"
                  placeholder="Select Status"
                  class="w-1/2"
								/>
                <span v-else>{{ policyDetailsForm.issuance_status_id }}</span>
							</dd>
            </div>
          </dl>
        </div>
				<x-divider class="my-4 mt-10" />
        <div class="flex justify-end gap-2">
          <x-button size="sm" @click="state.isEdit = true" v-if="!state.isEdit">
            Edit
          </x-button>
          <template v-else>
            <x-button
              size="sm"
              color="orange"
              @click="state.isEdit = false"
              :loading="policyDetailsForm.processing"
              :disabled="policyDetailsForm.processing"
              >Cancel</x-button
            >
            <x-button
              size="sm"
              color="primary"
              @click="onUpdate"
              :loading="policyDetailsForm.processing"
              :disabled="policyDetailsForm.processing"
              >Update</x-button
            >
          </template>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
