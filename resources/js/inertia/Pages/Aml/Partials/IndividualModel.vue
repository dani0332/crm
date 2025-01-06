<script setup>
import MemberDetailsModel from './MemberDetailsModel.vue';
import PayerDetails from './PayerDetails.vue';
import UBODetailsModels from './UBODetailsModels.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quoteType: Object,
  quoteDetails: Object,
  entityDetails: Object,
  nationalities: Object,
  emirates: Object,
  membersDetails: Object,
  uboDetails: Object,
  customerTypeEnum: Object,
  residentStatuses: Object,
  lookups: Object,
  quoteAmlStatus: Number,
  insuredPersonDetails: Object,
  cardHolderName: Object,
  kycLogs: Array,
});

const page = usePage();
const loader = ref({
  search: false,
});

const isEmptyNationality = ref(false);
const industryTypeCode = ref(false);
const emirateRegistrationId = ref(false);

const emit = defineEmits(['update:modelValue', 'loaded']);
const notification = useToast();
const { isRequired } = useRules();
const showModal = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

const modals = reactive({
  insuredDetailConfirmation: false,
  entityView: false,
});

const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const emirateRegistrationOptions = computed(() => {
  return props.emirates.map(emirate => ({
    value: emirate.id,
    label: emirate.text,
  }));
});

const industryTypeOptions = computed(() => {
  return props?.lookups?.company_type.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const validateCustomerFields = ref(false);

const customerAmlOnly = () => {
  validateCustomerFields.value = false;
  return true;
};

const gender = computed(() => {
  return [
    { value: 'Male', label: 'Male' },
    { value: 'Female', label: 'Female' },
  ];
});

const insuredFormDetails = useForm({
  customer_id: props.quoteDetails.customer_id,
  customer_type: props.customerTypeEnum.Individual,
  quote_type: props.quoteType.code,

  place_of_birth: props.quoteDetails?.customer?.detail?.place_of_birth ?? null,
  country_of_residence:
    props.quoteDetails?.customer?.detail?.country_of_residence ?? null,
  residential_address:
    props.quoteDetails?.customer?.detail?.residential_address ?? null,
  residential_status:
    props.quoteDetails?.customer?.detail?.residential_status ?? null,
  id_type: props.quoteDetails?.customer?.detail?.id_type ?? null,
  id_issuance_date:
    props.quoteDetails?.customer?.detail?.id_issuance_date ?? null,
  mode_of_contact:
    props.quoteDetails?.customer?.detail?.mode_of_contact ?? null,
  transaction_value:
    props.quoteDetails?.customer?.detail?.transaction_value ?? null,
  mode_of_delivery:
    props.quoteDetails?.customer?.detail?.mode_of_delivery ?? null,
  employment_sector:
    props.quoteDetails?.customer?.detail?.employment_sector ?? null,
  customer_tenure:
    props.quoteDetails?.customer?.detail?.customer_tenure ?? null,

  legal_structure: props.entityDetails?.entity?.legal_structure ?? null,
  country_of_corporation:
    props.entityDetails?.entity?.country_of_corporation ?? null,
  website: props.entityDetails?.entity?.website ?? null,
  entity_id_type: props.entityDetails?.entity?.id_type ?? null,
  entity_id_issuance_date:
    props.entityDetails?.entity?.id_issuance_date ?? null,
  id_expiry_date: props.entityDetails?.entity?.id_expiry_date ?? null,
  id_issuance_place: props.entityDetails?.entity?.id_issuance_place ?? null,
  id_issuance_authority:
    props.entityDetails?.entity?.id_issuance_authority ?? null,

  insured_id: props.insuredPersonDetails?.insured?.id ?? null,
  screening_id_type: props.insuredPersonDetails?.insured?.id_type ?? null,
  screening_id_number: props.insuredPersonDetails?.insured?.id_number ?? null,

  insured_first_name:
    props.insuredPersonDetails?.insured?.first_name ??
    (props.quoteType.code === 'Health'
      ? props.membersDetails[0]?.first_name
      : null),
  insured_last_name:
    props.insuredPersonDetails?.insured?.last_name ??
    (props.quoteType.code === 'Health'
      ? props.membersDetails[0]?.last_name
      : null),
  nationality_id: props.insuredPersonDetails?.insured.nationality_id ?? null,
  dob: props.insuredPersonDetails?.insured.dob ?? null,
  screening_gender: props.insuredPersonDetails?.insured?.gender ?? null,
  chassis_number:
    props.quoteDetails?.car_quote_request_detail?.chassis_number ?? null,

  entity_id: props.entityDetails?.entity?.id,
  trade_license_no: props.entityDetails?.entity?.trade_license_no,
  company_name: props.entityDetails?.entity?.company_name,
  company_address: props.entityDetails?.entity?.company_address,
  entity_type_code: props.entityDetails?.entity?.entity_type_code ?? 'Parent',
  industry_type_code: props.entityDetails?.entity?.industry_type_code ?? null,
  emirate_of_registration_id:
    props.entityDetails?.entity?.emirate_of_registration_id ?? null,
});

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
    return pattern.test(v) || 'Enter the correct EID number format';
  },

  passportNumberCheck: v => {
    const regex = /^[A-Za-z0-9]+$/;
    const lengthValid = v?.length >= 8 && v?.length <= 9;
    const isAlphanumeric = regex.test(v);
    return (
      (lengthValid && isAlphanumeric) ||
      'The entered value does not meet the required length of 8 to 9 characters. Please check and confirm.'
    );
  },
};

const submitQuoteUpdateForm = isValid => {
  insuredFormDetails.get(`${props.quoteDetails.id}/quoteUpdate`, {
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
      if (
        typeof response.props.flash.info !== 'undefined' &&
        response.props.flash.info?.length > 0
      ) {
        notification.error({
          title:
            response.props.flash.info?.message ||
            'GIG server connection issue. Please check API logs for details of the error',
          position: 'top',
        });
      }
    },
  });
};

const updateCustomerDetails = isValid => {
  let customerDetailData = {
    customer_id: insuredFormDetails.customer_id,
    customer_type: insuredFormDetails.customer_type,
    quote_type: insuredFormDetails.quote_type,
    place_of_birth: insuredFormDetails.place_of_birth,
    country_of_residence: insuredFormDetails.country_of_residence,
    residential_address: insuredFormDetails.residential_address,
    residential_status: insuredFormDetails.residential_status,
    id_type: insuredFormDetails.id_type,
    id_issuance_date: insuredFormDetails.id_issuance_date,
    mode_of_contact: insuredFormDetails.mode_of_contact,
    transaction_value: insuredFormDetails.transaction_value,
    mode_of_delivery: insuredFormDetails.mode_of_delivery,
    employment_sector: insuredFormDetails.employment_sector,
    customer_tenure: insuredFormDetails.customer_tenure,
  };

  axios
    .post(
      `${props.quoteDetails.id}/update-customer-details`,
      customerDetailData,
    )
    .then(res => {
      if (res.status) {
        notification.success({
          title: 'Customer Details Updated',
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      }
      linkLoader.value = false;
    })
    .catch(err => {
      console.log(err);
    });
};

const insuredDetailsSubmit = isValid => {
  if (insuredFormDetails.customer_type === props.customerTypeEnum.Entity) {
    industryTypeCode.value = insuredFormDetails.industry_type_code === null;
    emirateRegistrationId.value =
      insuredFormDetails.emirate_of_registration_id === null;
  } else {
    isEmptyNationality.value =
      insuredFormDetails.nationality_id === null &&
      validateCustomerFields.value === false;
  }

  if (!isValid) return;

  if (
    insuredFormDetails.customer_type == props.customerTypeEnum.Individual &&
    validateCustomerFields.value == true
  ) {
    //validate all fields and update only
    updateCustomerDetails();
  }

  if (
    insuredFormDetails.customer_type == props.customerTypeEnum.Individual &&
    validateCustomerFields.value == false
  ) {
    //validate basic information and run aml
    modals.insuredDetailConfirmation = true;
  }

  if (insuredFormDetails.customer_type == props.customerTypeEnum.Entity) {
    submitQuoteUpdateForm();
  }
};

const entityDetailsFound = ref(false);
const linkLoader = ref(false);
const switchToEntityView = () => {
  insuredFormDetails.customer_type = props.customerTypeEnum.Entity;
  modals.insuredDetailConfirmation = false;
  showModal.value = false;
  modals.entityView = true;
};

const tradeLicenseEntity = reactive({
  entity_id: null,
  trade_license: null,
  company_name: null,
  company_address: null,
});

const searchByTradeLicense = () => {
  loader.value.search = true;
  let url = `/kyc/aml-fetch-entity?trade_license=${insuredFormDetails.trade_license_no}`;
  axios
    .get(url)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;
        entityDetailsFound.value = true;
        tradeLicenseEntity.entity_id = response.id;
        tradeLicenseEntity.trade_license = response.trade_license_no;
        tradeLicenseEntity.company_name = response.company_name;
        tradeLicenseEntity.company_address = response.company_address;

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
    .finally(() => (loader.value.search = false));
};

const linkEntity = () => {
  linkLoader.value = true;
  let entityDetails = {
    quote_type_id: props.quoteType.id,
    quote_request_id: props.quoteDetails.id,
    entity_id: tradeLicenseEntity.entity_id,
  };
  axios
    .post(route('link-entity-details'), entityDetails)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;

        insuredFormDetails.trade_license_no = response.trade_license_no;
        insuredFormDetails.company_name = response.company_name;
        insuredFormDetails.company_address = response.company_address;
        insuredFormDetails.entity_type_code =
          response?.quote_request_entity_mapping[0]?.entity_type_code ?? '';
        insuredFormDetails.industry_type_code = response.industry_type_code;
        insuredFormDetails.emirate_of_registration_id =
          response.emirate_of_registration_id;

        notification.success({
          title: res.data.message,
          position: 'top',
        });
      }
      linkLoader.value = false;
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => (entityDetailsFound.value = false));
};

const insuredPersonDetailsFound = ref(false);
const insuredSearchValidation = computed(() => {
  if (
    insuredFormDetails.screening_id_type === '' ||
    insuredFormDetails.screening_id_number === '' ||
    insuredFormDetails.screening_id_number === null
  ) {
    insuredFormDetails.setError({
      screening_id_number: 'The field is required',
    });
    return false;
  } else {
    if (insuredFormDetails.screening_id_type === 'emiratesId') {
      let validateEmirate = rules.emirateNumberCheck(
        insuredFormDetails.screening_id_number,
      );
      if (validateEmirate !== true) {
        insuredFormDetails.setError({ screening_id_number: validateEmirate });
        return false;
      }
    }

    if (insuredFormDetails.screening_id_type === 'passport') {
      let validatePassport = rules.passportNumberCheck(
        insuredFormDetails.screening_id_number,
      );
      if (validatePassport !== true) {
        insuredFormDetails.setError({ screening_id_number: validatePassport });
        return false;
      }
    }
  }
  insuredFormDetails.clearErrors('screening_id_number');
  return true;
});

const submitInsuredPersonSearch = () => {
  if (insuredSearchValidation.value) {
    loader.value.search = true;
    let url = `/kyc/get-insured-person?id_type=${insuredFormDetails.screening_id_type}&id_number=${insuredFormDetails.screening_id_number}`;
    axios
      .get(url)
      .then(res => {
        if (res.data.status) {
          let response = res.data.response;
          insuredPersonDetailsFound.value = true;

          insuredFormDetails.insured_id = response?.id;
          insuredFormDetails.insured_first_name = response?.first_name;
          insuredFormDetails.insured_last_name = response?.last_name;
          insuredFormDetails.nationality_id = response?.nationality_id;
          insuredFormDetails.dob = response?.dob;
          insuredFormDetails.screening_gender = response?.gender;

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
      .finally(() => (loader.value.search = false));
  }
};

function clearInsuredPersonDetails() {
  insuredFormDetails.insured_first_name = '';
  insuredFormDetails.insured_last_name = '';
  insuredFormDetails.nationality_id = null;
  insuredFormDetails.dob = null;
  insuredFormDetails.gender = null;

  insuredPersonDetailsFound.value = false;
}

const is_insured = ref(0);
const documentIDTypeForScreening = computed(() => {
  return props.lookups.id_type
    ?.filter(docIDTypeScreening =>
      ['emiratesId', 'passport'].includes(docIDTypeScreening.code),
    )
    ?.map(docIDTypeScreening => ({
      value: docIDTypeScreening.code,
      label: docIDTypeScreening.text,
    }));
});

const applyScreeningIdNumMasking = () => {
  let screeningIdNumber = insuredFormDetails.screening_id_number.replace(
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

  insuredFormDetails.screening_id_number = screeningIdNumber;
};

const validatePassportNumber = type => {
  const regex = /^[A-Za-z0-9]+$/; // Allow only alphanumeric characters
  const lengthValid =
    insuredFormDetails.screening_id_number?.length >= 8 &&
    insuredFormDetails.screening_id_number?.length <= 9;
  const isAlphanumeric = regex.test(insuredFormDetails.screening_id_number);

  if (type === 'blur') {
    if (
      insuredFormDetails.screening_id_number &&
      (!lengthValid || !isAlphanumeric)
    ) {
      insuredFormDetails.setError({
        screening_id_number:
          'The entered value does not meet the required length of 8 to 9 characters. Please check and confirm.',
      });
      return true;
    } else {
      insuredFormDetails.clearErrors('screening_id_number');
      return false;
    }
  } else if (type === 'change') {
    if (
      insuredFormDetails.screening_id_number &&
      lengthValid &&
      isAlphanumeric
    ) {
      insuredFormDetails.clearErrors('screening_id_number');
    }
  }
};

const chassisNumberDisabled = computed(() => {
  let disallowedStatus = [
    page.props.quoteStatusEnums.PolicySentToCustomer,
    page.props.quoteStatusEnums.PolicyBooked,
  ];
  return disallowedStatus.includes(props?.quoteDetails?.quote_status_id);
});

const chassisNumberValidate = type => {
  const regex = /^[A-Za-z0-9]+$/; // Allow only alphanumeric characters
  const lengthValid =
    insuredFormDetails.chassis_number.length >= 8 &&
    insuredFormDetails.chassis_number.length <= 17;
  const isAlphanumeric = regex.test(insuredFormDetails.chassis_number);

  if (type === 'blur') {
    if (
      insuredFormDetails.chassis_number &&
      (!lengthValid || !isAlphanumeric)
    ) {
      insuredFormDetails.setError({
        chassis_number:
          'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm.',
      });
      return true;
    } else {
      insuredFormDetails.clearErrors('chassis_number');
      return false;
    }
  } else if (type === 'change') {
    if (insuredFormDetails.chassis_number && lengthValid && isAlphanumeric) {
      insuredFormDetails.clearErrors('chassis_number'); // Clear error only if validation passes
    }
  }
};

watch(
  props.membersDetails,
  () => {
    is_insured.value = !props.membersDetails.some(x => x.is_payer);
  },
  { immediate: true },
);

watch(() => {
  if (
    insuredFormDetails.screening_id_type === '' ||
    insuredFormDetails.screening_id_type === null
  ) {
    insuredFormDetails.screening_id_type =
      props.quoteType.id === page.props.quoteTypeIdEnum.Travel &&
      props.quoteDetails.direction_code === 'travelUaeInbound'
        ? 'passport'
        : 'emiratesId';
  }
});
</script>

<template>
  <div>
    <!-- Individual Type Insured Form -->
    <x-modal
      v-model="showModal"
      size="xl"
      title="Update and Verify"
      show-close
      backdrop
      is-form
    >
      <p class="text-center mb-10">
        Please confirm the Name, Nationality, and Date of Birth of the insured
        person(s) as per the Emirates ID
      </p>

      <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
        <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
          <x-field label="ID type" required>
            <x-select
              v-model="insuredFormDetails.screening_id_type"
              :options="documentIDTypeForScreening"
              placeholder="ID type"
              :rules="[isRequired]"
            />
          </x-field>
          <x-field label="ID number" required>
            <template
              v-if="insuredFormDetails.screening_id_type === 'emiratesId'"
            >
              <x-input
                v-model="insuredFormDetails.screening_id_number"
                placeholder="xxx-xxxx-xxxxxxx-x"
                :rules="[isRequired, rules.emirateNumberCheck]"
                @input="applyScreeningIdNumMasking"
                :error="insuredFormDetails.errors.screening_id_number"
              />
            </template>
            <template v-else>
              <x-input
                v-model="insuredFormDetails.screening_id_number"
                :placeholder="
                  insuredFormDetails.screening_id_type === ''
                    ? 'Enter ID Number'
                    : 'Enter Passport Number'
                "
                :rules="[isRequired, rules.passportNumberCheck]"
                @blur="validatePassportNumber('blur')"
                @keypress="validatePassportNumber('change')"
                :error="insuredFormDetails.errors.screening_id_number"
              />
            </template>
          </x-field>
          <template v-if="!insuredPersonDetailsFound">
            <x-field>
              <x-button
                @click.prevent="submitInsuredPersonSearch"
                size="sm"
                color="primary"
                :loading="loader.search"
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
                @click.prevent="clearInsuredPersonDetails"
              >
                Cancel
              </x-button>
            </div>
          </template>
          <x-field label="Insured First Name">
            <x-input
              v-model="insuredFormDetails.insured_first_name"
              :rules="[isRequired, rules.nameCheck]"
              placeholder="Insured First Name"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Insured Last Name">
            <x-input
              v-model="insuredFormDetails.insured_last_name"
              :rules="[isRequired, rules.nameCheck]"
              placeholder="Insured Last Name"
              type="text"
              class="w-full"
            />
          </x-field>

          <x-field label="Nationality">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.nationality_id"
              :hasError="isEmptyNationality"
              placeholder="Select Nationality"
              :options="nationalitiesOptions"
              class="w-full"
            />
          </x-field>
          <x-field label="Date of Birth">
            <DatePicker
              v-model="insuredFormDetails.dob"
              :rules="[isRequired]"
              placeholder="Date of Birth"
              class="w-full"
            />
          </x-field>
          <x-field label="Gender" required>
            <x-select
              v-model="insuredFormDetails.screening_gender"
              :options="gender"
              placeholder="Gender"
              :rules="[isRequired]"
            />
          </x-field>
          <div class="flex gap-5 align-center">
            <p>Is the insured the payer?</p>
            <x-form-group v-model="is_insured">
              <x-radio :value="1" label="Yes" />
              <x-radio :value="0" label="No" />
            </x-form-group>
          </div>
        </dl>

        <x-divider class="mb-4 mt-1" />
        <div v-if="quoteType.id === page.props.quoteTypeIdEnum.Car">
          <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
            <h3 class="font-semibold text-primary-800 text-lg">
              Additional Vehicle Details
            </h3>
          </div>

          <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
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
                    changes are needed, go to 'Send Update', select 'Add
                    Update', and choose 'Cancellation from Inception and
                    Reissuance'
                  </template>
                </x-tooltip>
                <x-input
                  :disabled="chassisNumberDisabled"
                  v-model="insuredFormDetails.chassis_number"
                  placeholder="Chassis Number"
                  type="text"
                  class="w-full"
                />
              </div>
            </template>
            <x-field label="Chassis Number" required v-else>
              <x-input
                v-model="insuredFormDetails.chassis_number"
                placeholder="Enter Chassis Number"
                type="text"
                class="w-full"
                :rules="[isRequired, rules.chassisNumberCheck]"
                @blur="chassisNumberValidate('blur')"
                @keypress="chassisNumberValidate('change')"
                :error="insuredFormDetails.errors.chassis_number"
              />
            </x-field>
          </dl>
          <x-divider class="mb-4 mt-1" />
        </div>

        <MemberDetailsModel
          :quoteType="quoteType"
          :quoteDetails="quoteDetails"
          :nationalities="nationalities"
          :membersDetails="membersDetails"
          :memberRelations="props.lookups.member_relation"
          :customerType="props.customerTypeEnum.Individual"
        />

        <x-divider class="mb-4 mt-4" />

        <PayerDetails
          :quoteType="quoteType"
          :quoteDetails="quoteDetails"
          :nationalities="nationalities"
          :membersDetails="membersDetails"
          :memberRelations="props.lookups.member_relation"
          :customerType="props.customerTypeEnum.Individual"
          :cardHolderName="cardHolderName"
        />
        <div class="my-5 flex justify-center">
          <x-button
            size="sm"
            color="success"
            type="submit"
            @click="customerAmlOnly"
          >
            Submit For AML Screening
          </x-button>
        </div>

        <x-divider class="mb-4 mt-4" />
        <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">KYC Details</h3>
        </div>

        <KycIndividualModal
          :roles="$page.props.rolesEnum"
          :quote="quoteDetails"
          :countryList="nationalities"
          :aml-quote-status="props.quoteAmlStatus"
          :nationalities="nationalities"
          :modelType="quoteType?.code"
          :idDocumentType="props.lookups.id_type"
          :modeOfContact="props.lookups.mode_of_contact"
          :modeOfDelivery="props.lookups.mode_of_delivery"
          :professional-title="props.lookups.professional_title"
          :employmentSectors="props.lookups.employment_sector"
          :residentialStatus="props.lookups.resident_status"
          :companyPosition="props.lookups.company_position"
          :entity-details="props.entityDetails"
          :customer-details="props.customerDetails"
          :kycLogs="kycLogs"
        />
      </x-form>
    </x-modal>

    <!-- Confirmation Model -->
    <x-modal
      v-model="modals.insuredDetailConfirmation"
      show-close
      :backdrop="true"
    >
      <p>
        Are you sure you want to run AML screen for this lead as Individual
        Customer?
      </p>
      <template #actions>
        <div class="text-center space-x-4">
          <x-button
            size="sm"
            color="#ff5e00"
            @click.prevent="switchToEntityView"
          >
            No
          </x-button>
          <x-button
            size="sm"
            color="success"
            @click.prevent="submitQuoteUpdateForm"
            :loading="insuredFormDetails.processing"
          >
            Yes
          </x-button>
        </div>
      </template>
    </x-modal>

    <!-- Entity Type Insured Form -->
    <x-modal
      v-model="modals.entityView"
      size="xl"
      title="Update and Verify"
      show-close
      backdrop
    >
      <p class="text-center mb-10">
        Please Enter Entity details to change the Customer Type to 'Entity'
      </p>

      <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
        <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
          <x-field label="Trade License No">
            <x-input
              v-model="insuredFormDetails.trade_license_no"
              :rules="[isRequired]"
              placeholder="Trade License No"
              type="text"
              class="w-full"
            />
            <x-button
              @click.prevent="searchByTradeLicense"
              size="xs"
              color="primary"
              :loading="loader.search"
            >
              Search
            </x-button>
          </x-field>
          <x-field label="Company Name">
            <x-input
              v-model="insuredFormDetails.company_name"
              :rules="[isRequired]"
              placeholder="Company Name"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Company Address">
            <x-input
              v-model="insuredFormDetails.company_address"
              :rules="[isRequired]"
              placeholder="Company Address"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Entity Type">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.entity_type_code"
              :rules="[isRequired]"
              placeholder="Select Entity Type"
              :options="[
                { label: 'Parent', value: 'Parent' },
                { label: 'Sub Entity', value: 'SubEntity' },
              ]"
              class="w-full"
            />
          </x-field>
          <x-field label="Industry Type">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.industry_type_code"
              :rules="[isRequired]"
              placeholder="Select Industry Type"
              :options="industryTypeOptions"
              class="w-full"
              :hasError="industryTypeCode"
            />
          </x-field>

          <x-field label="Emirate of Registration">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.emirate_of_registration_id"
              :rules="[isRequired]"
              placeholder="Select Emirate of Registration"
              :options="emirateRegistrationOptions"
              class="w-full"
              :hasError="emirateRegistrationId"
            />
          </x-field>
        </dl>
        <x-divider class="mb-4 mt-1" />
        <template v-if="entityDetailsFound">
          <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 mb-5">
            <x-field label="Trade License No">
              <x-input
                v-model="tradeLicenseEntity.trade_license"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <x-field label="Company Name">
              <x-input
                v-model="tradeLicenseEntity.company_name"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <x-field label="Company Address">
              <x-input
                v-model="tradeLicenseEntity.company_address"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <div class="text-left space-x-4">
              <x-button
                size="sm"
                color="red"
                @click.prevent="entityDetailsFound = false"
              >
                Hide
              </x-button>
              <x-button
                size="sm"
                color="orange"
                @click.prevent="linkEntity"
                :loading="linkLoader"
              >
                Link
              </x-button>
            </div>
          </dl>
        </template>
        <x-divider v-if="entityDetailsFound" class="mb-4 mt-1" />

        <UBODetailsModels
          :quoteDetails="quoteDetails"
          :quoteType="quoteType"
          :nationalities="nationalities"
          :uboDetails="uboDetails"
          :uboRelations="props.lookups.ubo_relation"
          :entity_id="insuredFormDetails.entity_id"
          :customerType="props.customerTypeEnum.Entity"
        />

        <x-divider class="mb-4 mt-4" />

        <PayerDetails
          :quoteType="quoteType"
          :quoteDetails="quoteDetails"
          :nationalities="nationalities"
          :membersDetails="membersDetails"
          :memberRelations="props.lookups.member_relation"
          :customerType="props.customerTypeEnum.Entity"
        />

        <div class="flex justify-center my-5">
          <x-button
            size="sm"
            color="success"
            type="submit"
            :loading="insuredFormDetails.processing"
          >
            Submit For AML Screening
          </x-button>
        </div>
        <x-divider class="mb-4 mt-4" />

        <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">KYC Details</h3>
        </div>

        <KycEntityModal
          :roles="$page.props.rolesEnum"
          :quote="quoteDetails"
          :country-list="nationalities"
          :aml-quote-status="props.amlQuoteStatus"
          :nationalities="nationalities"
          :modelType="props.quoteType?.code"
          :idDocumentType="props.lookups.id_type"
          :legal-structure="props.lookups.legal_structure"
          :issuance-place="props.lookups.issuance_place"
          :issuing-authority="props.lookups.issuing_authority"
          :entity-details="props.entityDetails"
          :uboRelation="props.lookups.ubo_relation"
          :industry-type="props.lookups.company_type"
        />
      </x-form>
    </x-modal>
  </div>
</template>
