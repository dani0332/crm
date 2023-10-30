<script setup>
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LeadStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts';

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
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Bike Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Bike Detail</h2>
      <div class="flex gap-2">
        <Link
          v-if="quote.quote_detail?.insly_id"
          :href="`/legacy-policy/${quote.quote_detail?.insly_id}`"
          preserve-scroll
        >
          <x-button size="sm" color="#ff5e00" tag="div">
            View Legacy policy
          </x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.BikeQuotesEdit)"
          :href="route('bike-quotes-edit', quote.uuid)"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link
          v-if="can(permissionsEnum.BikeQuotesList)"
          :href="route('bike-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"> Bike Quotes </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
            <dd>{{ quote?.advisor?.name }}</dd>
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

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS ECOMMERCE</dt>
            <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
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
            <dt class="font-medium">UAE licence held for</dt>
            <dd>{{ quote?.bike_quote?.uae_license_held_for?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Bike(s) to insure</dt>
            <dd>{{ quote?.bike_quote?.bike_company_to_insure }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Bike value(AED)</dt>
            <dd>{{ quote.asset_value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Year of manufacture</dt>
            <dd>{{ quote?.bike_quote?.year_of_manufacture }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Currently with</dt>
            <dd>{{ quote?.currently_insured_with?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Parent Ref-ID
                </label>
                <template #tooltip> Parent Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.parent_duplicate_quote_id }}</div>
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
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      :quote="quote"
      modelType="Bike"
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

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
    />

    <AuditLogs :id="$page.props.quote.id" :quote-type="quoteType" />

    <LeadHistory :quote="$page.props.quote" />
  </div>
</template>
