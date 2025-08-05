<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
const { isRequired } = useRules();

const page = usePage();
const notification = useToast();
const lookups = page.props.lookups;
const hasPermission = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

// Computed options for dropdowns
const rtaTransactionTypeOptions = computed(() => {
  return useGenerateOptions(lookups?.rta_transaction_type ?? [], 'code', 'text');
});

const rtaPlateCategoryOptions = computed(() => {
  return useGenerateOptions(lookups?.rta_plate_category ?? [], 'code', 'text');
});

const vehicleColorOptions = computed(() => {
  return useGenerateOptions(lookups?.vehicle_color ?? [], 'code', 'text');
});

const plateColorOptions = computed(() => {
  return useGenerateOptions(lookups?.vehicle_color ?? [], 'code', 'text');
});

const bankNameOptions = computed(() => {
  return useGenerateOptions(lookups?.bank_name ?? [], 'code', 'text');
});

const annualMileageEstimateOptions = computed(() => {
  return useGenerateOptions(lookups?.annual_mileage_estimate ?? [], 'code', 'text');
});

const plateCodeOptions = computed(() => {
  return useGenerateOptions(lookups?.plate_code ?? [], 'code', 'text');
});

const carQuoteRequestDetail = computed(() => {
  return page.props.quoteRequest?.car_quote_request_detail;
});

const additionalVehicleTransactionDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  additional_vehicle_transaction_details: true,
  rta_transaction_type: carQuoteRequestDetail.value?.rta_transaction_type?.toString() ?? '',
  plate_code: carQuoteRequestDetail.value?.plate_code ?? '',
  plate_number: carQuoteRequestDetail.value?.plate_number ?? '',
  traffic_code_number: carQuoteRequestDetail.value?.traffic_code_number ?? '',
  chassis_number: carQuoteRequestDetail.value?.chassis_number ?? '',
  engine_number: carQuoteRequestDetail.value?.engine_number ?? '',
  rta_plate_category: carQuoteRequestDetail.value?.rta_plate_category ?? '',
  vehicle_color: carQuoteRequestDetail.value?.vehicle_color ?? '',
  plate_color: carQuoteRequestDetail.value?.plate_color ?? '',
  bank_loan: carQuoteRequestDetail.value?.bank_loan ?? '',
  bank_name: carQuoteRequestDetail.value?.bank_name ?? '',
  first_registration_date: carQuoteRequestDetail.value?.first_registration_date ?? '',
  policy_effective_date: carQuoteRequestDetail.value?.policy_effective_date ?? '',
  policy_expiry_date: carQuoteRequestDetail.value?.policy_expiry_date ?? '',
  certificate_start_date: carQuoteRequestDetail.value?.certificate_start_date ?? '',
  certificate_end_date: carQuoteRequestDetail.value?.certificate_end_date ?? '',
  annual_mileage_estimate: carQuoteRequestDetail.value?.annual_mileage_estimate?.toString() ?? '',
});

const hasNotEditPermission = computed(() => {
  return !hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)
});

const submitAdditionalVehicleTransactionDetailsForm = (isValid) => {
  if (isValid) {
    additionalVehicleTransactionDetailsForm.processing = true;
    axios.post('/kyc/update-additional-vehicle-driver-details', additionalVehicleTransactionDetailsForm).then(response => {
      if (response.data.success) {
        notification.success({
          title: response.data.message,
          position: 'top',
        });
        router.reload({
          replace: true,
          preserveScroll: true,
          preserveState: true,
        });
      } else {
        notification.error({
          title: response.data.message,
          position: 'top',
        });
      }
    }).finally(() => {
      additionalVehicleTransactionDetailsForm.processing = false;
    });
  }
}

const isGIG = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.AXA;
});

const isLIVA = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.RSA;
});

const isSUKOON = computed(() => {
  return page.props.quoteRequest?.plan?.insurance_provider.code === page.props.insuranceProviderCodeEnum.OIC;
});
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Additional Vehicle and Transaction Details
      </h3>
    </div>
    <div>
    <x-form @submit="submitAdditionalVehicleTransactionDetailsForm" :auto-focus="false">
      <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
          <x-field label="RTA Transaction Type" required>
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.rta_transaction_type"
              :rules="[isRequired]"
              :options="rtaTransactionTypeOptions"
              placeholder="Select RTA Transaction Type"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Code" :required="! isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.plate_code"
              :rules="(! isGIG) ? [isRequired] : []"
              placeholder="Select Plate Code"
              :options="plateCodeOptions"
              class="w-full"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Number" :required="! isGIG">
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.plate_number"
              :rules="(! isGIG) ? [isRequired] : []"
              placeholder="Plate Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Traffic Code Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.traffic_code_number"
              :rules="[isRequired]"
              placeholder="Traffic Code Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Chassis Number" required>
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.chassis_number"
              :rules="[isRequired]"
              placeholder="Chassis Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Engine Number" :required="! isSUKOON">
            <x-input
              v-model="additionalVehicleTransactionDetailsForm.engine_number"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="Engine Number"
              type="text"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="RTA Plate Category" :required="isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.rta_plate_category"
              :rules="isGIG ? [isRequired] : []"
              :options="rtaPlateCategoryOptions"
              placeholder="Select RTA Plate Category"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Vehicle Color" required>
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.vehicle_color"
              :options="vehicleColorOptions"
              :rules="[isRequired]"
              placeholder="Select Vehicle Color"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Plate Color" :required="isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.plate_color"
              :options="plateColorOptions"
              :rules="isGIG ? [isRequired] : []"
              placeholder="Select Plate Color"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Bank Loan?" required>
            <x-select
              v-model="additionalVehicleTransactionDetailsForm.bank_loan"
              :rules="[isRequired]"
              :options="[
                { value: 1, label: 'Yes' },
                { value: 0, label: 'No' }
              ]"
              placeholder="Select Bank Loan"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Bank Name" :required="! isGIG">
            <x-select
              filterable
              v-model="additionalVehicleTransactionDetailsForm.bank_name"
              placeholder="Select Bank Name"
              :options="bankNameOptions"
              :disabled="additionalVehicleTransactionDetailsForm.bank_loan !== 1 || hasNotEditPermission"
              :rules="(! isGIG) ? [isRequired] : []"
              class="w-full"
            />
          </x-field>

          <x-field label="First Registration Date" :required="! isSUKOON">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.first_registration_date"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="First Registration Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Policy Effective Date" required>
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_effective_date"
              :rules="[isRequired]"
              placeholder="Policy Effective Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Policy Expiry Date" :required="isLIVA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.policy_expiry_date"
              :rules="isLIVA ? [isRequired] : []"
              placeholder="Policy Expiry Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Certificate Start Date" :required="! isSUKOON">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_start_date"
              :rules="(! isSUKOON) ? [isRequired] : []"
              placeholder="Certificate Start Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Certificate End Date" :required="isLIVA">
            <DatePicker
              v-model="additionalVehicleTransactionDetailsForm.certificate_end_date"
              :rules="isLIVA ? [isRequired] : []"
              placeholder="Certificate End Date"
              :disabled="hasNotEditPermission"
            />
          </x-field>

          <x-field label="Annual Mileage Estimate" :required="isLIVA">
            <x-input
              v-if="isGIG"
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="(! isGIG) ? [isRequired] : []"
              placeholder="Select Annual Mileage Estimate"
              type="text"
              :disabled="hasNotEditPermission"
            />
            <x-select
              v-else
              filterable
              v-model="additionalVehicleTransactionDetailsForm.annual_mileage_estimate"
              :rules="isLIVA ? [isRequired] : []"
              :options="annualMileageEstimateOptions"
              placeholder="Select Annual Mileage Estimate"
              :disabled="hasNotEditPermission"
            />
          </x-field>
        </dl>
        <div
          class="flex justify-end my-5 gap-x-2"
          v-if="hasPermission(permissionsEnum.EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)"
        >
          <x-button
            size="sm"
            color="orange"
            type="submit"
            class="px-6"
            :loading="additionalVehicleTransactionDetailsForm.processing"
          >
            Save
          </x-button>
        </div>
        <div
          class="flex justify-end my-5 gap-x-2"
          v-else
        >
          <x-tooltip>
            <x-button
              size="sm"
              color="orange"
              type="submit"
              class="px-6"
              disabled
            >
              Save
            </x-button>
            <template #tooltip>
              You don't have permission to edit this section.
            </template>
          </x-tooltip>
        </div>
      </x-form>
    </div>
  </div>
</template>
