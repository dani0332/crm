<script setup>
const page = usePage();

const props = defineProps({
  genderOptions: Object,
  nationalities: Object,
  uaeLicenses: Object,
  insuranceProviders: Object,
  yearOfManufacture: Object,
  jetski_uses: Object,
  jetski_materials: Object,
  dropdownSource: Object,
  model: String,
  quote: { type: Object, default: null },
  subSources: Array,
  leadSourceParams: Object,
});

const quoteForm = useForm({
  model: props.model,
  first_name: props.quote?.first_name || '',
  last_name: props.quote?.last_name || '',
  email: props.quote?.email || '',
  mobile_no: props.quote?.mobile_no || '',
  source: props.quote?.source || '',
  jetski_make: props.quote?.jetski_quote?.jetski_make,
  jetski_model: props.quote?.jetski_quote?.jetski_model,
  year_of_manufacture_id: props.quote?.jetski_quote?.year_of_manufacture_id,
  max_speed: props.quote?.jetski_quote?.max_speed,
  seat_capacity: props.quote?.jetski_quote?.seat_capacity,
  engine_power: props.quote?.jetski_quote?.engine_power,
  jetski_material_id: props.quote?.jetski_quote?.jetski_material_id,
  jetski_use_id: props.quote?.jetski_quote?.jetski_use_id,
  claim_history: props.quote?.jetski_quote?.claim_history,
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

const isEmptyField = ref(false);
const editMode = computed(() => (props.quote ? true : false));

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
      suffix: option.description || null,
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
    rolesEnum.JetskiManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const rolesEnum = page.props.rolesEnum;

// Watchers for field resets and partner name handling
watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
    }
  },
);

function onSubmit(isValid) {
  if (quoteForm.nationality_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('jetski-quotes-update', props.quote.uuid)
      : route('jetski-quotes-store');

    quoteForm.clearErrors();
    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Jetski Quote" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Jetski Quote <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link :href="route('jetski-quotes-list')">
          <x-button size="sm" color="#ff5e00"> Jetski Quotes List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
        {{ quoteForm?.errors?.error }}
      </x-alert>

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
            isReferralType && quoteForm.sub_source_id
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
          :rules="[isRequired, isEmail]"
          class="w-full"
          :disabled="editMode"
          :error="quoteForm.errors.email"
          label="Email"
          required
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm.errors.mobile_no"
          :disabled="editMode"
          label="Phone Number"
          required
        />

        <x-input
          v-model="quoteForm.jetski_make"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.jetski_make"
          label="JetSki Make"
          required
        />

        <x-input
          v-model="quoteForm.jetski_model"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.jetski_model"
          label="JetSki Model"
          required
        />

        <x-input
          v-model="quoteForm.max_speed"
          type="number"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.max_speed"
          label="Max Speed"
          required
        />

        <x-input
          v-model="quoteForm.seat_capacity"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.seat_capacity"
          label="Seating Capacity"
          required
        />

        <x-input
          v-model="quoteForm.engine_power"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.engine_power"
          label="Engine Power (hp)"
          required
        />

        <x-select
          v-model="quoteForm.year_of_manufacture_id"
          :rules="[isRequired]"
          :options="
            yearOfManufacture.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.year_of_manufacture_id"
          label="Year of manufacture"
          required
        />

        <x-select
          v-model="quoteForm.jetski_material_id"
          :rules="[isRequired]"
          :options="
            jetski_materials.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.jetski_material_id"
          label="Material of Construction"
          required
        />

        <x-select
          v-model="quoteForm.jetski_use_id"
          :rules="[isRequired]"
          :options="
            jetski_uses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
          :error="quoteForm.errors.jetski_use_id"
          label="Jet SKI Use"
          required
        />

        <x-input
          v-model="quoteForm.claim_history"
          type="text"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.claim_history"
          label="Claims Experience for past 5 years"
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
          {{ editMode ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
