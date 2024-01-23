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

const issuanceStatusOptions = computed(() => {
  return [
    { label: 'Select Status', value: '' },
    { label: 'Portal Down', value: 'portal_down' },
    { label: 'Waiting for client confirmation', value: 'waiting_for_client_confirmation' },
    { label: 'Issue found', value: 'issue_found' },
    { label: 'Underwriter Issuance', value: 'underwriter_issuance' },
    { label: 'Portal Issuance', value: 'portal_issuance' },
    { label: 'Policy already issued by the underwriter', value: 'policy_already_issued_by_the_underwriter' },
    { label: 'Renewal, Direct to Underwriter', value: 'renewal_direct_to_underwriter' },
    { label: 'Policy Issued', value: 'policy_issued' },
    { label: 'Other', value: 'other' },
  ];
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

const policyDetailsForm = useForm({
	first_name: props.quote?.first_name || '',
	last_name: props.quote?.last_name || '',
	provider_name: '',
	plan_name: '',
	policy_number: props.quote?.policy_number || '',
	issuance_date: props.quote?.policy_issuance_date || '',
	start_date: props.quote?.policy_start_date || '',
	expiry_date: props.quote?.renewal_expiry_date || '',
	insurer_quote_number: '',
	issuance_status_id: '',
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
								<span v-else>{{ 'in' }}</span>
								<!-- Provider Name -->
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
								<span v-else>{{ 'in' }}</span>
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
                  v-model="policyDetailsForm.policy_issuance_date"
                  name="policy_issuance_date"
                  :disabled="!state.isEdit"
                />
								<span v-else>{{ policyDetailsForm.policy_issuance_date }}</span>
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
                  v-model="policyDetailsForm.policy_start_date"
                  name="policy_start_date"
                  :disabled="!state.isEdit"
                  placeholder="dd-mm-yyyy"
                  class="w-[69%]"
                />
								<span v-else>{{ policyDetailsForm.policy_start_date }}</span>
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
                  v-model="policyDetailsForm.renewal_expiry_date"
                  name="renewal_expiry_date"
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
                  v-model="policyDetailsForm.issuance_status"
                  placeholder="Select Status"
                  class="w-1/2"
								/>
                <span v-else>{{ policyDetailsForm.issuance_status }}</span>
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
