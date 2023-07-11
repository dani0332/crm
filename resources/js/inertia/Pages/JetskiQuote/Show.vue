<script setup>
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LeadStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts';
import { useCan } from '../../Composables/can';

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
});

const page = usePage();

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Jetski Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Jetski Detail</h2>
      <div class="flex gap-2">
        <Link
          v-if="can(permissionsEnum.JetskiQuotesEdit)"
          :href="`/personal-quotes/jetski/${quote.uuid}/edit`"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link
          v-if="can(permissionsEnum.JetskiQuotesList)"
          href="/personal-quotes/jetski"
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
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
              <div>
                  <x-tooltip position="bottom">
                      <label class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700">
                          Ref ID
                      </label>
                      <template #tooltip> Reference ID </template>
                  </x-tooltip>
              </div>
              <div>{{ quote.code }}</div>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor?.email }}</dd>
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
            <dd>{{ quote?.created_by?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED BY</dt>
            <dd>{{ quote?.updated_by?.email }}</dd>
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
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Profile</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
            <dd>{{ quote.dob_formatted }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ quote.previous_policy_expiry_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PRICE</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY START DATE</dt>
            <dd>{{ quote.policy_start_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY END DATE</dt>
            <dd>{{ quote.policy_issuance_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREMIUM</dt>
            <dd>{{ quote.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote?.quote_detail?.transapp_code }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />

    <QuotePayments
      v-if="isBetaUser"
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

    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />

    <LeadHistory :quote="$page.props.quote" />
  </div>
</template>
