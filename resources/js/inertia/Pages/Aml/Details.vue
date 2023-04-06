<script setup>
defineProps({
  quoteTypeCode: Object,
  quoteTypeText: Object,
  quoteRequest: Array,
  businessTypeCode: Object,
  businessCoverTypeText: Array,
  businessCommuModeText: Array,
  kycLogs: Array,
  quoteStatusCode: Array,
  auditLogLine: Array,
  isCurrentUserFromCompliance: Array,
  isCurrentUserFromPaAml: Array,
  firstAmlLogResults: Array,
  latestAmlLogResults: Array,
  quoteTypeId: Array,
  getAMLNumRows: Array,
  nationalityList: Array,
  yearsList: Array,
  isCompanySearchEnabled: Array,
});

const page = usePage();
const loader = reactive({
  table: false,
});
const tableHeader = [
  { text: 'AML Id', value: 'id' },
  { text: 'Input', value: 'input' },
  { text: 'Search Type', value: 'search_type' },
  { text: 'Screenshot', value: 'screenshot' },
  { text: 'Match Found', value: 'results_found' },
  { text: 'Results Found', value: 'results_found' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteBusinessTypeCode = page.props.quoteBusinessTypeCode;
console.log(page.props);
</script>

<template>
  <div>
    <Head title="AML" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">{{ quoteTypeText }} Quote</h2>
      <div class="flex gap-2">
        <!-- <Link :href="`/personal-quotes/bike/${quote.uuid}/edit`">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link> -->

        <Link href="/kyc/aml" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Aml </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">{{ quoteTypeCode }} Quote ID</dt>
            <dd>{{ quoteRequest.id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quoteRequest.code }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Quote Status</dt>
            <dd>{{ quoteRequest.quote_status_text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">First Name</dt>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Last Name</dt>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Year of Birth</dt>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Phone Number</dt>
            <dd>{{ quoteRequest.mobile_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Email Address</dt>
            <dd>{{ quoteRequest.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Lang</dt>
            <dd>{{ quoteRequest.lang }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Source</dt>
            <dd>{{ quoteRequest.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Reviver Name</dt>
            <dd>{{ quoteRequest.reviver_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Promo Code</dt>
            <dd>{{ quoteRequest.promo_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Device</dt>
            <dd>{{ quoteRequest.device }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Payment Status</dt>
            <dd>{{ quoteRequest.payment_status_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Reference Url</dt>
            <dd>{{ quoteRequest.reference_url }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Additional Notes</dt>
            <dd>{{ quoteRequest.additional_notes }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Is Synced</dt>
            <dd>{{ quoteRequest.is_synced }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Customer Name</dt>
            <dd>{{ quoteRequest.cust_f_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Created At</dt>
            <dd>{{ quoteRequest.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Updated At</dt>
            <dd>{{ quoteRequest.updated_at }}</dd>
          </div>

          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Car">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Nationality</dt>
              <dd>{{ quoteRequest.nationality_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">UAE licence held for</dt>
              <dd>{{ quoteRequest.uae_license_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car Make</dt>
              <dd>{{ quoteRequest.car_make_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car Model</dt>
              <dd>{{ quoteRequest.car_model_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Year of manufacture</dt>
              <dd>{{ quoteRequest.year_of_manufacture }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Emirate Of Registration</dt>
              <dd>{{ quoteRequest.emirates_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Currently with</dt>
              <dd>{{ quoteRequest.currently_insured_with }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car value(AED)</dt>
              <dd>{{ quoteRequest.car_value }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Type Of Car Insurance</dt>
              <dd>{{ quoteRequest.car_type_ins_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Claim History</dt>
              <dd>{{ quoteRequest.claim_history_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Date of Birth</dt>
              <dd>{{ quoteRequest.dob }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Health">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Cover for</dt>
              <dd>{{ quoteRequest.health_cover_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Marital status</dt>
              <dd>{{ quoteRequest.marital_status_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Emirate of visa</dt>
              <dd>{{ quoteRequest.emirates_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Date of Birth</dt>
              <dd>{{ quoteRequest.dob }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Gender</dt>
              <dd>{{ quoteRequest.gender }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Preferred hospitals/clinics</dt>
              <dd>{{ quoteRequest.preference }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Details</dt>
              <dd>{{ quoteRequest.details }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Optional covers required</dt>
              <dd>{{ quoteRequest.mobile_no }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Nationality</dt>
              <dd>{{ quoteRequest.nationality_text }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Home">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">I am</dt>
              <dd>{{ quoteRequest.home_possession_type_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">I live in</dt>
              <dd>{{ quoteRequest.home_accommodation_type_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Cover for</dt>
              <dd>{{ quoteRequest.mobile_no }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Address</dt>
              <dd>{{ quoteRequest.address }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Travel">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Days cover for</dt>
              <dd>{{ quoteRequest.days_cover_for }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Regions cover</dt>
              <dd>{{ quoteRequest.region_cover_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Cover for</dt>
              <dd>{{ quoteRequest.cover_for_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Details</dt>
              <dd>{{ quoteRequest.details }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Nationality</dt>
              <dd>{{ quoteRequest.nationality_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Destination</dt>
              <dd>{{ quoteRequest.destination }}</dd>
            </div></template
          >
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Life">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Purpose of Insurance</dt>
              <dd>{{ quoteRequest.purpose_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Children</dt>
              <dd>{{ quoteRequest.children_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Marital status</dt>
              <dd>{{ quoteRequest.marital_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Tenure of Insurance</dt>
              <dd>{{ quoteRequest.tenure_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Smoker</dt>
              <dd>{{ quoteRequest.is_smoker }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">No. of Years</dt>
              <dd>{{ quoteRequest.number_of_year_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Sum Insured</dt>
              <!-- quoteRequest.currency_text -->
              <dd>
                {{ quoteRequest.currency_text }}
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Other Info</dt>
              <dd>{{ quoteRequest.others_info }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Date of Birth</dt>
              <dd>{{ quoteRequest.dob }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Gender</dt>
              <dd>{{ quoteRequest.gender }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Nationality</dt>
              <dd>{{ quoteRequest.nationality_text }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Bike">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Nationality</dt>
              <dd>{{ quoteRequest.nationality_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Date of Birth</dt>
              <dd>{{ quoteRequest.dob }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">UAE licence held for</dt>
              <dd>{{ quoteRequest.uae_license_text }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Bike(s) to insure</dt>
              <dd>{{ quoteRequest.bike_company_to_insure }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Bike value(AED)</dt>
              <dd>{{ quoteRequest.bike_value }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Year of manufacture</dt>
              <dd>{{ quoteRequest.year_of_manufacture }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Currently with</dt>
              <dd>{{ quoteRequest.currently_insured_with }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Yacht">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Boat Details</dt>
              <dd>{{ quoteRequest.boat_details }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Engine Details</dt>
              <dd>{{ quoteRequest.engine_details }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Claims Experience</dt>
              <dd>{{ quoteRequest.claim_experience }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Sum Insured</dt>
              <dd>{{ quoteRequest.sum_insured_value }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Use</dt>
              <dd>{{ quoteRequest.use }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Operator's Experience</dt>
              <dd>{{ quoteRequest.operator_experience }}</dd>
            </div>
          </template>
          <template v-if="quoteTypeCode == quoteTypeCodeEnum.Business">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Company Name</dt>
              <dd>{{ quoteRequest.company_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Type of Business insurance</dt>
              <dd>{{ quoteRequest.business_type_text }}</dd>
            </div>
            <template
              v-if="
                businessTypeCode &&
                businessTypeCode != quoteBusinessTypeCode.photographers
              "
            >
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Brief Details</dt>
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
                <dt class="font-medium">Number of Members</dt>
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
                <dt class="font-medium">Interest</dt>
                <dd>{{ quoteRequest.interest }}</dd>
              </div>
            </template>
            <template v-if="quoteRequest.reference_url == 'crm.afia.ae'">
              <template
                v-if="businessTypeCode == quoteBusinessTypeCode.groupMedical"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Contact Person Designation</dt>
                  <dd>{{ quoteRequest.contact_person_designation }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Renewal due date</dt>
                  <dd>{{ quoteRequest.renewal_due_date }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Cover type</dt>
                  <dd>{{ businessCoverTypeText }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Time to contact</dt>
                  <dd>{{ quoteRequest.time_to_contact }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Communication Mode preference</dt>
                  <dd>{{ businessCommuModeText }}</dd>
                </div>
              </template>
              <template
                v-if="businessTypeCode == quoteBusinessTypeCode.marineHull"
              >
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Boat Details</dt>
                  <dd>{{ quoteRequest.boat_details }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Engine details</dt>
                  <dd>{{ quoteRequest.engine_details }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Claims experience</dt>
                  <dd>{{ quoteRequest.claims_experience }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Sum Insured</dt>
                  <dd>{{ quoteRequest.sum_insured_value }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Use</dt>
                  <dd>{{ quoteRequest.use }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">Operator's Experience</dt>
                  <dd>{{ quoteRequest.operators_experience }}</dd>
                </div>
              </template>
            </template>
          </template>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Previous Quote Id</dt>
            <dd>{{ quoteRequest.mobile_no }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">KYC-AML Logs</h3>
      </div>
      <DataTable
        v-model:items-selected="quotesSelected"
        table-class-name="tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="kycLogs || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
      >
        <template #item-id="{ id }">
          <Link
            :href="`/kyc/aml/${id}`"
            class="text-primary-500 hover:underline"
          >
            {{ id }}
          </Link>
        </template>

        <template #item-screenshot="{ screenshot }">
          <img :src="screenshot" alt="IMCRM" class="w-6" />
        </template>
      </DataTable>
    </div>
    <AuditLogs :quote-type="quoteTypeCode" :id="quoteRequest.id" />
  </div>
</template>
