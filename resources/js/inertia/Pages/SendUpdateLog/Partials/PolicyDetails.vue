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
});

const state = reactive({
  isEdit: false,
});

// const onUpdate = () => {
//   planDetailsForm.post(
//     route('send-update-logs.save-plan-details'),
//     {
//       preserverScroll: true,
//       onSuccess: ({ props }) => {
//         notification.success({
//           title: 'The request has been updated',
//           position: 'top',
//         });
//         state.edit = false;
//       },
//       onError: errors => {
//         Object.keys(errors).forEach(function (key) {
//           notification.error({
//             title: errors[key],
//             position: 'top',
//           });
//         });
//       },
//     },
//   );
// }
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
								/>
								<!-- My first name -->
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
								/>
								<!-- My last name -->
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
								/>
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
								/>
								<!-- Plan Name -->
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
								/>
								<!-- Policy Number -->
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
								<x-input
								/>
								<!-- Issuance Date -->
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
								<x-input
								/>
								<!-- Start Date -->
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
								<x-input
								/>
								<!-- Expiry Date -->
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
								<x-input
								/>
								<!-- Insurer Quote Number -->
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
								<x-input
								/>
								<!-- Issuance Status -->
							</dd>
            </div>
          </dl>
        </div>
				<x-divider class="my-4 mt-10" />
        <div class="flex justify-end gap-2">
          <x-button size="sm" @click="state.isEdit = true" v-if="!state.isEdit">
            Edit
          </x-button>
          <!-- <template v-else>
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
          </template> -->
        </div>
      </template>
    </Collapsible>
  </div>
</template>
