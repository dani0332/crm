<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  homePossessionTypeEnum: Object,
  model: String,
  nationalities: Object,
  lookUpData: Object,
  subSources: { type: Array, default: () => [] },
  leadSourceParams: { type: Object, default: () => ({}) },
});
const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const rolesEnum = page.props.rolesEnum;
const teamNamesEnum = page.props.teamNamesEnum;
const isPcpSubSourceOptionAllowed = ref(
  useHasRole(rolesEnum.Admin) || useHasAnyTeam([{ name: teamNamesEnum.PCP }]),
);
const hasContentOrBuilding = ref(true);
const typeOfOwnerOccupancyField = ref(false);
const showBuildingField = ref(false);
const showContentsField = ref(false);
const showPersonalBelongingsField = ref(false);

const contentsAED = computed(
  () => props.quote?.home_quote?.contents_value_id || null,
);
const buildingAED = computed(
  () => props.quote?.home_quote?.building_value || null,
);
const personalBelongingsAED = computed(
  () => props.quote?.home_quote?.personal_belongings_value_id || null,
);

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
      disabled:
        !isPcpSubSourceOptionAllowed.value &&
        pcpOnlyOptions.includes(String(child.code)),
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
    rolesEnum.HomeManager,
    rolesEnum.Admin,
    rolesEnum.LeadPool,
  ]);
});

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: props.quote?.first_name || null,
  last_name: props.quote?.last_name || null,
  email: props.quote?.email || null,
  mobile_no: props.quote?.mobile_no || null,
  iam_possesion_type_id: props.quote?.home_quote?.possession_type_id || null,
  ilivein_accommodation_type_id:
    props.quote?.home_quote?.accommodation_type_id || null,
  has_contents: !!props.quote?.home_quote?.contents_value_id || null,
  has_building: !!props.quote?.home_quote?.building_value || null,
  has_personal_belongings:
    !!props.quote?.home_quote?.personal_belongings_value_id || null,
  contents_aed: contentsAED.value,
  building_aed: buildingAED.value,
  personal_belongings_aed: personalBelongingsAED.value,
  sub_area_id: props.quote?.home_quote?.sub_area_id || null,
  owner_occupancy_type_id:
    props.quote?.home_quote?.owner_occupancy_type_id || null,
  type_of_coverage_you_need: props.quote?.home_quote?.coverage_type_id || null,
  have_claimed_losses:
    props.quote?.home_quote?.has_claimed_losses !== undefined
      ? String(props.quote.home_quote.has_claimed_losses)
      : null,
  addressObj: {
    villa_apartment_office_no:
      page.props?.quote?.customerAddressData?.floor_number || null,
    villa_building_name:
      page.props?.quote?.customerAddressData?.building_name || null,
    street_name: page.props?.quote?.customerAddressData?.street || null,
  },
  dob: props.quote?.dob || null,
  nationality_id: props.quote?.nationality_id || null,
  gender: props.quote?.gender || null,
  company_name: props.quote?.company_name || null,
  company_address: props.quote?.company_address || null,
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
  notes: props.quote?.notes || '',
});

const isEdit = computed(() => {
  return route().current().includes('edit');
});
const { isRequired, isEmail, isMobileNo, minValue, maxCharacters } = useRules();

function onSubmit(isValid) {
  try {
    if (!isFormValid() || !isValid) {
      return;
    }

    if (!checkForPossesionTypeIdValidation()) {
      return;
    }

    quoteForm.has_contents = !!quoteForm.contents_aed;
    quoteForm.has_personal_belongings = !!quoteForm.personal_belongings_aed;
    quoteForm.has_building = !!quoteForm.building_aed;

    const action = isEdit.value
      ? route('home-revival-quotes-update', props.quote.uuid)
      : route('home-revival-quotes-list');

    if (!action) return;

    const submitMethod = isEdit.value ? quoteForm.put : quoteForm.post;

    submitMethod.call(quoteForm, action, {
      onError: errors => {
        handleError(errors);
      },
      onSuccess: () => {
        handleSuccess();
      },
      onFinish: () => {
        handleFinish();
      },
    });
  } catch (error) {
    console.error(
      'An unexpected error occurred during form submission:',
      error,
    );
  }
}

function handleSuccess() {}

function handleError(errors) {
  quoteForm.setError(errors);
  Object.keys(errors).forEach(field => {
    const errorMessage = errors[field];
    if (Array.isArray(errorMessage)) {
      errorMessage.forEach(msg => showToast('error', 'Error: ', msg));
    } else {
      showToast('error', 'Error: ', errorMessage);
    }
  });
}

function handleFinish() {}

function isFormValid() {
  if (quoteForm.sub_area_id == null || quoteForm.sub_area_id === '') {
    formFieldReq.sub_area_id = true;
    return false;
  }
  formFieldReq.sub_area_id = false;
  return true;
}

const formFieldReq = reactive({
  sub_area_id: false,
});

function showToast(type, title, message) {
  notification[type]({
    title: title || (type === 'success' ? 'Success' : 'Error'),
    message: message,
    position: 'top',
  });
}

const checkForPossesionTypeIdValidation = () => {
  const possessionTypeId = quoteForm.iam_possesion_type_id;
  const coverageTypeId = quoteForm.type_of_coverage_you_need;
  const coverageTypes = page.props?.lookUpData?.coverages || [];
  const coverageType = coverageTypes.find(
    coverage => coverage.id === coverageTypeId,
  );

  if (!coverageType) {
    showToast('error', 'Error', 'Invalid coverage type selected.');
    return false;
  }

  const isPossessionTypeValid =
    coverageType.applicableForPossessionTypes.includes(possessionTypeId);

  if (!isPossessionTypeValid) {
    showToast(
      'error',
      'Error',
      'Please select an appropriate coverage type for the chosen ownership status.',
    );
    return false;
  }

  return true;
};

const claimOptions = computed(() => {
  return [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
  ];
});

const showTypeOfOwnerOccupancy = computed(() => {
  if (
    !page.props?.lookUpData?.possessionType ||
    !Array.isArray(page.props.lookUpData.possessionType)
  ) {
    return false;
  }

  const possessionType = page.props.lookUpData.possessionType.find(
    item => item.code === 'landlord_renting_out',
  );

  if (!possessionType) return false;

  return quoteForm.iam_possesion_type_id === possessionType.id;
});

const coverageTypes = page.props?.lookUpData?.coverages || [];

const typeOfCoverageYouNeedOptions = computed(() => {
  if (!quoteForm.iam_possesion_type_id) {
    return [
      { value: '', label: 'CHOOSE OWNERSHIP STATUS FIRST', disabled: true },
    ];
  }

  if (!coverageTypes?.length) {
    return [
      { value: '', label: 'NO COVERAGE TYPES AVAILABLE', disabled: true },
    ];
  }

  return coverageTypes
    .filter(coverage =>
      coverage.applicableForPossessionTypes.includes(
        quoteForm.iam_possesion_type_id,
      ),
    )
    .map(coverage => ({
      value: coverage.id,
      label: coverage.text,
    }));
});

const handleConditionalFields = () => {
  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;
};

const handleCoverageChange = () => {
  if (!coverageTypes || !Array.isArray(coverageTypes)) return;
  if (!quoteForm || typeof quoteForm !== 'object') return;

  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;

  const selectedCoverage = coverageTypes.find(
    coverage => coverage.id === quoteForm?.type_of_coverage_you_need,
  );

  if (!selectedCoverage) return;

  const coverageVisibilityMap = {
    building: { showBuildingField: true },
    contents: { showContentsField: true },
    building_contents: { showBuildingField: true, showContentsField: true },
    building_contents_personal_belongings: {
      showBuildingField: true,
      showContentsField: true,
      showPersonalBelongingsField: true,
    },
    contents_personal_belongings: {
      showContentsField: true,
      showPersonalBelongingsField: true,
    },
  };

  const visibilityRules = coverageVisibilityMap[selectedCoverage.code] || {};
  if (visibilityRules.showBuildingField) showBuildingField.value = true;
  if (visibilityRules.showContentsField) showContentsField.value = true;
  if (visibilityRules.showPersonalBelongingsField)
    showPersonalBelongingsField.value = true;

  if (!isEdit.value) {
    if (!showBuildingField.value) quoteForm.building_aed = null;
    if (!showContentsField.value) quoteForm.contents_aed = null;
    if (!showPersonalBelongingsField.value)
      quoteForm.personal_belongings_aed = null;
  } else {
    quoteForm.building_aed =
      buildingAED?.value && showBuildingField.value ? buildingAED.value : null;
    quoteForm.contents_aed =
      contentsAED?.value && showContentsField.value ? contentsAED.value : null;
    quoteForm.personal_belongings_aed =
      personalBelongingsAED?.value && showPersonalBelongingsField.value
        ? personalBelongingsAED.value
        : null;
  }
};

const isMatchingCoverage = (
  coverage,
  has_building,
  has_contents,
  has_personal_belongings,
) => {
  const coverageCaseMap = {
    building: 1,
    contents: 2,
    building_contents: 3,
    building_contents_personal_belongings: 4,
    contents_personal_belongings: 5,
  };

  const caseNumber = coverageCaseMap[coverage.code];

  switch (caseNumber) {
    case 1:
      return has_building && !has_contents && !has_personal_belongings;
    case 2:
      return !has_building && has_contents && !has_personal_belongings;
    case 3:
      return has_building && has_contents && !has_personal_belongings;
    case 4:
      return has_building && has_contents && has_personal_belongings;
    case 5:
      return !has_building && has_contents && has_personal_belongings;
    default:
      return false;
  }
};

const setCoverageBasedOnBooleans = () => {
  if (props.quote?.home_quote?.possession_type_id) {
    if (props.quote?.home_quote) {
      props.quote.home_quote.has_building = quoteForm.has_building;
      props.quote.home_quote.has_contents = quoteForm.has_contents;
      props.quote.home_quote.has_personal_belongings =
        quoteForm.has_personal_belongings;
    }

    const { has_building, has_contents, has_personal_belongings } =
      props.quote?.home_quote || {};

    let selectedCoverage = null;

    if (coverageTypes.length) {
      coverageTypes.forEach(coverage => {
        if (
          isMatchingCoverage(
            coverage,
            has_building,
            has_contents,
            has_personal_belongings,
          )
        ) {
          selectedCoverage = coverage.id;
        }
      });

      if (selectedCoverage) {
        quoteForm.type_of_coverage_you_need = selectedCoverage;
        handleCoverageChange();
      }
    }
  }
};

watch(
  () => quoteForm.iam_possesion_type_id,
  () => {
    setCoverageBasedOnBooleans();
  },
  { immediate: true },
);

watch(
  () => quoteForm.sub_source_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      quoteForm.sub_source_options_id = null;
    }
  },
);

watch(
  () => quoteForm.sub_area_id,
  newValue => {
    if (newValue != null && newValue !== '') {
      formFieldReq.sub_area_id = false;
    }
  },
);

const locationAreaOptions = computed(() => {
  return page.props?.lookUpData?.subAreas?.length
    ? page.props.lookUpData.subAreas.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});
const possessionTypeOptions = computed(() => {
  return page.props?.lookUpData?.possessionType?.length
    ? page.props.lookUpData.possessionType.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});
const accommodationTypeOptions = computed(() => {
  return page.props?.lookUpData?.accommodationType?.length
    ? page.props.lookUpData.accommodationType.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});

const typeOfOwnerOccupancyOptions = computed(() => {
  quoteForm.owner_occupancy_type_id =
    page.props?.quote?.home_quote?.owner_occupancy_type_id;
  return page.props?.lookUpData?.ownerOccupancies?.length
    ? page.props.lookUpData.ownerOccupancies.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});

const contentValueInAEDOptions = computed(() => {
  quoteForm.contents_aed = contentsAED.value;
  return page.props?.lookUpData?.contentValues?.length
    ? page.props.lookUpData.contentValues.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});
const personalBelongingsInAEDOptions = computed(() => {
  quoteForm.personal_belongings_aed = personalBelongingsAED.value;
  return page.props?.lookUpData?.personalBelongingValues?.length
    ? page.props.lookUpData.personalBelongingValues.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});

const gender = computed(() => {
  return [
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
  ];
});
</script>

<template>
  <div>
    <Head title="Edit Home Revival" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Home Revival</h2>
      <div class="space-x-4">
        <Link
          v-if="$page.props?.quote?.uuid"
          :href="route('home-revival-quotes-show', $page.props?.quote?.uuid)"
        >
          <x-button size="sm" tag="div"> Cancel </x-button>
        </Link>
        <Link :href="route('home-revival-quotes-list')">
          <x-button size="sm" color="#ff5e00" tag="div">
            Home Revival List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
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
          label="FIRST NAME"
          required
          :rules="[isRequired]"
          class="w-full"
          maxLength="20"
          :error="quoteForm.errors.first_name"
        />
        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          required
          maxLength="50"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm.errors.last_name"
        />
        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          required
          :disabled="isEdit"
          :rules="[isRequired]"
          class="w-full"
          :error="quoteForm?.errors?.email"
        />
        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          required
          :disabled="isEdit"
          :rules="[isRequired, isMobileNo]"
          class="w-full"
          :error="quoteForm?.errors?.mobile_no"
        />

        <x-select
          v-model="quoteForm.sub_area_id"
          :rules="[isRequired]"
          :options="locationAreaOptions"
          class="w-full"
          :error="quoteForm.errors.sub_area_id"
          label="LOCATION AREA"
          filterable
          placeholder="Search by Location Area"
          required
        />

        <DatePicker v-model="quoteForm.dob" name="dob" label="DATE OF BIRTH" />

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
          type="text"
          :rules="[isRequired]"
          v-model="quoteForm.addressObj.villa_apartment_office_no"
          class="w-full"
          :error="quoteForm?.errors?.villa_apartment_office_no"
          label="Floor and Villa/ Apartment number"
          required
        />

        <x-input
          type="text"
          :rules="[isRequired]"
          v-model="quoteForm.addressObj.villa_building_name"
          class="w-full"
          :error="quoteForm?.errors?.villa_building_name"
          label="Villa/ Building name"
          required
        />

        <x-input
          type="text"
          :rules="[isRequired]"
          v-model="quoteForm.addressObj.street_name"
          class="w-full"
          :error="quoteForm?.errors?.street_name"
          label="Street name"
          required
        />

        <x-select
          v-model="quoteForm.iam_possesion_type_id"
          :rules="[isRequired]"
          :options="possessionTypeOptions"
          @update:modelValue="handleConditionalFields"
          class="w-full"
          label="OWNERSHIP STATUS"
          required
        />

        <x-select
          v-model="quoteForm.ilivein_accommodation_type_id"
          :rules="[isRequired]"
          :options="accommodationTypeOptions"
          class="w-full"
          label="TYPE OF PROPERTY"
          required
        />

        <x-select
          v-if="showTypeOfOwnerOccupancy"
          v-model="quoteForm.owner_occupancy_type_id"
          :rules="[isRequired]"
          :options="typeOfOwnerOccupancyOptions"
          class="w-full"
          label="TYPE OF OWNER'S OCCUPANCY"
          required
        />

        <x-select
          v-model="quoteForm.type_of_coverage_you_need"
          :rules="[isRequired]"
          :options="typeOfCoverageYouNeedOptions"
          @update:modelValue="handleCoverageChange"
          class="w-full"
          label="TYPE OF COVERAGE YOU NEED"
          required
        />

        <x-input
          v-if="showBuildingField"
          v-model="quoteForm.building_aed"
          type="number"
          class="w-full"
          :rules="[isRequired, minValue(100000)]"
          :error="quoteForm.errors.building_aed"
          label="BUILDING VALUE IN AED"
          required
        />

        <x-select
          v-if="showContentsField"
          v-model="quoteForm.contents_aed"
          :rules="[isRequired]"
          :options="contentValueInAEDOptions"
          class="w-full"
          label="CONTENTS VALUE IN AED"
          required
        />

        <x-select
          v-if="showPersonalBelongingsField"
          v-model="quoteForm.personal_belongings_aed"
          :rules="[isRequired]"
          :options="personalBelongingsInAEDOptions"
          class="w-full"
          label="PERSONAL BELONGINGS IN AED"
          required
        />

        <x-select
          v-model="quoteForm.have_claimed_losses"
          :rules="[isRequired]"
          :options="claimOptions"
          class="w-full"
          label="Have you made any claims or experienced any losses in the past 5 years?"
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
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
