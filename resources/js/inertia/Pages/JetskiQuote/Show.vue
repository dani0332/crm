<script setup>
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';

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
  embeddedProducts: Array,
  canAddBatchNumber: Boolean,
  vatPercentage: Number,
  sendUpdateOptions: Array,
  sendUpdateLogs: Array,
  hasPolicyIssuedStatus: Boolean,
  lockLeadSectionsDetails: Object,
  amlStatusName: String,
});

const page = usePage();

const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const modelClass = 'App\\Models\\PersonalQuote';
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const sectionExpanded = computed(() => !page.props.hasPolicyIssuedStatus);

const [LeadEditBtnTemplate, LeadEditBtnReuseTemplate] =
  createReusableTemplate();

const isAddUpdate = ref(false);
const onAddUpdate = () => {
  isAddUpdate.value = true;
};
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
</script>

<template>
  <div>
    <Head title="Jetski Quotes" />
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
          <h2 class="text-xl font-semibold">Jetski Detail</h2>
        </div>
        <template #body>
          <div
            class="flex gap-2 mb-4 justify-end"
            v-if="readOnlyMode.isDisable === true"
          >
            <Link
              v-if="quote.quote_detail?.insly_id"
              :href="`/legacy-policy/${quote.quote_detail?.insly_id}`"
              preserve-scroll
            >
              <x-button size="sm" color="#ff5e00" tag="div">
                View Legacy policy
              </x-button>
            </Link>

            <LeadEditBtnTemplate v-slot="{ isDisabled }">
              <Link
                v-if="!isDisabled"
                :href="route('jetski-quotes-edit', quote.uuid)"
              >
                <x-button size="sm" tag="div">Edit</x-button>
              </Link>
              <x-button v-else:disabled="isDisabled" size="sm" tag="div"
                >Edit</x-button
              >
            </LeadEditBtnTemplate>

            <x-tooltip
              v-if="lockLeadSectionsDetails.lead_details"
              placement="bottom"
            >
              <LeadEditBtnReuseTemplate
                v-if="
                  canAny([
                    permissionsEnum.JetskiQuotesEdit,
                    permissionsEnum.VIEW_ALL_LEADS,
                  ])
                "
                :isDisabled="true"
              />
              <template #tooltip
                >This lead is now locked as the policy has been booked. If
                changes are needed, go to 'Send Update', select 'Add Update',
                and choose 'Correction of Policy'</template
              >
            </x-tooltip>
            <template v-else>
              <LeadEditBtnReuseTemplate
                v-if="
                  canAny([
                    permissionsEnum.JetskiQuotesEdit,
                    permissionsEnum.VIEW_ALL_LEADS,
                  ])
                "
              />
            </template>
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

          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <div>
                  <x-tooltip placement="bottom">
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
                <dt class="font-medium">AML STATUS</dt>
                <dd>{{ amlStatusName ?? '' }}</dd>
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
            <h3 class="font-semibold text-primary-800 text-lg">
              Customer Profile
            </h3>
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
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">RECEIVE MARKETING UPDATES</dt>
                <dd>
                  {{ quote.customer.receive_marketing_updates ? 'Yes' : 'No' }}
                </dd>
              </div>
              <RiskRatingScoreDetails :quote="quote" :modelType="quoteType" />
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Jetski"
      :quote="quote"
      :canAddBatchNumber="canAddBatchNumber"
      :expanded="sectionExpanded"
    />

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
      :expanded="sectionExpanded"
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

    <AdditionalContacts
      :quote="quote"
      :quote-type="quoteType"
      :expanded="sectionExpanded"
    />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :expanded="sectionExpanded"
    />

    <QuoteDocument
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
      :expanded="sectionExpanded"
      quoteType="Jetski"
    />

    <SendUpdates
      v-if="hasPolicyIssuedStatus"
      :reportable="record"
      :quote_type_id="$page.props.quoteTypeId"
      :options="sendUpdateOptions"
      :data="sendUpdateLogs"
      @onAddUpdate="onAddUpdate"
    />

    <QuotePolicy
      :quote="quote"
      :can="can"
      :quoteStatusEnum="page.props.quoteStatusEnum"
    />

    <PlanDetails
      :insuranceProviders="insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :vatPrice="vatPercentage"
      :expanded="sectionExpanded"
      :isAddUpdate="isAddUpdate"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
      :expanded="sectionExpanded"
    />

    <AuditLogs
      :quote-type="quoteType"
      :id="$page.props.quote.id"
      :expanded="sectionExpanded"
    />

    <LeadHistory :quote="$page.props.quote" :expanded="sectionExpanded" />

    <lead-raw-data
      :modelType="'Jetski'"
      :code="$page.props.quote.code"
    ></lead-raw-data>
  </div>
</template>
