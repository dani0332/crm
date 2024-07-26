<script setup>
import IndividualModel from './Partials/IndividualModel.vue';
import EntityModel from './Partials/EntityModel.vue';
import { onMounted, ref } from 'vue';

const props = defineProps({
  quoteType: Object,
  quoteRequest: Object,
  entityDetails: Object,
  membersDetails: Object,
  uboDetails: Object,
  nationalities: Object,
  emirates: Object,
  customerTypeEnum: Object,
  businessTypeCode: Object,
  businessCoverTypeText: Array,
  businessCommuModeText: Array,
  kycLogs: Array,
  kycStatus: String,
  customerDetails: Object,
  amlDecisionStatusEnum: Object,
  lookups: Object,
    cardHolderName:Object,
});

const page = usePage();
const rolesEnum = page.props.rolesEnum;
const paymentsRef = ref(page.props.quoteRequest.payments);
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const loader = reactive({
  table: false,
});

const modals = reactive({
  insuranceForm: false,
});

const tableHeader = [
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
  tableHeader.push({ text: 'Action', value: 'action' });
}

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteBusinessTypeCode = page.props.quoteBusinessTypeCode;

const dateAndTimeFormat = date => {
  return date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
};

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const dateToYear = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    return `${year}`;
  }
  return '';
};

const decisionStatus = {
    [props.amlDecisionStatusEnum.PASS] : "Pass",
    [props.amlDecisionStatusEnum.FALSE_POSITIVE] : "Pass",
    [props.amlDecisionStatusEnum.TRUE_MATCH_ACCEPT_RISK] : "Pass",
    [props.amlDecisionStatusEnum.ESCALATED] : "Escalated",
    [props.amlDecisionStatusEnum.SENT_FOR_REVIEW] : "Sent For Review",
    [props.amlDecisionStatusEnum.REJECTED] : "Rejected",
    [props.amlDecisionStatusEnum.TRUE_MATCH_REJECT_RISK] : "Rejected",
};

</script>

<template>
  <div>
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
            <div>{{ quoteRequest.code }}</div>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">QUOTE STATUS</dt>
            <dd>{{ quoteRequest?.quote_status?.text ?? '' }}</dd>
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

          <template v-if="quoteType.code == quoteTypeCodeEnum.Car">
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
              <dd>{{ dateFormat(quoteRequest.dob) }}</dd>
            </div>
          </template>
          <template v-if="quoteType.code == quoteTypeCodeEnum.Health">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Home">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Travel">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Life">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Bike">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Pet">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">CREATED AT</dt>
              <dd>{{ quoteRequest.created_at }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">UPDATED AT</dt>
              <dd>{{ quoteRequest.updated_at }}</dd>
            </div>
          </template>
          <template v-if="quoteType.code == quoteTypeCodeEnum.Yacht">
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
          <template v-if="quoteType.code == quoteTypeCodeEnum.Business">
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
                businessTypeCode != quoteBusinessTypeCode.photographers
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
                businessTypeCode != quoteBusinessTypeCode.groupMedical
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
                businessTypeCode != quoteBusinessTypeCode.photographers
              "
            >
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INTEREST</dt>
                <dd>{{ quoteRequest.interest }}</dd>
              </div>
            </template>
            <!-- Need to be fetch from env -->
            <template v-if="quoteRequest.reference_url == 'crm.afia.ae'">
              <template
                v-if="businessTypeCode == quoteBusinessTypeCode.groupMedical"
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
                v-if="businessTypeCode == quoteBusinessTypeCode.marineHull"
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
        </dl>
        <div class="flex justify-end">
          <x-button
            class="mt-4"
            color="#ff5e00"
            size="sm"
            @click.prevent="modals.insuranceForm = true"
          >
            Update & Verify
          </x-button>
        </div>
      </div>
    </div>

    <!-- AML Screening Models Start -->
    <EntityModel
        v-if="props.kycStatus === customerTypeEnum.EntityShort"
        v-model="modals.insuranceForm"
        :quoteType="quoteType"
        :quoteDetails="quoteRequest"
        :entityDetails="entityDetails"
        :nationalities="nationalities"
        :membersDetails="membersDetails"
        :uboDetails="uboDetails"
        :customerTypeEnum="customerTypeEnum"
        :lookups="lookups"
        :quote-aml-status="page.props.quoteAmlStatus"
    />

    <IndividualModel
      v-else
      v-model="modals.insuranceForm"
      :quoteType="quoteType"
      :quoteDetails="quoteRequest"
      :entityDetails="entityDetails"
      :nationalities="nationalities"
      :emirates="emirates"
      :membersDetails="membersDetails"
      :uboDetails="uboDetails"
      :customerTypeEnum="customerTypeEnum"
      :lookups="lookups"
      :quote-aml-status="page.props.quoteAmlStatus"
      :customer-details="props.customerDetails"
      :cardHolderName="cardHolderName"
    />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">AML Status Logs</h3>
      </div>
      <x-divider class="mb-4 mt-1" />
      <DataTable
        table-class-name="tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="kycLogs || []"
        border-cell
        :rows-per-page="40"
        fixed-checkbox
        :hide-footer="kycLogs.length < 40"
      >
        <template #item-insurance_type="{ quotetype }">
          {{ quoteType.text }}
        </template>
        <template #item-full_name="{ EntityDetails }">
          {{ EntityDetails.Name.Full ?? '' }}
        </template>

        <template #item-status="{ match_found, decision }">
         {{ match_found > 0 ? (decision !== null ? decisionStatus[decision] : amlDecisionStatusEnum.ESCALATED) : amlDecisionStatusEnum.PASS}}
        </template>

        <template v-if="can(permissionsEnum.AMLDecisionUpdate)" #item-action="{ id }">
          <div class="space-x-4">
            <x-button
              size="xs"
              color="orange"
              outlined
              :href="`/kyc/aml/${id}`"
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
    />
  </div>
</template>
