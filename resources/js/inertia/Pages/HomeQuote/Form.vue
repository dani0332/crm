<script setup>
import { options } from 'sanitize-html';

const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  homePossessionTypeEnum: Object,
  model: String,
  lookUpData: Object,
});
const page = usePage();
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

console.log('PROPS: ', page.props);

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
});

console.log('quoteForm:', quoteForm);

const isEdit = computed(() => {
  return route().current().includes('edit');
});
const { isRequired, isEmail, isMobileNo, minValue } = useRules();

function successResponse() {
  notification.success({
    title: 'Quote updated successfully',
    position: 'top',
  });
  if (isEdit.value) {
    router.get(route('home-quotes-show', page.props?.quote?.uuid));
  } else {
    quoteForm.reset();
  }
}

const checkForPossesionTypeIdValidation = () => {
  const possessionTypeId = quoteForm.iam_possesion_type_id;
  const coverageTypeId = quoteForm.type_of_coverage_you_need;

  const coverageTypes = page.props?.lookUpData?.coverages || [];

  const coverageType = coverageTypes.find(
    coverage => coverage.id === coverageTypeId,
  );

  // If no coverage type is found, show an error
  if (!coverageType) {
    showToast('error', 'Error', 'Invalid coverage type selected.');
    return false;
  }

  // Check if the possessionTypeId is in the applicableForPossessionTypes array
  const isPossessionTypeValid = coverageType.applicableForPossessionTypes.includes(
    possessionTypeId,
  );

  // If the possession type is not valid, show an error
  if (!isPossessionTypeValid) {
    showToast(
      'error',
      'Error',
      'Please select an appropriate coverage type for the chosen ownership status.',
    );
    return false;
  }

  // If everything is valid, return true
  return true;
};

function onSubmit(isValid) {
  console.log('onSubmit:', quoteForm);
  try {
    if (!isFormValid() || !isValid) {
      console.info('Form validation failed.');
      return;
    }

    if (!checkForPossesionTypeIdValidation()) {
      return; // Stop submission if validation fails for possession type (ownership status)
    }

    // Ensure fields are included in the payload with true/false values
    // Set boolean values based on AED fields
    quoteForm.has_contents = !!quoteForm.contents_aed;
    quoteForm.has_personal_belongings = !!quoteForm.personal_belongings_aed;
    quoteForm.has_building = !!quoteForm.building_aed;

    const action = isEdit.value
      ? route('home-quotes-update', props.quote.uuid)
      : route('home-quotes-store');

    if (!action) {
      console.error('Form action is undefined.');
      return;
    }

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

function handleSuccess() {
  // Additional logic on successful submission
}

function handleError(errors) {
  console.error('Form submission failed with errors:', errors);

  // Display errors or set them on form fields
  quoteForm.setError(errors);

  // Loop through each error and show a toast notification
  Object.keys(errors).forEach(field => {
    // Assuming the error message is an array of messages for each field
    const errorMessage = errors[field];
    if (Array.isArray(errorMessage)) {
      errorMessage.forEach(msg => {
        showToast('error', 'Error: ', msg);
      });
    } else {
      showToast('error', 'Error: ', errorMessage);
    }
  });
}

function handleFinish() {
  // Cleanup actions if needed
}

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
    console.error('possessionType is not defined or not an array');
    return false;
  }

  const possessionType = page.props.lookUpData.possessionType.find(
    item => item.code === 'landlord_renting_out',
  );

  if (!possessionType) {
    console.warn('No possession type found with code: landlord_renting_out');
    return false;
  }

  return quoteForm.iam_possesion_type_id === possessionType.id;
});

const handleConditionalFields = () => {
  // Reset all AED fields visibility to false
  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;
};

function showToast(type, title, message) {
  notification[type]({
    title: title || (type === 'success' ? 'Success' : 'Error'),
    message: message,
    position: 'top',
  });
}

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

  // Filter the coverage types based on the selected possession type
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

const handleCoverageChange = () => {
  if (!coverageTypes || !Array.isArray(coverageTypes)) {
    console.error('coverageTypes is not defined or not an array');
    return;
  }

  if (!quoteForm || typeof quoteForm !== 'object') {
    console.error('quoteForm is not defined or not an object');
    return;
  }

  // Reset field visibility to false
  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;

  // Determine the selected coverage type
  const selectedCoverage = coverageTypes.find(
    coverage => coverage.id === quoteForm?.type_of_coverage_you_need,
  );

  if (!selectedCoverage) {
    console.warn('No matching coverage type found');
    return;
  }

  // Define a mapping object for coverage types and their visibility rules
  const coverageVisibilityMap = {
    9: { showBuildingField: true }, // Building only
    10: { showContentsField: true }, // Contents only
    11: { showBuildingField: true, showContentsField: true }, // Building and Contents
    12: {
      showBuildingField: true,
      showContentsField: true,
      showPersonalBelongingsField: true,
    }, // Building, Contents, and Personal Belongings
    13: { showContentsField: true, showPersonalBelongingsField: true }, // Contents and Personal Belongings
  };

  // Apply visibility rules based on the selected coverage type
  const visibilityRules = coverageVisibilityMap[selectedCoverage.id] || {};
  if (visibilityRules.showBuildingField) showBuildingField.value = true;
  if (visibilityRules.showContentsField) showContentsField.value = true;
  if (visibilityRules.showPersonalBelongingsField)
    showPersonalBelongingsField.value = true;

  // Reset AED fields only if the field is not visible and not in edit mode
  const resetAEDFields = () => {
    if (!showBuildingField.value) quoteForm.building_aed = null;
    if (!showContentsField.value) quoteForm.contents_aed = null;
    if (!showPersonalBelongingsField.value)
      quoteForm.personal_belongings_aed = null;
  };

  const preserveAEDFields = () => {
    if (buildingAED?.value && showBuildingField.value) {
      quoteForm.building_aed = buildingAED.value;
    } else {
      quoteForm.building_aed = null;
    }
    if (contentsAED?.value && showContentsField.value) {
      quoteForm.contents_aed = contentsAED.value;
    } else {
      quoteForm.contents_aed = null;
    }
    if (personalBelongingsAED?.value && showPersonalBelongingsField.value) {
      quoteForm.personal_belongings_aed = personalBelongingsAED.value;
    } else {
      quoteForm.personal_belongings_aed = null;
    }
  };

  if (!isEdit?.value) {
    resetAEDFields();
  } else {
    preserveAEDFields();
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

const isMatchingCoverage = (
  coverage,
  has_building,
  has_contents,
  has_personal_belongings,
) => {
  const coverageCaseMap = {
    [coverageTypes[0]?.id]: 1, // Building only
    [coverageTypes[1]?.id]: 2, // Contents only
    [coverageTypes[2]?.id]: 3, // Building and Contents
    [coverageTypes[3]?.id]: 4, // Building, Contents, and Personal Belongings
    [coverageTypes[4]?.id]: 5, // Contents and Personal Belongings
  };

  const caseNumber = coverageCaseMap[coverage.id];

  switch (caseNumber) {
    case 1: // Building only
      return has_building && !has_contents && !has_personal_belongings;
    case 2: // Contents only
      return !has_building && has_contents && !has_personal_belongings;
    case 3: // Building and Contents
      return has_building && has_contents && !has_personal_belongings;
    case 4: // Building, Contents, and Personal Belongings
      return has_building && has_contents && has_personal_belongings;
    case 5: // Contents and Personal Belongings
      return !has_building && has_contents && has_personal_belongings;
    default:
      return false;
  }
};

watch(
  () => quoteForm.iam_possesion_type_id,
  newVal => {
    console.log('in watcher iam_possesion_type_id:', newVal);
    setCoverageBasedOnBooleans();
  },
  { immediate: true },
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
  // assign quoteForm.contents_aed to contentsAED.value
  quoteForm.contents_aed = contentsAED.value;
  return page.props?.lookUpData?.contentValues?.length
    ? page.props.lookUpData.contentValues.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});
const personalBelongingsInAEDOptions = computed(() => {
  // assign quoteForm.personal_belongings_aed to personalBelongingsAED.value
  quoteForm.personal_belongings_aed = personalBelongingsAED.value;
  return page.props?.lookUpData?.personalBelongingValues?.length
    ? page.props.lookUpData.personalBelongingValues.map(item => ({
        value: item.id,
        label: item.text,
      }))
    : [];
});

watch(
  () => quoteForm.sub_area_id,
  newValue => {
    if (newValue != null && newValue !== '') {
      formFieldReq.sub_area_id = false; // Reset the validation error
    }
  },
);
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Home' : 'Create Home'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Home
      </h2>
      <div class="space-x-4">
        <Link
          v-if="$page.props?.quote?.uuid"
          :href="route('home-quotes-show', $page.props?.quote?.uuid)"
        >
          <x-button size="sm" tag="div"> Cancel </x-button>
        </Link>
        <Link :href="route('home-quotes-list')">
          <x-button size="sm" color="#ff5e00" tag="div"> Home List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="FIRST NAME" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            maxLength="20"
            :error="quoteForm.errors.first_name"
          />
        </x-field>
        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            maxLength="50"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>
        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="isEdit"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm?.errors?.email"
          />
        </x-field>
        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="isEdit"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm?.errors?.mobile_no"
          />
        </x-field>
        <x-field label="LOCATION AREA" required>
          <ComboBox
            v-model="quoteForm.sub_area_id"
            :rules="[isRequired]"
            :single="true"
            :options="locationAreaOptions"
            class="w-full"
            :hasError="formFieldReq.sub_area_id"
            :error="quoteForm.errors.sub_area_id"
          />
        </x-field>

        <x-field label="Floor and Villa/ Apartment number" required>
          <x-input
            type="text"
            :rules="[isRequired]"
            v-model="quoteForm.addressObj.villa_apartment_office_no"
            class="w-full"
            :error="quoteForm?.errors?.villa_apartment_office_no"
          />
        </x-field>

        <x-field label="Villa/ Building name" required>
          <x-input
            type="text"
            :rules="[isRequired]"
            v-model="quoteForm.addressObj.villa_building_name"
            class="w-full"
            :error="quoteForm?.errors?.villa_building_name"
          />
        </x-field>

        <x-field label="Street name" required>
          <x-input
            type="text"
            :rules="[isRequired]"
            v-model="quoteForm.addressObj.street_name"
            class="w-full"
            :error="quoteForm?.errors?.street_name"
          />
        </x-field>

        <x-field label="OWNERSHIP STATUS" required>
          <x-select
            v-model="quoteForm.iam_possesion_type_id"
            :rules="[isRequired]"
            :options="possessionTypeOptions"
            @update:modelValue="handleConditionalFields"
            class="w-full"
          />
        </x-field>
        <x-field label="TYPE OF PROPERTY" required>
          <x-select
            v-model="quoteForm.ilivein_accommodation_type_id"
            :rules="[isRequired]"
            :options="accommodationTypeOptions"
            class="w-full"
          />
        </x-field>
        <x-field
          label="TYPE OF OWNER'S OCCUPANCY"
          required
          v-if="showTypeOfOwnerOccupancy"
        >
          <x-select
            v-model="quoteForm.owner_occupancy_type_id"
            :rules="[isRequired]"
            :options="typeOfOwnerOccupancyOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="TYPE OF COVERAGE YOU NEED" required>
          <x-select
            v-model="quoteForm.type_of_coverage_you_need"
            :rules="[isRequired]"
            :options="typeOfCoverageYouNeedOptions"
            @update:modelValue="handleCoverageChange"
            class="w-full"
          />
        </x-field>
        <x-field
          label="BUILDING VALUE IN AED"
          required
          v-if="showBuildingField"
        >
          <x-input
            v-model="quoteForm.building_aed"
            type="number"
            class="w-full"
            :rules="[isRequired, minValue(100000)]"
            :error="quoteForm.errors.building_aed"
          />
        </x-field>
        <x-field
          label="CONTENTS VALUE IN AED"
          required
          v-if="showContentsField"
        >
          <x-select
            v-model="quoteForm.contents_aed"
            :rules="[isRequired]"
            :options="contentValueInAEDOptions"
            class="w-full"
          />
        </x-field>
        <x-field
          label="PERSONAL BELONGINGS IN AED"
          required
          v-if="showPersonalBelongingsField"
        >
          <x-select
            v-model="quoteForm.personal_belongings_aed"
            :rules="[isRequired]"
            :options="personalBelongingsInAEDOptions"
            class="w-full"
          />
        </x-field>
        <x-field
          label="Have you made any claims or experienced any losses in the past 5 years?"
          required
        >
          <x-select
            v-model="quoteForm.have_claimed_losses"
            :rules="[isRequired]"
            :options="claimOptions"
            class="w-full"
          />
        </x-field>
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
