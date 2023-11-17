<script setup>
import { ref, watch } from 'vue';
import MemberDetailsModel from './MemberDetailsModel.vue';
import UBODetailsModels from './UBODetailsModels.vue';
import PayerDetails from './PayerDetails.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quoteType: Object,
  quoteDetails: Object,
  entityDetails: Object,
  nationalities: Object,
  emirates: Object,
  industryType: Object,
  membersDetails: Object,
  uboDetails: Object,
  memberRelations: Object,
  uboRelations: Object,
  customerTypeEnum: Object,
  residentStatuses: Object,
  lookups: Object,
  entities: Array,
});

// const paymentsDataArray = ref(props.quoteDetails.payments || []);

const loader = ref({
  search: false,
});

const isEmptyNationality = ref(false);

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

const residentStatusOptions = computed(() => {
  return props?.lookups?.resident_status.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const modeOfDeliveryOptions = computed(() => {
  return props?.lookups?.mode_of_delivery.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const idTypeOptions = computed(() => {
  return props?.lookups?.id_type.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const modeOfContactOptions = computed(() => {
  return props?.lookups?.mode_of_contact.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const emirateRegistrationOptions = computed(() => {
  return props.emirates.map(emirate => ({
    value: emirate.id,
    label: emirate.text,
  }));
});

const industryTypeOptions = computed(() => {
  return props.industryType.map(indType => ({
    value: indType.code,
    label: indType.text,
  }));
});

const employmentSectorOptions = computed(() => {
  return props?.lookups?.employment_sector.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const legalStructureOptions = computed(() => {
  return props?.lookups?.legal_structure.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const idIssuanceAuthorityOptions = computed(() => {
  return props?.lookups?.issuing_authority.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

const idIssuancePlanceOptions = computed(() => {
  return props?.lookups?.issuance_place.map(item => ({
    value: item.code,
    label: item.text,
  }));
});

// const paymentDetailsRef = ref([
//     {
//         paymentCode : '',
//         paymentMethod: '',
//         payerName: '',
//         paymentAmount: '',
//         paidBy: '',
//     }
// ]);

const validateCustomerFields = ref(false);

const customerAmlOnly = () => {
  validateCustomerFields.value = false;
  return true;
};

const customerAllFields = () => {
  validateCustomerFields.value = true;
  return true;
};

const validateEntityFields = ref(false);

const entityAmlOnly = () => {
  validateEntityFields.value = false;
  return true;
};

const entityAllFields = () => {
  validateEntityFields.value = true;
  return true;
};

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

  insured_first_name: props.quoteDetails?.customer?.insured_first_name ?? null,
  insured_last_name: props.quoteDetails?.customer?.insured_last_name ?? null,
  insured_fullname: props.quoteDetails?.customer.insured_fullname ?? null,
  withFullName: false,
  nationality_id: props.quoteDetails?.customer.nationality_id ?? null,
  dob: props.quoteDetails?.customer.dob ?? null,

  entity_id: props.entityDetails?.entity?.id,
  trade_license_no: props.entityDetails?.entity?.trade_license_no,
  company_name: props.entityDetails?.entity?.company_name,
  company_address: props.entityDetails?.entity?.company_address,
  entity_type_code: props.entityDetails?.entity?.entity_type_code ?? 'Parent',
  industry_type_code: props.entityDetails?.entity?.industry_type_code,
  emirate_of_registration_id:
    props.entityDetails?.entity?.emirate_of_registration_id,
});

const submitQuoteUpdateForm = () => {
  insuredFormDetails.get(`${props.quoteDetails.id}/quoteUpdate`, {
    preserveScroll: true,
    onError: errors => {
      notification.error({
        title: errors.error || 'Quote not updated',
        position: 'top',
      });
    },
    onSuccess: () => {
      notification.success({
        title: 'Quote is updated',
        position: 'top',
      });
    },
  });
};

const updateCustomerDetails = () => {
  let customerDetailData = {
    customer_id: insuredFormDetails.customer_id,
    customer_type: insuredFormDetails.customer_type,
    quote_type: insuredFormDetails.quote_type,

    insured_first_name: insuredFormDetails.insured_first_name,
    insured_last_name: insuredFormDetails.insured_last_name,
    insured_fullname: insuredFormDetails.insured_fullname,
    nationality_id: insuredFormDetails.nationality_id,
    dob: insuredFormDetails.dob,

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
      console.log(res, 'RESPONSE');
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
  if (
    insuredFormDetails.nationality_id == null &&
    validateCustomerFields.value == false
  ) {
    isEmptyNationality.value = true;
  } else {
    isEmptyNationality.value = false;
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

const payerNameRef = ref(null);

const insurerFullName = computed(() => {
  return (
    insuredFormDetails.insured_first_name +
    ' ' +
    insuredFormDetails.insured_last_name
  );
});

const paidByRef = ref('third-party');

// new update function
// const updateInsuredAndPaymentsDetails = () => {
//     linkLoader.value = true;
//     let entityDetails = {
//         quote_type_id: props.quoteType.id,
//         quote_request_id: props.quoteDetails.id,
//         entity_id: tradeLicenseEntity.entity_id,
//         payment_Details: paymentDetailsRef.value
//     };
//     axios
//         .post(route('insuredPayerDetailsUpdate'), entityDetails)
//         .then(res => {
//             if (res.data.status) {

//                 notification.success({
//                     title: res.data.message,
//                     position: 'top',
//                 });
//             }else{
//                 notification.error({
//                     title: res.data.message,
//                     position: 'top',
//                 });
//             }
//             linkLoader.value = false;

//         })
//         .catch(err => {
//             console.log(err);
//         })
// }

//faisal
// const customerDetailsForm = useForm({

//     customer_id: props.quoteDetails.customer_id,
//     customer_type: props.customerTypeEnum.Individual,
//     quote_type: props.quoteType.code,

//     insured_first_name: props.quoteDetails?.customer?.insured_first_name ?? null,
//     insured_last_name: props.quoteDetails?.customer?.insured_last_name ?? null,
//     nationality_id:  null,//props.quoteDetails?.customer.nationality_id ??
//     dob: props.quoteDetails?.customer.dob ?? null,

//     place_of_birth: props.quoteDetails?.customer?.detail?.place_of_birth ?? null,
//     country_of_residence: props.quoteDetails?.customer?.detail?.country_of_residence ?? null,
//     residential_address: props.quoteDetails?.customer?.detail?.residential_address ?? null,
//     residential_status: props.quoteDetails?.customer?.detail?.residential_status ?? null,
//     id_type: props.quoteDetails?.customer?.detail?.id_type ?? null,
//     id_issuance_date: props.quoteDetails?.customer?.detail?.id_issuance_date ?? null,
//     mode_of_contact: props.quoteDetails?.customer?.detail?.mode_of_contact ?? null,
//     transaction_value: props.quoteDetails?.customer?.detail?.transaction_value ?? null,
//     mode_of_delivery: props.quoteDetails?.customer?.detail?.mode_of_delivery ?? null,
//     employment_sector: props.quoteDetails?.customer?.detail?.employment_sector ?? null,
//     customer_tenure: props.quoteDetails?.customer?.detail?.customer_tenure ?? null,
// });

//faisal
// const updateCustomerDetails = () => {

//     //submit form manually

//     console.log(myForm.value);

//     //validate customerDetailsForm manually
//     validateCustomerFields.value = true;

//     if (myForm.refs.value.validate()) {
//         console.log("VALIDATED");
//     }
//     else{
//         console.log("FAILED");
//     }

//     // console.log(isValid, ":VALID");

//     // customerDetailsForm.post(`${props.quoteDetails.id}/update-customer-details`, {
//     //     preserveScroll: true,
//     //     onError: errors => {
//     //         notification.error({
//     //             title: errors.error || 'Customer update failed',
//     //             position: 'top',
//     //         });
//     //     },
//     //     onSuccess: () => {
//     //         notification.success({
//     //             title: 'Customer update failed',
//     //             position: 'top',
//     //         });

//     //     },
//     // });

// }

const updateDetails = () => {
  // console.log("update clicked");
  // console.log(paymentDetailsRef.value);
  // updateInsuredAndPaymentsDetails(paymentDetailsRef);
};

// watch(
//   () => insurerFullName,
//   val => {
//     if (payerNameRef.value == val) {
//       paidByRef.value = 'self';
//     }
//   }
// );

const is_insured = ref(false);

watch(
  props.membersDetails,
  () => {
    is_insured.value = !props.membersDetails.some(x => x.is_payer);
  },
  { immediate: true },
);

onMounted(() => {
  // paymentDetailsRef.value = props.quoteDetails.payments.map(payment => ({
  //     paymentCode : payment.code,
  //     paymentMethod: payment.payment_method.name,
  //     payerName: payment.get_customer_payment_instrument?.card_holder_name ?? '',
  //     paymentAmount: payment.captured_amount,
  //     paidBy: 'self',
  // }));
});
</script>

<template>
  <div>
    <!-- Individual Type Insured Form -->
    <x-modal v-model="showModal" size="xl" show-close backdrop>
      <template #header>Update and Verify</template>
      <p class="text-center mb-10">
        Please confirm the Name, Nationality, and Date of Birth of the insured
        person(s) as per the Emirates ID
      </p>

      <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 items-center">
          <div class="flex gap-5 mb-5 align-center">
            <p>Want to send Fullname?</p>
            <x-radio
              v-model="insuredFormDetails.withFullName"
              :value="true"
              label="Yes"
            />
            <x-radio
              v-model="insuredFormDetails.withFullName"
              :value="false"
              label="No"
            />
          </div>
        </dl>
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 items-center">
          <div v-if="insuredFormDetails.withFullName">
            <x-field label="Insured Full Name">
              <x-input
                v-model="insuredFormDetails.insured_fullname"
                :rules="[isRequired]"
                placeholder="Insured Full Name"
                type="text"
                class="w-full"
              />
            </x-field>
          </div>
          <x-field
            label="Insured First Name"
            v-if="!insuredFormDetails.withFullName"
          >
            <x-input
              v-model="insuredFormDetails.insured_first_name"
              :rules="[isRequired]"
              placeholder="Insured First Name"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field
            label="Insured Last Name"
            v-if="!insuredFormDetails.withFullName"
          >
            <x-input
              v-model="insuredFormDetails.insured_last_name"
              :rules="[isRequired]"
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
          <div
            class="flex gap-5 mb-5 align-center"
            :class="{ 'mt-8': insuredFormDetails.withFullName }"
          >
            <p>Is the insured the payer?</p>
            <x-radio v-model="is_insured" :value="true" label="Yes" />
            <x-radio v-model="is_insured" :value="false" label="No" />
          </div>

          <!-- new fields -->

          <!-- <x-field label="Country / Place of Birth">
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.place_of_birth"
                            placeholder="Please enter the Place of Birth of the customer as per Passport"
                            :options="nationalitiesOptions"
                            :hasError="!insuredFormDetails.place_of_birth && validateCustomerFields"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Country of Residence">
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.country_of_residence"
                            placeholder="Country of Residence"
                            :options="nationalitiesOptions"
                            :hasError="!insuredFormDetails.country_of_residence && validateCustomerFields"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Residential Address">
                        <x-input
                            v-model="insuredFormDetails.residential_address"
                            placeholder="Residential Address"
                            :rules="validateCustomerFields ? [isRequired] : []"
                            type="text"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Resident Status">
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.residential_status"
                            placeholder="Resident Status"
                            :options="residentStatusOptions"
                            :hasError="!insuredFormDetails.residential_status && validateCustomerFields"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="ID Type">
                        <ComboBox
                            :single="true"
                            :hasError="!insuredFormDetails.id_type && validateCustomerFields"
                            v-model="insuredFormDetails.id_type"
                            placeholder="Please specify the type of ID received from the customer "
                            :options="idTypeOptions"
                            class="w-full"
                        />
                    </x-field>


                    <x-field label="ID Issue Date">
                        <DatePicker
                            v-model="insuredFormDetails.id_issuance_date"
                            :rules="validateCustomerFields ? [isRequired] : []"
                            placeholder="ID Issue Date"
                            class="w-full"
                        />
                    </x-field>


                    <x-field label="Mode Of Contact">
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.mode_of_contact"
                            :hasError="!insuredFormDetails.mode_of_contact && validateCustomerFields"
                            placeholder="Please specify the mode of contact with this customer"
                            :options="modeOfContactOptions"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Transaction Value">
                        <x-input
                            v-model="insuredFormDetails.transaction_value"
                            placeholder="Transaction Value"
                            :rules="validateCustomerFields ? [isRequired] : []"
                            type="number"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Mode Of Delivery">
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.mode_of_delivery"
                            :hasError="!insuredFormDetails.mode_of_delivery && validateCustomerFields"
                            placeholder="Please select the mode of delivery of the policy documents"
                            :options="modeOfDeliveryOptions"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Employment Sector">
                        <ComboBox
                            :single="true"
                            :hasError="!insuredFormDetails.employment_sector && validateCustomerFields"
                            v-model="insuredFormDetails.employment_sector"
                            placeholder="Employment Sector"
                            :options="employmentSectorOptions"
                            class="w-full"
                        />
                    </x-field>

                    <x-field label="Customer Tenure">
                        <x-input
                            v-model="insuredFormDetails.customer_tenure"
                            placeholder="Customer Tenure"
                            :rules="validateCustomerFields ? [isRequired] : []"
                            type="text"
                            class="w-full"
                        />
                    </x-field> -->
        </dl>

        <x-divider class="mb-4 mt-1" />

        <MemberDetailsModel
          :quoteType="quoteType"
          :quoteDetails="quoteDetails"
          :nationalities="nationalities"
          :membersDetails="membersDetails"
          :memberRelations="memberRelations"
          :customerType="props.customerTypeEnum.Individual"
        />

        <x-divider class="mb-4 mt-4" />

        <PayerDetails
          :quoteType="quoteType"
          :quoteDetails="quoteDetails"
          :nationalities="nationalities"
          :membersDetails="membersDetails"
          :memberRelations="memberRelations"
          :customerType="props.customerTypeEnum.Individual"
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
          :amlQuoteStatus="$page.props.amlQuoteStatus"
          :nationalities="nationalities"
          :modelType="quoteType?.code"
          :idDocumentType="props.lookups.id_type"
          :modeOfContact="props.lookups.mode_of_contact"
          :modeOfDelivery="props.lookups.mode_of_delivery"
          :employmentSectors="props.lookups.employment_sector"
          :residentialStatus="props.lookups.resident_status"
          :companyPosition="props.lookups.company_position"
        />

        <!-- <h3 class="font-semibold text-primary-800 text-lg mb-4">
                Payer Details
            </h3>

            <section v-if="paymentsDataArray.length > 0">
                <dl v-for="(payment, index) in paymentsDataArray" class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <x-field label="Payment ID:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentCode"
                            placeholder="Payment ID"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Payment Method:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentMethod"
                            placeholder="Payment Method"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field v-if="payment.payment_methods_code == 'CC'" label="Payer Name:">
                        <template v-if="payment.get_customer_payment_instrument?.card_holder_name">
                            <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                            />
                        </template>
                        <template v-else>
                            <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            type="text"
                            :rules="[isRequired]"
                            class="w-full"
                            />
                        </template>
                    </x-field>
                    <x-field v-else-if="payment.payment_methods_code == 'CSH' || payment.payment_methods_code == 'IP'" label="Payer Name:">
                        <x-input
                            v-model="insurerFullName"
                            placeholder="Payer Name"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field v-else>
                        <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            type="text"
                            :rules="[isRequired]"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Total Amount:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentAmount"
                            :readonly="true"
                            :rules="[isRequired]"
                            placeholder="Total Amount"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Paid By:">
                        <ComboBox
                            v-model="paymentDetailsRef[index].paidBy"
                            :single="true"
                            :rules="[isRequired]"
                            placeholder="Select Paid by"
                            :options="[
                                {label: 'Self', value: 'self'},
                                {label: 'Third Party', value: 'third-party'}
                            ]"
                            selected="self"
                            class="w-full"
                        />
                    </x-field>
                    <br/>
                    <x-divider class="mb-4 mt-4"/>
                    <x-divider class="mb-4 mt-4"/>
                </dl>
            </section> -->

        <div class="text-right space-x-4 mt-8">
          <!-- <x-button
            size="sm"
            color="warning"
            type="submit"
            @click="customerAllFields"
          >
            Save
          </x-button> -->
          <!-- <x-button
            size="sm"
            color="warning"
            type="submit"
            @click="customerAllFields"
          >
            Update Only
          </x-button>

          <x-button
            size="sm"
            color="success"
            type="submit"
            @click="customerAmlOnly"
          >
            Confirm
          </x-button> -->
        </div>
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
    <x-modal v-model="modals.entityView" size="xl" show-close backdrop>
      <template #header>Update and Verify</template>
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
            />
          </x-field>

          <!-- new fields -->

          <x-field label="Legal Structure">
            <ComboBox
              :single="true"
              :hasError="
                !insuredFormDetails.legal_structure && validateEntityFields
              "
              v-model="insuredFormDetails.legal_structure"
              placeholder="Legal Structure"
              :options="legalStructureOptions"
              class="w-full"
            />
          </x-field>

          <x-field label="Country of Incorporation">
            <ComboBox
              :single="true"
              :hasError="
                !insuredFormDetails.country_of_corporation &&
                validateEntityFields
              "
              v-model="insuredFormDetails.country_of_corporation"
              placeholder="Country of Incorporation"
              :options="nationalitiesOptions"
              class="w-full"
            />
          </x-field>

          <x-field label="Website">
            <x-input
              v-model="insuredFormDetails.website"
              placeholder="Please enter the official website of the entity here"
              type="text"
              :rules="validateEntityFields ? [isRequired] : []"
              class="w-full"
            />
          </x-field>

          <x-field label="ID / Document Type">
            <ComboBox
              :single="true"
              :hasError="
                !insuredFormDetails.entity_id_type && validateEntityFields
              "
              v-model="insuredFormDetails.entity_id_type"
              placeholder="Please specify the type of ID received from the customer "
              :options="idTypeOptions"
              class="w-full"
            />
          </x-field>

          <x-field label="ID / Document Issue Date">
            <DatePicker
              v-model="insuredFormDetails.entity_id_issuance_date"
              placeholder="Please specify the issuance date of the ID collected"
              :rules="validateEntityFields ? [isRequired] : []"
              class="w-full"
            />
          </x-field>

          <x-field label="ID / Document Expiry Date">
            <DatePicker
              v-model="insuredFormDetails.id_expiry_date"
              placeholder="Please specify the Expiry date of the ID collected"
              class="w-full"
              :rules="validateEntityFields ? [isRequired] : []"
            />
          </x-field>

          <x-field label="Place of Issue">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.id_issuance_place"
              :hasError="
                !insuredFormDetails.id_issuance_place && validateEntityFields
              "
              placeholder="Place of Issue"
              :options="idIssuancePlanceOptions"
              class="w-full"
            />
          </x-field>

          <x-field label="ID Issue Authority">
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.id_issuance_authority"
              :hasError="
                !insuredFormDetails.id_issuance_authority &&
                validateEntityFields
              "
              placeholder="ID Issue Authority"
              :options="idIssuanceAuthorityOptions"
              class="w-full"
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
          :uboRelations="uboRelations"
          :entity_id="insuredFormDetails.entity_id"
          :customerType="props.customerTypeEnum.Entity"
        />
        <div class="flex justify-center my-5">
          <x-button
            size="sm"
            color="success"
            type="submit"
            @click.prevent="entityAmlOnly"
            :loading="insuredFormDetails.processing"
          >
            Submit For AML Screening
          </x-button>
        </div>

        <x-divider class="mb-4 mt-4" />

        <!-- <h3 class="font-semibold text-primary-800 text-lg mb-4">
                Payer Details
            </h3>

            <section v-if="paymentsDataArray.length > 0">
                <dl v-for="(payment, index) in paymentsDataArray" class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <x-field label="Payment ID:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentCode"
                            placeholder="Payment ID"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Payment Method:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentMethod"
                            placeholder="Payment Method"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field v-if="payment.payment_methods_code == 'CC'" label="Payer Name:">
                        <template v-if="payment.get_customer_payment_instrument?.card_holder_name">
                            <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                            />
                        </template>
                        <template v-else>
                            <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            type="text"
                            :rules="[isRequired]"
                            class="w-full"
                            />
                        </template>
                    </x-field>
                    <x-field v-else-if="payment.payment_methods_code == 'CSH' || payment.payment_methods_code == 'IP'" label="Payer Name:">
                        <x-input
                            v-model="insurerFullName"
                            placeholder="Payer Name"
                            :readonly="true"
                            :rules="[isRequired]"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field v-else>
                        <x-input
                            v-model="paymentDetailsRef[index].payerName"
                            placeholder="Payer Name"
                            type="text"
                            :rules="[isRequired]"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Total Amount:">
                        <x-input
                            v-model="paymentDetailsRef[index].paymentAmount"
                            :readonly="true"
                            :rules="[isRequired]"
                            placeholder="Total Amount"
                            type="text"
                            class="w-full"
                        />
                    </x-field>
                    <x-field label="Paid By:">
                        <ComboBox
                            v-model="paymentDetailsRef[index].paidBy"
                            :single="true"
                            :rules="[isRequired]"
                            placeholder="Select Paid by"
                            :options="[
                                {label: 'Self', value: 'self'},
                                {label: 'Third Party', value: 'third-party'}
                            ]"
                            selected="self"
                            class="w-full"
                        />
                    </x-field>
                    <br/>
                    <x-divider class="mb-4 mt-4"/>
                    <x-divider class="mb-4 mt-4"/>
                </dl>
            </section> -->

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
          :entities="props.entities"
          :idDocumentType="props.lookups.id_type"
          :legal-structure="props.lookups.legal_structure"
          :issuance-place="props.lookups.issuance_place"
          :issuing-authority="props.lookups.issuing_authority"
        />

        <div class="text-right space-x-4 mt-8">
          <!-- <x-button
            size="sm"
            color="red"
            @click.prevent="modals.entityView = false"
          >
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="orange"
            type="submit"
            :loading="insuredFormDetails.processing"
            @click.prevent="entityAllFields"
          >
            Update Only
          </x-button> -->
        </div>
      </x-form>
    </x-modal>
  </div>
</template>
