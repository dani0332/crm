<script setup>
import { watch } from 'vue';

const props = defineProps({
  searchData: { type: Object, default: null },
  searchSuccess: { type: Boolean, default: false },
  customerType: { type: String, default: '' },
});
const page = usePage();
const notification = useToast();
const { isRequired, isEmail, isNumber, isMobileNo } = useRules();
const convertDate = date => useConvertDate(date);
const generateOptions = (items, valueKey, labelKey) =>
  useGenerateOptions(items, valueKey, labelKey);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const complianceDisable = ref(true);
const patternFieldDisable = ref(true);
const isSyncEnabled = ref(page.props.isInsurerSyncEnabled ?? false);
const syncProcessLoading = ref(false);

const emit = defineEmits(['update:insurerPortalSyncData']);

const insuredDetails = page.props.insuredDetails;
const lookups = page.props.lookups;
const isScreeningIndividual =
  page.props.screeningType == page.props.customerTypeEnum.IndividualShort;

const dateFormat = date =>
  date ? useDateFormat(date, 'YYYY-MM-DD').value : '-';
const incomeSource = computed(() => {
  const sourceMapping = { employed: 2, business: 1 };
  return (
    sourceMapping[page.props.customerDetails?.detail?.source_of_income] ?? null
  );
});
const nationalityOptions = computed(() => {
  return generateOptions(page.props.nationalities, 'id', 'text');
});
const countryList = computed(() => {
  return generateOptions(page.props.nationalities, 'id', 'country_name');
});
const residentialStatusOptions = computed(() => {
  return generateOptions(lookups.resident_status, 'code', 'text');
});
const documentIdTypeOptions = computed(() => {
  return generateOptions(
    isScreeningIndividual ? lookups.id_type : lookups.entity_document_type,
    'code',
    'text',
  );
});
const modeOfContactOptions = computed(() => {
  return generateOptions(lookups.mode_of_contact, 'code', 'text');
});
const modeOfDeliveryOptions = computed(() => {
  return generateOptions(lookups.mode_of_delivery, 'code', 'text');
});
const professionalTitleOptions = computed(() => {
  return generateOptions(lookups.professional_title, 'code', 'text');
});
const employmentSectorsOptions = computed(() => {
  return generateOptions(lookups.employment_sector, 'code', 'text');
});
const companyPositionOptions = computed(() => {
  return generateOptions(lookups.company_position, 'code', 'text');
});
const legalStructureOptions = computed(() => {
  return generateOptions(lookups.legal_structure, 'code', 'text');
});
const industryTypeOptions = computed(() => {
  return generateOptions(lookups.company_type, 'code', 'text');
});
const placeOfIssuanceOptions = computed(() => {
  return generateOptions(lookups.issuance_place, 'code', 'text');
});
const issuingAuthorityOptions = computed(() => {
  return generateOptions(lookups.issuing_authority, 'code', 'text');
});
const uboRelationOptions = computed(() => {
  return generateOptions(lookups.ubo_relation, 'code', 'text');
});
const transactionVolumeOptions = [
  ['less-than-3-in-month', 'Less than 3 transactions in a month'],
  ['4-7-in-month', '4 to 7 transactions in a month'],
  ['more-than-8-in-month', 'More than 8 transactions in a monthnthly'],
].map(([value, label]) => ({ value, label }));
const transactionActivitiesOptions = [
  ['less_expected_annual_activity', 'Less than expected Annual Activity'],
  ['near_expected_annual_activity', 'Near to expected Annual Activity'],
  ['more_expected_annual_activity', 'More than expected Annual Activity'],
].map(([value, label]) => ({ value, label }));
const premiumTenureOptions = [
  ['single_premium', 'Single Premium'],
  ['quarter_premium', 'Quarterly/Semi-Annual'],
  ['monthly', 'Monthly'],
].map(([value, label]) => ({ value, label }));
const transactionPatternOptions = [
  ['count_pattern_changes', 'Yes - Count Pattern Changes'],
  ['behaviour_changes', 'Yes - Behaviour Changes'],
  ['business_model_changes', 'Yes - Business Model Changes'],
  ['no_changes', 'No Changes'],
  ['not_applicable', 'Not Applicable'],
].map(([value, label]) => ({ value, label }));
const complianceRules = computed(() => {
  const hasPermission =
    can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch);
  return hasPermission ? [isRequired] : [];
});

function activePatternField() {
  const isCompliance =
    hasRole(rolesEnum.COMPLIANCE) || hasRole(rolesEnum.ComplianceSuperUser);
  const canUpdateAmlDecision =
    can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch);

  // Enable the pattern field if the user is compliance or has update permission
  patternFieldDisable.value = !(isCompliance || canUpdateAmlDecision);
}

const noEscalated = computed(() => {
  return page.props.isAnyEscalated ? null : 1;
});

const kycFormDetails = useForm({
  customer_id: page.props.quoteRequest.customer_id,
  quote_uuid: page.props.quoteRequest.uuid,
  quote_type_id: page.props.quoteType.id,
  insured_id: insuredDetails?.insured?.id,
  first_name:
    insuredDetails?.insured?.insured_kyc?.first_name ??
    insuredDetails?.insured?.first_name ??
    null,
  last_name:
    insuredDetails?.insured?.insured_kyc?.last_name ??
    insuredDetails?.insured?.last_name ??
    null,
  residential_address:
    (isScreeningIndividual
      ? insuredDetails?.insured?.insured_kyc?.residential_address
      : insuredDetails?.insured?.insured_kyc?.registered_address) ?? null,
  mobile_number: page.props.quoteRequest.mobile_no,
  email: page.props.quoteRequest.email,
  customer_tenure:
    insuredDetails?.insured?.insured_kyc?.customer_tenure ?? null,
  id_type:
    insuredDetails?.insured?.insured_kyc?.id_type ??
    insuredDetails?.insured?.id_type ??
    null,
  id_number:
    insuredDetails?.insured?.insured_kyc?.id_number ??
    insuredDetails?.insured?.id_number ??
    null,
  id_issue_date: convertDate(
    insuredDetails?.insured?.insured_kyc?.id_issuance_date,
  ),
  id_expiry_date: convertDate(
    insuredDetails?.insured?.insured_kyc?.id_expiry_date ?? null,
  ),
  mode_of_contact:
    insuredDetails?.insured?.insured_kyc?.mode_of_contact ?? 'phoneAndEmail',
  mode_of_delivery:
    insuredDetails?.insured?.insured_kyc?.mode_of_delivery ??
    'mod-delivery-pse',
  pep:
    insuredDetails?.insured?.insured_kyc?.pep ??
    page.props.quoteAmlStatus ??
    (noEscalated.value ? 2 : null),
  financial_sanctions:
    insuredDetails?.insured?.insured_kyc?.financial_sanctions ??
    page.props.quoteAmlStatus ??
    noEscalated.value,
  dual_nationality:
    insuredDetails?.insured?.insured_kyc?.dual_nationality ??
    page.props.quoteAmlStatus ??
    noEscalated.value,
  transaction_pattern:
    insuredDetails?.insured?.insured_kyc?.transaction_pattern ?? 'no_changes',
  // Individual Customer Type
  dob: dateFormat(insuredDetails?.insured?.dob) || null,
  nationality_id: insuredDetails?.insured?.nationality_id ?? null,
  country_of_residence:
    insuredDetails?.insured?.insured_kyc?.country_of_residence ??
    page.props.defaultNationality,
  place_of_birth: insuredDetails?.insured?.insured_kyc?.place_of_birth ?? null,
  resident_status:
    insuredDetails?.insured?.insured_kyc?.resident_status ?? 'uaeResident',
  in_sanction_list:
    insuredDetails?.insured?.insured_kyc?.in_sanction_list ??
    page.props.quoteAmlStatus ??
    noEscalated.value,
  is_partner:
    insuredDetails?.insured?.insured_kyc?.is_partner ??
    incomeSource.value ??
    noEscalated.value,
  deal_sanction_list:
    insuredDetails?.insured?.insured_kyc?.deal_sanction_list ??
    page.props.quoteAmlStatus ??
    noEscalated.value,
  is_operation_high_risk:
    insuredDetails?.insured?.insured_kyc?.is_operation_high_risk ??
    page.props.quoteAmlStatus ??
    noEscalated.value,
  professional_title: insuredDetails?.insured?.insured_kyc?.job_title ?? null,
  employment_sector:
    insuredDetails?.insured?.insured_kyc?.employment_sector ?? null,
  trade_license: insuredDetails?.insured?.insured_kyc?.trade_license_no ?? null,
  company_position:
    insuredDetails?.insured?.insured_kyc?.position_in_company ?? null,
  premium_tenure:
    insuredDetails?.insured?.insured_kyc?.premium_tenure ?? 'single_premium',
  income_source: insuredDetails?.insured?.insured_kyc?.source_of_income ?? null,
  // Entity Customer Type
  company_name:
    (isScreeningIndividual
      ? insuredDetails?.insured?.insured_kyc?.employer_company_name
      : insuredDetails?.insured?.company_name) ?? null,
  legal_structure:
    insuredDetails?.insured?.insured_kyc?.legal_structure ?? null,
  industry_type: insuredDetails?.insured?.industry_type_code ?? null,
  country_of_corporation:
    insuredDetails?.insured?.insured_kyc?.country_of_corporation ?? null,
  communication_address:
    insuredDetails?.insured?.insured_kyc?.communication_address ?? null,
  website: insuredDetails?.insured?.insured_kyc?.website ?? null,
  place_of_issue: insuredDetails?.insured?.insured_kyc?.issuance_place ?? null,
  issuing_authority:
    insuredDetails?.insured?.insured_kyc?.id_issuance_authority ?? null,
  manager_name:
    page.props.entityDetails?.entity?.quote_member?.first_name ?? null,
  manager_nationality:
    page.props.entityDetails?.entity?.quote_member?.nationality_id ?? null,
  manager_dob: page.props.entityDetails?.entity?.quote_member?.dob ?? null,
  manager_position:
    page.props.entityDetails?.entity?.quote_member?.relation_code ?? null,
  is_sanction_match:
    insuredDetails?.insured?.insured_kyc?.is_sanction_match ??
    noEscalated.value,
  in_fatf: insuredDetails?.insured?.insured_kyc?.in_fatf ?? noEscalated.value,
  deal_sanction_list:
    insuredDetails?.insured?.insured_kyc?.deal_sanction_list ??
    noEscalated.value,
  is_owner_high_risk:
    insuredDetails?.insured?.insured_kyc?.is_owner_high_risk ??
    noEscalated.value,
  transaction_volume:
    insuredDetails?.insured?.insured_kyc?.transaction_volume ?? null,
  transaction_activities:
    insuredDetails?.insured?.insured_kyc?.transaction_activities ?? null,
  customer_type: props.customerType,
  // GIG Screening specific fields (Only for GIG Screening API)
  chassis_number:
    (page.props.quoteType.code === page.props.quoteTypeCodeEnum.Car
      ? page.props.quoteRequest?.car_quote_request_detail?.chassis_number
      : page.props.quoteRequest?.bike_quote?.chassis_number) ?? null,
  get_quote_email_gig:
    (page.props.quoteType.code === page.props.quoteTypeCodeEnum.Car
      ? page.props.quoteRequest?.car_quote_request_detail?.insurer_quote_email
      : page.props.quoteRequest?.quote_detail?.insurer_quote_email) ??
    page.props.gigInsurerDefaultEmail,
});
function insuredKycFormValidate() {
  kycFormDetails.clearErrors();
  let isValid = true;
  return isValid;
}
const syncInsurerPortalUpdates = () => {
  syncProcessLoading.value = true;
  axios
    .post('/get-quote-details-from-insurer', {
      quoteTypeId: page.props.quoteType.id,
      quoteUID: page.props.quoteRequest.uuid,
    })
    .then(response => {
      // Safely check response.data exists and has expected structure
      const hasValidData = response.data && response.data.success == true;

      if (hasValidData) {
        notification.success({
          title: 'Quote details synced successfully from insurer portal',
          position: 'top',
        });

        // Safely check nested data properties
        if (response.data?.data) {
          emit('update:insurerPortalSyncData', response.data.data);
        }
        if (response.data?.original?.data) {
          emit('update:insurerPortalSyncData', response.data.original.data);
        }
      } else {
        // Handle case where request succeeded but data indicates failure
        notification.error({
          title:
            response.data?.message ||
            'Failed to sync quote details from insurer portal',
          position: 'top',
        });
      }
      syncProcessLoading.value = false;
    })
    .catch(error => {
      notification.error({
        title: 'Error syncing quote details from insurer portal',
        position: 'top',
      });
      console.error('Sync error:', error);
      syncProcessLoading.value = false;
    });
};

const submitInsuredKycForm = isValid => {
  if (!isValid) return;

  if (insuredKycFormValidate()) {
    kycFormDetails.processing = true;

    axios
      .post('/update-insured-kyc', kycFormDetails)
      .then(response => {
        console.log('response', response); // TODO:: this log is temporary
        if (response.data.insurer_screening) {
          if (
            response.data.insurer_screening.status == 'AML_SCREENING_FAILED'
          ) {
            notification.error({
              title:
                response.data.insurer_screening.message ||
                'GIG server connection issue. Please check API logs for details of the error',
              position: 'top',
            });
          } else if (
            response.data.insurer_screening.status == 'AML_SCREENING_CLEARED'
          ) {
            if (
              response.data.insurer_screening.autoCaptureStatus == 'success'
            ) {
              notification.success({
                title: response.data.insurer_screening.autoCaptureMessage,
                timeout: 30000,
              });
            }
            if (response.data.insurer_screening.autoCaptureStatus == 'failed') {
              notification.error({
                title:
                  response.data.insurer_screening.autoCaptureMessage ??
                  'Auto capture payment process failed',
                position: 'top',
                timeout: 30000,
              });
            }
          } else if (response.data.insurer_screening.isPolicyExpired) {
            notification.success({
              title: 'Capture Payment Manually',
              position: 'top',
            });
          }
        }
        if (response.data.success) {
          notification.success({
            title: 'KYC Document uploaded successfully',
            position: 'top',
          });
          router.reload({
            replace: true,
            preserveScroll: true,
            preserveState: true,
          });
        } else {
          notification.error({
            title: 'Document upload failed',
            position: 'top',
          });
          kycFormDetails.processing = false;
        }
      })
      .catch(errors => {
        Object.keys(errors.response.data.errors).forEach(function (key) {
          notification.error({
            title: errors.response.data.errors[key][0].replace(
              /^\["|"\]$/g,
              '',
            ),
            position: 'top',
          });
        });
      })
      .finally(() => (kycFormDetails.processing = false));
  } else {
    console.log('Validation Failed');
  }
};
function changeIncomeSource() {
  kycFormDetails.is_partner = kycFormDetails.income_source
    ? kycFormDetails.income_source == 'employed'
      ? 2
      : 1
    : false;
}
const isDateExpired = date => {
  if (!date) return false;
  const expiryDate = new Date(date);
  const today = new Date();
  // Clear time part for accurate date comparison
  today.setHours(0, 0, 0, 0);
  expiryDate.setHours(0, 0, 0, 0);
  return expiryDate < today;
};
onMounted(() => {
  complianceDisable.value = !(
    can(permissionsEnum.AMLDecisionUpdate) ||
    can(permissionsEnum.AMLDecisionUpdateTrueMatch)
  );
  changeIncomeSource();
  activePatternField();
});

// Add watcher for customerType prop
watch(
  () => props.customerType,
  newType => {
    // console.log('Customer type prop updated:', newType);
    // Ensure we always set a value, even if null
    kycFormDetails.customer_type = newType;

    // If we have searchData but customer_type is null/empty, try to infer from searchData
    if (
      (!newType || newType === '') &&
      props.searchData &&
      props.searchData.customer_type
    ) {
      // console.log('Inferring customer_type from searchData:', props.searchData.customer_type);
      kycFormDetails.customer_type = props.searchData.customer_type;
    }
  },
  { immediate: true },
);

const idExpiryDateError = ref('');

// Add watcher for searchData
watch(
  () => props.searchData,
  newData => {
    if (!props.searchSuccess || !newData || !newData.insured_kyc) return;

    const customerTypeEnum = page.props.customerTypeEnum;
    const kyc = newData.insured_kyc;

    // Check if ID expiry date is expired
    const idExpiryDate = convertDate(kyc.id_expiry_date);
    const isExpired = isDateExpired(idExpiryDate);

    // Show notification if ID is expired
    if (isExpired && kyc.id_expiry_date) {
      idExpiryDateError.value = 'This ID is expired';
    } else {
      idExpiryDateError.value = '';
    }

    // Set common fields for both individual and entity
    const commonFields = {
      insured_id: newData.id,
      first_name: kyc.first_name,
      last_name: kyc.last_name,
      residential_address: kyc.residential_address,
      customer_tenure: kyc.customer_tenure,
      id_type: kyc.id_type,
      id_number: kyc.id_number,
      id_issue_date: convertDate(kyc.id_issuance_date),
      id_expiry_date: isExpired ? null : idExpiryDate, // Set to null if expired
      mode_of_contact: kyc.mode_of_contact,
      mode_of_delivery: kyc.mode_of_delivery,
      transaction_pattern: kyc.transaction_pattern,
      // Compliance fields
      pep: kyc.pep,
      financial_sanctions: kyc.financial_sanctions,
      dual_nationality: kyc.dual_nationality,
      in_sanction_list: kyc.in_sanction_list,
      is_partner: kyc.is_partner,
      deal_sanction_list: kyc.deal_sanction_list,
      is_operation_high_risk: kyc.is_operation_high_risk,
      is_sanction_match: kyc.is_sanction_match,
      in_fatf: kyc.in_fatf,
      is_owner_high_risk: kyc.is_owner_high_risk,
      transaction_volume: kyc.transaction_volume,
      transaction_activities: kyc.transaction_activities,
    };

    // Apply all common fields to the form
    Object.entries(commonFields).forEach(([key, value]) => {
      if (value !== undefined) {
        kycFormDetails[key] = value;
      }
    });

    // Customer type specific fields
    if ([customerTypeEnum.Individual, null, ''].includes(props.customerType)) {
      // Individual specific fields
      kycFormDetails.dob = dateFormat(newData.dob);
      kycFormDetails.nationality_id = newData.nationality_id;
      kycFormDetails.country_of_residence = kyc.country_of_residence;
      kycFormDetails.place_of_birth = kyc.place_of_birth;
      kycFormDetails.resident_status = kyc.residential_status || 'uaeResident';
      kycFormDetails.premium_tenure = kyc.premium_tenure || 'single_premium';
      kycFormDetails.income_source = kyc.source_of_income;
      kycFormDetails.company_name = kyc.employer_company_name;
      kycFormDetails.employment_sector = kyc.employment_sector;
      kycFormDetails.professional_title = kyc.job_title;
      kycFormDetails.trade_license = kyc.trade_license_no;
      kycFormDetails.company_position = kyc.position_in_company;

      // Trigger income source handler to update related fields
      if (kyc.source_of_income) {
        changeIncomeSource();
      }
    } else if (props.customerType === customerTypeEnum.Entity) {
      // Entity specific fields
      kycFormDetails.company_name = newData.company_name;
      kycFormDetails.legal_structure = kyc.legal_structure;
      kycFormDetails.industry_type = newData.industry_type_code;
      kycFormDetails.country_of_corporation = kyc.country_of_corporation;
      kycFormDetails.communication_address = kyc.communication_address;
      kycFormDetails.website = kyc.website;
      kycFormDetails.place_of_issue = kyc.issuance_place;
      kycFormDetails.issuing_authority = kyc.id_issuance_authority;
      kycFormDetails.is_sanction_match = kyc.is_sanction_match;
      kycFormDetails.is_owner_high_risk = kyc.is_owner_high_risk;

      // Manager fields are left commented out as in the original code
      // They seem to be populated from a different data source (page.props.entityDetails)
    }
  },
  { immediate: true, deep: true },
);

// Add a watcher for the ID expiry date
watch(
  () => kycFormDetails.id_expiry_date,
  newDate => {
    if (isDateExpired(newDate)) {
      kycFormDetails.id_expiry_date = null;
      idExpiryDateError.value = 'This ID is expired';
    } else {
      idExpiryDateError.value = '';
    }
  },
);

const [SubmitInsuredKycFormBtnTemplate, SubmitInsuredKycFormBtnReuseTemplate] =
  createReusableTemplate();
</script>
<template>
  <x-form @submit="submitInsuredKycForm" :auto-focus="false">
    <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
      <x-field label="Customer ID">
        <x-input
          v-model="kycFormDetails.customer_id"
          placeholder="Customer ID"
          type="text"
          class="w-full"
          :disabled="true"
          :rules="[isRequired]"
        />
      </x-field>
      <x-field label="First Name">
        <x-input
          v-model="kycFormDetails.first_name"
          placeholder="First Name"
          type="text"
          class="w-full"
        />
      </x-field>
      <x-field label="Last Name">
        <x-input
          v-model="kycFormDetails.last_name"
          placeholder="Last Name"
          type="text"
          class="w-full"
        />
      </x-field>
      <template v-if="isScreeningIndividual">
        <x-field label="Date of Birth">
          <DatePicker
            v-model="kycFormDetails.dob"
            placeholder="Date of Birth"
            class="w-full"
          />
        </x-field>
        <x-field label="Nationality">
          <ComboBox
            v-model="kycFormDetails.nationality_id"
            :options="nationalityOptions"
            :single="true"
            placeholder="Select Nationality"
            class="w-full"
          />
        </x-field>
        <x-field label="Country of Residence">
          <ComboBox
            v-model="kycFormDetails.country_of_residence"
            :options="countryList"
            :single="true"
            placeholder="Select Country of Residence"
            class="w-full"
          />
        </x-field>
        <x-field label="Place of Birth">
          <ComboBox
            v-model="kycFormDetails.place_of_birth"
            :options="countryList"
            :single="true"
            placeholder="Place of Birth"
            class="w-full"
          />
        </x-field>
        <x-field label="Resident Status">
          <x-select
            v-model="kycFormDetails.resident_status"
            :options="residentialStatusOptions"
            placeholder="Resident Status"
          />
        </x-field>
      </template>
      <template v-if="!isScreeningIndividual">
        <x-field label="Employer / Company name">
          <x-input
            v-model="kycFormDetails.company_name"
            placeholder="Employer / Company name"
            type="text"
            class="w-full"
            :disabled="true"
          />
        </x-field>
        <x-field label="Legal structure">
          <x-select
            v-model="kycFormDetails.legal_structure"
            :options="legalStructureOptions"
            placeholder="Select Legal structure"
            class="w-full"
          />
        </x-field>
        <x-field label="Industry type">
          <x-select
            v-model="kycFormDetails.industry_type"
            :options="industryTypeOptions"
            placeholder="Select Industry type"
            class="w-full"
          />
        </x-field>
        <x-field label="Country of corporation">
          <ComboBox
            v-model="kycFormDetails.country_of_corporation"
            :options="countryList"
            :single="true"
            placeholder="Country of corporation"
            class="w-full"
          />
        </x-field>
      </template>
    </dl>
    <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 items-center">
      <x-field label="Resident Address">
        <x-input
          v-model="kycFormDetails.residential_address"
          placeholder="Resident Address"
          type="text"
          class="w-full"
        />
      </x-field>
      <x-field v-if="!isScreeningIndividual" label="Communication Address">
        <x-input
          v-model="kycFormDetails.communication_address"
          placeholder="Communication Address"
          type="text"
          class="w-full"
        />
      </x-field>
    </dl>
    <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
      <x-field label="Mobile Number">
        <x-input
          v-model="kycFormDetails.mobile_number"
          placeholder="Mobile Number"
          type="text"
          class="w-full"
          :disabled="true"
        />
      </x-field>
      <x-field label="Email">
        <x-input
          v-model="kycFormDetails.email"
          placeholder="Email"
          type="email"
          class="w-full"
          :disabled="true"
        />
      </x-field>
      <x-field v-if="!isScreeningIndividual" label="Website">
        <x-input
          v-model="kycFormDetails.website"
          placeholder="Website"
          type="text"
          class="w-full"
        />
      </x-field>
      <x-field label="Customer Tenure">
        <x-input
          v-model="kycFormDetails.customer_tenure"
          placeholder="Customer Tenure"
          type="number"
          class="w-full"
        />
      </x-field>
    </dl>
    <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
      <x-field
        :label="isScreeningIndividual ? 'ID type' : 'ID / Document Type'"
      >
        <x-select
          v-model="kycFormDetails.id_type"
          :options="documentIdTypeOptions"
          :placeholder="
            isScreeningIndividual ? 'ID type' : 'ID / Document Type'
          "
          :disabled="isScreeningIndividual"
        />
      </x-field>
      <x-field label="ID Number">
        <x-input
          v-model="kycFormDetails.id_number"
          :placeholder="
            kycFormDetails.id_type === 'emiratesId'
              ? 'xxx-xxxx-xxxxxxx-x'
              : 'ID Number'
          "
          type="text"
          class="w-full"
          :disabled="isScreeningIndividual"
        />
      </x-field>
      <x-field
        :label="
          isScreeningIndividual ? 'ID Issue Date' : 'ID / Document Issue Date'
        "
      >
        <DatePicker v-model="kycFormDetails.id_issue_date" />
      </x-field>
      <x-field
        :label="
          isScreeningIndividual ? 'ID Expiry Date' : 'ID / Document Expiry Date'
        "
      >
        <DatePicker
          v-model="kycFormDetails.id_expiry_date"
          :rules="[isRequired]"
          :error="idExpiryDateError"
          :min-date="new Date()"
        />
      </x-field>
    </dl>
    <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
      <x-field v-if="!isScreeningIndividual" label="Place of issue">
        <x-select
          v-model="kycFormDetails.place_of_issue"
          :options="placeOfIssuanceOptions"
          placeholder="Place of issue"
          :single="true"
        />
      </x-field>
      <x-field v-if="!isScreeningIndividual" label="ID issuing authority">
        <ComboBox
          v-model="kycFormDetails.issuing_authority"
          :options="issuingAuthorityOptions"
          :single="true"
          placeholder="ID issuing authority"
          class="w-full"
        />
      </x-field>
      <x-field label="Mode of Contact">
        <x-select
          v-model="kycFormDetails.mode_of_contact"
          :options="modeOfContactOptions"
          placeholder="Mode of Contact"
        />
      </x-field>
      <x-field label="Mode of Delivery">
        <x-select
          v-model="kycFormDetails.mode_of_delivery"
          :options="modeOfDeliveryOptions"
          placeholder="Mode of Delivery"
        />
      </x-field>
      <x-field v-if="isScreeningIndividual" label="Premium Tenure">
        <x-select
          v-model="kycFormDetails.premium_tenure"
          :options="premiumTenureOptions"
          placeholder="Premium Tenure"
          :single="true"
        />
      </x-field>
    </dl>
    <x-divider class="mb-4 mt-1" />
    <template v-if="isScreeningIndividual">
      <div class="grid md:grid-cols-1 gap-4">
        <h3 class="font-bold text-black-800 text-center">Source of Income</h3>
      </div>
      <div class="flex gap-5 align-center">
        <x-form-group v-model="kycFormDetails.income_source">
          <x-radio
            value="employed"
            label="Employed"
            :rules="[isRequired]"
            @change="changeIncomeSource()"
          />
          <x-radio
            value="business"
            label="Business"
            :rules="[isRequired]"
            @change="changeIncomeSource()"
          />
        </x-form-group>
      </div>
      <dl
        v-if="kycFormDetails.income_source == 'employed'"
        class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center"
      >
        <x-field label="Employer / Company Name">
          <x-input
            v-model="kycFormDetails.company_name"
            placeholder="Employer / Company Name"
            type="text"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Professional Job Title">
          <ComboBox
            v-model="kycFormDetails.professional_title"
            :options="professionalTitleOptions"
            :single="true"
            placeholder="Professional Job Title"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Employment Sector">
          <x-select
            v-model="kycFormDetails.employment_sector"
            placeholder="Employment Sector"
            :options="employmentSectorsOptions"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
      </dl>
      <dl
        v-if="kycFormDetails.income_source == 'business'"
        class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center"
      >
        <x-field label="Company Name">
          <x-input
            v-model="kycFormDetails.company_name"
            placeholder="Company Name"
            type="text"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Trade License #">
          <x-input
            v-model="kycFormDetails.trade_license"
            type="text"
            placeholder="Trade License #"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Position in Company">
          <x-select
            v-model="kycFormDetails.company_position"
            :options="companyPositionOptions"
            placeholder="Position in Company"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
      </dl>
    </template>
    <template v-if="!isScreeningIndividual">
      <div class="grid md:grid-cols-1 gap-4 mb-5">
        <h3 class="font-bold text-black-800 text-center">
          UBO and Manager details
        </h3>
      </div>
      <dl class="grid md:grid-cols-4 gap-x-6 gap-y-4 items-center">
        <x-field label="Name">
          <x-input
            v-model="kycFormDetails.manager_name"
            placeholder="Name"
            type="text"
            class="w-full"
          />
        </x-field>
        <x-field label="Nationality">
          <x-select
            v-model="kycFormDetails.manager_nationality"
            :options="nationalityOptions"
            placeholder="Select Nationality"
            class="w-full"
            filterable
          />
        </x-field>
        <x-field label="Date of Birth">
          <DatePicker
            v-model="kycFormDetails.manager_dob"
            placeholder="Date of Birth"
            class="w-full"
          />
        </x-field>
        <x-field label="Position">
          <x-select
            v-model="kycFormDetails.manager_position"
            :options="uboRelationOptions"
            placeholder="Select Position"
            class="w-full"
          />
        </x-field>
      </dl>
    </template>

    <x-divider class="mb-4 mt-1" />
    <div class="grid md:grid-cols-1 gap-4 mb-3">
      <h3 class="font-bold text-black-800 text-center">
        For Compliance Use Only
      </h3>
    </div>
    <div class="flex justify-between gap-4 mb-1">
      <x-label>Is the customer a PEP?</x-label>
      <div class="grid md:grid-cols-2">
        <x-form-group v-model="kycFormDetails.pep" :rules="complianceRules">
          <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
          <x-radio :value="2" label="No" :disabled="complianceDisable" />
        </x-form-group>
      </div>
    </div>
    <div class="flex justify-between gap-4 mb-1">
      <x-label>
        Is the customer or business subjected to financial sanctions / or
        connected with prescribed terrorist organizations?
      </x-label>
      <div class="grid md:grid-cols-2">
        <x-form-group
          v-model="kycFormDetails.financial_sanctions"
          :rules="complianceRules"
        >
          <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
          <x-radio :value="2" label="No" :disabled="complianceDisable" />
        </x-form-group>
      </div>
    </div>
    <div class="flex justify-between gap-4 mb-1">
      <x-label>Does the customer have dual nationality?</x-label>
      <div class="grid md:grid-cols-2">
        <x-form-group
          v-model="kycFormDetails.dual_nationality"
          :rules="complianceRules"
        >
          <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
          <x-radio :value="2" label="No" :disabled="complianceDisable" />
        </x-form-group>
      </div>
    </div>
    <template v-if="isScreeningIndividual">
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Is the Natural Person listed in any Sanction/OOL/SIP list?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.in_sanction_list"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Is the Natural Person an Owner/Shareholder/Partner in any
          Organization?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.is_partner"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Does the Natural Person intend to provide professional services in
          any sanctions-listed country/ies?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.deal_sanction_list"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Is the Natural Person controlling/involved in any business listed in
          High-Risk Countries?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.is_operation_high_risk"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
    </template>
    <template v-if="!isScreeningIndividual">
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Does the Company name or Subsidiary/Affiliate entities feature in any
          sanction list?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.in_sanction_list"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Is There A Sanction Match On The Owner/Partners/Bod, Senior
          Management, Group Company, Holding Company Or Related Company
          Names?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.is_sanction_match"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Does the company have any subsidiary, affiliate, branch, or
          group/holding company in FATF-listed high-risk monitored
          jurisdiction?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.in_fatf"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Does the customer intend to deal with any country listed in the
          Sanctions List?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.deal_sanction_list"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Do the customer or subsidiary/ affiliate entities have operations in
          any High-Risk Countries?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.is_operation_high_risk"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
      <div class="flex justify-between gap-4 mb-1">
        <x-label
          >Is the owner/ Shareholder/ Partner/Director of the company from
          High-Risk countries?</x-label
        >
        <div class="grid md:grid-cols-2">
          <x-form-group
            v-model="kycFormDetails.is_owner_high_risk"
            :rules="complianceRules"
          >
            <x-radio :value="1" label="Yes" :disabled="complianceDisable" />
            <x-radio :value="2" label="No" :disabled="complianceDisable" />
          </x-form-group>
        </div>
      </div>
    </template>
    <x-divider class="mb-4 mt-1" />
    <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center">
      <template v-if="!isScreeningIndividual">
        <x-field label="Transaction Volume">
          <x-select
            v-model="kycFormDetails.transaction_volume"
            :options="transactionVolumeOptions"
            placeholder="Transaction Volume"
            class="w-full"
            :single="true"
            :disabled="patternFieldDisable"
            :rules="complianceRules"
          />
        </x-field>
        <x-field label="Transaction Activities">
          <x-select
            v-model="kycFormDetails.transaction_activities"
            :options="transactionActivitiesOptions"
            placeholder="Transaction Activities"
            class="w-full"
            :single="true"
            :disabled="patternFieldDisable"
            :rules="complianceRules"
          />
        </x-field>
      </template>
      <x-field label="Transaction Pattern Changes">
        <x-select
          v-model="kycFormDetails.transaction_pattern"
          :options="transactionPatternOptions"
          placeholder="Transaction Pattern Changes"
          class="w-full"
          :single="true"
          :disabled="patternFieldDisable"
          :rules="complianceRules"
        />
      </x-field>
    </dl>
    <div class="flex justify-end my-5 gap-x-2">
      <x-button
        size="sm"
        color="primary"
        type="button"
        class="px-6"
        @click="syncInsurerPortalUpdates"
        :disabled="!isSyncEnabled || !can(permissionsEnum.AMLList)"
        :loading="syncProcessLoading"
      >
        Sync
      </x-button>
      <SubmitInsuredKycFormBtnTemplate>
        <x-button
          size="sm"
          color="orange"
          type="submit"
          class="px-6"
          :loading="kycFormDetails.processing"
          :disabled="
            !can(permissionsEnum.AMLList) || !kycFormDetails.insured_id
          "
        >
          Save
        </x-button>
      </SubmitInsuredKycFormBtnTemplate>

      <x-tooltip
        v-if="!can(permissionsEnum.AMLList) || !kycFormDetails.insured_id"
        placement="left"
      >
        <SubmitInsuredKycFormBtnReuseTemplate />
        <template #tooltip>
          {{
            kycFormDetails.insured_id
              ? "You don't have permission to edit this section"
              : "Search the Insured's ID number"
          }}
        </template>
      </x-tooltip>
      <template v-else>
        <SubmitInsuredKycFormBtnReuseTemplate />
      </template>
    </div>
  </x-form>
</template>
