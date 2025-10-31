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
  sub_source_id:
    parseInt(props.quote?.sub_source_id, 10) ||
    parseInt(props.leadSourceParams?.subSource, 10) ||
    null,
  sub_source_options_id:
    parseInt(props.quote?.sub_source_options_id, 10) ||
    parseInt(props.leadSourceParams?.subSourceOption, 10) ||
    null,
  notes: (() => {
    return props.quote?.notes || '';
  })(),
});

const { isRequired, isEmail, isMobileNo, maxCharacters } = useRules();
const editMode = computed(() => {
  return props.quote && props.quote.uuid ? true : false;
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
  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return (
    selectedSubSource?.childs?.map(option => ({
      value: option.id,
      label: option.text,
      code: option.code, // Include the code property for showPartnerNameField
      suffix: option.description || null,
      disabled:
        !isPCPTeam.value && pcpOnlyOptions.includes(String(option.code)),
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' || quoteForm.source === 'IMCRM'
  );
});

const canEditSubSourceFields = computed(() => {
  return useHasAnyRole([
    rolesEnum.YachtManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const rolesEnum = page.props.rolesEnum;
const teamNamesEnum = page.props.teamNamesEnum;
const isPCPTeam = useHasAnyTeam([{ name: teamNamesEnum.PCP }]);

// Watchers for field resets and partner name handling
watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
    }
  },
);

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
          v-if="isReferralType"
          label="IMCRM SUB-SOURCE"
          v-model="quoteForm.sub_source_id"
          :options="subSourceOptions"
          class="w-full"
          placeholder="Select IMCRM SUB-SOURCE"
          filterable
          filterPlaceholder="Filter IMCRM SUB-SOURCE...."
          :disabled="!canEditSubSourceFields"
          :rules="[isRequired]"
          required
          :error="quoteForm.errors.sub_source_id"
          tooltip="Manually created lead in IMCRM"
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
          v-if="isReferralType && quoteForm.sub_source_id"
          label="SUB SOURCE OPTIONS"
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
          tooltip="Type of referral lead"
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
