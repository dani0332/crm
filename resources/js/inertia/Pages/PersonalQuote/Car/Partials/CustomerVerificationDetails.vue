<script setup>
const {
  quoteId,
  modals,
  customerVerificationData,
  isLoadingVerificationDataUpdate,
} = defineProps({
  quoteId: Number,
  modals: Object,
  customerVerificationData: Object,
  isLoadingVerificationDataUpdate: Boolean,
});
const notification = useNotifications('toast');
const emit = defineEmits();
const isLoading = ref(false);

// Update webform data with OCR data
const updateAndSave = async () => {
  isLoading.value = true;

  // Make api request
  await axios
    .get(`/quotes/car/${quoteId}/update-ocr-webform`)
    .then(response => {
      // Emit event to reload updated plans
      emit('ocr-webform-updated');
    })
    .catch(error => {
      notification.error({
        title: 'Error occurred while updating',
        position: 'top',
      });

      isLoading.value = false;
      console.log(error);
    });
};

// Watch props to hide loader (once parent processing is completed)
watch(
  () => isLoadingVerificationDataUpdate,
  val => {
    if (val === false) {
      isLoading.value = false; // hide loader when parent finishes
    }
  },
);
</script>

<template>
  <div>
    <x-modal
      v-model="modals.customerVerification"
      size="lg"
      show-close
      backdrop
    >
      <template #header>
        <div class="pt-6 pb-4 px-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">
            WebForm Details & Customer Verified Details
          </h2>
          <hr class="border-gray-200" />
        </div>
      </template>

      <div class="px-6 pb-4 max-w-4xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
          <div
            class="bg-blue-50 p-5 rounded-lg border border-blue-200 shadow-sm hover:shadow-md transition-shadow duration-200"
          >
            <div class="mb-4 pb-3 border-b border-blue-200">
              <h3 class="text-lg font-semibold text-blue-800">
                WebForm Details
              </h3>
              <p class="text-sm text-blue-600 mt-1">
                Original form submission data
              </p>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Name
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.name || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Nationality
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.nationality || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Make and Model
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.carMakeAndModel || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Model Year
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.carModelYear || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  DOB
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.dob || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Emirate Of Registration
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{
                    customerVerificationData.webForm?.emirateOfRegistration ||
                    '-'
                  }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  UAE Licensed Held For
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:border-blue-200 transition-colors duration-150"
                >
                  {{
                    customerVerificationData.webForm?.uaeLicenseHeldFor || '-'
                  }}
                </div>
              </div>
            </div>
          </div>

          <div
            class="bg-green-50 p-5 rounded-lg border border-green-200 shadow-sm hover:shadow-md transition-shadow duration-200"
          >
            <div class="mb-4 pb-3 border-b border-green-200">
              <h3 class="text-lg font-semibold text-green-800">
                Customer Verified Details
              </h3>
              <p class="text-sm text-green-600 mt-1">
                Customer verification data
              </p>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Name
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.customerVerified?.name?.error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.customerVerified?.name?.value ||
                    '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="customerVerificationData.customerVerified?.name?.error"
                >
                  Name mismatch found
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Nationality
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.customerVerified?.nationality.error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.customerVerified?.nationality
                      .value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="
                    customerVerificationData.customerVerified?.nationality
                      ?.error
                  "
                >
                  Nationality mismatch found
                </span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Make and Model
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.customerVerified?.carMakeAndModel
                      .error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.customerVerified?.carMakeAndModel
                      .value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="
                    customerVerificationData.customerVerified?.carMakeAndModel
                      ?.error
                  "
                >
                  Make and model mismatch found
                </span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Model Year
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.customerVerified?.carModelYear
                      .error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.customerVerified?.carModelYear
                      .value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="
                    customerVerificationData.customerVerified?.carModelYear
                      ?.error
                  "
                >
                  Car model year mismatch found
                </span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  DOB
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.customerVerified?.dob.error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.customerVerified?.dob.value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="customerVerificationData.customerVerified?.dob?.error"
                >
                  DOB mismatch found
                </span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Emirate Of Registration
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.registrationCertificate
                      ?.placeOfIssue.error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.registrationCertificate
                      ?.placeOfIssue.value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="
                    customerVerificationData.registrationCertificate
                      ?.placeOfIssue?.error
                  "
                >
                  Emirate of registration mismatch found
                </span>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  UAE License Held For
                </label>
                <div
                  class="p-3 bg-white rounded-md text-gray-800 font-medium transition-colors duration-150"
                  :class="
                    customerVerificationData.vehicleDriverDetails
                      ?.driverLicenseIssueDate.error
                      ? 'border-2 border-red-600'
                      : 'border border-green-100 hover:border-green-200'
                  "
                >
                  {{
                    customerVerificationData.vehicleDriverDetails
                      ?.driverLicenseIssueDate.value || '-'
                  }}
                </div>
                <span
                  class="text-red-600 text-sm"
                  v-if="
                    customerVerificationData.vehicleDriverDetails
                      ?.driverLicenseIssueDate?.error
                  "
                >
                  UAE License Held For mismatch found
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Update and save button-->
      <div
        class="px-6 pb-6 max-w-4xl mx-auto flex justify-end"
        v-if="
          customerVerificationData.buttonData.status == 'requires_verification'
        "
      >
        <x-button
          size="sm"
          color="orange"
          :loading="isLoading"
          @click="updateAndSave"
          :disabled="isLoading"
          >Update & Save</x-button
        >
      </div>

      <template #actions>
        <div class="flex justify-end">
          <x-button
            size="sm"
            ghost
            @click.prevent="modals.customerVerification = false"
          >
            Close
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
