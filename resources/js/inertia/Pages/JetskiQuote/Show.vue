<script setup>
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LeadStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts';
import PlanDetails from '../../Components/PlanDetails.vue';
import RiskRatingScoreDetails from '../../Components/RiskRatingScoreDetails.vue';

defineProps({
  quote: Object,
  documentTypes: Object,
  quoteStatuses: Object,
  paymentMethods: Object,
  insuranceProviders: Object,
  personalPlans: Object,
  isBetaUser: Boolean,
  storageUrl: String,
  quoteType: String,
  can: Object,
  activities: Object,
  advisors: Object,
  lostReasons: Object,
  quoteStatusEnum: Object,
  embeddedProducts: Array,
  canAddBatchNumber: Boolean,
  vatPercentage: Number,
});

const page = usePage();

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
  
</script>

<template>
  <div>
    <Head title="Jetski Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Jetski Detail</h2>
      <div class="flex gap-2">
        <Link
          v-if="quote.quote_detail?.insly_id"
          :href="`/legacy-policy/${quote.quote_detail?.insly_id}`"
          preserve-scroll
        >
          <x-button
            size="sm"
            color="#ff5e00"
            tag="div"
            v-if="readOnlyMode.isDisable === true"
          >
            View Legacy policy
          </x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.JetskiQuotesEdit)"
          :href="route('jetski-quotes-edit', quote.uuid)"
        >
          <x-button size="sm" tag="div" v-if="readOnlyMode.isDisable === true"
            >Edit</x-button
          >
        </Link>

        <Link
          v-if="can(permissionsEnum.JetskiQuotesList)"
          :href="route('jetski-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div">
            Jetski Quotes
          </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Ref-ID
                </label>
                <template #tooltip> Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.code }}</div>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd class="break-words">{{ quote.advisor?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED BY</dt>
            <dd class="break-words">{{ quote?.created_by?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED BY</dt>
            <dd class="break-words">{{ quote?.updated_by?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.quote_detail?.lost_reason?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSACTION APPROVED AT</dt>
            <dd>{{ dateFormat(quote.transaction_approved_at) }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">JetSki Make</dt>
            <dd>{{ quote?.jetski_quote?.jetski_make }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">JetSki Model</dt>
            <dd>{{ quote?.jetski_quote?.jetski_model }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Max Speed</dt>
            <dd>{{ quote?.jetski_quote?.max_speed }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Seating Capacity</dt>
            <dd>{{ quote?.jetski_quote?.seat_capacity }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Engine Power (hp)</dt>
            <dd>{{ quote?.jetski_quote?.engine_power }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Year of manufacture</dt>
            <dd>{{ quote?.bike_quote?.year_of_manufacture }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Material of Construction</dt>
            <dd>{{ quote?.jetski_quote?.jetski_material_id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Jet SKI Use</dt>
            <dd>{{ quote?.jetski_quote?.jetski_use_id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Claims Experience for past 5 years</dt>
            <dd>{{ quote?.jetski_quote?.claim_history }}</dd>
          </div>
        </dl>
      </div>

      <div class="flex justify-between items-center mt-6 mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">Customer Profile</h3>
        <x-tag color="success" v-if="quote.kyc_decision === 'Complete'">
          KYC - Complete
        </x-tag>
        <x-tag color="amber" v-else> KYC - Pending </x-tag>
      </div>
      <x-divider class="mb-4 mt-1" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quote.nationality?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quote.dob }}</dd>
          </div>

          <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
        </dl>
      </div>
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Jetski"
      :quote="quote"
      :canAddBatchNumber="canAddBatchNumber"
    />
    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />

    <QuotePayments
      :can="can"
      :payments="quote.payments"
      :quote-type="quoteType"
      :payment-methods="paymentMethods"
      :insurance-providers="insuranceProviders"
      :is-beta-user="isBetaUser"
      :personal-plans="personalPlans"
    />

    <AdditionalContacts :quote="quote" :quote-type="quoteType" />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :quote-status-enum="quoteStatusEnum"
    />

    <QuoteDocuments
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
    />

    <QuotePolicy
      :quote="quote"
      :can="can"
      :quoteStatusEnum="quoteStatusesEnum"
    />

    <PlanDetails
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :vatPrice="vatPercentage"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
    />

    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />

    <LeadHistory :quote="$page.props.quote" />
  </div>
</template>
