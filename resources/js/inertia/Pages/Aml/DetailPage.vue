<script setup>
import { computed, ref } from 'vue';
import ScreeningModel from './Partials/ScreeningModel.vue';
import { useGmQuoteEmirateCrossTabListen } from '@/inertia/Composables/useGmQuoteEmirateCrossTabSync.js';
const props = defineProps({
  quoteType: Object,
  quoteRequest: Object,
  businessTypeCode: Object,
  businessCoverTypeText: Array,
  businessCommuModeText: Array,
  amlStatusName: String,
  amlStatusCode: Array,
  amlLogs: Array,
  lookups: Object,
  nationalities: Object,
  emirates: Object,
  insuredDetails: Object,
  entityDetails: Object,
  amlDecisionStatusEnum: Array,
  quoteTypeIdEnum: Array,
  quoteStatusEnums: Array,
  amlStatusEvaluation: Array,
  customerTypeEnum: Array,
  membersDetails: Object,
  uboDetails: Object,
  cardHolderName: Object,
  customerDetails: Object,
  quoteAmlStatus: String,
  defaultNationality: String,
  screeningType: String,
  gigInsurerDefaultEmail: String,
  permissionsEnum: Object,
  isEmirateOfRegistrationLocked: Boolean,
  // RTA Configuration (only available for Car quotes)
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
  isAddionalFieldsEnabled: Boolean,
});
const page = usePage();
const hasRole = role => useHasRole(role);
const complianceSection = ref(true);
const complianceComment = ref(props.quoteRequest.compliance_comments);
const complianceLoader = ref(false);
const IsComplianceCommentsEnable = ref(false);
const screeningModel = ref(false);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteBusinessTypeCode = page.props.quoteBusinessTypeCode;

useGmQuoteEmirateCrossTabListen({
  quoteUuid: computed(() => props.quoteRequest?.uuid),
  quoteId: computed(() => props.quoteRequest?.id),
  enabled: computed(() => props.quoteType?.code === quoteTypeCodeEnum.Business),
});
const rolesEnum = page.props.rolesEnum;
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const loader = reactive({
  amlLogTable: false,
  skipBridgerAMLBtn: false,
});
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
function dateAndTimeFormat(inputDate) {
  if (!inputDate) return 'N/A';
  return formatDate(inputDate);
}
const dateToYear = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    return `${year}`;
  }
  return '';
};
function enableComplianceComments() {
  if (hasRole(rolesEnum.ComplianceSuperUser)) {
    complianceSection.value = false;
  }
}
const updateComplianceComments = async () => {
  complianceLoader.value = true;
  await axios
    .post('/aml/update-quote-comment', {
      modelType: props.quoteType.code,
      quote_id: props.quoteRequest.id,
      compliance_comments: complianceComment.value,
    })
    .then(response => {
      IsComplianceCommentsEnable.value = false;
      complianceLoader.value = false;
      props.quoteRequest.compliance_comments =
        response.data.data.compliance_comments;
    })
    .catch(error => {
      console.error(error);
    });
};
const tempSkipBridgerAML = () => {
  loader.skipBridgerAMLBtn = true;
  let data = {
    quote_type_id: props.quoteType.id,
    quote_type_code: props.quoteType.code,
    quote_uuid: props.quoteRequest.uuid,
    quote_request_id: props.quoteRequest.id,
    current_aml_status: props.quoteRequest.aml_status,
  };
  axios
    .post(route('temp-skip-bridger-aml'), data)
    .then(res => {
      loader.skipBridgerAMLBtn = false;
      if (res.data.response) {
        notification.success({
          title: res.data.message,
          position: 'top',
        });
      }
    })
    .catch(err => {
      loader.skipBridgerAMLBtn = false;
      let errors = err.response.data.errors.error;
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    });
};
const amlLogstableHeader = [
  { text: 'Customer ID', value: 'customer_code' },
  { text: 'Customer Type', value: 'search_type' },
  { text: 'Insurance Type', value: 'insurance_type' },
  { text: 'Full Name', value: 'input' },
  { text: 'Nationality', value: 'nationality' },
  { text: 'Date of Birth', value: 'date_of_birth' },
  { text: 'Screening Date', value: 'created_at' },
  { text: 'Status', value: 'status' },
  { text: 'Notes', value: 'notes' },
];
if (can(permissionsEnum.AMLDecisionUpdate)) {
  amlLogstableHeader.push({ text: 'Action', value: 'action' });
}
onMounted(() => {
  enableComplianceComments();
});

const customerId = props.quoteRequest.customer_id ?? null;
const insuredId = props.insuredDetails?.insured?.id ?? null;
</script>

<template>
  <Head title="AML" />
  <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
    <h2 class="text-xl font-semibold">{{ quoteType.text }} Quote</h2>
    <div class="flex gap-2">
      <Link href="/kyc/aml" preserve-scroll>
        <x-button size="sm" color="primary" tag="div"> AML List</x-button>
      </Link>
    </div>
  </div>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">{{ quoteType.code }} QUOTE ID</dt>
          <dd>{{ quoteRequest.id }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <div>
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
              >
                Ref-ID
              </label>
              <template #tooltip> Reference ID</template>
            </x-tooltip>
          </div>
          <div>
            <Link
              :href="quoteRequest?.quote_link"
              class="text-primary-500 hover:underline"
            >
              {{ quoteRequest.code }}
            </Link>
          </div>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">QUOTE STATUS</dt>
          <dd>{{ quoteRequest?.quote_status?.text ?? '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">IM AML STATUS</dt>
          <dd>{{ amlStatusName ?? '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PHONE NUMBER</dt>
          <dd>{{ quoteRequest.mobile_no }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">FIRST NAME</dt>
          <dd>{{ quoteRequest.first_name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LAST NAME</dt>
          <dd>{{ quoteRequest.last_name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">YEAR OF BIRTH</dt>
          <dd>{{ dateToYear(quoteRequest.dob) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">EMAIL ADDRESS</dt>
          <dd>{{ quoteRequest.email }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">LANG</dt>
          <dd>{{ quoteRequest.lang }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">SOURCE</dt>
          <dd>{{ quoteRequest.source }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">REVIVER NAME</dt>
          <dd>{{ quoteRequest.reviver_name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PROMO CODE</dt>
          <dd>{{ quoteRequest.promo_code }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">DEVICE</dt>
          <dd>{{ quoteRequest.device }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PAYMENT STATUS</dt>
          <dd>{{ quoteRequest?.payment_status?.text ?? '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">REFERENCE URL</dt>
          <dd>{{ quoteRequest.reference_url }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ADDITIONAL NOTES</dt>
          <dd>{{ quoteRequest.additional_notes }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">IS SYNCED</dt>
          <dd>{{ quoteRequest.is_synced ? 'Yes' : 'No' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CUSTOMER NAME</dt>
          <dd>{{ quoteRequest?.customer.first_name }}</dd>
        </div>

        <template v-if="quoteType.code === quoteTypeCodeEnum.Car">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quoteRequest?.nationality?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UAE LICENCE HELD FOR</dt>
            <dd>{{ quoteRequest?.uae_license_held_for?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR MAKE</dt>
            <dd>{{ quoteRequest?.car_make?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR MODEL</dt>
            <dd>{{ quoteRequest?.car_model?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">YEAR OF MANUFACTURE</dt>
            <dd>{{ quoteRequest.year_of_manufacture }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMIRATES OF REGISTRATION</dt>
            <dd>{{ quoteRequest?.emirate?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ quoteRequest.currently_insured_with }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CAR VALUE(AED)</dt>
            <dd>{{ quoteRequest.car_value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF CAR INSURANCE</dt>
            <dd>{{ quoteRequest?.car_type_insurance?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CLAIM HISTORY</dt>
            <dd>{{ quoteRequest?.claim_history?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>
              {{ dateFormat(quoteRequest.dob) }}
            </dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Health">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ dateAndTimeFormat(quoteRequest.created_at) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ dateAndTimeFormat(quoteRequest.updated_at) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">COVER FOR</dt>
            <dd>{{ quoteRequest?.health_cover_for?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quoteRequest?.marital_status?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMIRATE OF VISA</dt>
            <dd>{{ quoteRequest?.emirate?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ dateFormat(quoteRequest.dob) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ quoteRequest.gender }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREFERRED HOSPITALS/CLINICS</dt>
            <dd>{{ quoteRequest.preference }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ quoteRequest.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">OPTIONAL COVERS REQUIRED</dt>
            <dd>
              Dental Cover: {{ quoteRequest.has_dental }} <br />
              Worldwide Cover: {{ quoteRequest.has_worldwide_cover }} <br />
              Home Country Cover: {{ quoteRequest.has_home }} <br />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quoteRequest?.nationality?.text ?? '' }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Home">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">I AM</dt>
            <dd>{{ quoteRequest?.possession_type?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">I LIVE IN</dt>
            <dd>{{ quoteRequest?.accommodation_type?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADDRESS</dt>
            <dd>{{ quoteRequest.address }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Travel">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DAYS COVER FOR</dt>
            <dd>{{ quoteRequest.days_cover_for }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">REGIONS COVER</dt>
            <dd>{{ quoteRequest?.regionCoverFor?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">COVER FOR</dt>
            <dd>{{ quoteRequest?.travelCoverFor?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ quoteRequest.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quoteRequest?.nationality?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DESTINATION</dt>
            <dd>{{ quoteRequest.destination }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Life">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PURPOSE OF INSURANCE</dt>
            <dd>{{ quoteRequest?.purposeOfInsurance?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CHILDREN</dt>
            <dd>{{ quoteRequest?.children?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quoteRequest?.maritalStatus?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TENURE OF INSURANCE</dt>
            <dd>{{ quoteRequest?.insuranceTenure?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SMOKER</dt>
            <dd>{{ quoteRequest.is_smoker ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NO. OF YEARS</dt>
            <dd>{{ quoteRequest?.numberOfYears?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUM INSURED</dt>
            <dd>{{ quoteRequest?.currency?.text ?? '' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">OTHER INFO</dt>
            <dd>{{ quoteRequest.others_info }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ dateFormat(quoteRequest.dob) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ quoteRequest.gender }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quoteRequest?.nationality?.text ?? '' }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Bike">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quoteRequest.nationality_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quoteRequest.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UAE LICENSE HELD FOR</dt>
            <dd>{{ quoteRequest.uae_license_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BIKE(S) TO INSURE</dt>
            <dd>{{ quoteRequest.bike_company_to_insure }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BIKE VALUE(AED)</dt>
            <dd>{{ quoteRequest.bike_value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">YEAR OF MANUFACTURE</dt>
            <dd>{{ quoteRequest.year_of_manufacture }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ quoteRequest.currently_insured_with }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Pet">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Yacht">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BOAT DETAILS</dt>
            <dd>{{ quoteRequest.boat_details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ENGINE DETAILS</dt>
            <dd>{{ quoteRequest.engine_details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CLAIMS EXPERIENCE</dt>
            <dd>{{ quoteRequest.claim_experience }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUM INSURED</dt>
            <dd>{{ quoteRequest.sum_insured_value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">USER</dt>
            <dd>{{ quoteRequest.use }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">OPERATOR'S EXPERIENCE</dt>
            <dd>{{ quoteRequest.operator_experience }}</dd>
          </div>
        </template>
        <template v-if="quoteType.code === quoteTypeCodeEnum.Business">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED AT</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED AT</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">COMPANY NAME</dt>
            <dd>{{ quoteRequest.company_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF BUSINESS INSURANCE</dt>
            <dd>{{ quoteRequest?.businessTypeOfInsurance?.text ?? '' }}</dd>
          </div>
          <template
            v-if="
              businessTypeCode &&
              businessTypeCode !== quoteBusinessTypeCode.photographers
            "
          >
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">BRIEF DETAILS</dt>
              <dd>{{ quoteRequest.brief_details }}</dd>
            </div>
          </template>
          <template
            v-if="
              businessTypeCode &&
              businessTypeCode !== quoteBusinessTypeCode.groupMedical
            "
          >
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">NUMBER OF MEMBERS</dt>
              <dd>{{ quoteRequest.number_of_employees }}</dd>
            </div>
          </template>
          <template
            v-if="
              businessTypeCode &&
              businessTypeCode !== quoteBusinessTypeCode.photographers
            "
          >
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">INTEREST</dt>
              <dd>{{ quoteRequest.interest }}</dd>
            </div>
          </template>
          <!-- TODO:: Need to be fetch from env -->
          <template v-if="quoteRequest.reference_url == 'crm.afia.ae'">
            <template
              v-if="businessTypeCode === quoteBusinessTypeCode.groupMedical"
            >
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CONTACT PERSON DESIGNATION</dt>
                <dd>{{ quoteRequest.contact_person_designation }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RENEWAL DUE DATE</dt>
                <dd>{{ quoteRequest.renewal_due_date }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COVER TYPE</dt>
                <dd>{{ businessCoverTypeText }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">TIME TO CONTACT</dt>
                <dd>{{ quoteRequest.time_to_contact }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMMUNICATION MODE PREFERENCE</dt>
                <dd>{{ businessCommuModeText }}</dd>
              </div>
            </template>
            <template
              v-if="businessTypeCode === quoteBusinessTypeCode.marineHull"
            >
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">BOAT DETAILS</dt>
                <dd>{{ quoteRequest.boat_details }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ENGINE DETAILS</dt>
                <dd>{{ quoteRequest.engine_details }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIM EXPERIENCE</dt>
                <dd>{{ quoteRequest.claims_experience }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUM INSURED</dt>
                <dd>{{ quoteRequest.sum_insured_value }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">USE</dt>
                <dd>{{ quoteRequest.use }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">OPERATOR'S EXPERIENCE</dt>
                <dd>{{ quoteRequest.operators_experience }}</dd>
              </div>
            </template>
          </template>
        </template>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">PREVIOUS QUOTE ID</dt>
          <dd>{{ quoteRequest.previous_quote_id }}</dd>
        </div>
        <div v-if="!complianceSection" class="grid sm:grid-cols-2">
          <dt class="font-medium">Compliance Officer Comments</dt>
          <dd v-if="!IsComplianceCommentsEnable" class="flex">
            {{
              quoteRequest.compliance_comments === null ||
              quoteRequest.compliance_comments === ''
                ? 'N/A'
                : quoteRequest.compliance_comments
            }}
            <span class="ml-2" @click="IsComplianceCommentsEnable = true">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 50 50"
                width="15px"
                height="15px"
              >
                <path
                  d="M 43.125 2 C 41.878906 2 40.636719 2.488281 39.6875 3.4375 L 38.875 4.25 L 45.75 11.125 C 45.746094 11.128906 46.5625 10.3125 46.5625 10.3125 C 48.464844 8.410156 48.460938 5.335938 46.5625 3.4375 C 45.609375 2.488281 44.371094 2 43.125 2 Z M 37.34375 6.03125 C 37.117188 6.0625 36.90625 6.175781 36.75 6.34375 L 4.3125 38.8125 C 4.183594 38.929688 4.085938 39.082031 4.03125 39.25 L 2.03125 46.75 C 1.941406 47.09375 2.042969 47.457031 2.292969 47.707031 C 2.542969 47.957031 2.90625 48.058594 3.25 47.96875 L 10.75 45.96875 C 10.917969 45.914063 11.070313 45.816406 11.1875 45.6875 L 43.65625 13.25 C 44.054688 12.863281 44.058594 12.226563 43.671875 11.828125 C 43.285156 11.429688 42.648438 11.425781 42.25 11.8125 L 9.96875 44.09375 L 5.90625 40.03125 L 38.1875 7.75 C 38.488281 7.460938 38.578125 7.011719 38.410156 6.628906 C 38.242188 6.246094 37.855469 6.007813 37.4375 6.03125 C 37.40625 6.03125 37.375 6.03125 37.34375 6.03125 Z"
                />
              </svg>
            </span>
          </dd>
          <dd v-else>
            <x-input
              type="text"
              placeholder="Compliance Officer Comments"
              v-model="complianceComment"
            />
            <x-button
              size="xs"
              class="mt-1 mr-1"
              color="#ff5e00"
              @click="updateComplianceComments"
              :loading="complianceLoader"
            >
              Update</x-button
            >
            <x-button
              size="xs"
              class="mt-1"
              color="#ff5e00"
              @click="IsComplianceCommentsEnable = false"
            >
              Cancel</x-button
            >
          </dd>
        </div>
      </dl>
      <div class="flex justify-end">
        <x-button
          v-if="can(permissionsEnum.SKIP_BRIDGER_AML)"
          class="mt-4 mr-2"
          color="red"
          size="sm"
          :loading="loader.skipBridgerAMLBtn"
          :disabled="
            !(quoteRequest.aml_status == props.amlStatusCode.AMLScreeningFailed)
          "
          @click="tempSkipBridgerAML"
        >
          Skip Bridger AML
        </x-button>
        <x-button
          class="mt-4"
          color="#ff5e00"
          size="sm"
          @click.prevent="screeningModel = true"
        >
          Update & Verify
        </x-button>
      </div>
    </div>
  </div>
  <ScreeningModel
    v-model="screeningModel"
    :quoteTypeCodeEnum="quoteTypeCodeEnum"
    :rta_transaction_types="rta_transaction_types"
    :rta_field_configurations="rta_field_configurations"
    :rta_validation_summaries="rta_validation_summaries"
    :isAddionalFieldsEnabled="props.isAddionalFieldsEnabled"
    :isEmirateOfRegistrationLocked="props.isEmirateOfRegistrationLocked"
  />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">AML Status Logs</h3>
    </div>
    <x-divider class="mb-4 mt-1" />
    <DataTable
      table-class-name="tablefixed"
      :headers="amlLogstableHeader"
      :loading="loader.amlLogTable"
      :items="amlLogs || []"
      border-cell
      :rows-per-page="40"
      :hide-footer="amlLogs.length < 40"
    >
      <template #item-insurance_type="{ quotetype }">
        {{ quoteType.text }}
      </template>
      <template #item-full_name="{ EntityDetails }">
        {{ EntityDetails?.Name?.Full ?? '' }}
      </template>
      <template #item-status="{ match_found, decision }">
        {{
          match_found > 0
            ? decision !== null
              ? props.amlStatusEvaluation[decision]
              : amlDecisionStatusEnum.ESCALATED
            : amlDecisionStatusEnum.PASS
        }}
      </template>
      <template
        v-if="can(permissionsEnum.AMLDecisionUpdate)"
        #item-action="{ id }"
      >
        <div class="space-x-4">
          <x-button
            size="xs"
            color="orange"
            outlined
            :href="`/kyc/aml/${id}/${insuredId}/${customerId}`"
          >
            View
          </x-button>
        </div>
      </template>
    </DataTable>
  </div>
  <AuditLogs
    :type="`App\\Models\\${quoteType.code}Quote`"
    :id="quoteRequest.id"
    :quoteType="quoteType.code"
  />
</template>
