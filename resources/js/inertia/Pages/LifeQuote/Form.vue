<script setup>
import { calculateBMI, calculateAge } from '../../Composables/utilities';

const notification = useNotifications('toast');
const page = usePage();
const { isRequired, isEmail, isMobileNo, maxCharacters } = useRules();

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  currency: Object,
  purposeOfInsurance: Object,
  maritalStatus: Object,
  typeOfInsurance: Object,
  numberOfYears: Object,
  subSources: Array,
  leadSourceParams: Object,
});

const quoteForm = useForm({
  modelType: 'Life',
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob || '',
  sum_insured_value: props.quote?.life_quote?.sum_insured_value || '',
  nationality_id: props.quote?.nationality_id || '',
  sum_insured_currency_id:
    props.quote?.life_quote?.sum_insured_currency_id || '',
  marital_status_id: props.quote?.life_quote?.marital_status_id || '',
  purpose_of_insurance_id:
    props.quote?.life_quote?.purpose_of_insurance_id || '',
  tenure_of_insurance_id: props.quote?.life_quote?.tenure_of_insurance_id || '',
  number_of_years_id: props.quote?.life_quote?.number_of_years_id || '',
  is_smoker: props.quote?.life_quote?.is_smoker || 0,
  gender: props.quote?.gender || '',
  others_info: props.quote?.life_quote?.others_info || '',
  height: props.quote?.life_quote?.height || '',
  weight: props.quote?.life_quote?.weight || '',
  bmi: props.quote?.life_quote?.bmi || '',
  age: props.quote?.life_quote?.age || '',
  source: props.quote?.source || '',
  // Sub-source fields
  sub_source_id:
    parseInt(
      props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0,
    ) || null,
  sub_source_options_id:
    parseInt(
      props.quote?.sub_source_options_id ||
        props.leadSourceParams?.subSourceOption ||
        0,
    ) || null,
  primary_ref_id:
    props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
  notes: (() => {
    let notes = props.quote?.notes || props.quote?.life_quote?.notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      if (notes) {
        notes = `${notes}, ${partnerName}`;
      } else {
        notes = partnerName;
      }
    }
    return notes;
  })(),
});

const editMode = computed(() => {
  return props.quote ? true : false;
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix: item.description || null,
  }));
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  return (
    selectedSubSource?.childs?.map(option => ({
      value: option.id,
      label: option.text,
      code: option.code, // Include the code property for showPartnerNameField
      suffix: option.description || null,
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM'
  );
});

const isEcomLeadExtension = computed(() => {
  return (
    props.leadSourceParams?.type === 'ecom_lead_extension' ||
    (quoteForm.sub_source_id === null &&
      quoteForm.sub_source_options_id === null &&
      quoteForm.primary_ref_id)
  );
});

const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([
    rolesEnum.LifeManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const showPartnerNameField = computed(() => {
  const selectedOption = subSourceOptionOptions.value.find(
    option => option.value === quoteForm.sub_source_options_id,
  );
  return selectedOption?.code === 'other-clubs-or-campaigns';
});

const rolesEnum = page.props.rolesEnum;

// Watchers for field resets and partner name handling
watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
      quoteForm.partner_name = '';
    }
  },
);

watch(
  () => quoteForm.sub_source_options_id,
  newValue => {
    if (!showPartnerNameField.value) {
      quoteForm.partner_name = '';
    }
  },
);

// Watcher for partner_name to update notes
watch(
  () => quoteForm.partner_name,
  (newValue, oldValue) => {
    if (!showPartnerNameField.value) return;

    // Remove old partner name from notes if it exists
    if (oldValue) {
      const oldPattern = new RegExp(
        `(, ${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`,
        'g',
      );
      quoteForm.notes = quoteForm.notes.replace(oldPattern, '').trim();
      // Clean up any double commas or leading/trailing commas
      quoteForm.notes = quoteForm.notes
        .replace(/,\s*,/g, ',')
        .replace(/^,\s*|,\s*$/g, '');
    }

    // Add new partner name to notes
    if (newValue) {
      if (quoteForm.notes) {
        quoteForm.notes = `${quoteForm.notes}, ${newValue}`;
      } else {
        quoteForm.notes = newValue;
      }
    }
  },
);

const validateHeight = value => {
  if (!value) return true;
  const height = parseFloat(value);
  if (height < 1 || height > 999) {
    return 'Height must be between 1 and 999 cm';
  }
  return true;
};

const validateWeight = value => {
  if (!value) return true;
  const weight = parseFloat(value);
  if (weight < 1 || weight > 999) {
    return 'Weight must be between 1 and 999 kg';
  }
  return true;
};

const validateSumAssured = value => {
  if (!value) return true;
  const sumAssured = parseFloat(value);
  if (sumAssured < 1 || sumAssured > 100000000) {
    return 'Sum Assured must be between 1 and 100,000,000';
  }
  return true;
};

const validateAlphaOnly = value => {
  if (!value) return true; // Allow empty values (optional)
  const isAlphabetOnly = /^[a-zA-Z\s]+$/.test(value); // Allows letters and spaces
  if (!isAlphabetOnly) {
    return 'Only alphabets are allowed';
  }
  return true;
};

function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('life-quotes-update', props.quote.uuid)
      : route('life-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
const getBMI = () => {
  if (!quoteForm.height || !quoteForm.weight) {
    quoteForm.bmi = '';
    return;
  }

  quoteForm.bmi = calculateBMI(quoteForm.height, quoteForm.weight);
};

const dobChanged = () => {
  if (!quoteForm.dob) {
    quoteForm.age = '';
    return;
  }
  const date = quoteForm.dob.split('T')[0];
  quoteForm.age = calculateAge(date);
};
watch(
  () => quoteForm.dob,
  (newValue, oldValue) => {
    if (newValue) {
      dobChanged();
    }
  },
  { deep: true },
);
</script>

<template>
  <div>
    <Head title="Life Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Life Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('life-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Life Quotes List</x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">{{
        quoteForm?.errors?.error
      }}</x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Lead Source Fields - Only show when type is referral  isReferralType && !isEcomLeadExtension -->

        <x-select
          v-if="isReferralType && !isEcomLeadExtension"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          filterPlaceholder="Filter IMCRM SUB-SOURCE...."
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          :required="subSourceOptions.length > 0"
          :error="quoteForm.errors.sub_source_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-select
          v-if="
            isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension
          "
          label="SUB SOURCE OPTION"
          v-model="quoteForm.sub_source_options_id"
          :options="subSourceOptionOptions"
          class="w-full"
          placeholder="Select Sub Source Option"
          filterable
          filterPlaceholder="Filter Sub Source Option...."
          :disabled="!canEditSubSourceFields"
          :rules="subSourceOptionOptions.length > 0 ? [isRequired] : []"
          :required="subSourceOptionOptions.length > 0"
          :error="quoteForm.errors.sub_source_options_id"
        >
          <template #suffix="{ item }">
            <x-tooltip v-if="item.suffix" placement="right">
              <x-icon icon="info" color="error" />
              <template #tooltip>
                {{ item.suffix }}
              </template>
            </x-tooltip>
          </template>
        </x-select>

        <x-input
          v-if="isEcomLeadExtension"
          label="PRIMARY REF ID"
          required
          v-model="quoteForm.primary_ref_id"
          class="w-full"
          type="text"
          placeholder="Enter Primary Ref ID"
          :rules="[isRequired]"
          :tooltip="'ID of the original ECOM lead'"
          :error="quoteForm.errors.primary_ref_id"
          :disabled="!canEditSubSourceFields"
        />

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          :required="canEditSubSourceFields"
          v-model="quoteForm.partner_name"
          class="w-full"
          type="text"
          placeholder="Enter Partner Name"
          maxLength="50"
          :rules="canEditSubSourceFields ? [isRequired, maxCharacters(50)] : []"
          :tooltip="'Name of campaign, event or club'"
          :error="quoteForm.errors.partner_name"
          :disabled="!canEditSubSourceFields"
        />
        <x-field label="Purpose of Insurance" required>
          <x-select
            v-model="quoteForm.purpose_of_insurance_id"
            :options="
              purposeOfInsurance.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.purpose_of_insurance_id"
          />
        </x-field>
        <x-field label="First Name" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired, validateAlphaOnly]"
            class="w-full"
            :error="quoteForm.errors.first_name"
            maxLength="20"
          />
        </x-field>
        <x-field label="Last Name" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            :rules="[isRequired, validateAlphaOnly]"
            class="w-full"
            :error="quoteForm.errors.last_name"
            maxLength="50"
          />
        </x-field>
        <x-field label="Email" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :rules="[isRequired]"
            :disabled="editMode"
            class="w-full"
            :error="quoteForm.errors.email"
          />
        </x-field>
        <x-field label="Phone Number" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :rules="[isRequired]"
            class="w-full"
            :disabled="editMode"
            :error="quoteForm.errors.mobile_no"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="quoteForm.dob"
            :rules="[isRequired]"
            input-classes="w-full"
          />
        </x-field>
        <x-field label="Age">
          <x-input
            v-model="quoteForm.age"
            type="text"
            class="w-full"
            :disabled="true"
            :error="quoteForm.errors.age"
          />
        </x-field>
        <x-field label="Nationality" required>
          <x-select
            v-model="quoteForm.nationality_id"
            :options="
              nationalities.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            filterable
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.nationality_id"
          />
        </x-field>
        <x-field label="Marital Status" required>
          <x-select
            v-model="quoteForm.marital_status_id"
            :options="
              maritalStatus.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.marital_status_id"
          />
        </x-field>
        <x-field label="Gender" required>
          <x-select
            v-model="quoteForm.gender"
            :options="[
              { value: 'Male', label: 'Male' },
              { value: 'Female', label: 'Female' },
            ]"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.gender"
          />
        </x-field>
        <div class="w-full">
          <div class="grid sm:grid-cols-3 gap-4">
            <x-field label="Height" required>
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.height"
                  @change="getBMI"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[isRequired, validateHeight]"
                  class="w-full"
                  :error="quoteForm.errors.height"
                  maxLength="20"
                >
                  <template #suffix>
                    <span
                      class="absolute inset-y-0 right-3 flex items-center text-gray-500"
                      >Cms</span
                    >
                  </template>
                </x-input>
              </div>
            </x-field>
            <x-field label="Weight" required>
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.weight"
                  @change="getBMI"
                  type="number"
                  step="0.01"
                  min="0"
                  :rules="[isRequired, validateWeight]"
                  class="w-full"
                  :error="quoteForm.errors.weight"
                  maxLength="20"
                >
                  <template #suffix>
                    <span
                      class="absolute inset-y-0 right-3 flex items-center text-gray-500"
                      >Kgs</span
                    >
                  </template>
                </x-input>
              </div>
            </x-field>
            <x-field label="BMI">
              <div class="relative w-full">
                <x-input
                  v-model="quoteForm.bmi"
                  type="text"
                  class="w-full"
                  :error="quoteForm.errors.bmi"
                  :disabled="true"
                />
              </div>
            </x-field>
          </div>
        </div>
        <x-field label="Tenure of Cover" required>
          <x-select
            v-model="quoteForm.number_of_years_id"
            :options="
              numberOfYears.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.number_of_years_id"
          />
        </x-field>
        <div class="w-full">
          <div class="grid sm:grid-cols-12 gap-4">
            <div class="sm:col-span-4">
              <x-field label="Currency" required>
                <x-select
                  v-model="quoteForm.sum_insured_currency_id"
                  :options="
                    currency.map(item => ({
                      value: item.id,
                      label: item.text,
                    }))
                  "
                  :rules="[isRequired]"
                  class="w-full"
                  :error="quoteForm.errors.sum_insured_currency_id"
                />
              </x-field>
            </div>
            <div class="sm:col-span-8">
              <x-field label="Sum Assured" required>
                <x-input
                  v-model="quoteForm.sum_insured_value"
                  type="number"
                  class="w-full"
                  :rules="[isRequired, validateSumAssured]"
                  :error="quoteForm.errors.sum_insured_value"
                />
              </x-field>
            </div>
          </div>
        </div>

        <x-field
          label="Have you smoked tobacco/nicotine in the last 12 months?"
          required
        >
          <x-select
            v-model="quoteForm.is_smoker"
            :options="[
              { value: 1, label: 'Yes' },
              { value: 0, label: 'No' },
            ]"
            class="w-full"
            :error="quoteForm.errors.is_smoker"
          />
        </x-field>
        <x-field label="Additional Notes">
          <x-textarea
            v-model="quoteForm.others_info"
            type="text"
            class="w-full"
            :error="quoteForm.errors.others_info"
          />
        </x-field>

        <x-textarea
          label="NOTES"
          v-model="quoteForm.notes"
          type="textarea"
          rows="5"
          class="w-full"
          :error="quoteForm.errors.notes"
        />
      </div>

      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ editMode ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
