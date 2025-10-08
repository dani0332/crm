<script setup>
const notification = useNotifications('toast');
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  yearOfManufacture: Object,
  dropdownSource: Object,
  model: String,
  quote: { type: Object, default: null },
  bikeQuoteDetail: { type: Object, default: null },
  quoteStatusEnums: Array,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const bikeClaimHistoryOptions = computed(() => {
  return props.dropdownSource.claim_history_id
    .filter(item => {
      return item.quote_type_id === null || item.quote_type_id === 6;
    })
    .map(item => ({
      value: item.id,
      label: item.text,
    }));
});

const bikeMakeOptions = computed(() => {
  return props.dropdownSource.bike_make_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const bikeModelOptions = computed(() => {
  return props.dropdownSource.bike_model_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

const getBikeModel = (initial = false) => {
  isBikeModelDisabled.value = true;
  axios.get(`/bike-model-by-id?id=${quoteForm.make_id}`).then(({ data }) => {
    props.dropdownSource.bike_model_id = data;
    isBikeModelDisabled.value = false;
    if (quoteForm.model_id !== null && !initial) {
      quoteForm.model_id = null;
    }
  });
};

const currentlyInsuredWithOptions = computed(() => {
  return props.dropdownSource.currently_insured_with_id.map(item => ({
    value: item.id,
    label: item.text,
  }));
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return props.subSources?.map(source => ({
    value: source.id,
    label: source.text,
    suffix: source.description || source.tooltip || `Information about ${source.text}`, // Use suffix for tooltip data
  })) || [];
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  return selectedSubSource?.childs?.map(child => ({
    value: child.id,
    label: child.text,
    suffix: child.description || child.tooltip || `Information about ${child.text}`, // Use suffix for tooltip data
  })) || [];
});

const isReferralType = computed(() => {
  return props.leadSourceParams?.type === 'referral' || props.quote?.source === 'IMCRM';
});

const isEcomLeadExtension = computed(() => {
  if (props.leadSourceParams?.type === 'ecom_lead_extension') return true;
  if (!quoteForm.sub_source_id && !quoteForm.sub_source_options_id && quoteForm.primary_ref_id) {
    return true;
  }
  return false;
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([rolesEnum.BikeManager, rolesEnum.Admin, rolesEnum.LeadPool]);
});

// Show partner name field when "other-clubs-or-campaigns" is selected
const showPartnerNameField = computed(() => {
  if (!quoteForm.sub_source_options_id) return false;
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  if (!selectedSubSource?.childs) return false;
  const selectedSubSourceOption = selectedSubSource.childs.find(child => child.id == quoteForm.sub_source_options_id);
  return selectedSubSourceOption?.code === 'other-clubs-or-campaigns';
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  dob: props.quote?.dob || null,
  nationality_id: props.quote?.nationality_id || null,
  uae_license_held_for_id:
    props.quote?.bike_quote?.uae_license_held_for_id || null,
  bike_value: props.bikeQuoteDetail?.bike_value || null,
  year_of_manufacture: props.quote?.bike_quote?.year_of_manufacture || null,
  back_home_license_held_for_id:
    props.bikeQuoteDetail?.back_home_license_held_for_id || null,
  notes: props.bikeQuoteDetail?.notes || '',
  has_ncd_supporting_documents: null,
  has_ncd_supporting_documents_dropdown: null,
  claim_history_id: props.bikeQuoteDetail?.claim_history_id || null,
  insurance_type_id: props.bikeQuoteDetail?.insurance_type_id || null,
  emirate_of_registration_id:
    props.bikeQuoteDetail?.emirate_of_registration_id || null,
  seat_capacity: props.bikeQuoteDetail?.seat_capacity || '',
  make_id: props.bikeQuoteDetail?.make_id || null,
  model_id: props.bikeQuoteDetail?.model_id || null,
  bike_value_tier: props.bikeQuoteDetail?.bike_value_tier || null,
  currently_insured_with: props.quote?.currently_insured_with_id || null,
  cubic_capacity: props.bikeQuoteDetail?.cubic_capacity || null,
  gender: props.quote?.customer?.gender || null,
  chassis_number: props.bikeQuoteDetail?.chassis_number || null,
  // Sub-source fields from CreateLeadModal
  sub_source_id: parseInt(props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0) || null,
  sub_source_options_id: parseInt(props.quote?.sub_source_options_id || props.leadSourceParams?.subSourceOption || 0) || null,
  primary_ref_id: props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
  notes: (() => {
    let notes = props.quote?.notes || props.bikeQuoteDetail?.notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      notes = notes ? `${notes}, ${partnerName}` : partnerName;
    }
    return notes;
  })(),
});

const { isRequired, isEmail, isMobileNo, maxCharacters } = useRules();

const formFieldReq = reactive({
  nationality: false,
  dob: false,
  make_id: false,
  model_id: false,
  currently_insured_with: false,
});

const editMode = computed(() => {
  return props.quote ? true : false;
});

// Watchers for sub-source fields
watch(() => quoteForm.sub_source_id, (newValue) => {
  if (newValue) {
    quoteForm.sub_source_options_id = null;
    quoteForm.partner_name = '';
  }
});

watch(() => quoteForm.sub_source_options_id, (newValue) => {
  if (newValue) {
    quoteForm.partner_name = '';
    quoteForm.primary_ref_id = '';
  }
});

// Watcher for partner_name to update notes
watch(() => quoteForm.partner_name, (newValue, oldValue) => {
  if (!showPartnerNameField.value) return;

  // Remove old partner name from notes if it exists
  if (oldValue) {
    const oldPattern = new RegExp(`(, ${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}|${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}, |${oldValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'g');
    quoteForm.notes = quoteForm.notes.replace(oldPattern, '').trim();
    // Clean up any double commas or leading/trailing commas
    quoteForm.notes = quoteForm.notes.replace(/,\s*,/g, ',').replace(/^,\s*|,\s*$/g, '');
  }

  // Add new partner name to notes
  if (newValue) {
    if (quoteForm.notes) {
      quoteForm.notes = `${quoteForm.notes}, ${newValue}`;
    } else {
      quoteForm.notes = newValue;
    }
  }
});

const isEmptyField = ref(false);
const isError = ref(false);
const isBikeModelDisabled = ref(false);
function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    formFieldReq.nationality_id = true;
  } else {
    formFieldReq.nationality_id = false;
  }
  if (quoteForm.dob == null || quoteForm.dob == '') {
    formFieldReq.dob = true;
  } else {
    formFieldReq.dob = false;
  }
  if (quoteForm.make_id == null || quoteForm.make_id == '') {
    formFieldReq.make_id = true;
  } else {
    formFieldReq.make_id = false;
  }
  if (
    quoteForm.currently_insured_with == null ||
    quoteForm.currently_insured_with == ''
  ) {
    formFieldReq.currently_insured_with = true;
  } else {
    formFieldReq.currently_insured_with = false;
  }
  if (quoteForm.model_id == null || quoteForm.model_id == '') {
    formFieldReq.model_id = true;
  } else {
    formFieldReq.model_id = false;
  }
  quoteForm.has_ncd_supporting_documents =
    quoteForm.has_ncd_supporting_documents_dropdown == 'y' ? 1 : 0;
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('bike-quotes-update', props.quote.uuid)
      : route('bike-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}

onMounted(() => {
  getBikeModel(true);
  quoteForm.has_ncd_supporting_documents_dropdown =
    props.bikeQuoteDetail?.has_ncd_supporting_documents == 1
      ? 'y'
      : 'n' || null;
});

const ccValidation = event => {
  if (quoteForm.cubic_capacity && quoteForm.cubic_capacity.length >= 5) {
    event.preventDefault();
  }
};

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const validateDecimal = event => {
  if (
    event.key === '.' ||
    event.key === 'Backspace' ||
    event.key === 'Delete'
  ) {
    return;
  }
  const regex = /^\d+(\.\d{0,2})?$/;
  if (!regex.test(event.key)) {
    event.preventDefault();
  }
};

const getModelDetails = onchange => {
  axios
    .get(`/getBikeModelDetails?bike_model_id=${quoteForm.model_id}`)
    .then(({ data }) => {
      const item = data.length > 0 ? data[0] : null;
      if (isError.value) {
        isError.value = false;
        return;
      }
      if (item) {
        notification.success({
          title: 'Vehicle Assumptions Data Found',
          position: 'top',
        });
        quoteForm.seat_capacity = item.seat_capacity;
        quoteForm.cubic_capacity = item.cubic_capacity;
      } else {
        notification.error({
          title: 'No Vehicle Assumptions Data Found',
          position: 'top',
        });
      }
    });
};

const gender = computed(() => {
  return [
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
  ];
});

const chassisNumberDisabled = computed(() => {
  let disallowedStatus = [
    props.quoteStatusEnums.PolicySentToCustomer,
    props.quoteStatusEnums.PolicyBooked,
  ];
  return disallowedStatus.includes(props?.quote?.quote_status_id);
});

const chassisNumberValidate = eventType => {
  const regex = /^[a-zA-Z0-9]*$/; // Allow only alphanumeric characters

  if (eventType === 'keypress') {
    const event = window.event || event;
    const key = event.key;
    if (
      !regex.test(key) &&
      key !== 'Backspace' &&
      key !== 'Delete' &&
      key !== 'ArrowLeft' &&
      key !== 'ArrowRight'
    ) {
      event.preventDefault();
    }
  }

  if (eventType === 'blur') {
    const lengthValid =
      quoteForm.chassis_number.length >= 8 &&
      quoteForm.chassis_number.length <= 17;
    const isAlphanumeric = regex.test(quoteForm.chassis_number);
    if (quoteForm.chassis_number && (!lengthValid || !isAlphanumeric)) {
      quoteForm.errors.chassis_number =
        'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
    } else {
      quoteForm.clearErrors('chassis_number');
    }
  }
};

const chassisNumberRule = v => {
  const regex = /^[a-zA-Z0-9]*$/;
  const lengthValid = v.length >= 8 && v.length <= 17;
  const isAlphanumeric = regex.test(v);
  if (v && (!lengthValid || !isAlphanumeric)) {
    return 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
  }
  return true;
};
</script>

<template>
  <div>
    <Head title="Bike Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Bike Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('bike-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Bike Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">


      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Lead Source Fields - Only show when type is referral -->
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
          v-if="isReferralType && quoteForm.sub_source_id && !isEcomLeadExtension"
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
          :error="quoteForm.errors.primary_ref_id"
          :disabled="!canEditSubSourceFields"
        />

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          :required="canEditSubSourceFields"
          v-model="quoteForm.partner_name"
          class="w-full"
          maxlength="50"
          type="text"
          placeholder="Enter Partner Name"
          :tooltip="'Name of campaign, event or club'"
          :rules="canEditSubSourceFields ? [isRequired, maxCharacters(50)] : []"
          :error="quoteForm.errors.partner_name"
          :disabled="!canEditSubSourceFields"
        />


        <x-input
          label="FIRST NAME"
          required
          v-model="quoteForm.first_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
          maxLength="20"
        />

        <x-input
          label="LAST NAME"
          required
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="50"
        />

        <x-input
          label="EMAIL"
          required
          v-model="quoteForm.email"
          type="email"
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
        />

        <x-input
          label="PHONE NUMBER"
          required
          v-model="quoteForm.mobile_no"
          type="tel"
          :disabled="editMode"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
        />

        <DatePicker
          label="DATE OF BIRTH"
          required
          v-model="quoteForm.dob"
          name="created_at_start"
          :rules="[isRequired]"
          :hasError="quoteForm.errors.dob || formFieldReq.dob"
        />
        <x-select
          v-model="quoteForm.nationality_id"
          :rules="[isRequired]"
          :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.nationality_id"
          label="NATIONALITY"
          filterable
          placeholder="Search by Nationality"
          required
        ></x-select>

        <x-select
          v-model="quoteForm.gender"
          :options="gender"
          placeholder="Gender"
          label="GENDER"
        />
        <x-select
          v-model="quoteForm.uae_license_held_for_id"
          :rules="[isRequired]"
          :options="
            uaeLicenses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.uae_license_held_for_id"
          label="UAE LICENCE HELD FOR"
          required
        />

        <x-select
          v-model="quoteForm.back_home_license_held_for_id"
          :options="
            dropdownSource.back_home_license_held_for_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          label="HOME COUNTRY DRIVING LICENSE HELD FOR"
        />

        <x-select
          v-model="quoteForm.make_id"
          :rules="[isRequired]"
          :options="bikeMakeOptions"
          class="w-full"
          :error="quoteForm.errors.make_id"
          @update:modelValue="getBikeModel()"
          filterable
          placeholder="Search by Bike Make"
          required
          label="BIKE MAKE"
        />

        <x-select
          v-model="quoteForm.model_id"
          :rules="[isRequired]"
          :options="bikeModelOptions"
          class="w-full"
          @update:modelValue="getModelDetails(true)"
          :disabled="isBikeModelDisabled || !bikeModelOptions.length"
          :error="quoteForm.errors.model_id"
          label="BIKE MODEL"
          required
          placeholder="Search by Bike Model"
        />

        <x-select
          label="BIKE MODEL YEAR"
          required
          v-model="quoteForm.year_of_manufacture"
          :rules="[isRequired]"
          :options="
            yearOfManufacture.map(item => ({
              value: item.text,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture"
        />

        <x-input
          v-model="quoteForm.cubic_capacity"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.cubic_capacity"
          @keypress="ccValidation"
          label="CC"
          required
        />

        <x-input
          label="BIKE VALUE"
          required
          v-if="isEdit"
          v-model="quoteForm.bike_value"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.bike_value"
          @keydown="validateDecimal"
        />

        <x-input
          label="BIKE VALUE(AT ENQUIRY)"
          required
          v-model="quoteForm.bike_value_tier"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.bike_value_tier"
          @keydown="validateDecimal"
        />

        <x-input
          label="SEAT CAPACITY"
          required
          v-model="quoteForm.seat_capacity"
          class="w-full"
          type="number"
          :rules="[isRequired]"
          :error="quoteForm.errors.seat_capacity"
        />

        <div>
          <template v-if="chassisNumberDisabled">
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
              >
                CHASSIS NUMBER
              </label>
              <template #tooltip>
                This lead is now locked as the policy has been booked. If
                changes are needed, go to 'Send Update', select 'Add Update',
                and choose 'Cancellation from Inception and Reissuance'
              </template>
            </x-tooltip>
            <x-input
              :disabled="chassisNumberDisabled"
              v-model="quoteForm.chassis_number"
              class="w-full"
              type="text"
              placeholder="Enter Chassis Number"
            />
          </template>
          <template v-else>
            <x-input
              label="CHASSIS NUMBER"
              v-model="quoteForm.chassis_number"
              class="w-full"
              type="text"
              placeholder="Enter Chassis Number"
              :rules="quoteForm.chassis_number ? [chassisNumberRule] : []"
              @keypress="chassisNumberValidate('keypress')"
              @blur="chassisNumberValidate('blur')"
              :error="quoteForm.errors.chassis_number"
            />
          </template>
        </div>

        <x-select
          label="EMIRATES OF REGISTRATION"
          required
          v-model="quoteForm.emirate_of_registration_id"
          :rules="[isRequired]"
          :options="
            dropdownSource.emirate_of_registration_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.emirate_of_registration_id"
        />

        <x-select
          label="TYPE OF BIKE INSURANCE"
          required
          v-model="quoteForm.insurance_type_id"
          :rules="[isRequired]"
          :options="
            dropdownSource.car_type_insurance_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.insurance_type_id"
        />

        <x-select
          v-model="quoteForm.currently_insured_with"
          :rules="[isRequired]"
          :options="currentlyInsuredWithOptions"
          class="w-full"
          :error="quoteForm.errors.currently_insured_with"
          label="CURRENTLY INSURED WITH"
          filterable
          placeholder="Search by Currently Insured With"
          required
          error="quoteForm.errors.currently_insured_with"
        />

        <x-select
          label="CLAIM HISTORY"
          required
          v-model="quoteForm.claim_history_id"
          :rules="[isRequired]"
          :options="bikeClaimHistoryOptions"
          class="w-full"
          :error="quoteForm.errors.claim_history_id"
        />

        <x-select
          label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
          v-model="quoteForm.has_ncd_supporting_documents_dropdown"
          :options="[
            { value: 'y', label: 'Yes' },
            { value: 'n', label: 'No' },
          ]"
          class="w-full"
        />

        <x-textarea
          label="ADDITIONAL NOTES"
          v-model="quoteForm.notes"
          type="textarea"
          rows="5"
          class="w-full"
          :adjust-to-text="false"
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
