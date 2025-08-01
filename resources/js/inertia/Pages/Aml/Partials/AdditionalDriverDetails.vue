<script setup>
import { ref, computed } from 'vue'
const { isRequired } = useRules();

const page = usePage();
const notification = useToast();

const lookups = page.props.lookups;

// Computed options for dropdowns
const driverGenderOptions = computed(() => [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
])

const licenseIssuePlaceOptions = computed(() => {
  return useGenerateOptions(lookups?.issuance_place ?? [], 'code', 'text');
});

const nationalitiesOptions = computed(() => {
  return useGenerateOptions(page.props.nationalities ?? [], 'code', 'text');
});

const drivingExperienceOptions = computed(() => {
  const options = [{ value: '0', label: 'No Experience' }]

  for (let i = 1; i <= 50; i++) {
    options.push({
      value: i.toString(),
      label: i === 1 ? '1 Year' : `${i} Years`
    })
  }

  return options
});

const additionalDriverDetailsForm = useForm({
  quote_type_id: page.props.quoteType.id,
  quote_uuid: page.props.quoteRequest?.uuid,
  insurance_provider_code: page.props.quoteRequest?.plan?.insurance_provider.code ?? '',
  is_insured_and_driver_same: page.props.quoteRequest?.car_quote_request_detail?.is_insured_and_driver_same ?? '',
  driver_first_name: page.props.quoteRequest?.car_quote_request_detail?.driver_first_name ?? '',
  driver_last_name: page.props.quoteRequest?.car_quote_request_detail?.driver_last_name ?? '',
  driver_dob: page.props.quoteRequest?.car_quote_request_detail?.driver_dob ?? '',
  driver_gender: page.props.quoteRequest?.car_quote_request_detail?.driver_gender ?? '',
  driver_license_number: page.props.quoteRequest?.car_quote_request_detail?.driver_license_number ?? '',
  license_issue_place: page.props.quoteRequest?.car_quote_request_detail?.license_issue_place ?? '',
  license_issue_date: page.props.quoteRequest?.car_quote_request_detail?.license_issue_date ?? '',
  license_expiry_date: page.props.quoteRequest?.car_quote_request_detail?.license_expiry_date ?? '',
  uae_driving_experience: page.props.quoteRequest?.car_quote_request_detail?.uae_driving_experience ?? '',
  home_country_license_issuance: page.props.quoteRequest?.car_quote_request_detail?.home_country_license_issuance ?? '',
  home_country_driving_experience: page.props.quoteRequest?.car_quote_request_detail?.home_country_driving_experience ?? '',
});

const state = reactive({
  isEdit: false,
});

const submitAdditionalDriverDetailsForm = (isValid) => {
  if (isValid) {
    additionalDriverDetailsForm.processing = true;
    axios.post('/kyc/update-additional-vehicle-driver-details', additionalDriverDetailsForm).then(response => {
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
    }).catch(error => {
      notification.error({
        title: error.response.data.message,
        position: 'top',
      });
    }).finally(() => {
      additionalDriverDetailsForm.processing = false;
    });
  }
};

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
        Additional Driver Details
      </h3>
    </div>

    <x-form @submit="submitAdditionalDriverDetailsForm">
      <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
        <!-- Is Insured and Driver Same -->
        <x-field label="Is the Insured and Driver the same?" required>
          <x-select
            v-model="additionalDriverDetailsForm.is_insured_and_driver_same"
            :rules="[isRequired]"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' }
            ]"
            placeholder="Select Is Insured and Driver Same"
            :disabled="!state.isEdit"
          />
        </x-field>

        <!-- Driver Name -->
        <x-field label="Driver First Name" :required="! isGIG">
          <x-input
            v-model="additionalDriverDetailsForm.driver_first_name"
            :rules="(! isGIG) ? [isRequired] : []"
            placeholder="Driver First Name"
            type="text"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Driver Last Name" :required="! isGIG">
          <x-input
            v-model="additionalDriverDetailsForm.driver_last_name"
            :rules="(! isGIG) ? [isRequired] : []"
            placeholder="Driver Last Name"
            type="text"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Driver DOB" :required="isSUKOON">
          <DatePicker
            v-model="additionalDriverDetailsForm.driver_dob"
            :rules="isSUKOON ? [isRequired] : []"
            placeholder="Driver DOB"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Driver Gender" :required="! isSUKOON">
          <x-select
            v-model="additionalDriverDetailsForm.driver_gender"
            :rules="(! isSUKOON) ? [isRequired] : []"
            :options="driverGenderOptions"
            placeholder="Select Driver Gender"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Driver License Number" required>
          <x-input
            v-model="additionalDriverDetailsForm.driver_license_number"
            :rules="[isRequired]"
            placeholder="Driver License Number"
            type="text"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="License Issue Place" :required="isSUKOON">
          <x-select
            v-model="additionalDriverDetailsForm.license_issue_place"
            :rules="isSUKOON ? [isRequired] : []"
            :options="licenseIssuePlaceOptions"
            placeholder="Select License Issue Place"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="License Issue Date" :required="isSUKOON">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_issue_date"
            :rules="isSUKOON ? [isRequired] : []"
            placeholder="License Issue Date"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="License Expiry Date" :required="! isLIVA">
          <DatePicker
            v-model="additionalDriverDetailsForm.license_expiry_date"
            placeholder="License Expiry Date"
            :rules="(! isLIVA) ? [isRequired] : []"
            :disabled="!state.isEdit"
          />
        </x-field>

        <!-- TODO: Required only if 'Driver same as Client?' is NO -->
        <x-field label="UAE Driving Experience" :required="isLIVA">
          <x-select
            v-model="additionalDriverDetailsForm.uae_driving_experience"
            :rules="isLIVA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select UAE License Years"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Home Country License Issuance" :required="! isGIG">
          <ComboBox
            :single="true"
            v-model="additionalDriverDetailsForm.home_country_license_issuance"
            :rules="(! isGIG) ? [isRequired] : []"
            placeholder="Select License Home Country"
            :options="nationalitiesOptions"
            class="w-full"
            :disabled="!state.isEdit"
          />
        </x-field>

        <x-field label="Home Country Driving Experience" :required="isLIVA">
          <x-select
            v-model="additionalDriverDetailsForm.home_country_driving_experience"
            :rules="isLIVA ? [isRequired] : []"
            :options="drivingExperienceOptions"
            placeholder="Select Driver Years Home Country"
            :disabled="!state.isEdit"
          />
        </x-field>
      </dl>
      <div class="flex justify-end my-5 gap-x-2">
        <template v-if="!state.isEdit">
          <x-tooltip v-if="false">
            <x-button
              class="focus:ring-2 focus:ring-black"
              size="sm"
              :disabled="false"
            >
              Edit
            </x-button>
            <template #tooltip>
              <span class="custom-tooltip-content">
                You don't have permission to edit this section.
              </span>
            </template>
          </x-tooltip>
          <x-button
            v-else
            class="focus:ring-2 focus:ring-black"
            size="sm"
            @click="state.isEdit = true"
          >
            Edit
          </x-button>
        </template>

        <template v-else>
          <x-button
            class="focus:ring-2 focus:ring-black"
            size="sm"
            color="blue"
            @click="state.isEdit = false"
          >
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="orange"
            type="submit"
            class="px-6"
            :loading="additionalDriverDetailsForm.processing"
          >
            Save
          </x-button>
        </template>
      </div>
    </x-form>
  </div>
</template>
