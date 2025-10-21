<script setup>
const { modals, customerVerificationData } = defineProps({
  quote: Object,
  modals: Object,
  customerVerificationData: Object,
});
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

      <div class="px-6 pb-6 max-w-4xl mx-auto">
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
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:bg-blue-25 hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.name || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Nationality
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:bg-blue-25 hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.nationality || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Make and Model
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:bg-blue-25 hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.carMakeAndModel || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Model Year
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:bg-blue-25 hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.carModelYear || '-' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  DOB
                </label>
                <div
                  class="p-3 bg-white border border-blue-100 rounded-md text-gray-800 font-medium hover:bg-blue-25 hover:border-blue-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.webForm?.dob || '-' }}
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
                  class="p-3 bg-white border border-green-100 rounded-md text-gray-800 font-medium hover:bg-green-25 hover:border-green-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.customerVerified?.name || '-' }}
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Nationality
                </label>
                <div
                  class="p-3 bg-white border border-green-100 rounded-md text-gray-800 font-medium hover:bg-green-25 hover:border-green-200 transition-colors duration-150"
                >
                  {{
                    customerVerificationData.customerVerified?.nationality ||
                    '-'
                  }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Make and Model
                </label>
                <div
                  class="p-3 bg-white border border-green-100 rounded-md text-gray-800 font-medium hover:bg-green-25 hover:border-green-200 transition-colors duration-150"
                >
                  {{
                    customerVerificationData.customerVerified
                      ?.carMakeAndModel || '-'
                  }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  Car Model Year
                </label>
                <div
                  class="p-3 bg-white border border-green-100 rounded-md text-gray-800 font-medium hover:bg-green-25 hover:border-green-200 transition-colors duration-150"
                >
                  {{
                    customerVerificationData.customerVerified?.carModelYear ||
                    '-'
                  }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-2">
                  DOB
                </label>
                <div
                  class="p-3 bg-white border border-green-100 rounded-md text-gray-800 font-medium hover:bg-green-25 hover:border-green-200 transition-colors duration-150"
                >
                  {{ customerVerificationData.customerVerified?.dob || '-' }}
                </div>
              </div>
            </div>
          </div>
        </div>
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
