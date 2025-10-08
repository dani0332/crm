<script setup>
const notification = useNotifications('toast');
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;

const props = defineProps({
  quote: { type: Object, default: null },
  pet_ages: Object,
  pet_types: Object,
  accomodation_types: Object,
  possession_types: Object,
  flash: Object,
  nationalities: Object,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  source: props.quote?.source || '',
  pet_type_id: props.quote?.pet_quote?.pet_type_id || '',
  breed_of_pet1: props.quote?.pet_quote?.breed_of_pet1 || '',
  pet_age_id: props.quote?.pet_quote?.pet_age_id || '',
  is_neutered: props.quote ? props.quote?.pet_quote?.is_neutered : null,
  is_microchipped: props.quote ? props.quote?.pet_quote?.is_microchipped : null,
  microchip_no: props.quote?.pet_quote?.microchip_no || '',
  is_mixed_breed: props.quote ? props.quote?.pet_quote?.is_mixed_breed : null,
  has_injury: props.quote ? props.quote?.pet_quote?.has_injury : null,
  gender: props.quote?.pet_quote?.gender || '',
  dob: props.quote?.unformatted_dob || null,
  nationality_id: props.quote?.nationality_id || null,
  customer_gender: props.quote?.customer?.gender || '',
  // Sub-source fields from CreateLeadModal
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
    let notes = props.quote?.notes || '';
    const partnerName = props.leadSourceParams?.partnerName;
    if (partnerName) {
      notes = notes ? `${notes}, ${partnerName}` : partnerName;
    }
    return notes;
  })(),
});

const { isRequired, isEmail, isMobileNo, maxCharacters } = useRules();
const editMode = computed(() => {
  return props.quote ? true : false;
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix:
      item.description || item.tooltip || `Information about ${item.text}`, // Use suffix for tooltip data
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
      code: option.code,
      suffix:
        option.description ||
        option.tooltip ||
        `Information about ${option.text}`, // Use suffix for tooltip data
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
    rolesEnum.PetManager,
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

// Watchers for sub-source fields
watch(
  () => quoteForm.sub_source_id,
  newValue => {
    if (newValue) {
      quoteForm.sub_source_options_id = null;
      quoteForm.partner_name = '';
    }
  },
);

watch(
  () => quoteForm.sub_source_options_id,
  newValue => {
    if (newValue) {
      quoteForm.partner_name = '';
      quoteForm.primary_ref_id = '';
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

function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('pet-quotes-update', props.quote.uuid)
      : route('pet-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    });
  }
}
</script>

<template>
  <div>
    <Head :title="editMode ? 'Update Pet Quote' : 'Pet Quote Create'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        <span>{{ editMode ? 'Update Pet' : 'Create Pet' }} </span>
      </h2>
      <div>
        <Link :href="route('pet-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Pet Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">{{
        quoteForm?.errors?.error
      }}</x-alert>

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
          v-model="quoteForm.first_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.first_name"
          maxLength="20"
          label="FIRST NAME"
          required
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="50"
          label="LAST NAME"
          required
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          :disabled="editMode"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.email"
          label="EMAIL"
          required
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :disabled="editMode"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
          label="MOBILE NUMBER"
          required
        />
        <DatePicker
          v-model="quoteForm.dob"
          :utc="false"
          model-type="yyyy-MM-dd"
          name="created_at_start"
          label="DATE OF BIRTH"
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
        />
        <x-select
          v-model="quoteForm.customer_gender"
          :options="[
            { value: 'Male', label: 'Male' },
            { value: 'Female', label: 'Female' },
          ]"
          class="w-full"
          label="GENDER"
          :rules="[isRequired]"
          required
          :error="quoteForm.errors.customer_gender"
        />
        <x-select
          v-model="quoteForm.pet_type_id"
          type="text"
          maxlength="3"
          :rules="[isRequired]"
          :options="
            pet_types.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.pet_type_id"
          label="TYPE OF PET"
          required
        />
        <x-input
          v-model="quoteForm.breed_of_pet1"
          type="tel"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.breed_of_pet1"
          label="BREED OF PET"
          required
        />
        <x-select
          v-model="quoteForm.pet_age_id"
          type="number"
          :rules="[isRequired]"
          :options="
            pet_ages.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.pet_age_id"
          label="AGE OF PET"
          required
        />
        <x-select
          v-model="quoteForm.is_neutered"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_neutered"
          label="IS NEUTERED"
        />
        <x-select
          v-model="quoteForm.is_microchipped"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_microchipped"
          label="IS MICROCHIPPED"
        />
        <x-input
          v-model="quoteForm.microchip_no"
          type="text"
          class="w-full"
          :error="quoteForm.errors.microchip_no"
          label="MICROCHIP NO"
        />
        <x-select
          v-model="quoteForm.is_mixed_breed"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.is_mixed_breed"
          label="IS MIXED BREED"
        />
        <x-select
          v-model="quoteForm.has_injury"
          :options="[
            { value: 1, label: 'Yes' },
            { value: 0, label: 'No' },
          ]"
          class="w-full"
          :error="quoteForm.errors.has_injury"
          label="HAS INJURY"
        />
        <x-select
          v-model="quoteForm.gender"
          :rules="[isRequired]"
          :options="[
            { value: 'Male', label: 'Male' },
            { value: 'Female', label: 'Female' },
          ]"
          class="w-full"
          :error="quoteForm.errors.gender"
          label="PET'S GENDER"
          required
        />
      </div>

      <!-- Additional Notes field -->
      <div class="grid sm:grid-cols-1 gap-4">
        <x-textarea
          label="ADDITIONAL NOTES"
          v-model="quoteForm.notes"
          :error="quoteForm.errors.notes"
          class="w-full"
          placeholder="Enter any additional notes..."
          rows="3"
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
