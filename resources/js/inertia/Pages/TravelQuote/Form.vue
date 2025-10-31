<script setup>
const props = defineProps({
  dropdownSource: Object,
  genderOptions: Object,
  fields: Object,
  quote: Object,
  travelers: Array,
  quotePlans: Array,
  errors: Array,
  travelDestinations: Object,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});
const page = usePage();
const travelQuoteEnum = page.props.travelQuoteEnum;
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;
const hasZeroValueForUAEResident = ref(false);
const editMode = computed(() =>
  props.quote && props.quote.uuid ? true : false,
);

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

const formFields = computed(() => {
  return Object.keys(props.fields).map(key => ({
    value: key,
    label: props.fields[key].label,
  }));
});

// Sub-source computed properties
const subSourceOptions = computed(() => {
  return (
    props.subSources?.map(source => ({
      value: source.id,
      label: source.text,
      suffix: source.description || null,
    })) || []
  );
});

const subSourceOptionOptions = computed(() => {
  if (!quoteForm.sub_source_id) return [];
  const selectedSubSource = props.subSources?.find(
    source => source.id == quoteForm.sub_source_id,
  );
  const pcpOnlyOptions = ['pcp-cross-sell', 'pcp-customer-referral'];
  return (
    selectedSubSource?.childs?.map(child => ({
      value: child.id,
      label: child.text,
      suffix: child.description || null,
      disabled: !isPCPTeam.value && pcpOnlyOptions.includes(String(child.code)),
    })) || []
  );
});

const isReferralType = computed(() => {
  return (
    props.leadSourceParams?.type === 'referral' ||
    props.quote?.source === 'IMCRM'
  );
});

// Role-based permissions for sub-source fields
const canEditSubSourceFields = computed(() => {
  return hasAnyRole([
    rolesEnum.TravelManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const teamNamesEnum = page.props.teamNamesEnum;
const isPCPTeam = useHasAnyTeam([{ name: teamNamesEnum.PCP }]);


const quoteForm = useForm({
  first_name: props.quote?.first_name || null,
  last_name: props.quote?.last_name || null,
  email: props.quote?.email || null, // not used in update mode
  direction_code: props.quote?.direction_code
    ? props.quote?.direction_code
    : editMode.value &&
        props.quote?.region_cover_for_id == travelQuoteEnum.REGION_COVER_ID_UAE
      ? travelQuoteEnum.TRAVEL_UAE_INBOUND
      : editMode.value &&
          props.quote?.currently_located_in_id_text ==
            travelQuoteEnum.LOCATION_UAE_TEXT &&
          props.quote?.region_cover_for_id !=
            travelQuoteEnum.REGION_COVER_ID_UAE
        ? travelQuoteEnum.TRAVEL_UAE_OUTBOUND
        : null,
  has_arrived_uae:
    props.quote?.has_arrived_uae?.toString() ||
    (editMode.value && props.quotePlans && props.quote?.has_arrived_uae
      ? '1'
      : editMode.value && props.quotePlans == null
        ? '0'
        : null), // not used in update mode
  has_arrived_destination:
    props.quote?.has_arrived_destination?.toString() ||
    (editMode.value && props.quotePlans && props.quote.has_arrived_destination
      ? '1'
      : editMode.value && props.quotePlans == null
        ? '0'
        : null), // not used in update mode
  coverage_code: props.quote?.coverage_code || null,
  uuid: editMode.value ? props.quote?.uuid : null,
  mobile_no: props.quote?.mobile_no || null, // not used in update mode
  nationality_id: props.quote?.nationality_id || null,
  destination_ids: props.quote?.destination_ids ?? [],
  start_date: props.quote?.start_date || null,
  end_date: props.quote?.end_date || null,
  region_cover_for_id: props.quote?.region_cover_for_id?.toString() || null,
  premium: props.quote?.premium || null,
  policy_number: props.quote?.policy_number || null, // not used in update mode
  days_cover_for: props.quote?.days_cover_for || null,
  members: [{ value: 'male', label: 'Male', primary: true }], // not used in update mode
  edit_mode: editMode.value,
  departure_country_id: null,
  addressObj: {
    address_type: page.props.customerAddressData?.type || null,
    villa_apartment_office_no:
      page.props.customerAddressData?.office_number || null,
    floor_no: page.props.customerAddressData?.floor_number || null,
    villa_building_name: page.props.customerAddressData?.building_name || null,
    street_name: page.props.customerAddressData?.street || null,
    area: page.props.customerAddressData?.area || null,
    city: page.props.customerAddressData?.city || null,
    landmark: page.props.customerAddressData?.landmark || null,
  },
  courierQuoteStatus: page.props.courierQuoteStatus || 'Pending',
  // Sub-source fields from CreateLeadModal
  sub_source_id:
    parseInt(props.quote?.sub_source_id, 10) ||
    parseInt(props.leadSourceParams?.subSource, 10) ||
    null,
  sub_source_options_id:
    parseInt(props.quote?.sub_source_options_id, 10) ||
    parseInt(props.leadSourceParams?.subSourceOption, 10) ||
    null,
  additional_notes: (() => {
    let notes = props.quote?.additional_notes || '';
    return notes;
  })(),
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  allowEmpty: v => true || 'This field is required',
  isPhone: v =>
    /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,10}$/im.test(v) ||
    'Phone must be valid',
};

const {
  isRequired,
  policy_number,
  premium,
  policy_expiry_date,
  policy_start_date,
  isEmail,
  isMobileNo,
  maxCharacters,
} = useRules();

const subTeamOptions = [
  { value: travelQuoteEnum.TRAVEL_UAE_INBOUND, label: 'To the UAE (Inbound)' },
  {
    value: travelQuoteEnum.TRAVEL_UAE_OUTBOUND,
    label: 'Outside UAE (Outbound)',
  },
];
const alreadylived = [
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No' },
];
const genderList = [
  { value: 'M', label: 'Male' },
  { value: 'F', label: 'Female' },
];
const inboundCoverageCode = [
  { value: travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP, label: 'Single Trip' },
  { value: travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP, label: 'Multi Trip' },
];
const outboundCoverageCode = [
  { value: travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP, label: 'Single Trip' },
  { value: travelQuoteEnum.COVERAGE_CODE_MULTI_TRIP, label: 'Multi Trip' },
];
const outboundRegions = [
  { value: '1', label: 'Worldwide (excl. US/Canada)' },
  { value: '2', label: 'Worldwide (incl. US/Canada)' },
  { value: '4', label: 'Schengen Countries' },
];

const isUAEResident = [
  { value: '1', label: 'Yes' },
  { value: '0', label: 'No' },
];

function addTravler() {
  if (quoteForm.members.length == 0) {
    quoteForm.members.push({ dob: '', gender: '', primary: true });
  } else {
    quoteForm.members.push({ dob: '', gender: '' });
  }
}
function removeMember(index) {
  quoteForm.members.splice(index, 1);
}

// Watch for sub_source_id changes to reset dependent fields
watch(
  () => quoteForm.sub_source_id,
  newValue => {
    quoteForm.sub_source_options_id = null;
  },
);

function onSubmit(isValid) {
  if (!isValid) return;

  if (
    quoteForm.direction_code != travelQuoteEnum.TRAVEL_UAE_INBOUND &&
    isArrivedUAE() &&
    quoteForm?.destination_ids?.length < 1
  ) {
    quoteForm.errors.destination_ids =
      'Please select at least one destination.';
    return;
  }

  if (
    quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_INBOUND &&
    quoteForm?.departure_country_id == null
  ) {
    quoteForm.errors.departure_country_id = 'Please select departing from.';
    return;
  }

  quoteForm.clearErrors();

  let method = 'post';
  let url = route('travel.store');

  if (editMode.value && quoteForm.uuid) {
    method = 'put';
    url = route('travel.update', quoteForm.uuid);
  }

  const options = {
    onError: errors => {
      quoteForm.setError(errors);
    },
  };

  quoteForm.transform(data => {
    // For edit mode, remove fields that shouldn't be updated
    if (editMode.value) {
      const { mobile_no, email, policy_number, members, ...updateData } = data;
      return updateData;
    }
    return data;
  });
  quoteForm.submit(method, url, options);
}

function addUpdatedTraveller() {
  if (props.travelers) {
    let listing = Object.keys(props.travelers).map((key, item) => {
      let listMember = {
        dob: props.travelers[key].dob,
        gender: props.travelers[key].gender,
        id: props.travelers[key].id,
      };
      if (props.quote.primary_member_id == props.travelers[key].id) {
        listMember.primary = true;
      }
      return listMember;
    });
    quoteForm.members = listing;
  }
}

onMounted(() => {
  addUpdatedTraveller();
  updateRegionCover();
  quoteForm.destination_ids = mappedDestinationIds.value ?? [];
  quoteForm.departure_country_id = props?.quote?.departure_country_id || null;
});

watch(
  () => quoteForm.direction_code,
  (newValue, oldValue) => {
    if (newValue == travelQuoteEnum.TRAVEL_UAE_INBOUND) {
      quoteForm.has_arrived_uae = '1';
      quoteForm.has_arrived_destination = null;
    } else {
      quoteForm.has_arrived_uae = null;
      quoteForm.has_arrived_destination = '0';
    }
  },
);

watch(
  () => quoteForm.has_arrived_uae,
  (newValue, oldValue) => {
    resetTravelInfo(newValue);
  },
);

watch(
  () => quoteForm.has_arrived_destination,
  (newValue, oldValue) => {
    resetTravelInfo(newValue);
  },
);

function resetTravelInfo(value) {
  if (value == 1) {
    quoteForm.start_date = null;
    quoteForm.end_date = null;
    quoteForm.coverage_code = null;
  }
}

const determineGridLayout = computed(() => {
  if (quoteForm.coverage_code == travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP)
    return 'grid sm:grid-cols-3 gap-4';
  if (
    quoteForm.has_arrived_destination == '0' &&
    quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_OUTBOUND
  )
    return 'grid sm:grid-cols-2 gap-4';
  return 'grid sm:grid-cols-2 gap-4';
});

const isArrivedUAE = () => {
  if (
    !(quoteForm.has_arrived_uae == 1 || quoteForm.has_arrived_destination == 1)
  )
    return true;
  return false;
};
const disablePastDates = date => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const inputDate = new Date(date);
  return inputDate < today;
};
watch(
  () => quoteForm.destination_ids,
  newDestinationIds => {
    if (newDestinationIds) {
      regionName(newDestinationIds); // Call the function to fetch advisors
    }
  },
  { deep: true },
);

function updateRegionCover(id) {
  quoteForm.region_cover_for_id = String(id) ?? '';
}

const regionName = ids => {
  let countries = page.props.fields.destination_id?.options;
  const matchedValues = ids.map(id => {
    const matchingOption = Array.from(countries).find(
      option => option.id === id,
    );
    return matchingOption ? matchingOption.text : null;
  });
  for (let i = 0; i < matchedValues.length; i++) {
    if (
      matchedValues[i] === 'United States of America' ||
      matchedValues[i] === 'Canada' ||
      matchedValues[i] === 'United States'
    ) {
      updateRegionCover(2);
      return 'Worldwide (incl. US/Canada)';
    } else if (schengenCountries.includes(matchedValues[i])) {
      let hasOtherCountry = matchedValues.some(value =>
        schengenCountries.includes(value),
      );
      if (
        hasOtherCountry === true &&
        matchedValues.some(value => !schengenCountries.includes(value))
      ) {
        updateRegionCover(1);
        return 'Worldwide (excl. US/Canada)';
      } else {
        updateRegionCover(4);
        return 'Schengen Countries';
      }
    } else {
      updateRegionCover(1);
    }
  }
};
const schengenCountries = [
  'Austria',
  'Belgium',
  'Czech Republic',
  'Denmark',
  'Estonia',
  'Finland',
  'France',
  'Germany',
  'Greece',
  'Hungary',
  'Iceland',
  'Italy',
  'Latvia',
  'Liechtenstein',
  'Lithuania',
  'Luxembourg',
  'Malta',
  'Netherlands',
  'Norway',
  'Poland',
  'Portugal',
  'Slovakia',
  'Slovenia',
  'Spain',
  'Sweden',
  'Switzerland',
];
const checkUAEResident = computed(() => {
  if (quoteForm.members.some(member => member.uae_resident === '0')) {
    hasZeroValueForUAEResident.value = true;
    return true;
  }
  hasZeroValueForUAEResident.value = false;
  return false;
});

const mappedDestinationIds = computed(() => {
  return props.travelDestinations?.map(
    destination => destination.destination_id,
  );
});

watch(mappedDestinationIds, newVal => {
  if (newVal.length < 1) return;
  quoteForm.destination_ids = newVal;
});

watch(
  [() => quoteForm.start_date, () => quoteForm.end_date],
  ([newStartDate, newEndDate]) => {
    if (newStartDate && newEndDate) {
      quoteForm.days_cover_for = calculateDaysDifference(
        newStartDate,
        newEndDate,
      );
    }
  },
);

const addressTypes = [
  { value: '', label: 'No Address' }, // option for leaving it blank
  { value: 'Home', label: 'Home' },
  { value: 'Office', label: 'Office' },
];

const isCourierStatusPending = computed(() => {
  return quoteForm.courierQuoteStatus !== 'Pending';
});

const villaApartmentOfficeLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Villa / Apartment Number';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Office Name';
  } else {
    label = 'Villa / Apartment / Office No.';
  }

  return label;
});

const villaBuildingLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Community / Building Name';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Building Name';
  } else {
    label = 'Villa / Building Name';
  }

  return label;
});

const floorLabel = computed(() => {
  let label;

  if (quoteForm.addressObj.address_type === 'Home') {
    label = 'Floor / Block';
  } else if (quoteForm.addressObj.address_type === 'Office') {
    label = 'Floor';
  } else {
    label = 'Floor No.';
  }

  return label;
});
</script>

<template>
  <div>
    <Head :title="editMode ? 'Update Travel' : 'Create Travel'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ editMode ? 'Update' : 'Create' }} Travel
      </h2>
      <div>
        <Link :href="route('travel.index')">
          <x-button size="sm" color="#ff5e00" tag="div"> Travel List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Sub-source fields (conditional display based on referral type) -->
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

        <x-select
          label="Where will your journey take you?"
          required
          v-model="quoteForm.direction_code"
          :options="subTeamOptions"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.direction_code"
        />

        <x-select
          v-if="quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_INBOUND"
          :label="'Has your trip started?'"
          required
          v-model="quoteForm.has_arrived_uae"
          :options="alreadylived"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.has_arrived_uae"
        />

        <x-select
          v-else
          :label="'Has your trip started?'"
          required
          v-model="quoteForm.has_arrived_destination"
          :options="alreadylived"
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.has_arrived_destination"
        />
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <x-select
          v-if="isArrivedUAE()"
          label="Travel Coverage"
          required
          v-model="quoteForm.coverage_code"
          :options="
            quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_INBOUND
              ? inboundCoverageCode
              : outboundCoverageCode
          "
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.coverage_code"
        />

        <x-select
          v-if="
            quoteForm.direction_code != travelQuoteEnum.TRAVEL_UAE_INBOUND &&
            isArrivedUAE()
          "
          v-model="quoteForm.destination_ids"
          :options="
            fields.destination_id.options.map(option => ({
              value: option.id,
              label: option.text,
            }))
          "
          class="w-full"
          :rules="[isRequired]"
          filterable
          label="Travel Destinations"
          required
          placeholder="Select travel destinations"
          multiple
          truncate
          :error="quoteForm.errors.destination_ids"
          virtual-list
          :virtualListItemHeight="34"
          :virtualListOverscan="5"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                quoteForm.destination_ids = fields.destination_id.options.map(
                  item => item.id,
                )
              "
              @clear="quoteForm.destination_ids = []"
            />
          </template>
        </x-select>

        <x-select
          label="Which regions do you need cover for?*"
          v-if="
            quoteForm.has_arrived_destination == '0' &&
            quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_OUTBOUND &&
            isArrivedUAE() &&
            quoteForm.destination_ids?.length > 0
          "
          required
          v-model="quoteForm.region_cover_for_id"
          :options="outboundRegions"
          :disabled="true"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.region_cover_for_id"
        />
        <DatePicker
          v-if="isArrivedUAE()"
          label="Travel Start Date"
          required
          v-model="quoteForm.start_date"
          name="created_at_start"
          :disabled-dates="disablePastDates"
        />
        <DatePicker
          v-if="
            quoteForm.coverage_code ==
              travelQuoteEnum.COVERAGE_CODE_SINGLE_TRIP && isArrivedUAE()
          "
          label="Travel End Date"
          required
          v-model="quoteForm.end_date"
          name="end_date"
          :disabled-dates="disablePastDates"
          :rules="[isRequired]"
        />

        <x-select
          v-if="quoteForm.direction_code == travelQuoteEnum.TRAVEL_UAE_INBOUND"
          label="Departing From"
          required
          v-model="quoteForm.departure_country_id"
          :options="
            fields.destination_id.options.map(option => ({
              value: option.id,
              label: option.text,
            }))
          "
          class="w-full"
          :rules="[isRequired]"
          :error="quoteForm.errors.departure_country_id"
          filterable
          placeholder="Select departing from"
        />

        <x-input
          v-model="quoteForm.first_name"
          :rules="[isRequired]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
          label="First Name"
          required
        />

        <x-input
          v-model="quoteForm.last_name"
          :rules="[isRequired]"
          class="w-full"
          maxLength="50"
          :error="quoteForm.errors.last_name"
          label="Last Name"
          required
        />

        <x-select
          v-model="quoteForm.nationality_id"
          :options="
            fields.nationality_id.options.map(option => ({
              value: option.id,
              label: option.text,
            }))
          "
          class="w-full"
          :rules="[isRequired]"
          filterable
          placeholder="Select nationality"
          label="Nationality"
          required
          :error="quoteForm.errors.nationality_id"
          virtual-list
          :virtualListItemHeight="34"
          :virtualListOverscan="5"
        />
        <x-input
          v-if="editMode"
          :value="quoteForm.days_cover_for"
          :disabled="true"
          class="w-full"
          label="Days Cover"
        />

        <x-input
          type="email"
          v-model="quoteForm.email"
          class="w-full"
          :disabled="editMode"
          :rules="[isEmail]"
          :error="quoteForm.errors.email"
          label="Email"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          class="w-full"
          :disabled="editMode"
          :rules="[isRequired, isMobileNo]"
          :error="quoteForm.errors.mobile_no"
          label="Mobile number"
          required
        />

        <x-select
          v-model="quoteForm.addressObj.address_type"
          placeholder="Select address type"
          :options="addressTypes"
          :disabled="isCourierStatusPending"
          filterable
          filterPlaceholder="Filter Address Type...."
          label="Address Type"
        />
        <x-field
          label="ADDRESS"
          required
          v-if="
            quoteForm.addressObj.address_type === 'Home' ||
            quoteForm.addressObj.address_type === 'Office'
          "
        >
          <div class="flex flex-wrap -mx-2">
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.villa_apartment_office_no"
                :placeholder="villaApartmentOfficeLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.floor_no"
                :placeholder="floorLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.villa_building_name"
                :placeholder="villaBuildingLabel"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.street_name"
                placeholder="Street (Optional)"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.area"
                placeholder="Area"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.city"
                placeholder="City"
                :rules="[isRequired]"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
            <div class="w-1/2 px-2">
              <x-input
                type="text"
                v-model="quoteForm.addressObj.landmark"
                placeholder="Landmark (Optional)"
                class="w-full"
                :disabled="isCourierStatusPending"
              />
            </div>
          </div>
        </x-field>
      </div>

      <!-- Additional Notes field -->
      <div class="grid sm:grid-cols-1 gap-4">
        <x-textarea
          label="ADDITIONAL NOTES"
          v-model="quoteForm.additional_notes"
          :error="quoteForm.errors.additional_notes"
          class="w-full"
          placeholder="Enter any additional notes..."
          rows="3"
        />
      </div>

      <template
        v-if="
          !editMode &&
          (quoteForm.has_arrived_uae == '0' ||
            quoteForm.has_arrived_destination == '0')
        "
      >
        <div class="grid mb-2" v-if="hasZeroValueForUAEResident">
          <div
            class="alert flex items-center bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
            role="alert"
          >
            <span class="text-sm text-red-500 dark:text-red-400 mt-1">
              This coverage is valid for UAE residents only. Please remove all
              non UAE residents from the traveller(s) below to proceed.
            </span>
          </div>
        </div>
        <div
          class="grid sm:grid-cols-4 gap-4"
          v-for="(travel, index) in quoteForm.members"
          :key="index"
        >
          <div class="row-span-4 md:row-span-3">
            <h2>
              {{
                travel.primary == true
                  ? 'Primary Traveler'
                  : 'Additional Traveler ' + index
              }}
            </h2>
            <div class="flex items-center justify-end">
              <x-button
                v-if="travel.primary != true"
                size="sm"
                outlined
                color="error"
                icon="xc"
                @click="removeMember(index)"
              />
            </div>
          </div>
          <DatePicker
            v-model="travel.dob"
            name="created_at_start"
            format="dd-MM-yyyy"
            :rules="[rules.isRequired]"
            :error="quoteForm.errors.dob"
            label="Date of Birth"
            required
          />

          <x-select
            v-model="travel.gender"
            placeholder="Gender"
            :options="genderList"
            :rules="[rules.isRequired]"
            class="w-full"
            label="Gender"
            required
          />

          <x-select
            v-if="
              quoteForm.direction_code != travelQuoteEnum.TRAVEL_UAE_INBOUND
            "
            v-model="travel.uae_resident"
            placeholder="Are you a UAE resident"
            :options="isUAEResident"
            :rules="[rules.isRequired]"
            @change="checkUAEResident"
            class="w-full"
            label="Are you a UAE resident"
            required
          />
        </div>
      </template>

      <x-button
        v-if="
          !editMode &&
          (quoteForm.has_arrived_uae == '0' ||
            quoteForm.has_arrived_destination == '0')
        "
        size="md"
        color="emerald"
        type="button"
        @click="addTravler()"
      >
        Add Traveler
      </x-button>
      <x-divider class="my-4" />
      <div
        class="grid mb-2"
        v-if="
          quoteForm.has_arrived_destination == 1 ||
          quoteForm.has_arrived_uae == 1
        "
      >
        <div
          class="alert flex items-center bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
          role="alert"
        >
          <span class="text-sm text-red-500 dark:text-red-400 mt-1">
            Select 'Yes' if you have already started your journey. Choose 'No'
            if you are yet to begin travelling, even if you're at the airport.
            (Note: Selecting 'Yes' means your trip has already started and thus
            ineligible for travel insurance).
          </span>
        </div>
      </div>

      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          v-if="
            !hasZeroValueForUAEResident &
            !(
              quoteForm.has_arrived_destination == 1 ||
              quoteForm.has_arrived_uae == 1
            )
          "
          :loading="quoteForm.processing"
        >
          {{ editMode ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
