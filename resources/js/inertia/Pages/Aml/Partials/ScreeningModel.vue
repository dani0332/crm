<script setup>
import AdditionalVehicleTransactionDetails from './AdditionalVehicleTransactionDetails.vue';
import AdditionalDriverDetails from './AdditionalDriverDetails.vue';
import KYCDetails from './KYCDetails.vue';
import MembersDetails from './MembersDetails.vue';
import { computed, ref, watch } from 'vue';
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quoteTypeCodeEnum: Object,
  // RTA Configuration props (for Car quotes)
  rta_transaction_types: {
    type: Object,
    default: () => ({}),
  },
  rta_field_configurations: {
    type: Object,
    default: () => ({}),
  },
  rta_validation_summaries: {
    type: Object,
    default: () => ({}),
  },
});
const page = usePage();
const { isRequired } = useRules();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const generateOptions = (items, valueKey, labelKey) =>
  useGenerateOptions(items, valueKey, labelKey);
const rules = {
  nameCheck: v => {
    const pattern = /^[a-zA-Z0-9\s]+$/;
    if (v == null || v == '') return true;
    return (
      pattern.test(v) || 'Special characters are not allowed in Insured Name'
    );
  },
  chassisNumberCheck: v => {
    const regex = /^[A-Za-z0-9]+$/;
    const lengthValid = v?.length >= 8 && v?.length <= 17;
    const isAlphanumeric = regex.test(v);
    return (
      (lengthValid && isAlphanumeric) ||
      'The entered value does not meet the required length of 8 to 17 characters'
    );
  },
  emirateNumberCheck: v => {
    const pattern = /^\d{3}-\d{4}-\d{7}-\d{1}$/;
    if (v.length == 15 || v.length > 18 || pattern.test(v)) {
      screeningFormDetails.errors.screening_id_number = '';
    } else {
      return 'Enter the correct EID number format';
    }
    return true;
  },
  passportNumberCheck: v => {
    const regex = /^[A-Za-z0-9]+$/;
    const lengthValid = v?.length >= 6 && v?.length <= 14;
    const isAlphanumeric = regex.test(v);
    return (
      (lengthValid && isAlphanumeric) ||
      'The entered value does not meet the required length of 6 to 14 characters. Please check and confirm.'
    );
  },
};
const applyScreeningIdNumMasking = () => {
  let screeningIdNumber = screeningFormDetails.screening_id_number.replace(
    /\D/g,
    '',
  );
  if (screeningIdNumber?.length > 15) {
    screeningIdNumber = screeningIdNumber.substring(0, 15); // Limit to 15 characters
  }
  if (screeningIdNumber?.length <= 3) {
    screeningIdNumber = screeningIdNumber.replace(/(\d{3})(\d{0,})/, '$1-$2');
  } else if (screeningIdNumber?.length <= 7) {
    screeningIdNumber = screeningIdNumber.replace(
      /(\d{3})(\d{4})(\d{0,})/,
      '$1-$2-$3',
    );
  } else if (screeningIdNumber?.length <= 13) {
    screeningIdNumber = screeningIdNumber.replace(
      /(\d{3})(\d{4})(\d{7})(\d{0,})/,
      '$1-$2-$3-$4',
    );
  } else {
    screeningIdNumber = screeningIdNumber.replace(
      /(\d{3})(\d{4})(\d{7})(\d{1,})/,
      '$1-$2-$3-$4',
    );
  }
  screeningFormDetails.screening_id_number = screeningIdNumber;
};
const validatePassportNumber = eventType => {
  const regex = /^[a-zA-Z0-9]*$/; // Allow only alphanumeric characters
  if (eventType == 'keypress') {
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
  if (eventType == 'blur') {
    const lengthValid =
      screeningFormDetails?.screening_id_number?.length >= 6 &&
      screeningFormDetails?.screening_id_number?.length <= 14;
    const isAlphanumeric = regex.test(screeningFormDetails.screening_id_number);
    if (
      screeningFormDetails.screening_id_number &&
      (!lengthValid || !isAlphanumeric)
    ) {
      screeningFormDetails.errors.screening_id_number =
        'The entered value does not meet the required length of 6 to 14 characters. Please check and confirm.';
      event.preventDefault();
      return true;
    } else {
      screeningFormDetails.clearErrors('screening_id_number');
      return false;
    }
  }
};
const chassisNumberValidate = eventType => {
  const regex = /^[a-zA-Z0-9]*$/; // Allow only alphanumeric characters
  if (eventType == 'keypress') {
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
  if (eventType == 'blur') {
    const lengthValid =
      screeningFormDetails.chassis_number?.length >= 8 &&
      screeningFormDetails.chassis_number?.length <= 17;
    const isAlphanumeric = regex.test(screeningFormDetails.chassis_number);
    if (
      screeningFormDetails.chassis_number &&
      (!lengthValid || !isAlphanumeric)
    ) {
      screeningFormDetails.errors.chassis_number =
        'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.';
      event.preventDefault();
      return true;
    } else {
      screeningFormDetails.clearErrors('chassis_number');
      return false;
    }
  }
};
const emit = defineEmits(['update:modelValue']);
const headerMessage = ref('');
const confirmationMessage = ref('');
const customerTypeConfirmationModel = ref(false);
const isScreeningIndividual = ref(true);
const IndividualDetailsFound = ref(false);
const EntityDetailsFound = ref(false);
const isInsuredPayer = ref(false);
const validateNationality = ref(false);
// const previousCustomerType = ref(null);
const loader = reactive({
  //   individualSearch: false,
  //   entitySearch: false,
  insuredSearch: false,
});
const showModal = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});
const customerTypeEnum = page.props.customerTypeEnum;
const quoteRequest = page.props.quoteRequest;

const customerTypeOptions = computed(() => {
  return [
    { value: customerTypeEnum.Individual, label: 'Individual' },
    { value: customerTypeEnum.Entity, label: 'Entity' },
  ];
});

const showVehicleAndDrvicerDetails = computed(() => {
  console.log('page.props.quoteType.id', page.props.quoteType.id, page.props.quoteTypeIdEnum.Car, page.props.quoteType.id === page.props.quoteTypeIdEnum.Car);
  console.log('page.props.insuranceProviderCodeEnum.AXA', page.props.quoteRequest?.plan?.insurance_provider.code, page.props.insuranceProviderCodeEnum.AXA);
  console.log('page.props.isPrivateCar', page.props.isPrivateCar);

  return (
    page.props.quoteType.id === page.props.quoteTypeIdEnum.Car &&
    [
      page.props.insuranceProviderCodeEnum.RSA, // LIVA
      page.props.insuranceProviderCodeEnum.AXA, // GIG
      page.props.insuranceProviderCodeEnum.OIC, // SUKOON
    ].includes(page.props.quoteRequest?.plan?.insurance_provider.code) &&
    (page.props.isPrivateCar ?? false)
  );
});

console.log('showVehicleAndDrvicerDetails', showVehicleAndDrvicerDetails.value);

const screeningFormDetails = useForm({
  customer_type: null,
  customer_id: quoteRequest.customer_id,
  quote_type: page.props.quoteType.code,
  // Individual Type
  screening_id_type: page.props.insuredDetails?.insured?.id_type ?? null,
  screening_id_number: page.props.insuredDetails?.insured?.id_number ?? null,
  insured_first_name:
    page.props.insuredDetails?.insured?.first_name ??
    (page.props.quoteType.code === page.props.quoteTypeCodeEnum.Health
      ? page.props.membersDetails[0]?.first_name
      : null),
  insured_last_name:
    page.props.insuredDetails?.insured?.last_name ??
    (page.props.quoteType.code === page.props.quoteTypeCodeEnum.Health
      ? page.props.membersDetails[0]?.last_name
      : null),
  nationality_id: page.props.insuredDetails?.insured.nationality_id ?? null,
  dob: page.props.insuredDetails?.insured.dob ?? null,
  screening_gender: page.props.insuredDetails?.insured?.gender ?? null,
  get_quote_email_gig:
    (page.props.quoteType.code === page.props.quoteTypeCodeEnum.Car
      ? quoteRequest?.car_quote_request_detail?.insurer_quote_email
      : quoteRequest?.quote_detail?.insurer_quote_email) ??
    page.props.gigInsurerDefaultEmail,
  chassis_number:
    (page.props.quoteType.code === props.quoteTypeCodeEnum.Car
      ? quoteRequest?.car_quote_request_detail?.chassis_number
      : quoteRequest?.bike_quote?.chassis_number) ?? null,
  // Entity Type
  // entity_id: page.props.entityDetails?.entity?.id ?? null,
  entity_type: page.props.entityDetails?.entity?.entity_type_code ?? 'Parent',
  trade_license_no:
    page.props.insuredDetails?.insured?.trade_license_no ?? null,
  company_name: page.props.insuredDetails?.insured?.company_name ?? null,
  company_address: page.props.insuredDetails?.insured?.company_address,
  industry_type_code: page.props.insuredDetails?.insured?.industry_type_code,
  emirate_of_registration_id:
    page.props.insuredDetails?.insured?.emirate_of_registration_id,
  lead_source: quoteRequest.source,
  insurance_provider_code:
    page.props.quoteRequest?.plan?.insurance_provider.code,
});
const modalHeaderMessage = () => {
  if (
    page.props.quoteType.code !== page.props.quoteTypeCodeEnum.Business &&
    [customerTypeEnum.Individual, null].includes(
      screeningFormDetails.customer_type,
    )
  ) {
    headerMessage.value =
      'Please confirm the Name, Nationality, and Date of Birth of the insured person(s) as per the Emirates ID';
  } else {
    headerMessage.value =
      'Please confirm the Company Name, and UBO details as per the Trade License';
  }
};

const clearErrors = () => {
  screeningFormDetails.clearErrors();

  screeningFormDetails.errors.dob = '';
  screeningFormDetails.errors.screening_gender = '';
};

const oldCustomerType = ref(null);
const newCustomerType = ref(null);

const setFieldsByCustomerType = (customerType = '') => {
  if (
    customerType == customerTypeEnum.Entity &&
    [undefined, customerTypeEnum.Individual].includes(oldCustomerType.value)
  ) {
    validateNationality.value = false;
    clearErrors();
    clearInsurerDetails(customerTypeEnum.Entity);
  } else if (
    customerType == customerTypeEnum.Individual &&
    oldCustomerType.value === customerTypeEnum.Entity
  ) {
    validateNationality.value = false;
    clearErrors();
    clearInsurerDetails(customerTypeEnum.Individual);
  }
};

watch(
  () => screeningFormDetails.customer_type,
  (newValue, oldValue) => {
    oldCustomerType.value = oldValue;
    newCustomerType.value = newValue;
    // Debug the current customer type
    // console.log('Customer type changed:', oldValue, '->', newValue);
  },
);

function customerTypeConfirmation() {
  if (screeningFormDetails.customer_type == newCustomerType.value) {
    return;
  }
  customerTypeConfirmationModel.value = true;
  if (screeningFormDetails.customer_type == customerTypeEnum.Individual) {
    confirmationMessage.value =
      'Are you sure you want to run AML screen for this lead as Individual Customer?';
  } else {
    confirmationMessage.value =
      'Are you sure you want to run AML screen for this lead as Entity?';
  }
}
const updateScreeningDetails = () => {
  setFieldsByCustomerType(screeningFormDetails.customer_type);
  isScreeningIndividual.value =
    screeningFormDetails.customer_type == customerTypeEnum.Individual;
  customerTypeConfirmationModel.value = false;
  modalHeaderMessage();
};

const documentIDTypeForScreening = computed(() => {
  return page.props.lookups.id_type
    ?.filter(docIDTypeScreening =>
      ['emiratesId', 'passport'].includes(docIDTypeScreening.code),
    )
    ?.map(docIDTypeScreening => ({
      value: docIDTypeScreening.code,
      label: docIDTypeScreening.text,
    }));
});
const nationalitiesOptions = computed(() => {
  return generateOptions(page.props.nationalities, 'id', 'text');
});
const gender = computed(() => {
  return [
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
  ];
});
const emirateRegistrationOptions = computed(() => {
  return generateOptions(page.props.emirates, 'id', 'text');
});
const industryTypeOptions = computed(() => {
  return generateOptions(
    page.props?.lookups?.company_type || [],
    'code',
    'text',
  );
});
const emiratesOfRegistrationOptions = computed(() => {
  return generateOptions(page.props.emirates, 'id', 'text');
});
const entityTypes = computed(() => {
  return [
    { value: 'Parent', label: 'Parent' },
    { value: 'SubEntity', label: 'Sub Entity' },
  ];
});

const chassisNumberDisabled = computed(() => {
  let disallowedStatus = [
    page.props.quoteStatusEnums.PolicySentToCustomer,
    page.props.quoteStatusEnums.PolicyBooked,
  ];
  return disallowedStatus.includes(page.props?.quoteRequest?.quote_status_id);
});

const individualSearchValidation = computed(() => {
  if (
    screeningFormDetails.screening_id_type === '' ||
    screeningFormDetails.screening_id_number === '' ||
    screeningFormDetails.screening_id_number === null
  ) {
    screeningFormDetails.setError({
      screening_id_number: 'The field is required',
    });
    return false;
  } else {
    if (screeningFormDetails.screening_id_type === 'emiratesId') {
      let validateEmirate = rules.emirateNumberCheck(
        screeningFormDetails.screening_id_number,
      );
      if (validateEmirate !== true) {
        screeningFormDetails.setError({ screening_id_number: validateEmirate });
        return false;
      }
    }
    if (screeningFormDetails.screening_id_type === 'passport') {
      let validatePassport = rules.passportNumberCheck(
        screeningFormDetails.screening_id_number,
      );
      if (validatePassport !== true) {
        screeningFormDetails.setError({
          screening_id_number: validatePassport,
        });
        return false;
      }
    }
  }
  screeningFormDetails.clearErrors('screening_id_number');
  return true;
});
const entitySearchValidation = computed(() => {
  if (
    screeningFormDetails.trade_license_no === '' ||
    screeningFormDetails.trade_license_no === null
  ) {
    screeningFormDetails.setError({
      trade_license_no: 'The field is required',
    });
    return false;
  }
  screeningFormDetails.clearErrors('trade_license_no');
  return true;
});
const searchResultData = ref(null);
const searchSuccessStatus = ref(false);
const insurerPortalSyncData = ref(null);
const searchInsuredDetails = customerType => {
  let searchInsuredValidation = [customerTypeEnum.Individual, ''].includes(
    customerType,
  )
    ? individualSearchValidation.value
    : entitySearchValidation.value;
  if (searchInsuredValidation) {
    loader.insuredSearch = true;
    let url = `/kyc/get-insured-details?customer_type=${customerType}&id_type=${screeningFormDetails.screening_id_type}&id_number=${screeningFormDetails.screening_id_number}&trade_license=${screeningFormDetails.trade_license_no}&quote_code=${quoteRequest.code}`;
    axios
      .get(url)
      .then(res => {
        if (res.data.status) {
          clearErrors();

          let response = res.data.response;

          // Store search results to pass to KYCDetails
          searchResultData.value = response;
          searchSuccessStatus.value = true;

          if (response.customer_type == customerTypeEnum.Individual) {
            IndividualDetailsFound.value = true;
            screeningFormDetails.insured_first_name = response.first_name;
            screeningFormDetails.insured_last_name = response?.last_name;
            screeningFormDetails.nationality_id = response?.nationality_id;
            screeningFormDetails.dob = response?.dob;
            screeningFormDetails.screening_gender = response?.gender;
          } else {
            EntityDetailsFound.value = true;
            screeningFormDetails.entity_id = response.id;
            screeningFormDetails.trade_license = response.trade_license_no;
            screeningFormDetails.company_name = response.company_name;
            screeningFormDetails.company_address = response.company_address;
            screeningFormDetails.industry_type_code =
              response.industry_type_code;
            screeningFormDetails.emirate_of_registration_id =
              response.emirate_of_registration_id;
          }

          notification.success({
            title: res.data.message,
            position: 'top',
          });
        } else {
          notification.error({
            title: res.data.message,
            position: 'top',
          });
        }
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => (loader.insuredSearch = false));
  }
};

function clearInsurerDetails(customerType) {
  screeningFormDetails.insured_first_name = null;
  screeningFormDetails.insured_last_name = null;
  screeningFormDetails.nationality_id = null;
  screeningFormDetails.dob = null;
  screeningFormDetails.screening_gender = null;
  screeningFormDetails.trade_license_no = null;
  screeningFormDetails.company_name = null;
  screeningFormDetails.company_address = null;
  screeningFormDetails.industry_type_code = null;
  screeningFormDetails.emirate_of_registration_id = null;
  screeningFormDetails.screening_id_type = null;
  screeningFormDetails.screening_id_number = null;
  updateScreeningIdType();

  // Clear search results
  searchResultData.value = null;
  searchSuccessStatus.value = false;

  customerType == customerTypeEnum.Individual
    ? (IndividualDetailsFound.value = false)
    : (EntityDetailsFound.value = false);
}
function screeningFormValidate() {
  clearErrors();

  let isValid = true;
  if (!screeningFormDetails.customer_type) {
    screeningFormDetails.setError(
      'customer_type',
      'Please select a Customer Type',
    );
    isValid = false;
  }
  // Individual customer validation
  document.getElementById('customer-type-field').scrollIntoView({
    behavior: 'smooth',
    block: 'nearest',
    inline: 'start',
  });
  if (
    screeningFormDetails.customer_type == customerTypeEnum.Individual ||
    screeningFormDetails.customer_type == null
  ) {
    if (screeningFormDetails.screening_id_type === 'emiratesId') {
      if (!screeningFormDetails.screening_id_number) {
        screeningFormDetails.setError(
          'screening_id_number',
          'This field is required',
        );
        isValid = false;
      } else {
        let validateEmirate = rules.emirateNumberCheck(
          screeningFormDetails.screening_id_number,
        );
        if (validateEmirate !== true) {
          screeningFormDetails.setError('screening_id_number', validateEmirate);
          isValid = false;
        }
      }
    } else if (screeningFormDetails.screening_id_type === 'passport') {
      if (!screeningFormDetails.screening_id_number) {
        screeningFormDetails.setError(
          'screening_id_number',
          'This field is required',
        );
        isValid = false;
      } else {
        let validatePassport = rules.passportNumberCheck(
          screeningFormDetails.screening_id_number,
        );
        if (validatePassport !== true) {
          screeningFormDetails.setError(
            'screening_id_number',
            validatePassport,
          );
          isValid = false;
        }
      }
    }

    if (!screeningFormDetails.insured_first_name) {
      screeningFormDetails.setError(
        'insured_first_name',
        'This field is required',
      );
      isValid = false;
    } else {
      let nameCheck = rules.nameCheck(screeningFormDetails.insured_first_name);
      if (nameCheck !== true) {
        screeningFormDetails.setError('insured_first_name', nameCheck);
        isValid = false;
      }
    }

    if (!screeningFormDetails.insured_last_name) {
      screeningFormDetails.setError(
        'insured_last_name',
        'This field is required',
      );
      isValid = false;
    } else {
      let nameCheck = rules.nameCheck(screeningFormDetails.insured_last_name);
      if (nameCheck !== true) {
        screeningFormDetails.setError('insured_last_name', nameCheck);
        isValid = false;
      }
    }

    if (
      !screeningFormDetails.nationality_id ||
      screeningFormDetails.nationality_id == null
    ) {
      validateNationality.value = true;
      console.log('Nationality error set:', screeningFormDetails.errors);
      isValid = false;
    }

    if (!screeningFormDetails.dob) {
      screeningFormDetails.setError('dob', 'Date of birth is required');
      isValid = false;
    }

    if (!screeningFormDetails.screening_gender) {
      screeningFormDetails.setError('screening_gender', 'Gender is required');
      isValid = false;
    }
  }
  // Entity customer validation
  else if (screeningFormDetails.customer_type == customerTypeEnum.Entity) {
    if (!screeningFormDetails.entity_type) {
      screeningFormDetails.setError('entity_type', 'This field is required');
      isValid = false;
    }

    if (!screeningFormDetails.trade_license_no) {
      screeningFormDetails.setError(
        'trade_license_no',
        'This field is required',
      );
      isValid = false;
    }

    if (!screeningFormDetails.company_name) {
      screeningFormDetails.setError('company_name', 'This field is required');
      isValid = false;
    }

    if (!screeningFormDetails.company_address) {
      screeningFormDetails.setError(
        'company_address',
        'This field is required',
      );
      isValid = false;
    }

    if (!screeningFormDetails.industry_type_code) {
      screeningFormDetails.setError(
        'industry_type_code',
        'This field is required',
      );
      isValid = false;
    }

    if (!screeningFormDetails.emirate_of_registration_id) {
      screeningFormDetails.setError(
        'emirate_of_registration_id',
        'This field is required',
      );
      isValid = false;
    }
  }
  if (
    (page.props.quoteType.id === page.props.quoteTypeIdEnum.Car ||
      page.props.quoteType.id === page.props.quoteTypeIdEnum.Bike) &&
    !chassisNumberDisabled.value &&
    !showVehicleAndDrvicerDetails.value
  ) {
    if (!screeningFormDetails.chassis_number) {
      screeningFormDetails.setError('chassis_number', 'This field is required');
      isValid = false;
    } else {
      let chassisCheck = rules.chassisNumberCheck(
        screeningFormDetails.chassis_number,
      );
      if (chassisCheck !== true) {
        screeningFormDetails.setError('chassis_number', chassisCheck);
        isValid = false;
      }
    }
  }

  return isValid;
}
const submitScreeningForm = isValid => {
  if (screeningFormValidate()) {
    // updateFormDetails();
    screeningFormDetails.get(`${quoteRequest.id}/quoteUpdate`, {
      preserveScroll: true,
      onError: errors => {
        notification.error({
          title: errors.error || 'Quote not updated',
          position: 'top',
        });
      },
      onSuccess: response => {
        if (response.props.flash.success?.length === 0) {
          notification.success({
            title: 'Quote is updated',
            position: 'top',
          });
        }
      },
    });
  } else {
    console.log('Validation Failed');
  }
};
const updateScreeningType = () => {
  if (page.props.screeningType == customerTypeEnum.EntityShort) {
    isScreeningIndividual.value = false;
    if (page.props.insuredDetails?.insured?.id) {
      screeningFormDetails.customer_type = customerTypeEnum.Entity;
    }
  } else {
    if (screeningFormDetails?.insured_first_name !== null) {
      screeningFormDetails.customer_type = customerTypeEnum.Individual;
    }
  }
};

function updateScreeningIdType() {
  if (
    screeningFormDetails.screening_id_type === '' ||
    screeningFormDetails.screening_id_type === null
  ) {
    screeningFormDetails.screening_id_type =
      page.props.quoteType.id === page.props.quoteTypeIdEnum.Travel &&
      quoteRequest.direction_code === 'travelUaeInbound'
        ? 'passport'
        : 'emiratesId';
  }
}

watch(() => {
  updateScreeningIdType();
});
watch(
  () => screeningFormDetails.nationality_id,
  newValue => {
    if (newValue !== null) {
      validateNationality.value = false;
    }
  },
);
onMounted(() => {
  modalHeaderMessage();
  // setFieldsByCustomerType();
  updateScreeningType();
});

const handleModalClose = () => {
  screeningFormDetails.customer_type = oldCustomerType.value;
  customerTypeConfirmationModel.value = false;
};

const updateInsurerPortalSyncData = data => {
  insurerPortalSyncData.value = data;
};

const [SubmitForScreeningBtnTemplate, SubmitForScreeningBtnReuseTemplate] =
  createReusableTemplate();
</script>
<template>
  <x-modal
    v-model="showModal"
    size="xl"
    title="Update and Verify"
    show-close
    backdrop
    is-form
    persistent
    @submit="submitScreeningForm"
  >
    <template v-if="showVehicleAndDrvicerDetails">
      <AdditionalVehicleTransactionDetails
        :insurerPortalSyncData="insurerPortalSyncData"
        :rta_transaction_types="rta_transaction_types"
        :rta_field_configurations="rta_field_configurations"
        :rta_validation_summaries="rta_validation_summaries"
      />
      <x-divider class="mb-4 mt-4" />
      <AdditionalDriverDetails :insurerPortalSyncData="insurerPortalSyncData" />
      <x-divider class="mb-4 mt-4" />
    </template>

    <x-field label="Customer Type" required>
      <div class="grid md:grid-cols-3" id="customer-type-field">
        <x-select
          v-model="screeningFormDetails.customer_type"
          :options="customerTypeOptions"
          placeholder="Select Customer Type"
          :rules="[isRequired]"
          :error="screeningFormDetails.errors.customer_type"
          @update:model-value="customerTypeConfirmation"
        />
      </div>
    </x-field>
    <x-divider class="mb-4 mt-1" />
    <p class="text-center mb-10">{{ headerMessage }}</p>
    <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
      <!-- Form Details as per the Individual Customer -->
      <template v-if="isScreeningIndividual">
        <x-field label="ID Type" required>
          <x-select
            v-model="screeningFormDetails.screening_id_type"
            :options="documentIDTypeForScreening"
            placeholder="ID Type"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="ID number" required>
          <!-- Emirates ID Should be get from Enums -->
          <template
            v-if="screeningFormDetails.screening_id_type === 'emiratesId'"
          >
            <x-input
              v-model="screeningFormDetails.screening_id_number"
              placeholder="xxx-xxxx-xxxxxxx-x"
              :rules="[isRequired, rules.emirateNumberCheck]"
              @input="applyScreeningIdNumMasking"
              :error="screeningFormDetails.errors.screening_id_number"
            />
          </template>
          <template v-else>
            <x-input
              v-model="screeningFormDetails.screening_id_number"
              :placeholder="
                screeningFormDetails.screening_id_type === ''
                  ? 'Enter ID Number'
                  : 'Enter Passport Number'
              "
              :rules="[isRequired, rules.passportNumberCheck]"
              @blur="validatePassportNumber('blur')"
              @keypress="validatePassportNumber('keypress')"
              :error="screeningFormDetails.errors.screening_id_number"
            />
          </template>
        </x-field>
        <template v-if="!IndividualDetailsFound">
          <x-field>
            <x-button
              @click.prevent="searchInsuredDetails(customerTypeEnum.Individual)"
              class="focus:ring-2 focus:ring-black focus:ring-opacity-60"
              size="sm"
              color="primary"
              :loading="loader.insuredSearch"
            >
              Search
            </x-button>
          </x-field>
        </template>
        <template v-else>
          <div class="text-left space-x-4">
            <x-button
              size="sm"
              color="info"
              @click.prevent="clearInsurerDetails(customerTypeEnum.Individual)"
            >
              Cancel
            </x-button>
          </div>
        </template>
        <x-field label="Insured First Name" required>
          <x-input
            v-model="screeningFormDetails.insured_first_name"
            :rules="[isRequired, rules.nameCheck]"
            placeholder="Insured First Name"
            type="text"
            class="w-full"
            :error="screeningFormDetails.errors.insured_first_name"
          />
        </x-field>
        <x-field label="Insured Last Name" required>
          <x-input
            v-model="screeningFormDetails.insured_last_name"
            :rules="[isRequired, rules.nameCheck]"
            placeholder="Insured Last Name"
            type="text"
            class="w-full"
            :error="screeningFormDetails.errors.insured_last_name"
          />
        </x-field>
        <x-field label="Nationality" required>
          <ComboBox
            :single="true"
            v-model="screeningFormDetails.nationality_id"
            placeholder="Select Nationality"
            :options="nationalitiesOptions"
            class="w-full"
            :hasError="validateNationality"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="screeningFormDetails.dob"
            :rules="[isRequired]"
            placeholder="Date of Birth"
            class="w-full"
            :error="screeningFormDetails.errors.dob"
          />
        </x-field>
        <x-field label="Gender" required>
          <x-select
            v-model="screeningFormDetails.screening_gender"
            :rules="[isRequired]"
            :options="gender"
            placeholder="Gender"
            :error="screeningFormDetails.errors.screening_gender"
          />
        </x-field>
        <div class="flex gap-5 align-center">
          <p>Is the insured the payer?</p>
          <x-form-group v-model="isInsuredPayer">
            <x-radio :value="1" label="Yes" />
            <x-radio :value="0" label="No" />
          </x-form-group>
        </div>
        <x-field
          v-if="
            page.props.quoteType.id === page.props.quoteTypeIdEnum.Car ||
            page.props.quoteType.id === page.props.quoteTypeIdEnum.Bike ||
            page.props.quoteType.id === page.props.quoteTypeIdEnum.Home
          "
          label="Email in GIG Portal"
        >
          <x-input
            v-model="screeningFormDetails.get_quote_email_gig"
            placeholder="Email in GIG Portal"
            type="text"
            class="w-full"
          />
        </x-field>
      </template>
      <!-- Form Details as per the Entity -->
      <template v-else>
        <x-field label="Entity Type" required>
          <x-select
            v-model="screeningFormDetails.entity_type"
            :options="entityTypes"
            placeholder="Entity Type"
            :rules="[isRequired]"
            :error="screeningFormDetails.errors.entity_type"
          />
        </x-field>
        <x-field label="Trade License No" required>
          <x-input
            v-model="screeningFormDetails.trade_license_no"
            :rules="[isRequired]"
            placeholder="Trade License No"
            type="text"
            class="w-full"
            :error="screeningFormDetails.errors.trade_license_no"
          />
        </x-field>
        <template v-if="!EntityDetailsFound">
          <x-field>
            <x-button
              @click.prevent="searchInsuredDetails(customerTypeEnum.Entity)"
              class="focus:ring-2 focus:ring-black focus:ring-opacity-60"
              size="sm"
              color="primary"
              :loading="loader.insuredSearch"
            >
              Search
            </x-button>
          </x-field>
        </template>
        <template v-else>
          <div class="text-left space-x-4">
            <x-button
              size="sm"
              color="info"
              @click.prevent="clearInsurerDetails(customerTypeEnum.Entity)"
            >
              Cancel
            </x-button>
          </div>
        </template>
        <x-field label="Company Name" required>
          <x-input
            v-model="screeningFormDetails.company_name"
            placeholder="Company Name"
            type="text"
            class="w-full"
            :rules="[isRequired]"
            :error="screeningFormDetails.errors.company_name"
          />
        </x-field>
        <x-field label="Company Address" required>
          <x-input
            v-model="screeningFormDetails.company_address"
            placeholder="Company Address"
            type="text"
            class="w-full"
            :rules="[isRequired]"
            :error="screeningFormDetails.errors.company_address"
          />
        </x-field>
        <x-field label="Industry Type" required>
          <x-select
            v-model="screeningFormDetails.industry_type_code"
            :options="industryTypeOptions"
            placeholder="Industry Type"
            class="w-full"
            :rules="[isRequired]"
            :error="screeningFormDetails.errors.industry_type_code"
          />
        </x-field>
        <x-field label="Emirates of Registration" required>
          <x-select
            v-model="screeningFormDetails.emirate_of_registration_id"
            :options="emiratesOfRegistrationOptions"
            placeholder="Emirates of Registration"
            type="text"
            class="w-full"
            :rules="[isRequired]"
            :error="screeningFormDetails.errors.emirate_of_registration_id"
          />
        </x-field>
      </template>
    </dl>
    <x-divider
      v-if="
        page.props.quoteType.id === page.props.quoteTypeIdEnum.Car ||
        page.props.quoteType.id === page.props.quoteTypeIdEnum.Bike
      "
      class="mb-4 mt-1"
    />
    <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
      <div
        v-if="
          (page.props.quoteType.id === page.props.quoteTypeIdEnum.Car ||
            page.props.quoteType.id === page.props.quoteTypeIdEnum.Bike) &&
          !showVehicleAndDrvicerDetails
        "
      >
        <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">
            Additional Vehicle Details
          </h3>
        </div>
        <template v-if="chassisNumberDisabled">
          <div>
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-700 text-sm underline decoration-dotted decoration-primary-600"
              >
                Chassis Number <sup class="text-red-500">*</sup>
              </label>
              <template #tooltip>
                This lead is now locked as the policy has been booked. If
                changes are needed, go to 'Send Update', select 'Add Update',
                and choose 'Cancellation from Inception and Reissuance'
              </template>
              <x-input
                :disabled="chassisNumberDisabled"
                v-model="screeningFormDetails.chassis_number"
                placeholder="Chassis Number"
                type="text"
                class="w-full"
              />
            </x-tooltip>
          </div>
        </template>
        <x-field label="Chassis Number" required v-else>
          <x-input
            v-model="screeningFormDetails.chassis_number"
            placeholder="Enter Chassis Number"
            type="text"
            class="w-full"
            :rules="[isRequired, rules.chassisNumberCheck]"
            @blur="chassisNumberValidate('blur')"
            @keypress="chassisNumberValidate('keypress')"
            :error="screeningFormDetails.errors.chassis_number"
          />
        </x-field>
      </div>
    </dl>

    <MembersDetails
      :customerType="
        isScreeningIndividual
          ? customerTypeEnum.Individual
          : customerTypeEnum.Entity
      "
      :isPayerDetails="false"
    />
    <x-divider class="mb-4 mt-4" />
    <!-- This Component is used for Payer Details -->
    <MembersDetails
      :customerType="
        isScreeningIndividual
          ? customerTypeEnum.Individual
          : customerTypeEnum.Entity
      "
      :isPayerDetails="true"
    />
    <SubmitForScreeningBtnTemplate>
      <x-button
        class="focus:ring-2 focus:ring-black focus:ring-opacity-60"
        size="sm"
        color="success"
        type="submit"
        :loading="screeningFormDetails.processing"
        :disabled="!can(permissionsEnum.AMLList)"
      >
        Submit For AML Screening
      </x-button>
    </SubmitForScreeningBtnTemplate>
    <div class="flex justify-center my-5">
      <x-tooltip v-if="!can(permissionsEnum.AMLList)" placement="bottom">
        <SubmitForScreeningBtnReuseTemplate />
        <template #tooltip
          >You don't have permission to edit this section</template
        >
      </x-tooltip>
      <template v-else>
        <SubmitForScreeningBtnReuseTemplate />
      </template>
    </div>
    <x-divider class="mb-4 mt-1" />
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">KYC Details</h3>
    </div>
    <KYCDetails
      :searchData="searchResultData"
      :searchSuccess="searchSuccessStatus"
      :customerType="screeningFormDetails.customer_type"
      @update:insurerPortalSyncData="updateInsurerPortalSyncData"
    />
    <x-modal
      v-model="customerTypeConfirmationModel"
      size="lg"
      backdrop
      persistent
    >
      <div class="text-lg text-center">
        <span>{{ confirmationMessage }}</span>
      </div>
      <div class="mt-2 text-center">
        <x-button
          size="sm"
          color="orange"
          class="mt-4 text-center mr-2"
          @click="updateScreeningDetails"
        >
          <span>Confirm</span>
        </x-button>
        <x-button
          size="sm"
          color="gray"
          class="mt-4 text-center"
          @click="handleModalClose"
        >
          <span>Close</span>
        </x-button>
      </div>
    </x-modal>
  </x-modal>
</template>
