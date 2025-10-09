<script setup>
import { XInput } from '@indielayer/ui';

const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  insuranceProviders: Object,
  yearOfManufacture: Array,
  dropdownSource: Object,
  model: String,
  quote: { type: Object, default: null },
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (
    props.subSources?.map(source => ({
      value: source.id,
      label: source.text,
      suffix:
        source.description ||
        source.tooltip ||
        `Information about ${source.text}`, // Use suffix for tooltip data
    })) || []
  );
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  return (
    selectedSubSource?.childs?.map(child => ({
      value: child.id,
      label: child.text,
      suffix:
        child.description || child.tooltip || `Information about ${child.text}`, // Use suffix for tooltip data
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' ||
    props.quote?.source === 'IMCRM'
  );
});

const isEcomLeadExtension = computed(() => {
  if (props.leadSourceParams?.type === 'ecom_lead_extension') return true;
  if (
    !quoteForm.sub_source_id &&
    !quoteForm.sub_source_options_id &&
    quoteForm.primary_ref_id
  ) {
    return true;
  }
  return false;
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([
    rolesEnum.CycleManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

// Show partner name field when "other-clubs-or-campaigns" is selected
const showPartnerNameField = computed(() => {
  if (!quoteForm.sub_source_options_id) return false;
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  if (!selectedSubSource?.childs) return false;
  const selectedSubSourceOption = selectedSubSource.childs.find(
    child => child.id == quoteForm.sub_source_options_id,
  );
  return selectedSubSourceOption?.code === 'other-clubs-or-campaigns';
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  source: props.quote?.source || '',
  cycle_make: props.quote?.cycle_quote?.cycle_make || '',
  cycle_model: props.quote?.cycle_quote?.cycle_model || '',
  accessories: props.quote?.cycle_quote?.accessories || '',
  asset_value: props.quote?.asset_value || null,
  year_of_manufacture_id:
    props.quote?.cycle_quote?.year_of_manufacture_id || null,
  has_accident: String(props.quote?.cycle_quote?.has_accident) || null,
  has_good_condition:
    String(props.quote?.cycle_quote?.has_good_condition) || null,

  dob: props.quote?.unformatted_dob || null,
  nationality_id: props.quote?.nationality_id || null,
  gender: props.quote?.customer?.gender || null,
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

const YearOfManufacture = computed(() => {
  if (props.yearOfManufacture.length > 0)
    return props.yearOfManufacture
      .sort((a, b) => a.sort_order - b.sort_order)
      .map(item => ({
        value: item.id,
        label: item.text,
      }));
  else return [];
});

function onSubmit(isValid) {
  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('cycle-quotes-update', props.quote.uuid)
      : route('cycle-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {},
    });
  }
}

const gender = computed(() => {
  return [
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
  ];
});
</script>

<template>
  <div>
    <Head title="Cycle Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Cycle Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('cycle-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Cycle Quotes List </x-button>
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
          label="First Name"
          required
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
          maxLength="50"
          label="Last Name"
          required
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          :disabled="editMode"
          :rules="[isRequired, isEmail]"
          class="w-full"
          :error="quoteForm.errors.email"
          label="Email"
          required
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :disabled="editMode"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
          label="Mobile Number"
          required
        />
        <DatePicker
          v-model="quoteForm.dob"
          :utc="false"
          model-type="yyyy-MM-dd"
          name="created_at_start"
          label="Date of Birth"
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
          label="Gender"
        />
        <x-input
          v-model="quoteForm.cycle_make"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.cycle_make"
          label="Cycle Make"
          required
        />
        <x-input
          v-model="quoteForm.cycle_model"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.cycle_model"
          label="Cycle Model"
          required
        />
        <x-select
          v-model="quoteForm.year_of_manufacture_id"
          :rules="[isRequired]"
          :options="YearOfManufacture"
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture_id"
          label="Year of manufacture"
          required
        />
        <x-input
          v-model="quoteForm.asset_value"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.asset_value"
          label="Purchased value(AED)"
          required
        />
        <x-input
          v-model="quoteForm.accessories"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.accessories"
          label="Accessories"
          required
        />

        <div class="px-2 w-full">
          <div class="mb-2">
            <div class="flex gap-12 mt-2">
              <x-form-group
                v-model="quoteForm.has_accident"
                :rules="[isRequired]"
                label="Have you had any accidents or injuries whilst cycling in the past
                  3 years in the UAE"
                required
              >
                <x-radio value="1" label="Yes" />
                <x-radio value="0" label="No" />
              </x-form-group>
            </div>
          </div>
        </div>

        <div class="px-2 w-full">
          <div class="mb-2">
            <div class="flex gap-12 mt-2">
              <x-form-group
                v-model="quoteForm.has_good_condition"
                :rules="[isRequired]"
                label="Confirm that your bicycle is currently in good condition and there
                  is no existing damage"
                required
              >
                <x-radio value="1" label="Yes" />
                <x-radio value="0" label="No" />
              </x-form-group>
            </div>
          </div>
        </div>
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
          {{ editMode ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
