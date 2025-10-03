<script setup>
const notification = useNotifications('toast');
const page = usePage();

const props = defineProps({
  quote: { type: Object, default: null },
  nationalities: Object,
  subSources: Array,
  leadSourceParams: Object,
});

const quoteForm = useForm({
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  source: props.quote?.source || '',
  company_name: props.quote?.company_name || null,
  company_address: props.quote?.company_address || null,
  boat_details: props.quote?.yacht_quote?.boat_details || '',
  engine_details: props.quote?.yacht_quote?.engine_details || '',
  claim_experience: props.quote?.yacht_quote?.claim_experience || '',
  asset_value: props.quote?.asset_value || null,
  use: props.quote?.yacht_quote?.use || '',
  operator_experience: props.quote?.yacht_quote?.operator_experience || '',

  dob: props.quote?.unformatted_dob || null,
  nationality_id: props.quote?.nationality_id || null,
  gender: props.quote?.customer?.gender || null,
  // Sub-source fields
  sub_source_id: parseInt(props.quote?.sub_source_id || props.leadSourceParams?.subSource || 0) || null,
  sub_source_options_id: parseInt(props.quote?.sub_source_options_id || props.leadSourceParams?.subSourceOption || 0) || null,
  primary_ref_id: props.quote?.primary_ref_id || props.leadSourceParams?.primaryRefId || '',
  partner_name: props.leadSourceParams?.partnerName || '',
  notes: (() => {
    let notes = props.quote?.notes || '';
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

const { isRequired, isEmail, isMobileNo } = useRules();
const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (props.subSources || []).map(item => ({
    value: item.id,
    label: item.text,
    suffix: item.description || item.tooltip || `Information about ${item.text}`, // Use suffix for tooltip data
  }));
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(source => source.id == quoteForm.sub_source_id);
  return selectedSubSource?.childs?.map(option => ({
    value: option.id,
    label: option.text,
    code: option.code, // Include the code property for showPartnerNameField
    suffix: option.description || option.tooltip || `Information about ${option.text}`, // Use suffix for tooltip data
  })) || [];
});

const isReferralType = computed(() => {
  return props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM';
});

const isEcomLeadExtension = computed(() => {
  return props.leadSourceParams?.type === 'ecom_lead_extension' ||
    (quoteForm.sub_source_id === null && quoteForm.sub_source_options_id === null && quoteForm.primary_ref_id);
});

const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([rolesEnum.YachtManager, rolesEnum.Admin, rolesEnum.LeadPool]);
});

const showPartnerNameField = computed(() => {
  const selectedOption = subSourceOptionOptions.value.find(
    option => option.value === quoteForm.sub_source_options_id
  );
  console.log("selectedOption",selectedOption);
  console.log("quoteForm.sub_source_options_id",quoteForm.sub_source_options_id);
  return selectedOption?.code === 'other-clubs-or-campaigns';
});

const rolesEnum = page.props.rolesEnum;

// Watchers for field resets and partner name handling
watch(() => quoteForm.sub_source_id, (newValue) => {
  if (newValue !== quoteForm.sub_source_id) {
    quoteForm.sub_source_options_id = null;
  }
});

watch(() => quoteForm.sub_source_options_id, (newValue) => {
  if (!showPartnerNameField.value) {
    quoteForm.partner_name = '';
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

// removed additional_notes sync; only notes is used
function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    const url = editMode.value
      ? route('yacht-quotes-update', props.quote.uuid)
      : route('yacht-quotes-store');

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
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
    <Head title="Yacht Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Yacht Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('yacht-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Yacht Quotes List </x-button>
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
          label="SUB SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select Sub Source"
          filterable
          filterPlaceholder="Filter Sub Source...."
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

        <div v-if="isEcomLeadExtension">
          <x-tooltip placement="right">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Primary Ref Id
            </label>
            <template #tooltip> ID of the original ECOM lead </template>
          </x-tooltip>
          <x-input
            v-model="quoteForm.primary_ref_id"
            class="w-full"
            type="text"
            placeholder="Enter Primary Ref ID"
            :rules="[isRequired]"
            :error="quoteForm.errors.primary_ref_id"
            :disabled="!canEditSubSourceFields"
            required
          />
        </div>

        <x-input
          v-if="showPartnerNameField"
          label="PARTNER NAME"
          required
          v-model="quoteForm.partner_name"
          class="w-full"
          type="text"
          placeholder="Enter Partner Name"
          :rules="[isRequired]"
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
          :rules="[isRequired, isMobileNo]"
          :disabled="editMode"
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
          :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          placeholder="Nationality"
          filterable
          label="NATIONALITY"
        />
        <x-select
          v-model="quoteForm.gender"
          :options="gender"
          placeholder="Gender"
          label="GENDER"
        />
        <x-input
          v-model="quoteForm.company_name"
          type="text"
          class="w-full"
          :error="quoteForm?.errors?.company_name"
          label="COMPANY NAME"
        />
        <x-input
          v-model="quoteForm.company_address"
          type="text"
          class="w-full"
          :error="quoteForm?.errors?.company_address"
          label="COMPANY ADDRESS"
        />
        <x-input
          v-model="quoteForm.boat_details"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.boat_details"
          label="BOAT DETAILS"
          required
        />
        <x-input
          v-model="quoteForm.engine_details"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.engine_details"
          label="ENGINE DETAILS"
          required
        />
        <x-input
          v-model="quoteForm.claim_experience"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.claim_experience"
          label="CLAIM EXPERIENCE"
          required
        />
        <x-input
          v-model="quoteForm.asset_value"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.asset_value"
          label="SUM INSURED"
          required
        />
        <x-input
          v-model="quoteForm.use"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.use"
          label="USE"
          required
        />
        <x-input
          v-model="quoteForm.operator_experience"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.operator_experience"
          label="OPERATOR EXPERIENCE"
          required
        />
      </div>

      <x-textarea
        v-model="quoteForm.notes"
        class="w-full"
        :error="quoteForm.errors.notes"
        label="ADDITIONAL NOTES"
        placeholder="Enter additional notes"
      />

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
