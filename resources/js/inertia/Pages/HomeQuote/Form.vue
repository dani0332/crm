<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  homePossessionTypeEnum: Object,
  model: String,
});
const page = usePage();
const hasContentOrBuilding = ref(true);
const typeOfOwnerOccupancyField = ref(false);
const showBuildingField = ref(false);
const showContentsField = ref(false);
const showPersonalBelongingsField = ref(false);

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: props.quote?.first_name || null,
  last_name: props.quote?.last_name || null,
  email: props.quote?.email || null,
  mobile_no: props.quote?.mobile_no || null,
  iam_possesion_type_id:
    props.quote?.home_quote?.iam_possesion_type_id || null,
  ilivein_accommodation_type_id:
    props.quote?.home_quote?.ilivein_accommodation_type_id || null,
  has_contents: props.quote?.home_quote?.has_contents || null,
  has_building: props.quote?.home_quote?.has_building || null,
  has_personal_belongings:
    props.quote?.home_quote?.has_personal_belongings || null,
  contents_aed: props.quote?.home_quote?.contents_aed || null,
  building_aed: props.quote?.home_quote?.building_aed || null,
  personal_belongings_aed:
    props.quote?.home_quote?.personal_belongings_aed || null,
  location_area: props.quote?.home_quote?.location_area || null,
  is_property_rented_holiday_home:
    props.quote?.home_quote?.is_property_rented_holiday_home || null,
  type_of_coverage_you_need:
    props.quote?.home_quote?.type_of_coverage_you_need || null,
  have_claimed_losses: props.quote?.home_quote?.have_claimed_losses || null,
});
const isEdit = computed(() => {
  return route().current().includes('edit');
});
const { isRequired, isEmail, isMobileNo } = useRules();

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
// function onSubmit(isValid) {
//   if (quoteForm.location_area == null || quoteForm.location_area == '') {
//     formFieldReq.location_area = true;
//   } else {
//     formFieldReq.location_area = false;
//   }
//   if (quoteForm.has_contents || quoteForm.has_building) {
//     if (isValid) {
//       if (isEdit.value) {
//         quoteForm
//           .transform(data => ({
//             ...data,
//             has_contents: data.has_contents ? true : false,
//             has_personal_belongings: data.has_personal_belongings
//               ? true
//               : false,
//             has_building: data.has_building ? true : false,
//           }))
//           .put(route('home-quotes-update', props.quote.uuid), {
//             onError: errors => {
//               console.log(errors);
//             },
//             onSuccess: () => {},
//           });
//       } else {
//         quoteForm.post(route('home-quotes-store'), {
//           onError: errors => {
//             quoteForm.setError(errors);
//           },
//           onSuccess: () => {},
//         });
//       }
//     }
//   } else {
//     hasContentOrBuilding.value = false;
//   }
// }

function onSubmit(isValid) {
  try {
    if (!isFormValid() || !isValid) {
      console.info('Form validation failed.');
      return;
    }

    // Ensure fields are included in the payload with true/false values
    // Set boolean values based on AED fields
    quoteForm.has_contents = !!quoteForm.contents_aed;
    quoteForm.has_personal_belongings = !!quoteForm.personal_belongings_aed;
    quoteForm.has_building = !!quoteForm.building_aed;

    console.log('Form validation passed.');
    console.log('Submitting form data:', quoteForm);

    const action = isEdit.value
      ? route('home-quotes-update', props.quote.id)
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
  console.log('Form submitted successfully.');
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
  console.log('Form submission process completed.');
  // Cleanup actions if needed
}

function isFormValid() {
  if (quoteForm.location_area == null || quoteForm.location_area === '') {
    formFieldReq.location_area = true;
    return false;
  }
  formFieldReq.location_area = false;
  return true;
}

const formFieldReq = reactive({
  location_area: false,
});

const locationAreaOptions = computed(() => {
  return [
    { value: '1', label: 'AL SHARAQI' },
    { value: '2', label: 'AL DANAH' },
  ];
});
const typeOfOwnerOccupancyOptions = computed(() => {
  return [
    { value: '0', label: 'Owner renting out (annually)' },
    { value: '1', label: 'Owner renting out short term/Holiday home' },
  ];
});
const contentValueInAEDOptions = computed(() => {
  return [
    { value: '1 - 50,000', label: 'AED 1 - 50,000' },
    { value: '50,001 - 100,000', label: 'AED 50,001 - 100,000' },
    { value: '100,001 - 150,000', label: 'AED 100,001 - 150,000' },
    { value: '150,001 - 200,000', label: 'AED 150,001 - 200,000' },
    { value: '200,001 - 250,000', label: 'AED 200,001 - 250,000' },
    { value: '250,001 - 300,000', label: 'AED 250,001 - 300,000' },
    { value: '300,001 - 400,000', label: 'AED 300,001 - 400,000' },
  ];
});
const personalBelongingsInAEDOptions = computed(() => {
  return [
    { value: '1-25,000', label: 'AED 1-25,000' },
    { value: '25,001 - 50,000', label: 'AED 25,001 - 50,000' },
    { value: '50,001 - 100,000', label: 'AED 50,001 - 100,000' },
    { value: '100,001 - 150,000', label: 'AED 100,001 - 150,000' },
    { value: '150,001 and above', label: 'AED 150,001 and above' },
  ];
});
const claimOptions = computed(() => {
  return [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
  ];
});
const showTypeOfOwnerOccupancy = computed(() => {
  return quoteForm.iam_possesion_type_id === 2;
});

const typeOfCoverageYouNeedOptions = computed(() => {
  if (!quoteForm.iam_possesion_type_id) {
    return [
      { value: '', label: 'CHOOSE OWNERSHIP STATUS FIRST', disabled: true },
    ];
  }
  switch (quoteForm.iam_possesion_type_id) {
    case 1: // Homeowner living in the property
      return [
        { value: 'landlord_living_building', label: 'Building only' },
        { value: 'landlord_living_contents', label: 'Contents only' },
        {
          value: 'landlord_living_building_contents',
          label: 'Building and Contents',
        },
        {
          value: 'landlord_living_building_contents_personal',
          label: 'Building, Contents and Personal Belongings',
        },
        {
          value: 'landlord_living_contents_personal',
          label: 'Contents and Personal Belongings',
        },
      ];
    case 2: // Homeowner renting out property
      return [
        { value: 'landlord_renting_building', label: 'Building only' },
        {
          value: 'landlord_renting_building_contents',
          label: 'Building and Contents',
        },
      ];
    case 3: // Tenant renting home
      return [
        { value: 'tenant_renting_contents', label: 'Contents only' },
        {
          value: 'tenant_renting_contents_personal',
          label: 'Contents and Personal Belongings',
        },
      ];
    default:
      return [];
  }
});

const handleConditionalFields = () => {
  // Reset all AED fields visibility to false
  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;
};

const handleCoverageChange = () => {
  // Reset all AED fields visibility to false
  showBuildingField.value = false;
  showContentsField.value = false;
  showPersonalBelongingsField.value = false;

  //   Update field visibility based on the selected coverage
  switch (quoteForm.type_of_coverage_you_need) {
    case 'landlord_living_building':
    case 'landlord_renting_building':
      showBuildingField.value = true;
      break;
    case 'landlord_living_contents':
    case 'tenant_renting_contents':
      showContentsField.value = true;
      break;
    case 'landlord_living_building_contents':
    case 'landlord_renting_building_contents':
      showBuildingField.value = true;
      showContentsField.value = true;
      break;
    case 'landlord_living_building_contents_personal':
      showBuildingField.value = true;
      showContentsField.value = true;
      showPersonalBelongingsField.value = true;
      break;
    case 'landlord_living_contents_personal':
    case 'tenant_renting_contents_personal':
      showContentsField.value = true;
      showPersonalBelongingsField.value = true;
      break;
  }
};

function showToast(type, title, message) {
  notification[type]({
    title: title || (type === 'success' ? 'Success' : 'Error'),
    message: message,
    position: 'top',
  });
}
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
          :href="route('home.show', $page.props?.quote?.uuid)"
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
            v-model="quoteForm.location_area"
            :rules="[isRequired]"
            :single="true"
            :options="locationAreaOptions"
            class="w-full"
            :hasError="formFieldReq.location_area"
            :error="quoteForm.errors.location_area"
          />
        </x-field>

        <x-field label="OWNERSHIP STATUS" required>
          <x-select
            v-model="quoteForm.iam_possesion_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.iam_possesion_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            @update:modelValue="handleConditionalFields"
            class="w-full"
          />
        </x-field>
        <x-field label="TYPE OF PROPERTY" required>
          <x-select
            v-model="quoteForm.ilivein_accommodation_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.ilivein_accommodation_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field
          label="TYPE OF OWNER'S OCCUPANCY"
          required
          v-if="showTypeOfOwnerOccupancy"
        >
          <x-select
            v-model="quoteForm.is_property_rented_holiday_home"
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
        <x-field label="BUILDING AED" required v-if="showBuildingField">
          <x-input
            v-model="quoteForm.building_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
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
